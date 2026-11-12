<?= $this->extend('layouts/staff') ?>
<?= $this->section('actions') ?>
<a class="btn btn-sm" href="<?= site_url('staff/menu/new') ?>">+ New item</a>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="grid" style="grid-template-columns:minmax(0,3fr) minmax(260px,1fr);align-items:start" id="menuAdm">
    <div class="table-wrap">
        <table class="data">
            <tr><th></th><th>Item</th><th>Category</th><th class="num">Price</th><th class="num">Prep</th><th>Status</th><th></th></tr>
            <?php foreach ($items as $i): ?>
                <tr>
                    <td style="width:64px"><img src="<?= esc(img_url($i['photo'])) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:10px"></td>
                    <td><b><?= esc($i['name']) ?></b><br><span class="tiny muted"><?= esc(character_limiter((string) $i['description'], 70)) ?></span>
                        <?php $opts = json_decode($i['options'] ?: '[]', true) ?: []; if ($opts): ?><br><span class="tiny" style="color:var(--rust)">Options: <?= esc(implode(', ', array_column($opts, 'name'))) ?></span><?php endif ?></td>
                    <td><?= esc($i['category'] ?? '—') ?></td>
                    <td class="num"><?= peso($i['price']) ?></td>
                    <td class="num"><?= (int) $i['prep_minutes'] ?> min</td>
                    <td><button class="btn btn-sm <?= $i['is_available'] ? 'btn-green' : 'btn-red' ?>" data-post="staff/menu/<?= $i['id'] ?>/toggle" data-reload><?= $i['is_available'] ? 'Available' : 'Sold out' ?></button></td>
                    <td class="nowrap">
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('staff/menu/' . $i['id'] . '/edit') ?>">Edit</a>
                        <button class="btn btn-ghost btn-sm" data-post="staff/menu/<?= $i['id'] ?>/delete" data-confirm="Delete <?= esc($i['name'], 'attr') ?>? This can’t be undone." data-reload>🗑</button>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>

    <div class="card">
        <h3>Categories</h3>
        <?php foreach ($cats as $c): ?>
            <form method="post" data-ajax data-reload action="<?= site_url('staff/categories/save') ?>" class="flex mb-1" style="flex-wrap:nowrap">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <input name="sort_order" value="<?= (int) $c['sort_order'] ?>" class="input" style="width:58px;padding:.45rem" title="Order">
                <input name="name" value="<?= esc($c['name']) ?>" class="input" style="padding:.45rem .6rem">
                <button class="btn btn-ghost btn-sm" title="Save">✓</button>
                <button type="button" class="btn btn-ghost btn-sm" data-post="staff/categories/<?= $c['id'] ?>/delete" data-confirm="Delete category <?= esc($c['name'], 'attr') ?>?" data-reload <?= $c['n'] ? 'disabled title="Has items"' : '' ?>>🗑</button>
            </form>
        <?php endforeach ?>
        <form method="post" data-ajax data-reload action="<?= site_url('staff/categories/save') ?>" class="flex mt-2" style="flex-wrap:nowrap">
            <input name="sort_order" value="<?= count($cats) + 1 ?>" class="input" style="width:58px;padding:.45rem">
            <input name="name" placeholder="New category" class="input" style="padding:.45rem .6rem" required>
            <button class="btn btn-sm">Add</button>
        </form>
    </div>
</div>
<style>@media (max-width: 1100px) { #menuAdm { grid-template-columns: 1fr !important; } }</style>
<?= $this->endSection() ?>
