<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class TablesAdmin extends BaseController
{
    public function index()
    {
        $tables = $this->db->table('dining_tables')->orderBy('sort_order')->orderBy('code')->get()->getResultArray();
        foreach ($tables as &$t) {
            $t['url'] = site_url('menu') . '?table=' . rawurlencode($t['code']);
        }
        return view('staff/tables/index', ['title' => 'Tables & QR codes', 'active' => 'qr', 'tables' => $tables,
            'isLocal' => (bool) preg_match('#//(localhost|127\.0\.0\.1)#', base_url())]);
    }

    public function save()
    {
        $id   = (int) $this->request->getPost('id');
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $data = [
            'code' => $code, 'seats' => max(1, min(40, (int) $this->request->getPost('seats'))),
            'zone' => trim(strip_tags((string) $this->request->getPost('zone'))) ?: 'Main Hall',
            'sort_order' => (int) $this->request->getPost('sort_order'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (! preg_match('/^[A-Z0-9\-]{1,10}$/', $code)) {
            return $this->reply('Table code: 1–10 letters/numbers, e.g. T11.', false);
        }
        if ($this->db->table('dining_tables')->where('code', $code)->where('id !=', $id)->countAllResults()) {
            return $this->reply('Table ' . $code . ' already exists.', false);
        }
        if ($id) {
            $this->db->table('dining_tables')->where('id', $id)->update($data);
        } else {
            $this->db->table('dining_tables')->insert($data + ['status' => 'available']);
            $id = (int) $this->db->insertID();
        }
        log_activity('table_saved', 'table', $id, $code . ' · ' . $data['seats'] . ' seats · ' . $data['zone']);
        return $this->reply('Table ' . $code . ' saved.');
    }

    public function delete(int $id)
    {
        $t = $this->db->table('dining_tables')->where('id', $id)->get()->getRowArray();
        if (! $t) {
            return $this->reply('Not found.', false);
        }
        if ($this->orders->openVisit($id)) {
            return $this->reply('Table ' . $t['code'] . ' has guests right now.', false);
        }
        if ($this->db->table('orders')->where('table_id', $id)->countAllResults()) {
            return $this->reply('Table ' . $t['code'] . ' has order history and can’t be deleted (rename it instead).', false);
        }
        $this->db->table('dining_tables')->where('id', $id)->delete();
        log_activity('table_deleted', 'table', $id, $t['code']);
        return $this->reply('Table ' . $t['code'] . ' deleted.');
    }
}
