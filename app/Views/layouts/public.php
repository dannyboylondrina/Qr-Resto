<?php
$uri    = service('request')->getUri()->getPath();
$active = static fn (string $p) => str_contains('/' . trim($uri, '/') . '/', '/' . $p . '/') ? 'active' : '';
$locale = service('request')->getLocale();
?><!DOCTYPE html>
<html lang="<?= $locale === 'fil' ? 'fil' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= esc($title ?? 'Salo — Modern Filipino Kitchen') ?></title>
    <meta name="description" content="Salo is a modern Filipino kitchen in Legazpi Village, Makati. Order from your table, reserve, and track every course.">
    <meta name="theme-color" content="#f7f2ea">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-name" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <link rel="icon" href="<?= base_url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="<?= esc($bodyClass ?? '') ?>">
<header class="site-header">
    <div class="container nav">
        <a href="<?= site_url('/') ?>" class="logo" aria-label="Salo home"><b>Salo</b><small><?= t('tagline') ?></small></a>
        <nav class="nav-links" aria-label="Main">
            <a href="<?= site_url('menu') ?>" class="<?= $active('menu') ?>"><?= t('nav_menu') ?></a>
            <a href="<?= site_url('reserve') ?>" class="<?= $active('reserve') ?>"><?= t('nav_reserve') ?></a>
            <a href="<?= site_url('tables') ?>" class="<?= $active('tables') ?>"><?= t('nav_floor') ?></a>
            <a href="<?= site_url('track') ?>" class="<?= $active('track') ?>"><?= t('nav_track') ?></a>
            <a href="<?= site_url(current_user() ? 'staff' : 'login') ?>"><?= t('nav_staff') ?></a>
        </nav>
        <div class="nav-right">
            <div class="lang" role="group" aria-label="Language">
                <a href="<?= site_url('lang/en') ?>" class="<?= $locale !== 'fil' ? 'on' : '' ?>">EN</a>
                <a href="<?= site_url('lang/fil') ?>" class="<?= $locale === 'fil' ? 'on' : '' ?>">FIL</a>
            </div>
            <a href="<?= site_url('menu') ?>" class="btn btn-sm"><?= t('order_now') ?></a>
            <button class="burger" aria-label="Open menu"><span></span><span></span><span></span></button>
        </div>
    </div>
    <div class="container mobile-nav">
        <a href="<?= site_url('menu') ?>"><?= t('nav_menu') ?></a>
        <a href="<?= site_url('reserve') ?>"><?= t('nav_reserve') ?></a>
        <a href="<?= site_url('tables') ?>"><?= t('nav_floor') ?></a>
        <a href="<?= site_url('track') ?>"><?= t('nav_track') ?></a>
        <a href="<?= site_url(current_user() ? 'staff' : 'login') ?>"><?= t('nav_staff') ?></a>
        <a href="<?= site_url('menu') ?>" class="btn"><?= t('order_now') ?></a>
    </div>
</header>

<?= $this->renderSection('banner') ?>

<main>
    <?= view('partials/flash') ?>
    <?= $this->renderSection('content') ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= site_url('/') ?>" class="logo mb-2"><b>Salo</b><small style="color:#a8a29e"><?= t('tagline') ?></small></a>
                <p class="mt-2" style="max-width:34ch"><?= t('footer_blurb') ?></p>
                <p class="small mb-0">24 Antonio Drive, Legazpi Village,<br>Makati City · <a href="tel:+63285550101" style="display:inline">+63 2 8555 0101</a></p>
                <p class="small">Tue–Thu 11:00–21:30 · Fri–Sat 11:00–22:30<br>Sun 11:00–20:00 · Mon <?= t('closed') ?></p>
            </div>
            <div>
                <h4><?= t('guests') ?></h4>
                <a href="<?= site_url('menu') ?>"><?= t('f_menu') ?></a>
                <a href="<?= site_url('reserve') ?>"><?= t('f_reserve') ?></a>
                <a href="<?= site_url('tables') ?>"><?= t('f_floor') ?></a>
                <a href="<?= site_url('track') ?>"><?= t('f_track') ?></a>
            </div>
            <div>
                <h4><?= t('staff') ?></h4>
                <a href="<?= site_url('login') ?>"><?= t('f_signin') ?></a>
                <a href="<?= site_url('staff') ?>"><?= t('f_dashboard') ?></a>
                <a href="<?= site_url('staff/orders') ?>"><?= t('f_board') ?></a>
                <a href="<?= site_url('kitchen') ?>"><?= t('f_kitchen') ?></a>
            </div>
            <div>
                <h4><?= t('we_accept') ?></h4>
                <?= view('partials/pay_icons', ['dark' => true]) ?>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Salo — Modern Filipino Kitchen · DineFlow</span>
            <span><?= t('we_accept') ?></span>
        </div>
    </div>
</footer>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
