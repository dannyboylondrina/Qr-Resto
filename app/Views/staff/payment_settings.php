<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="grid grid-2" style="align-items:start">
    <form class="card" method="post" action="<?= site_url('staff/payment-settings') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <h3>Payment methods</h3>
        <label class="check mb-1"><input type="checkbox" name="cash_enabled" value="1" <?= setting('cash_enabled') === '1' ? 'checked' : '' ?>> 💵 Cash (pay at the counter)</label>
        <label class="check mb-1"><input type="checkbox" name="gcash_enabled" value="1" <?= setting('gcash_enabled') === '1' ? 'checked' : '' ?>> 📱 GCash</label>
        <label class="check mb-2"><input type="checkbox" name="bank_enabled" value="1" <?= setting('bank_enabled') === '1' ? 'checked' : '' ?>> 🏦 Online Banking (InstaPay / PESONet)</label>

        <h3 class="mt-3">GCash</h3>
        <div class="field"><label>Account name</label><input name="gcash_name" class="input" value="<?= esc(setting('gcash_name')) ?>"></div>
        <div class="field"><label>GCash number</label><input name="gcash_number" class="input" value="<?= esc(setting('gcash_number')) ?>"></div>
        <div class="field"><label>GCash QR code image</label>
            <?php if (setting('gcash_qr')): ?>
                <img src="<?= esc(img_url(setting('gcash_qr'))) ?>" alt="GCash QR" style="width:180px;border-radius:12px;border:1px solid var(--line)" class="mb-1">
                <label class="check small"><input type="checkbox" name="remove_qr" value="1"> Remove current QR</label>
            <?php else: ?><p class="hint">No QR uploaded yet — guests will see the name and number only.</p><?php endif ?>
            <input type="file" name="gcash_qr" accept="image/*" class="input mt-1">
        </div>

        <h3 class="mt-3">Large-party deposit</h3>
        <div class="grid grid-2">
            <div class="field"><label>Deposit amount (₱)</label><input name="deposit_amount" type="number" step="0.01" min="0" class="input" value="<?= esc(setting('deposit_amount')) ?>"></div>
            <div class="field"><label>For parties of at least</label><input name="deposit_min_guests" type="number" min="2" max="12" class="input" value="<?= esc(setting('deposit_min_guests', 8)) ?>"></div>
        </div>
        <p class="hint">Paid via GCash / Online Banking when booking and deducted from the final bill when the party is seated.</p>
        <button class="btn mt-2">Save settings</button>
    </form>

    <div class="card">
        <h3>Bank accounts</h3>
        <p class="muted small">Shown to guests who choose Online Banking.</p>
        <?php foreach ($banks as $b): ?>
            <form method="post" data-ajax data-reload action="<?= site_url('staff/banks/save') ?>" class="card-flat mb-2">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <div class="grid grid-2">
                    <div class="field mb-1"><label class="small">Bank</label><input name="bank_name" class="input" value="<?= esc($b['bank_name']) ?>" required></div>
                    <div class="field mb-1"><label class="small">Account number</label><input name="account_number" class="input" value="<?= esc($b['account_number']) ?>" required></div>
                </div>
                <div class="field mb-1"><label class="small">Account name</label><input name="account_name" class="input" value="<?= esc($b['account_name']) ?>" required></div>
                <div class="flex between">
                    <label class="check small"><input type="checkbox" name="is_active" value="1" <?= $b['is_active'] ? 'checked' : '' ?>> Shown to guests</label>
                    <input type="hidden" name="sort_order" value="<?= (int) $b['sort_order'] ?>">
                    <div class="flex"><button class="btn btn-sm">Save</button>
                        <button type="button" class="btn btn-ghost btn-sm" data-post="staff/banks/<?= $b['id'] ?>/delete" data-confirm="Remove <?= esc($b['bank_name'], 'attr') ?>?" data-reload>🗑</button></div>
                </div>
            </form>
        <?php endforeach ?>
        <form method="post" data-ajax data-reload action="<?= site_url('staff/banks/save') ?>" class="card-flat" style="border-style:dashed">
            <b class="small">Add bank account</b>
            <div class="grid grid-2 mt-1">
                <input name="bank_name" class="input" placeholder="Bank (e.g. Security Bank)" required>
                <input name="account_number" class="input" placeholder="Account number" required>
            </div>
            <input name="account_name" class="input mt-1" placeholder="Account name" required>
            <input type="hidden" name="is_active" value="1"><input type="hidden" name="sort_order" value="<?= count($banks) + 1 ?>">
            <button class="btn btn-sm mt-1">+ Add</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
