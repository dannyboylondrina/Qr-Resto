<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<section class="page-head">
    <div class="container">
        <span class="kicker"><?= t('track_kicker') ?></span>
        <h1><?= t('track_title') ?></h1>
    </div>
</section>
<section class="container" style="max-width:640px">
    <form class="card" method="get" action="<?= site_url('track') ?>">
        <label for="code"><?= t('order_code') ?></label>
        <div class="flex" style="flex-wrap:nowrap">
            <input id="code" name="code" class="input code-input" maxlength="6" minlength="6" required autocomplete="off" placeholder="ABC123" autofocus>
            <button class="btn btn-lg"><?= t('track') ?></button>
        </div>
        <p class="hint">The 6-character code shown after you ordered (also on your receipt).</p>
    </form>

    <div class="card-flat mt-3 hidden" id="recentBox">
        <h3><?= t('recent_orders') ?></h3>
        <div class="flex" id="recent"></div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  let codes = [];
  try { codes = JSON.parse(localStorage.getItem('df_orders')) || []; } catch (e) {}
  <?= json_encode(array_values($mine)) ?>.forEach((c) => { if (!codes.includes(c)) codes.push(c); });
  codes = codes.filter((c) => /^[A-Z0-9]{6}$/.test(c)).slice(0, 10);
  if (!codes.length) return;
  document.getElementById('recentBox').classList.remove('hidden');
  document.getElementById('recent').innerHTML = codes.map((c) => '<a class="order-code" style="font-size:1rem;text-decoration:none" href="' + DF.url('track/' + c) + '">' + c + '</a>').join('');
})();
</script>
<?= $this->endSection() ?>
