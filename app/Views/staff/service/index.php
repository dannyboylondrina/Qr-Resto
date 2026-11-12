<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div data-live="<?= site_url('staff/service/list') ?>" data-interval="4000">
    <?= view('staff/service/_list', ['data' => $data]) ?>
</div>
<?= $this->endSection() ?>
