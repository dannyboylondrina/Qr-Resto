<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Libraries\XlsxWriter;
use Dompdf\Dompdf;

class Reports extends BaseController
{
    private function range(): array
    {
        $preset = (string) ($this->request->getGet('range') ?: 'month');
        $from   = (string) $this->request->getGet('from');
        $to     = (string) $this->request->getGet('to');
        switch ($preset) {
            case 'today': $from = $to = date('Y-m-d'); break;
            case 'yesterday': $from = $to = date('Y-m-d', strtotime('-1 day')); break;
            case 'week': $from = date('Y-m-d', strtotime('monday this week')); $to = date('Y-m-d'); break;
            case 'lastmonth': $from = date('Y-m-01', strtotime('first day of last month')); $to = date('Y-m-t', strtotime('last day of last month')); break;
            case 'custom':
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                    $from = date('Y-m-01'); $to = date('Y-m-d');
                }
                if ($from > $to) { [$from, $to] = [$to, $from]; }
                break;
            default: $preset = 'month'; $from = date('Y-m-01'); $to = date('Y-m-d');
        }
        return [$preset, $from, $to];
    }

    private function data(string $from, string $to): array
    {
        $args = [$from . ' 00:00:00', $to . ' 23:59:59'];
        $base = "FROM orders WHERE payment_status='paid' AND status!='cancelled' AND paid_at BETWEEN ? AND ?";
        $sum  = $this->db->query("SELECT COUNT(*) n, COALESCE(SUM(subtotal),0) gross, COALESCE(SUM(discount_amount),0) disc, COALESCE(SUM(total),0) net $base", $args)->getRowArray();
        $sum['avg'] = $sum['n'] ? $sum['net'] / $sum['n'] : 0;
        return [
            'sum'     => $sum,
            'daily'   => $this->db->query("SELECT DATE(paid_at) d, COUNT(*) n, SUM(subtotal) gross, SUM(discount_amount) disc, SUM(total) net,
                            SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END) cash, SUM(CASE WHEN payment_method='gcash' THEN total ELSE 0 END) gcash,
                            SUM(CASE WHEN payment_method='online_banking' THEN total ELSE 0 END) bank $base GROUP BY DATE(paid_at) ORDER BY d", $args)->getResultArray(),
            'methods' => $this->db->query("SELECT payment_method m, COUNT(*) n, SUM(total) t $base GROUP BY payment_method ORDER BY t DESC", $args)->getResultArray(),
            'types'   => $this->db->query("SELECT type, COUNT(*) n, SUM(total) t $base GROUP BY type", $args)->getResultArray(),
            'best'    => $this->db->query("SELECT oi.name, SUM(oi.quantity) qty, SUM(oi.line_total) rev FROM order_items oi JOIN orders o ON o.id = oi.order_id
                            WHERE o.payment_status='paid' AND o.status!='cancelled' AND o.paid_at BETWEEN ? AND ? GROUP BY oi.name ORDER BY qty DESC LIMIT 15", $args)->getResultArray(),
            'discounts' => $this->db->query("SELECT discount_type t, COALESCE(promo_code,'') code, COUNT(*) n, SUM(discount_amount) amt $base AND discount_type!='none' GROUP BY discount_type, promo_code ORDER BY amt DESC", $args)->getResultArray(),
        ];
    }

    public function index()
    {
        [$preset, $from, $to] = $this->range();
        return view('staff/reports', ['title' => 'Reports', 'active' => 'reports', 'preset' => $preset, 'from' => $from, 'to' => $to] + $this->data($from, $to));
    }

    public function export(string $format)
    {
        [, $from, $to] = $this->range();
        $d    = $this->data($from, $to);
        $name = 'Salo-sales-' . $from . '_to_' . $to;
        log_activity('report_exported', 'report', null, strtoupper($format) . ' ' . $from . ' → ' . $to);

        if ($format === 'pdf') {
            $html = view('staff/reports_pdf', ['from' => $from, 'to' => $to] + $d);
            $pdf  = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
            $pdf->loadHtml($html, 'UTF-8');
            $pdf->setPaper('A4');
            $pdf->render();
            return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.pdf"')->setBody($pdf->output());
        }

        $x = new XlsxWriter();
        $s = $d['sum'];
        $x->addSheet('Summary', [
            ['Salo — Sales report', $from . ' to ' . $to],
            ['Paid orders', (int) $s['n']], ['Gross sales', (float) $s['gross']], ['Discounts', (float) $s['disc']],
            ['Net sales', (float) $s['net']], ['Average ticket', round((float) $s['avg'], 2)],
        ], [0]);
        $rows = [['Date', 'Orders', 'Gross', 'Discounts', 'Net', 'Cash', 'GCash', 'Online Banking']];
        foreach ($d['daily'] as $r) {
            $rows[] = [$r['d'], (int) $r['n'], (float) $r['gross'], (float) $r['disc'], (float) $r['net'], (float) $r['cash'], (float) $r['gcash'], (float) $r['bank']];
        }
        $x->addSheet('Daily', $rows);
        $rows = [['Payment method', 'Orders', 'Amount']];
        foreach ($d['methods'] as $m) {
            $rows[] = [payment_method_label($m['m']), (int) $m['n'], (float) $m['t']];
        }
        $x->addSheet('Payment methods', $rows);
        $rows = [['Item', 'Qty sold', 'Revenue']];
        foreach ($d['best'] as $b) {
            $rows[] = [$b['name'], (int) $b['qty'], (float) $b['rev']];
        }
        $x->addSheet('Best sellers', $rows);
        $rows = [['Discount', 'Promo code', 'Orders', 'Amount']];
        foreach ($d['discounts'] as $r) {
            $rows[] = [discount_label($r['t']) ?: $r['t'], $r['code'], (int) $r['n'], (float) $r['amt']];
        }
        $x->addSheet('Discounts', $rows);

        return $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"')->setBody($x->build());
    }
}
