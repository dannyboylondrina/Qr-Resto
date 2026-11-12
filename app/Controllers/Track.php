<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use RuntimeException;

class Track extends BaseController
{
    public function index()
    {
        if ($code = $this->request->getGet('code')) {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
            if (! service('throttler')->check('track-' . md5($this->request->getIPAddress()), 30, MINUTE)) {
                return redirect()->to(site_url('track'))->with('error', 'Too many lookups — please wait a minute.');
            }
            if ($this->orders->findByCode($code)) {
                return redirect()->to(site_url('track/' . $code));
            }
            return redirect()->to(site_url('track'))->with('error', 'We couldn’t find order “' . $code . '”. Check the 6-character code.');
        }
        return view('track/index', ['title' => 'Track your order — Salo', 'mine' => array_reverse((array) session('my_orders'))]);
    }

    private function load(string $code): array
    {
        $order = $this->orders->findByCode($code);
        if (! $order) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Order not found');
        }
        $order['items'] = $this->orders->items((int) $order['id']);
        $order['table'] = $order['table_id'] ? $this->db->table('dining_tables')->where('id', $order['table_id'])->get()->getRowArray() : null;
        $order['bank']  = $order['bank_account_id'] ? $this->db->table('bank_accounts')->where('id', $order['bank_account_id'])->get()->getRowArray() : null;
        return $order;
    }

    public function show(string $code)
    {
        $order  = $this->load($code);
        $review = $this->db->table('reviews')->where('order_id', $order['id'])->get()->getRowArray();
        $gcashQr = setting('gcash_qr');
        return view('track/show', [
            'title'   => 'Order ' . $order['code'] . ' — Salo',
            'o'       => $order,
            'review'  => $review,
            'banks'   => bank_accounts(),
            'methods' => enabled_payment_methods(false),
            'gcash'   => ['name' => setting('gcash_name'), 'number' => setting('gcash_number'), 'qr' => $gcashQr ? img_url($gcashQr) : null],
        ]);
    }

    /** Live-refreshing status block. */
    public function status(string $code)
    {
        return view('track/_status', ['o' => $this->load($code)]);
    }

    public function resubmit(string $code)
    {
        $order = $this->orders->findByCode($code);
        if (! $order) {
            return $this->reply('Order not found.', false);
        }
        if (! in_array($order['payment_status'], ['awaiting', 'rejected'], true) || $order['status'] === 'cancelled') {
            return $this->reply('This order doesn’t need payment details right now.', false);
        }
        $method = (string) $this->request->getPost('payment_method');
        if (! in_array($method, enabled_payment_methods(false), true)) {
            return $this->reply('Please choose GCash or Online Banking.', false);
        }
        $ref = trim(mb_substr(preg_replace('/[^A-Za-z0-9\- ]/', '', (string) $this->request->getPost('reference_no')), 0, 60));
        if ($ref === '') {
            return $this->reply('Please enter the reference number.', false);
        }
        try {
            $proof = save_uploaded_image($this->request->getFile('proof'), 'proofs');
            if (! $proof) {
                throw new RuntimeException('Please upload the screenshot of your payment.');
            }
        } catch (RuntimeException $e) {
            return $this->reply($e->getMessage(), false);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->where('id', $order['id'])->update([
            'payment_method' => $method, 'reference_no' => $ref, 'proof_image' => $proof, 'payment_status' => 'for_verification',
            'payment_submitted_at' => $now, 'rejection_reason' => null,
            'bank_account_id' => $method === 'online_banking' ? ((int) $this->request->getPost('bank_account_id') ?: null) : null,
            'updated_at' => $now,
        ]);
        return $this->reply('Thanks! Our cashier is verifying your payment now.', true, site_url('track/' . $order['code']));
    }

    public function review(string $code)
    {
        $order = $this->orders->findByCode($code);
        if (! $order || $order['status'] !== 'served') {
            return $this->reply('You can leave a review once your order has been served.', false);
        }
        if ($this->db->table('reviews')->where('order_id', $order['id'])->countAllResults()) {
            return $this->reply('You already reviewed this order — thank you!', false);
        }
        $rating = (int) $this->request->getPost('rating');
        if ($rating < 1 || $rating > 5) {
            return $this->reply('Please choose 1 to 5 stars.', false);
        }
        $this->db->table('reviews')->insert([
            'order_id' => $order['id'], 'rating' => $rating,
            'comment' => trim(mb_substr(strip_tags((string) $this->request->getPost('comment')), 0, 1000)) ?: null,
            'guest_name' => trim(mb_substr(strip_tags((string) $this->request->getPost('guest_name')), 0, 120)) ?: ($order['customer_name'] ?: null),
            'is_approved' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->reply(t('thanks_review'), true, site_url('track/' . $order['code']));
    }

    /** Receipt as PDF (paid orders only). */
    public function receipt(string $code)
    {
        $order = $this->load($code);
        if ($order['payment_status'] !== 'paid') {
            return redirect()->to(site_url('track/' . $order['code']))->with('error', 'The receipt is available once payment is confirmed.');
        }
        $html = view('receipt/pdf', ['o' => $order]);
        if (! class_exists(Dompdf::class)) {
            return $this->response->setBody($html); // fallback: printable HTML
        }
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper([0, 0, 260, 400 + 22 * count($order['items'])]);
        $pdf->render();
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="Salo-receipt-' . $order['code'] . '.pdf"')
            ->setBody($pdf->output());
    }
}
