<?= $this->extend('layouts/staff') ?>
<?= $this->section('actions') ?>
<?php $qs = http_build_query(['range' => $preset, 'from' => $from, 'to' => $to]); ?>
<a class="btn btn-sm btn-ghost" href="<?= site_url('staff/reports/export/pdf?' . $qs) ?>">⬇ PDF</a>
<a class="btn btn-sm btn-ghost" href="<?= site_url('staff/reports/export/xlsx?' . $qs) ?>">⬇ Excel</a>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<form method="get" class="card flex mb-3" style="padding:1rem">
    <?php foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'month' => 'This month', 'lastmonth' => 'Last month'] as $k => $l): ?>
        <a class="btn btn-sm <?= $preset === $k ? 'btn-dark' : 'btn-ghost' ?>" href="?range=<?= $k ?>"><?= $l ?></a>
    <?php endforeach ?>
    <input type="hidden" name="range" value="custom">
    <input type="date" name="from" value="<?= esc($from) ?>" class="input" style="width:auto;padding:.45rem">
    <span>to</span>
    <input type="date" name="to" value="<?= esc($to) ?>" class="input" style="width:auto;padding:.45rem">
    <button class="btn btn-sm">Apply</button>
</form>

<p class="muted"><?= date('M j, Y', strtotime($from)) ?> – <?= date('M j, Y', strtotime($to)) ?> · paid orders only</p>
<div class="grid grid-4 mb-3">
    <div class="kpi"><span>Net sales</span><b><?= peso($sum['net']) ?></b><small>after discounts</small></div>
    <div class="kpi"><span>Paid orders</span><b><?= (int) $sum['n'] ?></b><small>avg ticket <?= peso($sum['avg']) ?></small></div>
    <div class="kpi"><span>Gross sales</span><b><?= peso($sum['gross']) ?></b><small>before discounts</small></div>
    <div class="kpi"><span>Discounts given</span><b><?= peso($sum['disc']) ?></b><small>Senior/PWD + promos</small></div>
</div>

<div class="grid grid-2 mb-3" style="align-items:start">
    <div class="card">
        <h3>By payment method</h3>
        <table class="data">
            <tr><th>Method</th><th class="num">Orders</th><th class="num">Amount</th><th class="num">Share</th></tr>
            <?php foreach ($methods as $m): ?>
                <tr><td><?= esc(payment_method_label($m['m'])) ?></td><td class="num"><?= (int) $m['n'] ?></td><td class="num"><?= peso($m['t']) ?></td>
                    <td class="num"><?= $sum['net'] > 0 ? round($m['t'] / $sum['net'] * 100, 1) : 0 ?>%</td></tr>
            <?php endforeach ?>
            <?php foreach ($types as $t): ?>
                <tr style="background:#faf6ef"><td><?= $t['type'] === 'dine_in' ? 'Dine-in' : 'Take-away' ?></td><td class="num"><?= (int) $t['n'] ?></td><td class="num"><?= peso($t['t']) ?></td><td></td></tr>
            <?php endforeach ?>
        </table>
    </div>
    <div class="card">
        <h3>Discounts given</h3>
        <table class="data">
            <tr><th>Type</th><th class="num">Orders</th><th class="num">Amount</th></tr>
            <?php foreach ($discounts as $d): ?>
                <tr><td><?= esc($d['t'] === 'promo' ? 'Promo ' . $d['code'] : discount_label($d['t'])) ?></td><td class="num"><?= (int) $d['n'] ?></td><td class="num"><?= peso($d['amt']) ?></td></tr>
            <?php endforeach ?>
            <?php if (! $discounts): ?><tr><td colspan="3" class="muted">None</td></tr><?php endif ?>
        </table>
    </div>
</div>

<div class="grid mb-3" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr);align-items:start" id="repGrid">
    <div class="table-wrap">
        <table class="data">
            <tr><th>Date</th><th class="num">Orders</th><th class="num">Gross</th><th class="num">Discounts</th><th class="num">Net</th><th class="num">Cash</th><th class="num">GCash</th><th class="num">Bank</th></tr>
            <?php foreach ($daily as $r): ?>
                <tr><td class="nowrap"><?= date('D, M j', strtotime($r['d'])) ?></td><td class="num"><?= (int) $r['n'] ?></td><td class="num"><?= peso($r['gross']) ?></td>
                    <td class="num"><?= peso($r['disc']) ?></td><td class="num"><b><?= peso($r['net']) ?></b></td><td class="num"><?= peso($r['cash']) ?></td>
                    <td class="num"><?= peso($r['gcash']) ?></td><td class="num"><?= peso($r['bank']) ?></td></tr>
            <?php endforeach ?>
            <?php if (! $daily): ?><tr><td colspan="8" class="muted">No paid orders in this period.</td></tr><?php endif ?>
        </table>
    </div>
    <div class="card">
        <h3>Best sellers</h3>
        <?php foreach ($best as $i => $b): ?>
            <div class="sum-row small"><span><?= $i + 1 ?>. <?= esc($b['name']) ?></span><span><b><?= (int) $b['qty'] ?></b> · <?= peso($b['rev']) ?></span></div>
        <?php endforeach ?>
        <?php if (! $best): ?><p class="muted">—</p><?php endif ?>
    </div>
</div>
<style>@media (max-width: 1000px) { #repGrid { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>
