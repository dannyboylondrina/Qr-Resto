<?= $this->extend('layouts/staff') ?>
<?= $this->section('actions') ?>
<button class="btn btn-sm" onclick="window.print()">🖨 Print all cards</button>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php if ($isLocal): ?>
    <div class="alert alert-warn no-print">These QR codes point to <b><?= esc(base_url()) ?></b>, which phones can’t open. Open this page using the computer’s Wi-Fi address
        (e.g. <code>http://192.168.1.10/QRCodeResto/public/staff/qr-codes</code>) or set <code>app.baseURL</code> in <code>.env</code> before printing.</div>
<?php endif ?>

<div class="card no-print mb-3">
    <h3>Tables</h3>
    <div class="table-wrap">
        <table class="data">
            <tr><th>Code</th><th>Seats</th><th>Zone</th><th>Order</th><th></th></tr>
            <?php foreach ($tables as $t): ?>
                <tr><td colspan="5" style="padding:.4rem .6rem">
                    <form method="post" data-ajax data-reload action="<?= site_url('staff/tables/save') ?>" class="flex" style="flex-wrap:nowrap">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input name="code" value="<?= esc($t['code']) ?>" class="input" style="width:90px;padding:.45rem" required>
                        <input name="seats" type="number" min="1" max="40" value="<?= (int) $t['seats'] ?>" class="input" style="width:80px;padding:.45rem">
                        <input name="zone" value="<?= esc($t['zone']) ?>" class="input" style="padding:.45rem" list="zones">
                        <input name="sort_order" type="number" value="<?= (int) $t['sort_order'] ?>" class="input" style="width:70px;padding:.45rem">
                        <button class="btn btn-ghost btn-sm">Save</button>
                        <button type="button" class="btn btn-ghost btn-sm" data-post="staff/tables/<?= $t['id'] ?>/delete" data-confirm="Delete table <?= esc($t['code'], 'attr') ?>?" data-reload>🗑</button>
                    </form>
                </td></tr>
            <?php endforeach ?>
            <tr><td colspan="5" style="padding:.6rem;background:#faf6ef">
                <form method="post" data-ajax data-reload action="<?= site_url('staff/tables/save') ?>" class="flex" style="flex-wrap:nowrap">
                    <input name="code" placeholder="T11" class="input" style="width:90px;padding:.45rem" required>
                    <input name="seats" type="number" min="1" max="40" value="4" class="input" style="width:80px;padding:.45rem">
                    <input name="zone" placeholder="Main Hall" class="input" style="padding:.45rem" list="zones">
                    <input name="sort_order" type="number" value="<?= count($tables) + 1 ?>" class="input" style="width:70px;padding:.45rem">
                    <button class="btn btn-sm">+ Add table</button>
                </form>
            </td></tr>
        </table>
    </div>
    <datalist id="zones"><?php foreach (array_unique(array_column($tables, 'zone')) as $z): ?><option value="<?= esc($z) ?>"><?php endforeach ?></datalist>
</div>

<h3 class="no-print">QR cards <span class="muted small">— table tent / sticker layout</span></h3>
<div class="qr-cards">
    <?php foreach ($tables as $t): ?>
        <div class="qr-card" data-code="<?= esc($t['code']) ?>" data-url="<?= esc($t['url']) ?>">
            <div class="tlabel">TABLE</div>
            <div class="tnum"><?= esc($t['code']) ?></div>
            <div class="scan">Scan to order</div>
            <div class="qrbox"></div>
            <div class="brand">Salo</div>
            <div class="tiny muted">Modern Filipino Kitchen</div>
            <div class="pays">Pay with Cash, GCash or Online Banking</div>
            <div class="flex no-print mt-2" style="justify-content:center">
                <button class="btn btn-ghost btn-sm" data-download>⬇ PNG</button>
                <a class="btn btn-ghost btn-sm" href="<?= esc($t['url']) ?>" target="_blank">Test</a>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset('vendor/qrcode.js') ?>"></script>
<script>
(function () {
  function makeQr(url) { const q = qrcode(0, 'M'); q.addData(url); q.make(); return q; }

  document.querySelectorAll('.qr-card').forEach((card) => {
    card.querySelector('.qrbox').innerHTML = makeQr(card.dataset.url).createSvgTag({ cellSize: 6, margin: 0, scalable: true });
  });

  // Draw a printable card onto a canvas and download it as PNG
  function downloadCard(card) {
    const code = card.dataset.code, q = makeQr(card.dataset.url), n = q.getModuleCount();
    const W = 900, H = 1300, c = document.createElement('canvas'), x = c.getContext('2d');
    c.width = W; c.height = H;
    x.fillStyle = '#fffdf9'; x.fillRect(0, 0, W, H);
    x.strokeStyle = '#e4dccd'; x.lineWidth = 6; x.strokeRect(20, 20, W - 40, H - 40);
    x.textAlign = 'center'; x.fillStyle = '#b5451b'; x.font = '700 34px Inter, Arial'; x.fillText('T A B L E', W / 2, 120);
    x.fillStyle = '#1c1917'; x.font = '600 190px Fraunces, Georgia, serif'; x.fillText(code, W / 2, 310);
    x.font = '600 44px Inter, Arial'; x.fillText('Scan to order', W / 2, 385);
    const size = 560, cell = size / n, ox = (W - size) / 2, oy = 430;
    x.fillStyle = '#1c1917';
    for (let r = 0; r < n; r++) for (let col = 0; col < n; col++) if (q.isDark(r, col)) x.fillRect(ox + col * cell, oy + r * cell, Math.ceil(cell), Math.ceil(cell));
    x.font = '600 76px Fraunces, Georgia, serif'; x.fillText('Salo', W / 2, 1110);
    x.fillStyle = '#78716c'; x.font = '500 30px Inter, Arial'; x.fillText('Modern Filipino Kitchen', W / 2, 1160);
    x.font = '500 28px Inter, Arial'; x.fillText('Pay with Cash, GCash or Online Banking', W / 2, 1215);
    const a = document.createElement('a');
    a.download = 'Salo-table-' + code + '.png';
    a.href = c.toDataURL('image/png');
    a.click();
  }
  document.addEventListener('click', (e) => { const b = e.target.closest('[data-download]'); if (b) downloadCard(b.closest('.qr-card')); });
})();
</script>
<?= $this->endSection() ?>
