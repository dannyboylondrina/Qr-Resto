<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<section class="page-head">
    <div class="container">
        <span class="kicker"><?= t('floor_kicker') ?></span>
        <h1><?= t('floor_title') ?></h1>
        <div class="flex between">
            <div class="legend">
                <span style="color:var(--green)"><span class="dot"></span> <?= t('available') ?></span>
                <span style="color:var(--red)"><span class="dot"></span> <?= t('occupied') ?></span>
                <span style="color:var(--amber)"><span class="dot"></span> <?= t('reserved') ?></span>
            </div>
            <div class="flex">
                <a href="<?= site_url('menu') ?>" class="btn"><?= t('start_order') ?></a>
                <a href="<?= site_url('reserve') ?>" class="btn btn-ghost"><?= t('reserve_instead') ?></a>
            </div>
        </div>
        <p class="muted small mt-2"><?= t('tap_available') ?></p>
    </div>
</section>
<section class="container">
    <div class="card" data-live="<?= site_url('tables/live') ?>" data-interval="5000">
        <?= view('tables/_grid', ['tables' => $tables]) ?>
    </div>
</section>
<?= $this->endSection() ?>
