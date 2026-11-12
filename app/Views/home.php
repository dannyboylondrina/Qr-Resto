<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="hero">
    <div class="container">
        <span class="kicker">Legazpi Village · Makati</span>
        <h1><?= t('hero_title') ?></h1>
        <p class="lead"><?= t('hero_sub') ?></p>
        <div class="flex mt-3">
            <a href="<?= site_url('menu') ?>" class="btn btn-lg"><?= t('view_menu') ?> →</a>
            <a href="<?= site_url('reserve') ?>" class="btn btn-lg btn-outline"><?= t('reserve_table') ?></a>
        </div>
    </div>
</section>

<section>
    <div class="container photo-row">
        <div class="img-card"><img src="<?= base_url('uploads/menu/hero-grill.jpg') ?>" alt="Whole roast pork glistening from the fire" loading="eager"></div>
        <div class="img-card"><img src="<?= base_url('uploads/menu/hero-soup.jpg') ?>" alt="A steaming bowl of noodle soup" loading="lazy"></div>
        <div class="img-card"><img src="<?= base_url('uploads/menu/hero-bread.jpg') ?>" alt="Fresh pandesal bread rolls" loading="lazy"></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <span class="kicker">How it works</span>
        <h2 style="max-width:16ch"><?= t('steps_title') ?></h2>
        <div class="steps mt-3">
            <div class="step"><div class="num">01</div><h3><?= t('step1_t') ?></h3><p class="muted"><?= t('step1_d') ?></p></div>
            <div class="step"><div class="num">02</div><h3><?= t('step2_t') ?></h3><p class="muted"><?= t('step2_d') ?></p></div>
            <div class="step"><div class="num">03</div><h3><?= t('step3_t') ?></h3><p class="muted"><?= t('step3_d') ?></p></div>
        </div>
        <div class="flex mt-2">
            <a href="<?= site_url('menu') ?>" class="btn"><?= t('start_order') ?></a>
            <a href="<?= site_url('tables') ?>" class="btn btn-ghost"><?= t('see_tables') ?></a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container room">
        <div class="img-card"><img src="<?= base_url('uploads/menu/inasal.jpg') ?>" alt="Chicken inasal grilled over coals" loading="lazy"></div>
        <div>
            <span class="kicker"><?= t('room_kicker') ?></span>
            <h2><?= t('room_title') ?></h2>
            <p class="lead"><?= t('room_text') ?></p>
            <div class="stats">
                <div class="stat"><b><?= (int) $seats ?></b><span><?= t('seats') ?></span></div>
                <div class="stat"><b>22</b><span><?= t('avg_ticket') ?></span></div>
            </div>
            <a href="<?= site_url('reserve') ?>" class="btn btn-dark"><?= t('reserve_table') ?></a>
        </div>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <span class="kicker">Reviews</span>
        <h2><?= t('reviews_title') ?></h2>
        <div class="grid grid-3 mt-3">
            <?php foreach ($reviews as $r): ?>
                <article class="card review-card">
                    <div class="stars" aria-label="<?= (int) $r['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $r['rating']) ?><span style="color:var(--line)"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span></div>
                    <blockquote>“<?= esc($r['comment']) ?>”</blockquote>
                    <div class="small muted">— <?= esc($r['guest_name'] ?: 'Guest') ?></div>
                </article>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="dark-band grid grid-2" style="gap:3rem">
            <div>
                <span class="kicker"><?= t('visit_kicker') ?></span>
                <h2><?= t('visit_title') ?></h2>
                <ul class="muted" style="padding-left:1.1rem;line-height:1.9">
                    <li><?= t('visit_1') ?></li>
                    <li><?= t('visit_2') ?></li>
                    <li><?= t('visit_3') ?></li>
                </ul>
                <p class="mt-2">24 Antonio Drive, Legazpi Village, Makati City<br><a href="tel:+63285550101" style="color:#f2b79c">+63 2 8555 0101</a></p>
            </div>
            <div>
                <h3 style="color:#fff"><?= t('opening_hours') ?></h3>
                <table class="hours-table" style="color:#e7e5e4">
                    <tr><td>Tue – Thu</td><td>11:00 – 21:30</td></tr>
                    <tr><td>Fri – Sat</td><td>11:00 – 22:30</td></tr>
                    <tr><td>Sun</td><td>11:00 – 20:00</td></tr>
                    <tr><td>Mon</td><td><?= t('closed') ?></td></tr>
                </table>
                <div class="flex mt-3">
                    <a href="<?= site_url('reserve') ?>" class="btn"><?= t('book_table') ?></a>
                    <a href="<?= site_url('menu?type=take_away') ?>" class="btn btn-outline" style="color:#fff;border-color:#fff"><?= t('order_takeaway') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section style="padding-bottom:1rem">
    <div class="container flex between">
        <span class="kicker mb-0"><?= t('we_accept') ?></span>
        <?= view('partials/pay_icons') ?>
    </div>
</section>

<?= $this->endSection() ?>
