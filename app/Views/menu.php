<?= $this->extend('layouts/public') ?>

<?= $this->section('banner') ?>
<div class="table-banner hidden" id="tableBanner">
    <div class="container">
        <span>🍽️ <b id="bannerText"></b></span>
        <button type="button" class="link" id="notMyTable"><?= t('not_your_table') ?></button>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="page-head">
    <div class="container">
        <span class="kicker"><?= t('menu_kicker') ?></span>
        <h1><?= t('menu_title') ?></h1>
        <?php if ($tableError): ?><div class="alert alert-warn mt-2"><?= esc($tableError) ?></div><?php endif ?>
    </div>
</section>

<section class="container">
    <div class="card" id="tableBox">
        <div class="order-setup">
            <div>
                <h3 class="mb-1"><?= t('your_table') ?></h3>
                <div class="segmented" role="tablist">
                    <button type="button" data-type="dine_in" class="active"><?= t('dine_in') ?></button>
                    <button type="button" data-type="take_away"><?= t('take_away') ?></button>
                </div>
            </div>
            <div>
                <div id="dineFields" class="grid grid-3">
                    <div class="field mb-0">
                        <label for="tableSelect"><?= t('table') ?></label>
                        <select id="tableSelect" class="input">
                            <option value=""><?= t('choose_table') ?>…</option>
                        </select>
                        <div class="hint"><?= t('table_hint') ?> <a href="<?= site_url('tables') ?>" class="link"><?= t('nav_floor') ?></a></div>
                    </div>
                    <div class="field mb-0">
                        <label for="guests"><?= t('num_guests') ?></label>
                        <input id="guests" type="number" class="input" min="1" max="30" value="2" inputmode="numeric">
                    </div>
                    <div class="field mb-0">
                        <label for="dineName"><?= t('your_name') ?> <span class="muted small">(optional)</span></label>
                        <input id="dineName" class="input" maxlength="120" autocomplete="name">
                    </div>
                </div>
                <div id="takeFields" class="grid grid-3 hidden">
                    <div class="field mb-0">
                        <label for="taName"><?= t('your_name') ?> *</label>
                        <input id="taName" class="input" maxlength="120" autocomplete="name">
                    </div>
                    <div class="field mb-0">
                        <label for="taMobile"><?= t('mobile') ?> *</label>
                        <input id="taMobile" class="input" type="tel" inputmode="tel" placeholder="09XX XXX XXXX" autocomplete="tel">
                    </div>
                    <div class="field mb-0">
                        <label>&nbsp;</label>
                        <div class="hint" style="margin-top:.7rem">🛍️ <?= t('collect_counter') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="serviceBar" class="service-bar mt-2 hidden">
            <button type="button" class="btn btn-ghost btn-sm" data-service="call_staff">🙋 <?= t('call_staff') ?></button>
            <button type="button" class="btn btn-ghost btn-sm" data-service="bill">🧾 <?= t('request_bill') ?></button>
            <button type="button" class="btn btn-ghost btn-sm" data-service="water">💧 <?= t('need_water') ?></button>
        </div>
    </div>
</section>

<section class="container mt-4" style="padding-bottom:6rem">
    <div class="cat-filter" id="catFilter">
        <button type="button" class="active" data-cat="all"><?= t('all') ?></button>
        <?php foreach ($categories as $c): ?>
            <button type="button" data-cat="<?= esc($c['slug']) ?>"><?= esc($c['name']) ?></button>
        <?php endforeach ?>
    </div>
    <div class="menu-grid" id="menuGrid">
        <?php foreach ($items as $i): ?>
            <article class="menu-card <?= $i['is_available'] ? '' : 'soldout' ?>" data-cat="<?= esc($i['cat_slug']) ?>" data-id="<?= (int) $i['id'] ?>">
                <div class="ph">
                    <img src="<?= esc(img_url($i['photo'])) ?>" alt="<?= esc($i['name']) ?>" loading="lazy" onerror="this.src='<?= base_url('assets/img/placeholder.svg') ?>'">
                    <?php if (! $i['is_available']): ?><span class="soldout-tag"><?= t('sold_out') ?></span><?php endif ?>
                </div>
                <div class="body">
                    <span class="cat"><?= esc($i['category']) ?> · ~<?= (int) $i['prep_minutes'] ?> <?= t('min') ?></span>
                    <h3><?= esc($i['name']) ?></h3>
                    <p class="desc"><?= esc($i['description']) ?></p>
                    <div class="foot">
                        <span class="price"><?= peso0($i['price']) ?></span>
                        <?php if ($i['is_available']): ?>
                            <button type="button" class="btn btn-sm" data-add="<?= (int) $i['id'] ?>">+ <?= t('add') ?></button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-ghost" disabled><?= t('sold_out') ?></button>
                        <?php endif ?>
                    </div>
                </div>
            </article>
        <?php endforeach ?>
    </div>
</section>

<button type="button" class="btn btn-dark tray-fab hidden" id="trayFab">
    <span>🧺 <?= t('view_tray') ?> · <b id="fabCount">0</b></span><b id="fabTotal">₱0</b>
</button>

<!-- Tray drawer -->
<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer" aria-label="<?= t('tray') ?>">
    <div class="drawer-head">
        <h3 class="mb-0"><?= t('tray') ?></h3>
        <button type="button" class="close-x" data-close-drawer aria-label="Close">×</button>
    </div>
    <div class="drawer-body">
        <div id="trayDest" class="badge rust mb-2"></div>
        <div id="trayLines"></div>
        <div id="trayEmpty" class="muted center" style="padding:2rem 0"><?= t('tray_empty') ?></div>

        <div id="checkout">
            <div class="flex mt-2" style="flex-wrap:nowrap">
                <input id="promoInput" class="input" placeholder="<?= t('promo_code') ?>" style="text-transform:uppercase" maxlength="40">
                <button type="button" class="btn btn-ghost" id="promoBtn"><?= t('apply') ?></button>
            </div>
            <div id="promoMsg" class="hint"></div>

            <div class="mt-2">
                <div class="sum-row"><span><?= t('subtotal') ?></span><span id="sumSub">₱0</span></div>
                <div class="sum-row" id="discRow" style="color:var(--green)"><span><?= t('discount') ?> <span id="discCode"></span></span><span id="sumDisc">−₱0</span></div>
                <div class="sum-row total"><span><?= t('total') ?></span><span id="sumTotal">₱0</span></div>
            </div>

            <h4 class="mt-3 mb-1"><?= t('payment_method') ?></h4>
            <div class="pay-options" id="payOptions">
                <label class="pay-opt hidden" data-method="pay_later"><input type="radio" name="pm" value="pay_later"><span><b><?= t('pay_later') ?></b><span class="small muted"><?= t('pay_later_d') ?></span></span></label>
                <label class="pay-opt" data-method="cash"><input type="radio" name="pm" value="cash"><span><b>💵 <?= t('cash') ?></b><span class="small muted"><?= t('pay_cash_d') ?></span></span></label>
                <label class="pay-opt" data-method="gcash"><input type="radio" name="pm" value="gcash"><span><b>📱 <?= t('gcash') ?></b><span class="small muted"><?= t('pay_gcash_d') ?></span></span></label>
                <label class="pay-opt" data-method="online_banking"><input type="radio" name="pm" value="online_banking"><span><b>🏦 <?= t('online_banking') ?></b><span class="small muted"><?= t('pay_bank_d') ?></span></span></label>
            </div>

            <div class="pay-box hidden" id="boxGcash">
                <div class="center"><span class="small muted"><?= t('send_exact') ?></span><div class="serif" style="font-size:2rem" data-amount>₱0</div></div>
                <img id="gcashQr" class="qr hidden" alt="GCash QR code">
                <dl class="kv mt-1"><dt><?= t('account_name') ?></dt><dd id="gcashName"></dd><dt><?= t('gcash_number') ?></dt><dd id="gcashNumber"></dd></dl>
            </div>
            <div class="pay-box hidden" id="boxBank">
                <div class="center"><span class="small muted"><?= t('send_exact') ?></span><div class="serif" style="font-size:2rem" data-amount>₱0</div></div>
                <select id="bankSelect" class="input mt-1"></select>
                <dl class="kv mt-1"><dt><?= t('account_name') ?></dt><dd id="bankName"></dd><dt><?= t('account_number') ?></dt><dd id="bankNumber"></dd></dl>
            </div>
            <div id="proofFields" class="hidden mt-2">
                <div class="field"><label for="refNo"><?= t('reference_no') ?></label><input id="refNo" class="input" maxlength="60" inputmode="numeric"></div>
                <div class="field"><label for="proofFile"><?= t('proof') ?></label><input id="proofFile" type="file" accept="image/*" class="input"></div>
                <div class="hint"><?= t('or_pay_later') ?></div>
            </div>
            <div class="field mt-2">
                <label for="contactMobile"><?= t('mobile') ?> <span class="muted small">— SMS when it’s ready (optional)</span></label>
                <input id="contactMobile" class="input" type="tel" inputmode="tel" placeholder="09XX XXX XXXX">
            </div>
            <div class="field">
                <label for="contactEmail"><?= t('email_optional') ?></label>
                <input id="contactEmail" class="input" type="email" inputmode="email">
            </div>
        </div>
    </div>
    <div class="drawer-foot">
        <div id="orderError" class="alert alert-error hidden"></div>
        <p class="tiny muted mb-1"><?= t('pay_first_note') ?></p>
        <button type="button" class="btn btn-lg btn-block" id="placeBtn"><?= t('place_order') ?></button>
    </div>
</aside>

<!-- Item options dialog -->
<dialog class="modal" id="itemDialog">
    <form method="dialog" id="itemForm">
        <div class="modal-img"><img id="dlgImg" alt=""></div>
        <div class="modal-body">
            <span class="kicker mb-0" id="dlgCat"></span>
            <h3 id="dlgName" class="mt-1"></h3>
            <p class="muted small" id="dlgDesc"></p>
            <div id="dlgOptions"></div>
            <div class="field">
                <label for="dlgNote"><?= t('note') ?></label>
                <input id="dlgNote" class="input" placeholder="<?= t('note_ph') ?>" maxlength="200">
            </div>
        </div>
        <div class="modal-foot">
            <div class="qty"><button type="button" id="qtyMinus" aria-label="Less">−</button><span id="dlgQty">1</span><button type="button" id="qtyPlus" aria-label="More">+</button></div>
            <div class="flex">
                <button type="button" class="btn btn-ghost" value="cancel" id="dlgCancel">✕</button>
                <button type="button" class="btn" id="dlgAdd"><?= t('add_to_tray') ?> · <span id="dlgPrice"></span></button>
            </div>
        </div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>window.MENU = <?= json_encode($jsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('js/menu.js') ?>"></script>
<?= $this->endSection() ?>
