<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div data-live="<?= site_url('staff/orders/board') ?>" data-interval="5000">
    <?= view('staff/orders/_board', ['board' => $board]) ?>
</div>

<dialog class="modal" id="discDialog">
    <form method="post" data-ajax id="discForm">
        <div class="modal-body">
            <h3>Senior Citizen / PWD discount</h3>
            <p class="muted small">20% off the subtotal of order <b id="discCode"></b> (<span id="discAmt"></span>). Replaces any promo code. Check the physical ID.</p>
            <div class="segmented mb-2">
                <label><input type="radio" name="type" value="senior" checked><span>Senior Citizen</span></label>
                <label><input type="radio" name="type" value="pwd"><span>PWD</span></label>
            </div>
            <div class="field"><label>ID number</label><input name="id_no" class="input" required maxlength="60"></div>
            <div class="field"><label>Name on ID</label><input name="id_name" class="input" required maxlength="120"></div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button>
            <button class="btn">Apply discount</button>
        </div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function openDiscount(id, code, subtotal) {
  const f = document.getElementById('discForm');
  f.reset();
  f.action = DF.url('staff/orders/' + id + '/discount');
  document.getElementById('discCode').textContent = code;
  document.getElementById('discAmt').textContent = '−₱' + (subtotal * 0.2).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  document.getElementById('discDialog').showModal();
}
</script>
<?= $this->endSection() ?>
