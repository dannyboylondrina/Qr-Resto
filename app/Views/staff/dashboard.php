<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<?php
$methodLabels = ['cash' => 'Cash', 'gcash' => 'GCash', 'online_banking' => 'Online Banking', 'pay_later' => 'Tab'];
$mData = [];
foreach ($methods as $m) {
    $mData[$methodLabels[$m['m']] ?? $m['m']] = (float) $m['t'];
}
$tData = [];
foreach ($types as $t) {
    $tData[$t['type'] === 'dine_in' ? 'Dine-in' : 'Take-away'] = (int) $t['n'];
}
?>
<div class="grid grid-4 mb-3">
    <a class="kpi" href="<?= site_url('staff/orders') ?>" style="text-decoration:none"><span>Payments to verify</span><b><?= $live['verify'] ?></b><small>GCash / bank</small></a>
    <a class="kpi" href="<?= site_url('kitchen') ?>" style="text-decoration:none"><span>In the kitchen</span><b><?= $live['kitchen'] ?></b><small>orders cooking</small></a>
    <a class="kpi" href="<?= site_url('staff/service') ?>" style="text-decoration:none"><span>Service requests</span><b><?= $live['service'] ?></b><small>open now</small></a>
    <a class="kpi" href="<?= site_url('staff/floor') ?>" style="text-decoration:none"><span>Tables occupied</span><b><?= $live['tables'] ?></b><small>right now</small></a>
</div>

<div class="grid grid-3 mb-3">
    <?php foreach (['Today' => $today, 'This week' => $week, 'This month' => $month] as $label => $k): ?>
        <div class="kpi">
            <span><?= $label ?> revenue</span>
            <b><?= peso($k['rev']) ?></b>
            <small><?= (int) $k['n'] ?> orders · avg ticket <?= peso($k['avg']) ?></small>
        </div>
    <?php endforeach ?>
</div>

<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)" id="dashA">
    <div class="card"><h3>Sales — last 14 days</h3><div class="chart-box"><canvas id="cSales"></canvas></div></div>
    <div class="card"><h3>By payment method <span class="muted small">(month)</span></h3><div class="chart-box"><canvas id="cMethods"></canvas></div></div>
</div>
<div class="grid mt-3" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)" id="dashB">
    <div class="card"><h3>Peak hours <span class="muted small">(orders, last 30 days)</span></h3><div class="chart-box"><canvas id="cHours"></canvas></div></div>
    <div class="card"><h3>Dine-in vs take-away <span class="muted small">(month)</span></h3><div class="chart-box"><canvas id="cTypes"></canvas></div></div>
</div>

<div class="card mt-3">
    <h3>Best sellers this month</h3>
    <?php if (! $best): ?><p class="muted">No paid orders yet this month.</p><?php else: ?>
    <div class="table-wrap"><table class="data">
        <tr><th>#</th><th>Item</th><th class="num">Qty sold</th><th class="num">Revenue</th></tr>
        <?php foreach ($best as $i => $b): ?>
            <tr><td><?= $i + 1 ?></td><td><b><?= esc($b['name']) ?></b></td><td class="num"><?= (int) $b['qty'] ?></td><td class="num"><?= peso($b['rev']) ?></td></tr>
        <?php endforeach ?>
    </table></div>
    <?php endif ?>
</div>
<style>@media (max-width: 960px) { #dashA, #dashB { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset('vendor/chart.umd.min.js') ?>"></script>
<script>
(function () {
  const rust = '#b5451b', ink = '#1c1917', palette = ['#b5451b', '#2a5b8f', '#2f7d4f', '#b7791f', '#78716c'];
  Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
  Chart.defaults.color = '#57534e';
  const peso = (v) => '₱' + Number(v).toLocaleString('en-PH');
  const daily = <?= json_encode($daily) ?>;
  new Chart(document.getElementById('cSales'), {
    type: 'bar',
    data: { labels: Object.keys(daily).map((d) => new Date(d + 'T00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })),
      datasets: [{ data: Object.values(daily), backgroundColor: rust, borderRadius: 6 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => peso(c.raw) } } },
      scales: { y: { ticks: { callback: peso }, grid: { color: '#eee7dc' } }, x: { grid: { display: false } } } },
  });
  const hours = <?= json_encode($hours) ?>;
  new Chart(document.getElementById('cHours'), {
    type: 'line',
    data: { labels: Object.keys(hours).map((h) => (h % 12 || 12) + (h < 12 ? 'am' : 'pm')),
      datasets: [{ data: Object.values(hours), borderColor: ink, backgroundColor: 'rgba(181,69,27,.15)', fill: true, tension: .35, pointRadius: 3 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#eee7dc' } }, x: { grid: { display: false } } } },
  });
  const doughnut = (id, obj, money) => new Chart(document.getElementById(id), {
    type: 'doughnut',
    data: { labels: Object.keys(obj), datasets: [{ data: Object.values(obj), backgroundColor: palette, borderWidth: 2, borderColor: '#fffdf9' }] },
    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.label + ': ' + (money ? peso(c.raw) : c.raw) } } } },
  });
  doughnut('cMethods', <?= json_encode($mData ?: new stdClass()) ?>, true);
  doughnut('cTypes', <?= json_encode($tData ?: new stdClass()) ?>, false);
})();
</script>
<?= $this->endSection() ?>
