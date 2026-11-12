<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Libraries\Notifier;

class Orders extends BaseController
{
    public function index()
    {
        return view('staff/orders/index', ['title' => 'Order board', 'active' => 'orders', 'board' => $this->boardData()]);
    }

    public function board()
    {
        return view('staff/orders/_board', ['board' => $this->boardData()]);
    }

    private function boardData(): array
    {
        $since  = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $orders = $this->db->table('orders o')->select('o.*, t.code AS table_code, b.bank_name')
            ->join('dining_tables t', 't.id = o.table_id', 'left')
            ->join('bank_accounts b', 'b.id = o.bank_account_id', 'left')
            ->where('o.status !=', 'cancelled')
            ->groupStart()->where('o.created_at >=', $since)->orWhereIn('o.status', ['received', 'preparing', 'ready'])->groupEnd()
            ->orderBy('o.id', 'ASC')->get()->getResultArray();

        $ids   = array_column($orders, 'id') ?: [0];
        $items = [];
        foreach ($this->db->table('order_items')->whereIn('order_id', $ids)->get()->getResultArray() as $it) {
            $items[$it['order_id']][] = $it;
        }
        $cols = ['awaiting' => [], 'verify' => [], 'paid' => [], 'preparing' => [], 'ready' => [], 'served' => []];
        $today = date('Y-m-d');
        foreach ($orders as $o) {
            $o['items'] = $items[$o['id']] ?? [];
            if ($o['payment_status'] === 'for_verification') {
                $cols['verify'][] = $o;
            } elseif (! $o['released_at']) {
                $cols['awaiting'][] = $o;
            } elseif ($o['status'] === 'received') {
                $cols['paid'][] = $o;
            } elseif ($o['status'] === 'preparing') {
                $cols['preparing'][] = $o;
            } elseif ($o['status'] === 'ready') {
                $cols['ready'][] = $o;
            } elseif ($o['status'] === 'served' && substr((string) $o['served_at'], 0, 10) === $today) {
                $cols['served'][] = $o;
            }
        }
        $cols['served'] = array_slice(array_reverse($cols['served']), 0, 15);
        return $cols;
    }

    private function order(int $id): ?array
    {
        return $this->orders->find($id);
    }

    public function markPaid(int $id)
    {
        $o = $this->order($id);
        if (! $o || $o['status'] === 'cancelled') {
            return $this->reply('Order not found.', false);
        }
        if (in_array($o['payment_status'], ['paid'], true)) {
            return $this->reply('Order ' . $o['code'] . ' is already paid.', false);
        }
        $method = $o['payment_status'] === 'tab' ? 'cash' : null;
        $this->orders->markPaid($o, (int) current_user()['id'], $method);
        log_activity('cash_marked_paid', 'order', $id, $o['code'] . ' · ' . peso($o['total']) . ' · ' . payment_method_label($method ?: $o['payment_method']));
        return $this->reply('Order ' . $o['code'] . ' marked as paid — sent to the kitchen.');
    }

    public function approve(int $id)
    {
        $o = $this->order($id);
        if (! $o || $o['payment_status'] !== 'for_verification') {
            return $this->reply('This payment is no longer waiting for verification.', false);
        }
        $this->orders->markPaid($o, (int) current_user()['id']);
        log_activity('payment_approved', 'order', $id, $o['code'] . ' · ' . payment_method_label($o['payment_method']) . ' ref ' . $o['reference_no'] . ' · ' . peso($o['total']));
        (new Notifier())->orderPaymentApproved($o);
        return $this->reply('Payment approved — order ' . $o['code'] . ' sent to the kitchen.');
    }

    public function reject(int $id)
    {
        $o      = $this->order($id);
        $reason = trim(mb_substr(strip_tags((string) $this->request->getPost('reason')), 0, 250));
        if (! $o || $o['payment_status'] !== 'for_verification') {
            return $this->reply('This payment is no longer waiting for verification.', false);
        }
        if ($reason === '') {
            return $this->reply('Please give a reason so the guest can fix it.', false);
        }
        $this->db->table('orders')->where('id', $id)->update([
            'payment_status' => 'rejected', 'rejection_reason' => $reason, 'verified_by' => current_user()['id'],
            'verified_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('payment_rejected', 'order', $id, $o['code'] . ' · ref ' . $o['reference_no'] . ' · ' . $reason);
        (new Notifier())->orderPaymentRejected($o, $reason);
        return $this->reply('Payment rejected. The guest will see your reason on their tracking page.');
    }

    public function setStatus(int $id, string $status)
    {
        $o = $this->order($id);
        if (! $o) {
            return $this->reply('Order not found.', false);
        }
        if ($err = $this->orders->changeStatus($o, $status)) {
            return $this->reply($err, false);
        }
        return $this->reply('Order ' . $o['code'] . ' → ' . order_status_label($status));
    }

    public function discount(int $id)
    {
        $o    = $this->order($id);
        $type = (string) $this->request->getPost('type');
        $idNo = trim(mb_substr(strip_tags((string) $this->request->getPost('id_no')), 0, 60));
        $nm   = trim(mb_substr(strip_tags((string) $this->request->getPost('id_name')), 0, 120));
        if (! $o || $o['status'] === 'cancelled') {
            return $this->reply('Order not found.', false);
        }
        if (! in_array($type, ['senior', 'pwd'], true) || $idNo === '' || $nm === '') {
            return $this->reply('Choose Senior Citizen or PWD and enter the ID number and name.', false);
        }
        if (in_array($o['payment_status'], ['paid', 'for_verification'], true)) {
            return $this->reply('Apply discounts before payment (this order is already ' . payment_status_label($o['payment_status']) . ').', false);
        }
        $amount = round((float) $o['subtotal'] * 0.20, 2);
        $this->orders->applyDiscount($o, $type, $amount, ['discount_id_no' => $idNo, 'discount_id_name' => $nm, 'promo_code' => null]);
        log_activity('discount_applied', 'order', $id, $o['code'] . ' · ' . discount_label($type) . ' · ' . peso($amount) . ' · ID ' . $idNo . ' (' . $nm . ')');
        return $this->reply(discount_label($type) . ' discount of ' . peso($amount) . ' applied to ' . $o['code'] . '.');
    }

    public function removeDiscount(int $id)
    {
        $o = $this->order($id);
        if (! $o || in_array($o['payment_status'], ['paid', 'for_verification'], true)) {
            return $this->reply('Discount can’t be changed on this order.', false);
        }
        $this->orders->applyDiscount($o, 'none', 0, ['discount_id_no' => null, 'discount_id_name' => null, 'promo_code' => null]);
        log_activity('discount_removed', 'order', $id, $o['code']);
        return $this->reply('Discount removed.');
    }

    public function cancel(int $id)
    {
        $o      = $this->order($id);
        $reason = trim(mb_substr(strip_tags((string) $this->request->getPost('reason')), 0, 250));
        if (! $o || in_array($o['status'], ['served', 'cancelled'], true)) {
            return $this->reply('This order can’t be cancelled.', false);
        }
        $this->db->table('orders')->where('id', $id)->update(['status' => 'cancelled', 'rejection_reason' => $reason ?: null, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity('order_cancelled', 'order', $id, $o['code'] . ' · ' . peso($o['total']) . ($reason ? ' · ' . $reason : '') . ($o['payment_status'] === 'paid' ? ' · WAS PAID — refund needed' : ''));
        return $this->reply('Order ' . $o['code'] . ' cancelled.' . ($o['payment_status'] === 'paid' ? ' It was already paid — remember to refund.' : ''));
    }

    public function receipt(int $id)
    {
        $o = $this->order($id);
        if (! $o) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $o['items'] = $this->orders->items($id);
        $o['table'] = $o['table_id'] ? $this->db->table('dining_tables')->where('id', $o['table_id'])->get()->getRowArray() : null;
        return view('staff/orders/receipt', ['o' => $o]);
    }
}
