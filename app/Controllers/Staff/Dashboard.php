<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $today = date('Y-m-d');
        $week  = date('Y-m-d', strtotime('monday this week'));
        $month = date('Y-m-01');

        $kpi = function (string $from) {
            $r = $this->db->query("SELECT COUNT(*) n, COALESCE(SUM(total),0) rev FROM orders WHERE payment_status='paid' AND status!='cancelled' AND paid_at >= ?", [$from . ' 00:00:00'])->getRowArray();
            $r['avg'] = $r['n'] ? $r['rev'] / $r['n'] : 0;
            return $r;
        };

        $from30 = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
        $daily  = [];
        for ($i = 13; $i >= 0; $i--) {
            $daily[date('Y-m-d', strtotime("-$i days"))] = 0;
        }
        foreach ($this->db->query("SELECT DATE(paid_at) d, SUM(total) t FROM orders WHERE payment_status='paid' AND status!='cancelled' AND paid_at >= ? GROUP BY DATE(paid_at)", [date('Y-m-d', strtotime('-13 days'))])->getResultArray() as $r) {
            $daily[$r['d']] = (float) $r['t'];
        }
        $hours = array_fill(10, 13, 0);
        foreach ($this->db->query("SELECT HOUR(created_at) h, COUNT(*) n FROM orders WHERE status!='cancelled' AND created_at >= ? GROUP BY HOUR(created_at)", [$from30])->getResultArray() as $r) {
            $hours[(int) $r['h']] = (int) $r['n'];
        }
        ksort($hours);

        return view('staff/dashboard', [
            'title'   => 'Sales dashboard',
            'active'  => 'dashboard',
            'today'   => $kpi($today),
            'week'    => $kpi($week),
            'month'   => $kpi($month),
            'best'    => $this->db->query("SELECT oi.name, SUM(oi.quantity) qty, SUM(oi.line_total) rev FROM order_items oi JOIN orders o ON o.id=oi.order_id
                            WHERE o.payment_status='paid' AND o.status!='cancelled' AND o.paid_at >= ? GROUP BY oi.name ORDER BY qty DESC LIMIT 8", [$month . ' 00:00:00'])->getResultArray(),
            'methods' => $this->db->query("SELECT payment_method m, COUNT(*) n, SUM(total) t FROM orders WHERE payment_status='paid' AND status!='cancelled' AND paid_at >= ? GROUP BY payment_method", [$month . ' 00:00:00'])->getResultArray(),
            'types'   => $this->db->query("SELECT type, COUNT(*) n, SUM(total) t FROM orders WHERE payment_status='paid' AND status!='cancelled' AND paid_at >= ? GROUP BY type", [$month . ' 00:00:00'])->getResultArray(),
            'daily'   => $daily,
            'hours'   => $hours,
            'live'    => [
                'verify'  => $this->db->table('orders')->where('payment_status', 'for_verification')->where('status !=', 'cancelled')->countAllResults(),
                'kitchen' => $this->db->table('orders')->where('released_at IS NOT NULL')->whereIn('status', ['received', 'preparing'])->countAllResults(),
                'service' => $this->db->table('service_requests')->where('status', 'open')->countAllResults(),
                'tables'  => $this->db->table('dining_tables')->where('status', 'occupied')->countAllResults(),
            ],
        ]);
    }

    /** Lightweight state used by every staff screen for alerts and sidebar counters. */
    public function pulse()
    {
        $db = $this->db;
        $o  = $db->query("SELECT MAX(id) max_id, MAX(updated_at) upd,
                SUM(payment_status='for_verification' AND status!='cancelled') verify,
                SUM(payment_status='awaiting' AND status!='cancelled' AND created_at >= ?) awaiting,
                SUM(released_at IS NOT NULL AND status IN ('received','preparing')) kitchen,
                UNIX_TIMESTAMP(MAX(released_at)) kmax,
                UNIX_TIMESTAMP(MAX(payment_submitted_at)) vmax
              FROM orders", [date('Y-m-d', strtotime('-1 day'))])->getRowArray();
        $r = $db->query("SELECT MAX(id) max_id, SUM(status='pending') pending, SUM(payment_status='for_verification') verify,
                UNIX_TIMESTAMP(MAX(payment_submitted_at)) vmax, MAX(updated_at) upd FROM reservations")->getRowArray();
        $s = $db->query("SELECT COUNT(*) n, MAX(id) max_id FROM service_requests WHERE status='open'")->getRowArray();
        $sMax = (int) ($db->query('SELECT MAX(id) m FROM service_requests')->getRow()->m ?? 0);
        $t = $db->query('SELECT MAX(updated_at) upd FROM dining_tables')->getRow()->upd ?? '';

        $latestOrder = $db->query('SELECT o.code, o.type, o.total, t.code tcode FROM orders o LEFT JOIN dining_tables t ON t.id=o.table_id ORDER BY o.id DESC LIMIT 1')->getRowArray();
        $latestSvc   = $db->query('SELECT s.type, t.code FROM service_requests s JOIN dining_tables t ON t.id=s.table_id ORDER BY s.id DESC LIMIT 1')->getRowArray();
        $verifyLabel = $db->query("SELECT o.code, o.total FROM orders o WHERE payment_status='for_verification' ORDER BY payment_submitted_at DESC LIMIT 1")->getRowArray();
        $svcNames    = ['call_staff' => 'Call staff', 'bill' => 'Bill requested', 'water' => 'Water / utensils'];

        return $this->json([
            'ok'           => true,
            'order_max'    => (int) $o['max_id'],
            'order_label'  => $latestOrder ? ($latestOrder['type'] === 'dine_in' ? 'Table ' . $latestOrder['tcode'] : 'Take-away') . ' · ' . peso($latestOrder['total']) : '',
            'awaiting'     => (int) $o['awaiting'],
            'verify'       => (int) $o['verify'] + (int) $r['verify'],
            'verify_max'   => max((int) $o['vmax'], (int) $r['vmax']),
            'verify_label' => $verifyLabel ? $verifyLabel['code'] . ' · ' . peso($verifyLabel['total']) : '',
            'kitchen'      => (int) $o['kitchen'],
            'kitchen_max'  => (int) $o['kmax'],
            'service'      => (int) $s['n'],
            'service_max'  => $sMax,
            'service_label'=> $latestSvc ? 'Table ' . $latestSvc['code'] . ' · ' . ($svcNames[$latestSvc['type']] ?? '') : '',
            'bills'        => $db->table('table_visits')->where('status', 'bill_requested')->countAllResults(),
            'reservations' => (int) $r['pending'],
            'res_max'      => (int) $r['max_id'],
            'reviews'      => has_role('admin') ? $db->table('reviews')->where('is_approved', 0)->countAllResults() : 0,
            'hash'         => md5($o['upd'] . '|' . $r['upd'] . '|' . $t . '|' . $s['n'] . '|' . $s['max_id']),
        ]);
    }
}
