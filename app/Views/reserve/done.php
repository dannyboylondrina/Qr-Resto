<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $badge = ['pending' => 'amber', 'confirmed' => 'green', 'seated' => 'green', 'cancelled' => 'red', 'no_show' => 'red'][$r['status']] ?? ''; ?>
<section class="page-head">
    <div class="container" style="max-width:720px">
        <span class="kicker"><?= t('reserve_kicker') ?></span>
        <h1><?= $r['status'] === 'confirmed' ? 'You’re booked.' : 'Request received.' ?></h1>
        <p class="lead">Reservation code <span class="order-code"><?= esc($r['code']) ?></span></p>
    </div>
</section>
<section class="container" style="max-width:720px">
    <div class="card">
        <div class="flex between mb-2"><h3 class="mb-0"><?= esc($r['name']) ?></h3><span class="badge <?= $badge ?>"><?= esc(ucfirst(str_replace('_', '-', $r['status']))) ?></span></div>
        <dl class="kv">
            <dt><?= t('date') ?></dt><dd><?= date('l, F j, Y', strtotime($r['res_date'])) ?></dd>
            <dt><?= t('time') ?></dt><dd><?= date('g:i A', strtotime($r['res_time'])) ?></dd>
            <dt><?= t('guests') ?></dt><dd><?= (int) $r['guests'] ?></dd>
            <dt><?= t('table') ?></dt><dd><?= $r['table'] ? esc($r['table']['code'] . ' · ' . $r['table']['zone']) : 'Assigned on arrival' ?></dd>
            <?php if ($r['amount_due'] > 0): ?>
                <dt>Paid ahead</dt><dd><?= peso($r['amount_due']) ?> via <?= esc(payment_method_label($r['payment_method'])) ?> · <?= esc(payment_status_label($r['payment_status'])) ?></dd>
            <?php endif ?>
        </dl>
        <?php if ($r['items']): ?>
            <h4 class="mt-2 mb-1">Pre-order</h4>
            <?php foreach ($r['items'] as $it): ?><div class="sum-row"><span><?= (int) $it['quantity'] ?>× <?= esc($it['name']) ?></span><span><?= peso($it['line_total']) ?></span></div><?php endforeach ?>
        <?php endif ?>
        <div class="alert alert-info mt-2">
            <?php if ($r['payment_status'] === 'for_verification'): ?>
                We’re verifying your payment. Your reservation is confirmed as soon as it’s approved — we’ll text <?= esc($r['mobile']) ?>.
            <?php elseif ($r['status'] === 'pending'): ?>
                Our team will confirm by SMS<?= $r['email'] ? ' and email' : '' ?> shortly. Nothing to pay now — cash on arrival is fine.
            <?php else: ?>
                We hold tables for 15 minutes past your booking. See you soon!
            <?php endif ?>
        </div>
        <div class="flex"><a href="<?= site_url('menu') ?>" class="btn btn-ghost"><?= t('view_menu') ?></a><a href="<?= site_url('/') ?>" class="btn btn-ghost">Home</a></div>
    </div>
</section>
<?= $this->endSection() ?>
