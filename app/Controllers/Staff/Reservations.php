<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Libraries\Notifier;

class Reservations extends BaseController
{
    public function index()
    {
        $view   = $this->request->getGet('view') === 'calendar' ? 'calendar' : 'list';
        $month  = preg_match('/^\d{4}-\d{2}$/', (string) $this->request->getGet('month')) ? $this->request->getGet('month') : date('Y-m');
        $date   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->request->getGet('date')) ? $this->request->getGet('date') : null;
        $status = (string) $this->request->getGet('status');

        $q = $this->db->table('reservations r')->select('r.*, t.code AS table_code, b.bank_name')
            ->join('dining_tables t', 't.id = r.table_id', 'left')->join('bank_accounts b', 'b.id = r.bank_account_id', 'left');
        if ($date) {
            $q->where('r.res_date', $date);
        } elseif ($view === 'list' && $status !== 'all') {
            $q->groupStart()->where('r.res_date >=', date('Y-m-d'))->orWhere('r.status', 'pending')->groupEnd();
        }
        if (in_array($status, ['pending', 'confirmed', 'seated', 'cancelled', 'no_show'], true)) {
            $q->where('r.status', $status);
        }
        $list = $view === 'list' || $date ? $q->orderBy('r.res_date')->orderBy('r.res_time')->limit(300)->get()->getResultArray() : [];
        foreach ($list as &$r) {
            $r['items'] = $this->db->table('reservation_items')->where('reservation_id', $r['id'])->get()->getResultArray();
        }

        $counts = [];
        if ($view === 'calendar') {
            foreach ($this->db->query("SELECT res_date d, COUNT(*) n, SUM(guests) g, SUM(status='pending') p FROM reservations
                    WHERE res_date BETWEEN ? AND ? AND status NOT IN ('cancelled') GROUP BY res_date", [$month . '-01', date('Y-m-t', strtotime($month . '-01'))])->getResultArray() as $c) {
                $counts[$c['d']] = $c;
            }
        }
        return view('staff/reservations/index', [
            'title' => 'Reservations', 'active' => 'reservations', 'view' => $view, 'month' => $month, 'date' => $date, 'status' => $status,
            'list' => $list, 'counts' => $counts,
            'tables' => $this->db->table('dining_tables')->orderBy('sort_order')->get()->getResultArray(),
        ]);
    }

    public function action(int $id, string $action)
    {
        $r = $this->db->table('reservations')->where('id', $id)->get()->getRowArray();
        if (! $r) {
            return $this->reply('Reservation not found.', false);
        }
        $now  = date('Y-m-d H:i:s');
        $user = current_user();
        $upd  = fn (array $d) => $this->db->table('reservations')->where('id', $id)->update($d + ['updated_at' => $now]);
        $notifier = new Notifier();

        switch ($action) {
            case 'approve':
                if ($r['payment_status'] !== 'for_verification') {
                    return $this->reply('No payment waiting for verification.', false);
                }
                $upd(['payment_status' => 'paid', 'verified_by' => $user['id'], 'verified_at' => $now, 'status' => $r['status'] === 'pending' ? 'confirmed' : $r['status'], 'rejection_reason' => null]);
                log_activity('payment_approved', 'reservation', $id, $r['code'] . ' · ' . payment_method_label($r['payment_method']) . ' ref ' . $r['reference_no'] . ' · ' . peso($r['amount_due']));
                $notifier->reservationStatus($r, 'confirmed');
                return $this->reply('Payment approved — reservation ' . $r['code'] . ' confirmed.');

            case 'reject':
                $reason = trim(mb_substr(strip_tags((string) $this->request->getPost('reason')), 0, 250));
                if ($r['payment_status'] !== 'for_verification' || $reason === '') {
                    return $this->reply('Enter a reason to reject this payment.', false);
                }
                $upd(['payment_status' => 'rejected', 'rejection_reason' => $reason, 'verified_by' => $user['id'], 'verified_at' => $now]);
                log_activity('payment_rejected', 'reservation', $id, $r['code'] . ' · ' . $reason);
                $notifier->notify($r['mobile'], $r['email'], 'Reservation payment not approved',
                    "Salo: We couldn't verify the payment for reservation {$r['code']} ({$reason}). Please call +63 2 8555 0101 or re-book.");
                return $this->reply('Payment rejected and the guest notified.');

            case 'confirm':
                if ($r['payment_status'] === 'for_verification') {
                    return $this->reply('Approve the payment first (it confirms the reservation).', false);
                }
                $upd(['status' => 'confirmed']);
                log_activity('reservation_confirmed', 'reservation', $id, $r['code']);
                $notifier->reservationStatus($r, 'confirmed');
                return $this->reply('Reservation ' . $r['code'] . ' confirmed.');

            case 'cancel':
                $upd(['status' => 'cancelled']);
                $paid = $r['payment_status'] === 'paid' && $r['amount_due'] > 0;
                log_activity('reservation_cancelled', 'reservation', $id, $r['code'] . ($paid ? ' · paid ' . peso($r['amount_due']) . ' — refund needed' : ''));
                $notifier->reservationStatus($r, 'cancelled', $paid ? 'Our team will contact you about your refund.' : '');
                return $this->reply('Reservation cancelled.' . ($paid ? ' It had a paid pre-order/deposit — arrange a refund.' : ''));

            case 'noshow':
                $upd(['status' => 'no_show']);
                log_activity('reservation_no_show', 'reservation', $id, $r['code']);
                return $this->reply('Marked as no-show.');

            case 'assign':
                $tid = (int) $this->request->getPost('table_id');
                $t   = $tid ? $this->db->table('dining_tables')->where('id', $tid)->get()->getRowArray() : null;
                $upd(['table_id' => $t['id'] ?? null]);
                log_activity('reservation_table', 'reservation', $id, $r['code'] . ' → ' . ($t['code'] ?? 'no table'));
                return $this->reply($t ? 'Table ' . $t['code'] . ' assigned.' : 'Table unassigned.');

            case 'seat':
                return $this->seat($r);
        }
        return $this->reply('Unknown action.', false);
    }

    /** Guest arrived: open a table visit (with deposit credit) and fire the pre-order to the kitchen. */
    private function seat(array $r)
    {
        if (in_array($r['status'], ['seated', 'cancelled'], true)) {
            return $this->reply('This reservation is already ' . $r['status'] . '.', false);
        }
        if (! $r['table_id']) {
            return $this->reply('Assign a table first.', false);
        }
        if ($this->orders->openVisit((int) $r['table_id'])) {
            return $this->reply('That table is still occupied — close it or assign another table.', false);
        }
        $now  = date('Y-m-d H:i:s');
        $paid = $r['payment_status'] === 'paid';
        $this->db->table('table_visits')->insert([
            'table_id' => $r['table_id'], 'reservation_id' => $r['id'], 'status' => 'open',
            'deposit_credit' => $paid ? $r['deposit_amount'] : 0, 'opened_at' => $now,
        ]);
        $visitId = (int) $this->db->insertID();
        $this->db->table('dining_tables')->where('id', $r['table_id'])->update(['status' => 'occupied', 'updated_at' => $now]);

        $items = $this->db->table('reservation_items')->where('reservation_id', $r['id'])->get()->getResultArray();
        if ($items) {
            $code = $this->orders->generateCode();
            $this->db->table('orders')->insert([
                'code' => $code, 'type' => 'dine_in', 'table_id' => $r['table_id'], 'visit_id' => $visitId, 'reservation_id' => $r['id'],
                'guests' => $r['guests'], 'customer_name' => $r['name'], 'customer_mobile' => $r['mobile'], 'customer_email' => $r['email'],
                'subtotal' => $r['preorder_total'], 'total' => $r['preorder_total'], 'status' => 'received',
                'payment_method' => $paid ? $r['payment_method'] : 'cash', 'payment_status' => $paid ? 'paid' : 'awaiting',
                'reference_no' => $paid ? $r['reference_no'] : null, 'proof_image' => $paid ? $r['proof_image'] : null,
                'verified_by' => $paid ? $r['verified_by'] : null, 'verified_at' => $paid ? $r['verified_at'] : null,
                'paid_at' => $paid ? $now : null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $oid = (int) $this->db->insertID();
            foreach ($items as $it) {
                $this->db->table('order_items')->insert([
                    'order_id' => $oid, 'menu_item_id' => $it['menu_item_id'], 'name' => $it['name'], 'unit_price' => $it['unit_price'],
                    'quantity' => $it['quantity'], 'line_total' => $it['line_total'], 'prep_minutes' => $it['prep_minutes'], 'note' => 'Pre-order · ' . $r['code'],
                ]);
            }
            if ($paid) {
                $this->orders->release($this->orders->find($oid));
            }
        }
        $this->db->table('reservations')->where('id', $r['id'])->update(['status' => 'seated', 'updated_at' => $now]);
        log_activity('reservation_seated', 'reservation', $r['id'], $r['code'] . ($items ? ' · pre-order fired' : ''));
        return $this->reply($r['name'] . ' seated.' . ($items ? ($paid ? ' Pre-order sent to the kitchen.' : ' Pre-order is waiting for payment on the Orders board.') : ''));
    }
}
