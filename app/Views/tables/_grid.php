<?php
$zones = [];
foreach ($tables as $t) {
    $zones[$t['zone']][] = $t;
}
?>
<?php foreach ($zones as $zone => $list): ?>
    <div class="zone">
        <h3><?= esc($zone) ?></h3>
        <div class="tables-grid">
            <?php foreach ($list as $t): $s = $t['effective']; ?>
                <?php if ($s === 'available'): ?>
                    <a class="tbl available <?= $t['seats'] >= 8 ? 'wide' : '' ?>" href="<?= site_url('menu?table=' . urlencode($t['code'])) ?>" title="Start an order at <?= esc($t['code']) ?>">
                <?php else: ?>
                    <div class="tbl <?= esc($s) ?> <?= $t['seats'] >= 8 ? 'wide' : '' ?>">
                <?php endif ?>
                    <b><?= esc($t['code']) ?></b>
                    <span class="seats"><?= (int) $t['seats'] ?> <?= t('seats') ?></span>
                    <span class="state"><span class="dot"></span> <?= t($s) ?></span>
                <?= $s === 'available' ? '</a>' : '</div>' ?>
            <?php endforeach ?>
        </div>
    </div>
<?php endforeach ?>
<p class="tiny muted">Updated <?= date('g:i:s A') ?></p>
