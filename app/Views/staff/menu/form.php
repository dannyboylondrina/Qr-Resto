<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<?php $v = static fn ($k, $d = '') => old($k) ?? ($item[$k] ?? $d); ?>
<form method="post" action="<?= site_url('staff/menu/save') ?>" enctype="multipart/form-data" class="grid" style="grid-template-columns:minmax(0,2fr) minmax(260px,1fr);align-items:start" id="itemForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
    <div class="card">
        <div class="grid grid-2">
            <div class="field"><label>Name *</label><input name="name" class="input" required maxlength="120" value="<?= esc($v('name')) ?>"></div>
            <div class="field"><label>Category *</label>
                <select name="category_id" class="input" required>
                    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int) $v('category_id') === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="field"><label>Description</label><textarea name="description" class="input" maxlength="500"><?= esc($v('description')) ?></textarea></div>
        <div class="grid grid-3">
            <div class="field"><label>Price (₱) *</label><input name="price" type="number" step="0.01" min="0" class="input" required value="<?= esc($v('price')) ?>"></div>
            <div class="field"><label>Prep time (min) *</label><input name="prep_minutes" type="number" min="1" max="239" class="input" required value="<?= esc($v('prep_minutes', 10)) ?>"></div>
            <div class="field"><label>Sort order</label><input name="sort_order" type="number" class="input" value="<?= esc($v('sort_order', 0)) ?>"></div>
        </div>
        <div class="field">
            <label>Options & add-ons</label>
            <textarea name="options" class="input" style="font-family:ui-monospace,monospace;min-height:120px" placeholder="Spice level*: Mild, Medium, Hot&#10;Add-ons+: Extra rice=40, Fried egg=30"><?= esc(old('options') ?? $optText) ?></textarea>
            <div class="hint">One group per line: <code>Group name: choice, choice=extra price</code>.
                Add <b>*</b> after the name for a required single choice, <b>+</b> for “choose any”. Example: <code>Size*: Regular, Large=40</code></div>
        </div>
        <label class="check"><input type="checkbox" name="is_available" value="1" <?= $v('is_available', 1) ? 'checked' : '' ?>> Available (untick = Sold out)</label>
    </div>
    <div class="card">
        <label>Photo</label>
        <img src="<?= esc(img_url($item['photo'] ?? null)) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:12px" class="mb-2">
        <div class="field"><input type="file" name="photo" accept="image/*" class="input"><div class="hint">JPG/PNG/WEBP, max 5 MB.</div></div>
        <div class="field"><label class="small">…or image URL</label><input name="photo_url" class="input" placeholder="https://…" value="<?= esc(old('photo_url') ?? (preg_match('#^https?://#', $item['photo'] ?? '') ? $item['photo'] : '')) ?>"></div>
        <button class="btn btn-block">Save item</button>
        <a href="<?= site_url('staff/menu') ?>" class="btn btn-ghost btn-block mt-1">Cancel</a>
    </div>
</form>
<style>@media (max-width: 900px) { #itemForm { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>
