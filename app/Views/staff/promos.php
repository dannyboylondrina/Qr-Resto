<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="card mb-3">
    <h3>New promo code</h3>
    <form method="post" data-ajax data-reload action="<?= site_url('staff/promos/save') ?>" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));align-items:end">
        <div class="field mb-0"><label>Code</label><input name="code" class="input" required placeholder="MERIENDA15" style="text-transform:uppercase"></div>
        <div class="field mb-0"><label>Type</label><select name="type" class="input"><option value="percent">Percent %</option><option value="fixed">Fixed ₱</option></select></div>
        <div class="field mb-0"><label>Value</label><input name="value" type="number" step="0.01" min="0.01" class="input" required></div>
        <div class="field mb-0"><label>Minimum order ₱</label><input name="min_order" type="number" step="0.01" min="0" value="0" class="input"></div>
        <div class="field mb-0"><label>Expires</label><input name="expires_at" type="date" class="input"></div>
        <div class="field mb-0"><label>Usage limit</label><input name="usage_limit" type="number" min="1" class="input" placeholder="Unlimited"></div>
        <button class="btn">Create</button>
    </form>
</div>
<div class="table-wrap">
    <table class="data">
        <tr><th>Code</th><th>Discount</th><th class="num">Min. order</th><th>Expires</th><th class="num">Used</th><th>Status</th><th></th></tr>
        <?php foreach ($promos as $p): $expired = $p['expires_at'] && $p['expires_at'] < date('Y-m-d'); ?>
            <tr>
                <td><span class="order-code" style="font-size:.85rem;padding:.1rem .45rem"><?= esc($p['code']) ?></span></td>
                <td><?= $p['type'] === 'percent' ? (float) $p['value'] . '%' : peso($p['value']) ?> off</td>
                <td class="num"><?= peso($p['min_order']) ?></td>
                <td><?= $p['expires_at'] ? date('M j, Y', strtotime($p['expires_at'])) : '—' ?> <?= $expired ? '<span class="badge red">Expired</span>' : '' ?></td>
                <td class="num"><?= (int) $p['used_count'] ?><?= $p['usage_limit'] ? ' / ' . (int) $p['usage_limit'] : '' ?></td>
                <td><button class="btn btn-sm <?= $p['is_active'] ? 'btn-green' : 'btn-ghost' ?>" data-post="staff/promos/<?= $p['id'] ?>/toggle" data-reload><?= $p['is_active'] ? 'Active' : 'Disabled' ?></button></td>
                <td><button class="btn btn-ghost btn-sm" data-post="staff/promos/<?= $p['id'] ?>/delete" data-confirm="Delete <?= esc($p['code'], 'attr') ?>?" data-reload>🗑</button></td>
            </tr>
        <?php endforeach ?>
        <?php if (! $promos): ?><tr><td colspan="7" class="muted">No promo codes yet.</td></tr><?php endif ?>
    </table>
</div>
<?= $this->endSection() ?>
