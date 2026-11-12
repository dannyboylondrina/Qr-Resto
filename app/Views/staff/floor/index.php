<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="legend mb-1">
    <span style="color:var(--green)"><span class="dot"></span> Available</span>
    <span style="color:var(--red)"><span class="dot"></span> Occupied</span>
    <span style="color:var(--amber)"><span class="dot"></span> Reserved</span>
    <span class="muted">Tables turn Occupied automatically on the first order; “Close table” frees them after payment.</span>
</div>
<div data-live="<?= site_url('staff/floor/board') ?>" data-interval="6000">
    <?= view('staff/floor/_board', ['tables' => $tables]) ?>
</div>

<dialog class="modal" id="settleDialog">
    <form method="post" data-ajax id="settleForm">
        <div class="modal-body">
            <h3>Settle bill · Table <span id="settleTable"></span></h3>
            <p>Collect <b class="serif" style="font-size:1.6rem" id="settleAmt"></b></p>
            <p class="small muted">Marks every unpaid order of this visit (tab and awaiting) as paid. Payments already submitted for verification are approved on the Orders page.</p>
            <div class="pay-options mb-2">
                <?php foreach (['cash' => '💵 Cash', 'gcash' => '📱 GCash', 'online_banking' => '🏦 Online Banking'] as $m => $l): ?>
                    <label class="pay-opt"><input type="radio" name="method" value="<?= $m ?>" <?= $m === 'cash' ? 'checked' : '' ?>><b><?= $l ?></b></label>
                <?php endforeach ?>
            </div>
            <div class="field"><label>Reference no. <span class="muted small">(GCash / bank)</span></label><input name="reference" class="input" maxlength="60"></div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button>
            <button class="btn btn-green">Mark bill as paid</button>
        </div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function openSettle(visitId, code, amount) {
  const f = document.getElementById('settleForm');
  f.reset();
  f.action = DF.url('staff/floor/visit/' + visitId + '/settle');
  document.getElementById('settleTable').textContent = code;
  document.getElementById('settleAmt').textContent = '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  document.getElementById('settleDialog').showModal();
}
</script>
<?= $this->endSection() ?>
