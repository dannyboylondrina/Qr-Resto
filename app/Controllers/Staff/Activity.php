<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Activity extends BaseController
{
    public function index()
    {
        $tab = $this->request->getGet('tab') === 'notifications' ? 'notifications' : 'log';
        $q   = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) $this->request->getGet('page'));
        $per  = 50;

        if ($tab === 'notifications') {
            $b = $this->db->table('notifications');
            if ($q !== '') {
                $b->groupStart()->like('recipient', $q)->orLike('message', $q)->groupEnd();
            }
        } else {
            $b = $this->db->table('activity_logs');
            if ($q !== '') {
                $b->groupStart()->like('user_name', $q)->orLike('action', $q)->orLike('details', $q)->groupEnd();
            }
            if ($act = $this->request->getGet('action')) {
                $b->where('action', $act);
            }
        }
        $total = $b->countAllResults(false);
        $rows  = $b->orderBy('id', 'DESC')->limit($per, ($page - 1) * $per)->get()->getResultArray();
        return view('staff/activity', [
            'title' => 'Activity log', 'active' => 'activity', 'tab' => $tab, 'rows' => $rows, 'q' => $q,
            'page' => $page, 'pages' => max(1, (int) ceil($total / $per)), 'total' => $total,
            'actions' => array_column($this->db->query('SELECT DISTINCT action FROM activity_logs ORDER BY action')->getResultArray(), 'action'),
        ]);
    }
}
