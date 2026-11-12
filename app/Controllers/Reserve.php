<?php

namespace App\Controllers;

use RuntimeException;

class Reserve extends BaseController
{
    public function index()
    {
        $items = $this->db->table('menu_items m')->select('m.*, c.name AS category')->join('categories c', 'c.id = m.category_id', 'left')
            ->where('m.is_available', 1)->orderBy('c.sort_order')->orderBy('m.sort_order')->get()->getResultArray();
        $gcashQr = setting('gcash_qr');
        return view('reserve/index', [
            'title'      => 'Reserve a table — Salo',
            'items'      => $items,
            'slots'      => reservation_slots(),
            'banks'      => bank_accounts(),
            'methods'    => enabled_payment_methods(false),
            'deposit'    => (float) setting('deposit_amount', 2000),
            'depositMin' => (int) setting('deposit_min_guests', 8),
            'gcash'      => ['name' => setting('gcash_name'), 'number' => setting('gcash_number'), 'qr' => $gcashQr ? img_url($gcashQr) : null],
        ]);
    }

    /** Tables free for a date/time/party size (JSON). */
    public function availableTables()
    {
        $date   = (string) $this->request->getGet('date');
        $time   = (string) $this->request->getGet('time');
        $guests = max(1, (int) $this->request->getGet('guests'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! in_array($time, reservation_slots(), true)) {
            return $this->json(['ok' => true, 'tables' => []]);
        }
        return $this->json(['ok' => true, 'tables' => array_map(static fn ($t) => [
            'id' => (int) $t['id'], 'code' => $t['code'], 'zone' => $t['zone'], 'seats' => (int) $t['seats'],
        ], $this->freeTables($date, $time, $guests))]);
    }

    /** A table is busy if another active reservation on that day starts within 2 hours of this one. */
    private function freeTables(string $date, string $time, int $guests): array
    {
        $tables = $this->db->table('dining_tables')->where('seats >=', $guests)->orderBy('seats')->orderBy('code')->get()->getResultArray();
        $busy   = [];
        $start  = strtotime("$date $time");
        foreach ($this->db->table('reservations')->where('res_date', $date)->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->where('table_id IS NOT NULL')->get()->getResultArray() as $r) {
            if (abs(strtotime($r['res_date'] . ' ' . $r['res_time']) - $start) < 2 * 3600) {
                $busy[$r['table_id']] = true;
            }
        }
        return array_values(array_filter($tables, static fn ($t) => ! isset($busy[$t['id']])));
    }

    public function submit()
    {
        if (! service('throttler')->check('reserve-' . md5($this->request->getIPAddress()), 5, MINUTE)) {
            return redirect()->back()->withInput()->with('error', 'Too many requests — please wait a minute.');
        }
        $p = $this->request->getPost();
        try {
            $name   = trim(mb_substr(strip_tags((string) ($p['name'] ?? '')), 0, 120));
            $mobile = trim(preg_replace('/[^0-9+ \-]/', '', (string) ($p['mobile'] ?? '')));
            $email  = trim((string) ($p['email'] ?? ''));
            $date   = (string) ($p['date'] ?? '');
            $time   = (string) ($p['time'] ?? '');
            $guests = (int) ($p['guests'] ?? 0);
            if ($name === '' || strlen(preg_replace('/\D/', '', $mobile)) < 10) {
                throw new RuntimeException('Please enter your full name and a valid mobile number.');
            }
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email or leave it blank.');
            }
            $d = \DateTime::createFromFormat('Y-m-d', $date);
            if (! $d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d') || $date > date('Y-m-d', strtotime('+30 days'))) {
                throw new RuntimeException('Please choose a date within the next 30 days.');
            }
            if ($d->format('N') == 1) {
                throw new RuntimeException('We’re closed on Mondays — please choose another day.');
            }
            if (! in_array($time, reservation_slots(), true)) {
                throw new RuntimeException('Please choose a time slot.');
            }
            if ($date === date('Y-m-d') && strtotime("$date $time") < time()) {
                throw new RuntimeException('That time has already passed today.');
            }
            if ($guests < 1 || $guests > 12) {
                throw new RuntimeException('Reservations are for 1 to 12 guests. For bigger groups please call us.');
            }
            $tableId = null;
            if (! empty($p['table_id'])) {
                $free = array_column($this->freeTables($date, $time, $guests), 'id');
                if (! in_array((int) $p['table_id'], array_map('intval', $free), true)) {
                    throw new RuntimeException('Sorry, that table was just taken — please pick another or choose “Any table”.');
                }
                $tableId = (int) $p['table_id'];
            }

            // Pre-order
            $pre      = [];
            $preTotal = 0.0;
            foreach ((array) ($p['pre'] ?? []) as $itemId => $qty) {
                $qty = max(0, min(30, (int) $qty));
                if (! $qty) {
                    continue;
                }
                $it = $this->db->table('menu_items')->where(['id' => (int) $itemId, 'is_available' => 1])->get()->getRowArray();
                if (! $it) {
                    continue;
                }
                $line      = round($it['price'] * $qty, 2);
                $preTotal += $line;
                $pre[]     = ['menu_item_id' => $it['id'], 'name' => $it['name'], 'unit_price' => $it['price'], 'quantity' => $qty, 'line_total' => $line, 'prep_minutes' => $it['prep_minutes']];
            }
            $deposit = $guests >= (int) setting('deposit_min_guests', 8) ? (float) setting('deposit_amount', 2000) : 0.0;
            $due     = round($preTotal + $deposit, 2);

            $method = 'none';
            $paymentStatus = 'none';
            $ref = null;
            $proof = null;
            $bankId = null;
            if ($due > 0) {
                $method = (string) ($p['payment_method'] ?? '');
                if (! in_array($method, enabled_payment_methods(false), true)) {
                    throw new RuntimeException('Pre-orders and large-party deposits are paid via GCash or Online Banking — please choose one.');
                }
                $ref = trim(mb_substr(preg_replace('/[^A-Za-z0-9\- ]/', '', (string) ($p['reference_no'] ?? '')), 0, 60));
                if ($ref === '') {
                    throw new RuntimeException('Please enter the reference number of your ' . peso($due) . ' payment.');
                }
                $proof = save_uploaded_image($this->request->getFile('proof'), 'proofs');
                if (! $proof) {
                    throw new RuntimeException('Please upload the screenshot of your payment.');
                }
                $paymentStatus = 'for_verification';
                $bankId        = $method === 'online_banking' ? ((int) ($p['bank_account_id'] ?? 0) ?: null) : null;
            }

            $now  = date('Y-m-d H:i:s');
            $code = $this->orders->generateCode('reservations');
            $this->db->transStart();
            $this->db->table('reservations')->insert([
                'code' => $code, 'name' => $name, 'mobile' => $mobile, 'email' => $email ?: null, 'res_date' => $date, 'res_time' => $time . ':00',
                'guests' => $guests, 'table_id' => $tableId, 'notes' => trim(mb_substr(strip_tags((string) ($p['notes'] ?? '')), 0, 1000)) ?: null,
                'preorder_total' => $preTotal, 'deposit_amount' => $deposit, 'amount_due' => $due,
                'payment_method' => $method, 'payment_status' => $paymentStatus, 'bank_account_id' => $bankId,
                'reference_no' => $ref, 'proof_image' => $proof, 'payment_submitted_at' => $proof ? $now : null,
                'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $rid = $this->db->insertID();
            foreach ($pre as $line) {
                $line['reservation_id'] = $rid;
                $this->db->table('reservation_items')->insert($line);
            }
            $this->db->transComplete();

            return redirect()->to(site_url('reserve/done/' . $code));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function done(string $code)
    {
        $r = $this->db->table('reservations')->where('code', strtoupper($code))->get()->getRowArray();
        if (! $r) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $r['items'] = $this->db->table('reservation_items')->where('reservation_id', $r['id'])->get()->getResultArray();
        $r['table'] = $r['table_id'] ? $this->db->table('dining_tables')->where('id', $r['table_id'])->get()->getRowArray() : null;
        return view('reserve/done', ['title' => 'Reservation ' . $r['code'] . ' — Salo', 'r' => $r]);
    }
}
