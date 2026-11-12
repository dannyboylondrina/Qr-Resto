<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Service extends BaseController
{
    public function index()
    {
        return view('staff/service/index', ['title' => 'Service requests', 'active' => 'service', 'data' => $this->data()]);
    }

    public function list()
    {
        return view('staff/service/_list', ['data' => $this->data()]);
    }

    private function data(): array
    {
        $q = fn (string $status, int $limit) => $this->db->table('service_requests s')
            ->select('s.*, t.code, t.zone, u.name AS handler')
            ->join('dining_tables t', 't.id = s.table_id')->join('users u', 'u.id = s.handled_by', 'left')
            ->where('s.status', $status)->orderBy('s.id', $status === 'open' ? 'ASC' : 'DESC')->limit($limit)->get()->getResultArray();
        return ['open' => $q('open', 100), 'done' => $q('done', 15)];
    }

    public function markDone(int $id)
    {
        $r = $this->db->table('service_requests')->where('id', $id)->get()->getRowArray();
        if (! $r || $r['status'] !== 'open') {
            return $this->reply('Already handled.', false);
        }
        $this->db->table('service_requests')->where('id', $id)->update(['status' => 'done', 'handled_by' => current_user()['id'], 'done_at' => date('Y-m-d H:i:s')]);
        return $this->reply('Marked as done.');
    }
}
