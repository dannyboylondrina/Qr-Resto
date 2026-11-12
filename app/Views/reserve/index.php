<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<section class="page-head">
    <div class="container">
        <span class="kicker"><?= t('reserve_kicker') ?></span>
        <h1><?= t('reserve_title') ?></h1>
        <p class="lead"><?= t('avg_cover') ?></p>
    </div>
</section>

<section class="container grid" style="grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);align-items:start" id="resGrid">
    <form class="card" method="post" action="<?= site_url('reserve') ?>" enctype="multipart/form-data" id="resForm">
        <?= csrf_field() ?>
        <div class="grid grid-2">
            <div class="field"><label><?= t('full_name') ?> *</label><input name="name" class="input" required maxlength="120" value="<?= esc(old('name')) ?>" autocomplete="name"></div>
            <div class="field"><label><?= t('mobile') ?> *</label><input name="mobile" class="input" type="tel" required value="<?= esc(old('mobile')) ?>" placeholder="09XX XXX XXXX" autocomplete="tel"></div>
        </div>
        <div class="field"><label><?= t('email_optional') ?></label><input name="email" type="email" class="input" value="<?= esc(old('email')) ?>" autocomplete="email"></div>
        <div class="grid grid-3">
            <div class="field"><label><?= t('date') ?> *</label>
                <input name="date" id="resDate" type="date" class="input" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" value="<?= esc(old('date') ?: date('Y-m-d', strtotime('+1 day'))) ?>">
                <div class="hint">Up to 30 days ahead · closed Mondays</div>
            </div>
            <div class="field"><label><?= t('time') ?> *</label>
                <select name="time" id="resTime" class="input" required>
                    <?php foreach ($slots as $s): ?><option value="<?= $s ?>" <?= old('time') === $s ? 'selected' : ($s === '19:00' && ! old('time') ? 'selected' : '') ?>><?= date('g:i A', strtotime($s)) ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="field"><label><?= t('guests') ?> *</label>
                <select name="guests" id="resGuests" class="input" required>
                    <?php for ($g = 1; $g <= 12; $g++): ?><option value="<?= $g ?>" <?= (int) (old('guests') ?: 2) === $g ? 'selected' : '' ?>><?= $g ?></option><?php endfor ?>
                </select>
            </div>
        </div>
        <div class="field"><label><?= t('preferred_table') ?></label>
            <select name="table_id" id="resTable" class="input"><option value=""><?= t('any_table') ?></option></select>
            <div class="hint" id="tableHint">Only tables free at that time are listed.</div>
        </div>
        <div class="field"><label><?= t('special_requests') ?></label><textarea name="notes" class="input" maxlength="1000" placeholder="Birthday, high chair, allergies…"><?= esc(old('notes')) ?></textarea></div>

        <details class="card-flat mb-2" id="preDetails">
            <summary style="cursor:pointer;font-weight:600"><?= t('preorder') ?></summary>
            <p class="small muted mt-1"><?= t('preorder_d') ?></p>
            <?php foreach ($items as $it): ?>
                <div class="flex between" style="padding:.45rem 0;border-bottom:1px solid var(--line);flex-wrap:nowrap">
                    <div><b><?= esc($it['name']) ?></b> <span class="muted small">· <?= esc($it['category']) ?></span><br><span class="small"><?= peso0($it['price']) ?></span></div>
                    <input type="number" min="0" max="30" value="0" name="pre[<?= (int) $it['id'] ?>]" data-price="<?= (float) $it['price'] ?>" class="input pre-qty" style="width:80px" inputmode="numeric">
                </div>
            <?php endforeach ?>
        </details>

        <div id="paySection" class="hidden">
            <div class="alert alert-info"><?= t('amount_due_now') ?>: <b id="dueAmount">₱0</b> <span class="small" id="dueBreakdown"></span></div>
            <div class="pay-options mb-2">
                <?php foreach ($methods as $i => $m): ?>
                    <label class="pay-opt"><input type="radio" name="payment_method" value="<?= $m ?>" <?= $i === 0 ? 'checked' : '' ?>><b><?= $m === 'gcash' ? '📱 GCash' : '🏦 Online Banking (InstaPay / PESONet)' ?></b></label>
                <?php endforeach ?>
            </div>
            <div class="pay-box" data-box="gcash">
                <?php if ($gcash['qr']): ?><img class="qr" src="<?= esc($gcash['qr']) ?>" alt="GCash QR"><?php endif ?>
                <dl class="kv"><dt><?= t('account_name') ?></dt><dd><?= esc($gcash['name']) ?></dd><dt><?= t('gcash_number') ?></dt><dd><?= esc($gcash['number']) ?></dd></dl>
            </div>
            <div class="pay-box hidden" data-box="online_banking">
                <select name="bank_account_id" class="input">
                    <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['bank_name']) ?> — <?= esc($b['account_name']) ?> · <?= esc($b['account_number']) ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="grid grid-2 mt-2">
                <div class="field"><label><?= t('reference_no') ?> *</label><input name="reference_no" class="input pay-req" maxlength="60"></div>
                <div class="field"><label><?= t('proof') ?> *</label><input type="file" name="proof" accept="image/*" class="input pay-req"></div>
            </div>
        </div>
        <p id="noPay" class="small muted">✓ <?= t('pay_on_arrival') ?></p>

        <button class="btn btn-lg btn-block mt-2"><?= t('request_reservation') ?></button>
    </form>

    <aside>
        <div class="card">
            <h3><?= t('good_to_know') ?></h3>
            <ul style="padding-left:1.1rem;line-height:1.9" class="muted">
                <li><?= t('gtk_1') ?></li>
                <li><?= t('gtk_2') ?></li>
                <li><?= t('gtk_3') ?></li>
                <li><?= t('gtk_4') ?></li>
                <li><?= t('deposit_note', [$depositMin, peso0($deposit)]) ?></li>
            </ul>
            <?= view('partials/pay_icons') ?>
        </div>
        <div class="img-card mt-3" style="aspect-ratio:4/3"><img src="<?= base_url('uploads/menu/kare-kare.jpg') ?>" alt="Kare-kare" loading="lazy"></div>
    </aside>
</section>
<style>@media (max-width: 860px) { #resGrid { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const deposit = <?= json_encode($deposit) ?>, depositMin = <?= (int) $depositMin ?>, oldTable = <?= json_encode(old('table_id')) ?>;
  const $ = (s) => document.querySelector(s);
  const peso = (n) => '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 });

  async function loadTables() {
    const sel = $('#resTable'), cur = sel.value || oldTable;
    const q = new URLSearchParams({ date: $('#resDate').value, time: $('#resTime').value, guests: $('#resGuests').value });
    try {
      const r = await (await DF.get('reserve/tables?' + q)).json();
      sel.innerHTML = '<option value=""><?= esc(t('any_table'), 'js') ?></option>' + r.tables.map((t) =>
        '<option value="' + t.id + '"' + (String(t.id) === String(cur) ? ' selected' : '') + '>' + t.code + ' · ' + t.zone + ' · ' + t.seats + ' seats</option>').join('');
      $('#tableHint').textContent = r.tables.length ? 'Only tables free at that time are listed.' : 'No specific table free then — choose “Any table” and we’ll seat you.';
    } catch (e) {}
  }
  function updateDue() {
    let pre = 0;
    document.querySelectorAll('.pre-qty').forEach((i) => (pre += (parseInt(i.value, 10) || 0) * Number(i.dataset.price)));
    const dep = parseInt($('#resGuests').value, 10) >= depositMin ? deposit : 0;
    const due = pre + dep;
    $('#paySection').classList.toggle('hidden', due <= 0);
    $('#noPay').classList.toggle('hidden', due > 0);
    document.querySelectorAll('.pay-req').forEach((i) => (i.required = due > 0));
    $('#dueAmount').textContent = peso(due);
    $('#dueBreakdown').textContent = [pre ? 'pre-order ' + peso(pre) : '', dep ? 'deposit ' + peso(dep) : ''].filter(Boolean).join(' + ');
  }
  function syncBox() {
    const m = (document.querySelector('input[name=payment_method]:checked') || {}).value;
    document.querySelectorAll('#paySection [data-box]').forEach((b) => b.classList.toggle('hidden', b.dataset.box !== m));
  }
  ['#resDate', '#resTime', '#resGuests'].forEach((s) => $(s).addEventListener('change', () => { loadTables(); updateDue(); }));
  document.querySelectorAll('.pre-qty').forEach((i) => i.addEventListener('input', updateDue));
  document.querySelectorAll('input[name=payment_method]').forEach((i) => i.addEventListener('change', syncBox));
  loadTables(); updateDue(); syncBox();
})();
</script>
<?= $this->endSection() ?>
