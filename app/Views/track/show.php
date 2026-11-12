<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $needsPayment = in_array($o['payment_status'], ['awaiting', 'rejected'], true) && $o['payment_method'] !== 'cash' && $o['status'] !== 'cancelled'; ?>
<section class="page-head" style="padding-bottom:1rem">
    <div class="container">
        <span class="kicker"><?= t('track_kicker') ?></span>
        <h1><?= t('track_title') ?></h1>
        <div class="flex"><span class="muted"><?= t('order_code') ?></span> <span class="order-code"><?= esc($o['code']) ?></span>
            <span class="muted small"><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></span></div>
    </div>
</section>

<section class="container grid" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);align-items:start" id="trackGrid">
    <div>
        <div class="card" id="statusBox" data-live="<?= site_url('track/' . $o['code'] . '/status') ?>" data-interval="8000">
            <?= view('track/_status', ['o' => $o]) ?>
        </div>

        <?php if ($needsPayment && $methods): ?>
        <div class="card mt-3" id="payCard">
            <h3><?= t('resubmit') ?></h3>
            <form method="post" action="<?= site_url('track/' . $o['code'] . '/payment') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="pay-options mb-2">
                    <?php foreach ($methods as $m): ?>
                        <label class="pay-opt"><input type="radio" name="payment_method" value="<?= $m ?>" <?= $o['payment_method'] === $m || ($o['payment_method'] === 'cash' && $m === $methods[0]) ? 'checked' : '' ?>>
                            <b><?= $m === 'gcash' ? '📱 GCash' : '🏦 Online Banking' ?></b></label>
                    <?php endforeach ?>
                </div>
                <div class="pay-box" data-box="gcash">
                    <div class="center"><span class="small muted"><?= t('send_exact') ?></span><div class="serif" style="font-size:2rem"><?= peso($o['total']) ?></div></div>
                    <?php if ($gcash['qr']): ?><img class="qr" src="<?= esc($gcash['qr']) ?>" alt="GCash QR"><?php endif ?>
                    <dl class="kv"><dt><?= t('account_name') ?></dt><dd><?= esc($gcash['name']) ?></dd><dt><?= t('gcash_number') ?></dt><dd><?= esc($gcash['number']) ?></dd></dl>
                </div>
                <div class="pay-box" data-box="online_banking">
                    <div class="center"><span class="small muted"><?= t('send_exact') ?></span><div class="serif" style="font-size:2rem"><?= peso($o['total']) ?></div></div>
                    <select name="bank_account_id" class="input mb-1">
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= ($o['bank_account_id'] ?? null) == $b['id'] ? 'selected' : '' ?>><?= esc($b['bank_name']) ?> — <?= esc($b['account_name']) ?> · <?= esc($b['account_number']) ?></option><?php endforeach ?>
                    </select>
                </div>
                <div class="field mt-2"><label><?= t('reference_no') ?></label><input name="reference_no" class="input" required maxlength="60"></div>
                <div class="field"><label><?= t('proof') ?></label><input type="file" name="proof" accept="image/*" class="input" required></div>
                <button class="btn btn-block"><?= t('resubmit') ?></button>
            </form>
        </div>
        <?php endif ?>

        <?php if ($o['status'] === 'served'): ?>
        <div class="card mt-3">
            <?php if ($review): ?>
                <h3><?= t('thanks_review') ?></h3>
                <div class="stars"><?= str_repeat('★', (int) $review['rating']) ?></div>
            <?php else: ?>
                <h3><?= t('rate_title') ?></h3>
                <form method="post" action="<?= site_url('track/' . $o['code'] . '/review') ?>">
                    <?= csrf_field() ?>
                    <div class="rating mb-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="r<?= $i ?>" value="<?= $i ?>" required><label for="r<?= $i ?>" title="<?= $i ?> stars">★</label>
                        <?php endfor ?>
                    </div>
                    <div class="field"><label><?= t('your_name') ?></label><input name="guest_name" class="input" maxlength="120" value="<?= esc($o['customer_name'] ?? '') ?>"></div>
                    <div class="field"><label><?= t('comment') ?></label><textarea name="comment" class="input" maxlength="1000"></textarea></div>
                    <button class="btn"><?= t('submit_review') ?></button>
                </form>
            <?php endif ?>
        </div>
        <?php endif ?>
    </div>

    <div>
        <div class="card">
            <h3><?= t('items') ?></h3>
            <?php foreach ($o['items'] as $it): ?>
                <div class="tray-line" style="grid-template-columns:1fr auto">
                    <div><b><?= (int) $it['quantity'] ?>×</b> <?= esc($it['name']) ?>
                        <?php if ($it['options_text']): ?><div class="opts"><?= esc($it['options_text']) ?></div><?php endif ?>
                        <?php if ($it['note']): ?><div class="opts">📝 <?= esc($it['note']) ?></div><?php endif ?>
                    </div>
                    <div class="right"><?= peso($it['line_total']) ?></div>
                </div>
            <?php endforeach ?>
            <div class="mt-2">
                <div class="sum-row"><span><?= t('subtotal') ?></span><span><?= peso($o['subtotal']) ?></span></div>
                <?php if ($o['discount_amount'] > 0): ?>
                    <div class="sum-row" style="color:var(--green)"><span><?= t('discount') ?> · <?= esc($o['discount_type'] === 'promo' ? $o['promo_code'] : discount_label($o['discount_type'])) ?></span><span>−<?= peso($o['discount_amount']) ?></span></div>
                <?php endif ?>
                <div class="sum-row total"><span><?= t('total') ?></span><span><?= peso($o['total']) ?></span></div>
            </div>
        </div>

        <?php if ($o['payment_status'] === 'paid'): ?>
        <div class="card mt-3">
            <div class="flex between mb-1"><h3 class="mb-0"><?= t('receipt') ?></h3><a class="btn btn-sm" href="<?= site_url('track/' . $o['code'] . '/receipt') ?>">⬇ <?= t('download_pdf') ?></a></div>
            <dl class="kv">
                <dt><?= t('order_code') ?></dt><dd><?= esc($o['code']) ?></dd>
                <dt><?= t('table') ?></dt><dd><?= $o['table'] ? esc($o['table']['code']) . ' · ' . t('dine_in') : t('take_away') ?></dd>
                <dt><?= t('payment') ?></dt><dd><?= esc(payment_method_label($o['payment_method'])) ?></dd>
                <?php if ($o['reference_no']): ?><dt>Reference</dt><dd><?= esc($o['reference_no']) ?></dd><?php endif ?>
                <dt>Paid</dt><dd><?= date('M j, Y g:i A', strtotime($o['paid_at'])) ?></dd>
                <dt><?= t('total') ?></dt><dd><?= peso($o['total']) ?></dd>
            </dl>
        </div>
        <?php endif ?>

        <div class="flex mt-3">
            <a class="btn btn-ghost" href="<?= site_url('menu') ?>"><?= $o['type'] === 'dine_in' ? t('add_to_table') : t('nav_menu') ?></a>
            <a class="btn btn-ghost" href="<?= site_url('track') ?>"><?= t('recent_orders') ?></a>
        </div>
    </div>
</section>
<style>@media (max-width: 820px) { #trackGrid { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  // remember this order on the device
  try { const k = 'df_orders', a = JSON.parse(localStorage.getItem(k)) || []; if (!a.includes('<?= esc($o['code'], 'js') ?>')) { a.unshift('<?= esc($o['code'], 'js') ?>'); localStorage.setItem(k, JSON.stringify(a.slice(0, 10))); } } catch (e) {}

  // payment box toggle
  const pay = document.getElementById('payCard');
  if (pay) {
    const sync = () => { const m = (pay.querySelector('input[name=payment_method]:checked') || {}).value; pay.querySelectorAll('[data-box]').forEach((b) => b.classList.toggle('hidden', b.dataset.box !== m)); };
    pay.addEventListener('change', sync); sync();
  }

  // countdown
  let offset = 0;
  function tick() {
    const el = document.querySelector('.countdown[data-eta]');
    if (!el) return;
    if (!el.dataset.synced) { offset = Number(el.dataset.now) - Date.now(); el.dataset.synced = 1; }
    const left = (Number(el.dataset.eta) - (Date.now() + offset)) / 60000;
    const label = document.querySelector('[data-eta-label]');
    if (left > 0) { el.textContent = Math.ceil(left); if (label) label.textContent = '<?= esc(t('minutes_left'), 'js') ?>'; }
    else { el.textContent = '🔥'; if (label) label.textContent = '<?= esc(t('almost_ready'), 'js') ?>'; }
  }
  setInterval(tick, 1000); tick();

  // reload the page when payment / served state changes (to show receipt, forms, review)
  const box = document.getElementById('statusBox');
  const first = box.querySelector('[data-ps]');
  const start = first.dataset.ps + '|' + (first.dataset.st === 'served');
  box.addEventListener('df:updated', () => {
    const n = box.querySelector('[data-ps]');
    if (n && n.dataset.ps + '|' + (n.dataset.st === 'served') !== start) location.reload();
    tick();
  });
})();
</script>
<?= $this->endSection() ?>
