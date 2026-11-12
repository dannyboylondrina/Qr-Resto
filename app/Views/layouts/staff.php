<?php
$u      = current_user();
$active = $active ?? '';
$isKitchenOnly = $u['role'] === 'kitchen';
$link = static function (string $key, string $url, string $label, string $count = '') use ($active) {
    return '<a href="' . site_url($url) . '" class="' . ($active === $key ? 'active' : '') . '"><span>' . $label . '</span>'
        . ($count ? '<span class="count hidden" data-count="' . $count . '"></span>' : '') . '</a>';
};
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Staff') ?> · DineFlow</title>
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-name" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= base_url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="staff-body <?= esc($bodyClass ?? '') ?>" data-role="<?= esc($u['role']) ?>">
<div class="staff-shell">
    <aside class="sidebar" id="sidebar">
        <a href="<?= site_url($isKitchenOnly ? 'kitchen' : 'staff') ?>" class="logo"><b>Salo</b><small style="color:#a8a29e">DineFlow · <?= esc(ucfirst($u['role'])) ?></small></a>
        <nav>
            <?php if (! $isKitchenOnly): ?>
                <div class="grp">Service</div>
                <?= $link('dashboard', 'staff', '📊 Dashboard') ?>
                <?= $link('orders', 'staff/orders', '🧾 Orders', 'orders') ?>
                <?= $link('service', 'staff/service', '🙋 Service requests', 'service') ?>
                <?= $link('kitchen', 'kitchen', '🔥 Kitchen display', 'kitchen') ?>
                <?= $link('floor', 'staff/floor', '🪑 Floor', 'bills') ?>
                <?= $link('reservations', 'staff/reservations', '📅 Reservations', 'reservations') ?>
            <?php else: ?>
                <div class="grp">Kitchen</div>
                <?= $link('kitchen', 'kitchen', '🔥 Kitchen display', 'kitchen') ?>
            <?php endif ?>
            <?php if ($u['role'] === 'admin'): ?>
                <div class="grp">Admin</div>
                <?= $link('menu', 'staff/menu', '🍲 Manage menu') ?>
                <?= $link('qr', 'staff/qr-codes', '🔳 Tables & QR codes') ?>
                <?= $link('promos', 'staff/promos', '🏷️ Promos') ?>
                <?= $link('reviews', 'staff/reviews', '⭐ Reviews', 'reviews') ?>
                <?= $link('reports', 'staff/reports', '📈 Reports') ?>
                <?= $link('payments', 'staff/payment-settings', '💳 Payment settings') ?>
                <?= $link('users', 'staff/users', '👥 Staff accounts') ?>
                <?= $link('activity', 'staff/activity', '🗂️ Activity log') ?>
            <?php endif ?>
            <div class="grp">You</div>
            <?= $link('account', 'staff/account', '🔐 My account') ?>
            <a href="<?= site_url('/') ?>" target="_blank" rel="noopener"><span>↗ View website</span></a>
        </nav>
        <div class="me">
            <div style="color:#fff;font-weight:600"><?= esc($u['name']) ?></div>
            <div class="tiny" style="color:#a8a29e"><?= esc($u['email']) ?></div>
            <form method="post" action="<?= site_url('logout') ?>" class="mt-1"><?= csrf_field() ?><button>Sign out</button></form>
        </div>
    </aside>

    <div class="staff-main">
        <div class="staff-top">
            <div class="flex">
                <button class="btn btn-ghost btn-sm staff-burger" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">☰</button>
                <h1><?= esc($title ?? '') ?></h1>
            </div>
            <div class="flex">
                <?= $this->renderSection('actions') ?>
                <button type="button" class="btn btn-ghost btn-sm" id="soundBtn" title="Sound alerts for new orders, payments and service requests">🔕 Enable sound</button>
            </div>
        </div>
        <?= view('partials/flash', ['inStaff' => true]) ?>
        <?= $this->renderSection('content') ?>
    </div>
</div>
<div id="toasts"></div>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/staff.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
