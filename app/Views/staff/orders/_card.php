<?php
/** One order card on the board. @var array $o @var string $col */
$cls = ['awaiting' => 'c-awaiting', 'verify' => 'c-verify', 'paid' => 'c-paid', 'preparing' => 'c-preparing', 'ready' => 'c-ready', 'served' => 'c-served'][$col];
$canDiscount = in_array($o['payment_status'], ['awaiting', 'rejected', 'tab'], true);
?>
<div class="ocard <?= $cls ?>">
    <div class="flex between" style="align-items:flex-start;flex-wrap:nowrap">
        <div>
            <div class="tno"><?= $o['type'] === 'dine_in' ? esc($o['table_code']) : 'TO-GO' ?></div>
            <div class="tiny muted"><?= $o['type'] === 'dine_in' ? (int) $o['guests'] . ' guests' : esc($o['customer_name']) . ' · ' . esc($o['customer_mobile']) ?></div>
        </div>
        <div class="right">
            <div class="order-code" style="font-size:.85rem;padding:.15rem .5rem"><?= esc($o['code']) ?></div>
            <div class="tiny muted mt-1"><?= time_ago($o['created_at']) ?></div>
        </div>
    </div>
    <ul>
        <?php foreach ($o['items'] as $it): ?>
            <li><b><?= (int) $it['quantity'] ?>×</b> <?= esc($it['name']) ?>
                <?php if ($it['options_text']): ?><br><span class="tiny muted"><?= esc($it['options_text']) ?></span><?php endif ?>
                <?php if ($it['note']): ?><br><span class="tiny" style="color:var(--amber)">📝 <?= esc($it['note']) ?></span><?php endif ?></li>
        <?php endforeach ?>
    </ul>
    <div class="flex between">
        <b><?= peso($o['total']) ?></b>
        <span class="badge <?= ['awaiting' => 'amber', 'rejected' => 'red', 'for_verification' => 'blue', 'paid' => 'green', 'tab' => 'rust'][$o['payment_status']] ?? '' ?>">
            <?= esc(payment_method_label($o['payment_method'])) ?> · <?= esc(payment_status_label($o['payment_status'])) ?>
        </span>
    </div>
    <?php if ($o['discount_amount'] > 0): ?>
        <div class="tiny mt-1" style="color:var(--green)">− <?= peso($o['discount_amount']) ?> · <?= esc($o['discount_type'] === 'promo' ? 'Promo ' . $o['promo_code'] : discount_label($o['discount_type']) . ' · ID ' . $o['discount_id_no']) ?></div>
    <?php endif ?>
    <?php if ($o['payment_status'] === 'rejected'): ?><div class="tiny mt-1" style="color:var(--red)">Rejected: <?= esc($o['rejection_reason']) ?></div><?php endif ?>
    <?php if ($col === 'verify'): ?>
        <div class="tiny mt-1">Ref <b><?= esc($o['reference_no']) ?></b><?= $o['bank_name'] ? ' · ' . esc($o['bank_name']) : '' ?></div>
    <?php endif ?>

    <div class="actions">
        <?php if ($col === 'awaiting'): ?>
            <button class="btn btn-green btn-sm" data-post="staff/orders/<?= $o['id'] ?>/paid" data-confirm="Payment of <?= esc(peso($o['total']), 'attr') ?> received for <?= esc($o['code'], 'attr') ?>?">💵 Mark as paid</button>
        <?php elseif ($col === 'verify'): ?>
            <button class="btn btn-green btn-sm" data-post="staff/orders/<?= $o['id'] ?>/approve" data-confirm="Approve <?= esc(payment_method_label($o['payment_method']), 'attr') ?> payment ref <?= esc($o['reference_no'], 'attr') ?> for <?= esc(peso($o['total']), 'attr') ?>?">✓ Approve</button>
            <button class="btn btn-red btn-sm" data-post="staff/orders/<?= $o['id'] ?>/reject" data-prompt="Reason for rejecting (shown to the guest):" data-field="reason">✕ Reject</button>
        <?php elseif ($col === 'paid'): ?>
            <button class="btn btn-amber btn-sm" data-post="staff/orders/<?= $o['id'] ?>/status/preparing">Preparing</button>
            <button class="btn btn-green btn-sm" data-post="staff/orders/<?= $o['id'] ?>/status/ready">Ready</button>
        <?php elseif ($col === 'preparing'): ?>
            <button class="btn btn-green btn-sm" data-post="staff/orders/<?= $o['id'] ?>/status/ready">Ready</button>
        <?php elseif ($col === 'ready'): ?>
            <button class="btn btn-dark btn-sm" data-post="staff/orders/<?= $o['id'] ?>/status/served">Served</button>
        <?php endif ?>
        <?php if ($o['payment_status'] === 'tab' && $col !== 'served'): ?>
            <span class="tiny muted">Settle on the Floor page</span>
        <?php endif ?>
        <?php if ($canDiscount): ?>
            <button class="btn btn-ghost btn-sm" onclick="openDiscount(<?= (int) $o['id'] ?>, '<?= esc($o['code'], 'js') ?>', <?= (float) $o['subtotal'] ?>)">Senior/PWD</button>
            <?php if (in_array($o['discount_type'], ['senior', 'pwd'], true)): ?>
                <button class="btn btn-ghost btn-sm" data-post="staff/orders/<?= $o['id'] ?>/remove-discount" data-confirm="Remove the discount?">− disc.</button>
            <?php endif ?>
        <?php endif ?>
        <?php if ($o['payment_status'] === 'paid'): ?>
            <a class="btn btn-ghost btn-sm" href="<?= site_url('staff/orders/' . $o['id'] . '/receipt') ?>" target="_blank">🖨 Receipt</a>
        <?php endif ?>
        <?php if ($col !== 'served'): ?>
            <button class="btn btn-ghost btn-sm" data-post="staff/orders/<?= $o['id'] ?>/cancel" data-prompt="Cancel order <?= esc($o['code'], 'attr') ?>? Reason (optional):" data-field="reason" data-required="false" title="Cancel order">🗑</button>
        <?php endif ?>
    </div>
</div>
