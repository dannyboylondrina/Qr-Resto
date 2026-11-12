<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Floor extends BaseController
{
    public function index()
    {
        return view('staff/floor/index', ['title' => 'Floor', 'active' => 'floor', 'tables' => $this->data(), 'methods' => enabled_payment_methods()]);
    }

    public function board()
    {
        return view('staff/floor/_board', ['tables' => $this->data()]);
    }

    private function data(): array
    {
        $tables = table_effective_statuses();
        foreach ($tables as &$t) {
            $visit     = $this->orders->openVisit((int) $t['id']);
            $t['bill'] = $visit ? $this->orders->visitBill((int) $visit['id']) : null;
            $t['requests'] = $this->db->table('service_requests')->where(['table_id' => $t['id'], 'status' => 'open'])->countAllResults();
        }
        return $tables;
    }

    public function setStatus(int $id)
    {
        $status = (string) $this->request->getPost('status');
        $table  = $this->db->table('dining_tables')->where('id', $id)->get()->getRowArray();
        if (! $table || ! in_array($status, ['available', 'occupied', 'reserved'], true)) {
            return $this->reply('Invalid table or status.', false);
        }
        $visit = $this->orders->openVisit($id);
        if ($status === 'available' && $visit) {
            $bill = $this->orders->visitBill((int) $visit['id']);
            if ($bill['unpaid'] > 0) {
                return $this->reply('Table ' . $table['code'] . ' still has an unpaid bill of ' . peso($bill['unpaid']) . '. Settle it first.', false);
            }
            $this->db->table('table_visits')->where('id', $visit['id'])->update(['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->table('dining_tables')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity('table_status', 'table', $id, $table['code'] . ' → ' . $status);
        return $this->reply('Table ' . $table['code'] . ' is now ' . $status . '.');
    }

    /** Pays every unpaid (tab / awaiting / rejected) order of the visit with one method. */
    public function settle(int $visitId)
    {
        $method = (string) $this->request->getPost('method');
        $ref    = trim(mb_substr(preg_replace('/[^A-Za-z0-9\- ]/', '', (string) $this->request->getPost('reference')), 0, 60));
        if (! in_array($method, ['cash', 'gcash', 'online_banking'], true)) {
            return $this->reply('Choose how the bill was paid.', false);
        }
        if ($method !== 'cash' && $ref === '') {
            return $this->reply('Enter the GCash / bank reference number.', false);
        }
        $bill = $this->orders->visitBill($visitId);
        if (! $bill['visit'] || $bill['visit']['status'] === 'closed') {
            return $this->reply('This table visit is already closed.', false);
        }
        $n = 0;
        $pending = 0;
        foreach ($bill['orders'] as $o) {
            if (in_array($o['payment_status'], ['tab', 'awaiting', 'rejected'], true)) {
                $this->orders->markPaid($o, (int) current_user()['id'], $method, $ref);
                $n++;
            } elseif ($o['payment_status'] === 'for_verification') {
                $pending++;
            }
        }
        $table = $this->db->table('dining_tables')->where('id', $bill['visit']['table_id'])->get()->getRowArray();
        log_activity('bill_settled', 'table', $table['id'], $table['code'] . ' · ' . $n . ' order(s) · ' . peso($bill['unpaid']) .
            ($bill['deposit_credit'] > 0 ? ' (deposit credit ' . peso($bill['deposit_credit']) . ', collected ' . peso($bill['balance']) . ')' : '') . ' · ' . payment_method_label($method) . ($ref ? ' ref ' . $ref : ''));
        $this->db->table('service_requests')->where(['table_id' => $table['id'], 'type' => 'bill', 'status' => 'open'])
            ->update(['status' => 'done', 'handled_by' => current_user()['id'], 'done_at' => date('Y-m-d H:i:s')]);
        return $this->reply('Bill for ' . $table['code'] . ' settled (' . $n . ' order' . ($n === 1 ? '' : 's') . ').' . ($pending ? ' ' . $pending . ' payment(s) still need verification on the Orders page.' : ''));
    }

    public function close(int $visitId)
    {
        $bill = $this->orders->visitBill($visitId);
        if (! $bill['visit'] || $bill['visit']['status'] === 'closed') {
            return $this->reply('Already closed.', false);
        }
        if ($bill['unpaid'] > 0) {
            return $this->reply('There is still ' . peso($bill['unpaid']) . ' unpaid. Settle the bill first.', false);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('table_visits')->where('id', $visitId)->update(['status' => 'closed', 'closed_at' => $now]);
        $this->db->table('dining_tables')->where('id', $bill['visit']['table_id'])->update(['status' => 'available', 'updated_at' => $now]);
        $this->db->table('service_requests')->where(['table_id' => $bill['visit']['table_id'], 'status' => 'open'])
            ->update(['status' => 'done', 'handled_by' => current_user()['id'], 'done_at' => $now]);
        $table = $this->db->table('dining_tables')->where('id', $bill['visit']['table_id'])->get()->getRowArray();
        log_activity('table_closed', 'table', $table['id'], $table['code'] . ' · visit total ' . peso($bill['total']));
        return $this->reply('Table ' . $table['code'] . ' closed and set to Available.');
    }
}
