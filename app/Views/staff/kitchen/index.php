<?= $this->extend('layouts/staff') ?>
<?= $this->section('actions') ?>
<span class="badge" id="clock"></span>
<button class="btn btn-ghost btn-sm" style="color:#f5f0e8" onclick="document.documentElement.requestFullscreen && document.documentElement.requestFullscreen()">⛶ Full screen</button>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div data-live="<?= site_url('kitchen/board') ?>" data-interval="4000" id="kboard">
    <?= view('staff/kitchen/_board', ['tickets' => $tickets]) ?>
</div>

<details class="card-flat mt-4">
    <summary style="cursor:pointer;font-weight:700">🚫 Sold-out switches</summary>
    <p class="muted small mt-1">Tap to mark an item sold out — it greys out on the guest menu immediately.</p>
    <div class="flex mt-2">
        <?php foreach ($items as $it): ?>
            <button class="btn btn-sm <?= $it['is_available'] ? 'btn-ghost' : 'btn-red' ?>" style="<?= $it['is_available'] ? 'color:#f5f0e8' : '' ?>"
                    data-post="kitchen/item/<?= $it['id'] ?>/toggle" data-reload><?= $it['is_available'] ? '' : '✕ ' ?><?= esc($it['name']) ?></button>
        <?php endforeach ?>
    </div>
</details>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  let offset = 0;
  function sync() { const g = document.querySelector("[data-now]"); if (g) offset = Number(g.dataset.now) - Date.now() / 1000; }
  function tick() {
    const now = Date.now() / 1000 + offset;
    document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
    document.querySelectorAll('.kcard').forEach((c) => {
      const start = Number(c.dataset.start), eta = Number(c.dataset.eta), el = Math.max(0, now - start);
      c.querySelector('[data-timer]').textContent = Math.floor(el / 60) + ':' + String(Math.floor(el % 60)).padStart(2, '0');
      c.classList.toggle('on-time', now < eta);
      c.classList.toggle('warn', now >= eta && now < eta + 300);   // amber at the estimated time
      c.classList.toggle('late', now >= eta + 300);                // red when 5+ minutes late
    });
  }
  sync(); setInterval(tick, 1000); tick();
  document.getElementById('kboard').addEventListener('df:updated', () => { sync(); tick(); });
})();
</script>
<?= $this->endSection() ?>
