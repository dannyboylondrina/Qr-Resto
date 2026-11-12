<?php
/** Live status block for the guest tracking page. @var array $o */
$steps    = ['received', 'preparing', 'ready', 'served'];
$idx      = array_search($o['status'], $steps, true);
$released = (bool) $o['released_at'];
$ps       = $o['payment_status'];
$psBadge  = ['awaiting' => 'amber', 'for_verification' => 'blue', 'paid' => 'green', 'rejected' => 'red', 'tab' => 'rust'][$ps] ?? '';
?>
<div data-ps="<?= esc($ps) ?>" data-st="<?= esc($o['status']) ?>">
    <div class="flex between">
        <div class="flex">
            <span class="badge dark"><?= $o['type'] === 'dine_in' ? 'Table ' . esc($o['table']['code'] ?? '?') . ' · ' . t('dine_in') : t('take_away') ?></span>
            <span class="badge"><?= esc(payment_method_label($o['payment_method'])) ?></span>
            <span class="badge <?= $psBadge ?>"><?= esc(payment_status_label($ps)) ?></span>
        </div>
        <?php if ($o['status'] === 'cancelled'): ?><span class="badge red"><?= t('st_cancelled') ?></span><?php endif ?>
    </div>

    <?php if ($o['status'] !== 'cancelled'): ?>
    <div class="timeline">
        <?php foreach ($steps as $i => $s): ?>
            <?php $done = $released && $idx !== false && $i <= $idx; ?>
            <div class="tl <?= $done ? 'done' : '' ?> <?= $done && $i === $idx ? 'current' : '' ?>"><?= order_status_label($s) ?></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="center" style="padding:.5rem 0 1rem">
        <?php if ($o['status'] === 'cancelled'): ?>
            <p class="muted">This order was cancelled. Please ask our staff if you have questions.</p>
        <?php elseif (! $released && $ps === 'awaiting' && $o['payment_method'] === 'cash'): ?>
            <div class="alert alert-warn" style="font-size:1.05rem"><?= t('pay_at_counter', [peso($o['total']), $o['code']]) ?></div>
        <?php elseif (! $released && $ps === 'awaiting'): ?>
            <div class="alert alert-warn"><?= t('waiting_payment') ?> — send <?= peso($o['total']) ?> via <?= esc(payment_method_label($o['payment_method'])) ?> and submit the reference below.</div>
        <?php elseif ($ps === 'for_verification'): ?>
            <div class="alert alert-info">⏳ Our cashier is verifying your payment (ref. <?= esc($o['reference_no']) ?>). The kitchen starts as soon as it’s approved.</div>
        <?php elseif ($ps === 'rejected'): ?>
            <div class="alert alert-error"><b>Payment not approved.</b> <?= esc($o['rejection_reason'] ?: '') ?><br>Please check the details and re-submit below.</div>
        <?php elseif (in_array($o['status'], ['received', 'preparing'], true) && $o['estimated_ready_at']): ?>
            <div class="countdown" data-eta="<?= strtotime($o['estimated_ready_at']) * 1000 ?>" data-now="<?= time() * 1000 ?>">–</div>
            <div class="muted" data-eta-label><?= t('minutes_left') ?></div>
        <?php elseif ($o['status'] === 'ready'): ?>
            <div class="countdown">🛎️</div>
            <h3><?= $o['type'] === 'take_away' ? 'Ready — please collect at the counter.' : 'Ready — on its way to your table!' ?></h3>
        <?php elseif ($o['status'] === 'served'): ?>
            <h3>Served. Masarap na kainan! 🇵🇭</h3>
        <?php endif ?>
        <?php if ($ps === 'tab'): ?>
            <p class="small muted">Added to your table’s bill — settle it when you request the bill.</p>
        <?php endif ?>
    </div>
</div>
