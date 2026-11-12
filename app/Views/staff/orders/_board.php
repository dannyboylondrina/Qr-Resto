<?php
$titles = ['awaiting' => 'Awaiting payment', 'verify' => 'For verification', 'paid' => 'Paid · To prepare', 'preparing' => 'Preparing', 'ready' => 'Ready', 'served' => 'Served today'];
?>
<?php if ($board['verify']): ?>
<div class="verify-panel">
    <h3 style="font-family:var(--sans);font-size:1rem;font-weight:700">💳 Payments to verify (<?= count($board['verify']) ?>)</h3>
    <div class="verify-list">
        <?php foreach ($board['verify'] as $o): ?>
            <div class="ocard c-verify" style="margin:0">
                <div class="flex between"><b class="serif" style="font-size:1.6rem"><?= $o['type'] === 'dine_in' ? 'Table ' . esc($o['table_code']) : 'Take-away' ?></b><span class="order-code" style="font-size:.85rem;padding:.15rem .5rem"><?= esc($o['code']) ?></span></div>
                <dl class="kv mt-1">
                    <dt>Method</dt><dd><?= esc(payment_method_label($o['payment_method'])) ?><?= $o['bank_name'] ? ' · ' . esc($o['bank_name']) : '' ?></dd>
                    <dt>Amount</dt><dd><?= peso($o['total']) ?></dd>
                    <dt>Reference</dt><dd style="font-family:ui-monospace,monospace"><?= esc($o['reference_no']) ?></dd>
                    <dt>Sent</dt><dd><?= time_ago($o['payment_submitted_at']) ?></dd>
                </dl>
                <?php if ($o['proof_image']): ?>
                    <a href="<?= esc(img_url($o['proof_image'])) ?>" target="_blank" rel="noopener"><img class="proof-thumb" src="<?= esc(img_url($o['proof_image'])) ?>" alt="Proof of payment"></a>
                <?php endif ?>
                <div class="actions">
                    <button class="btn btn-green btn-sm" data-post="staff/orders/<?= $o['id'] ?>/approve" data-confirm="Approve payment ref <?= esc($o['reference_no'], 'attr') ?> for <?= esc(peso($o['total']), 'attr') ?>?">✓ Approve payment</button>
                    <button class="btn btn-red btn-sm" data-post="staff/orders/<?= $o['id'] ?>/reject" data-prompt="Reason for rejecting (shown to the guest):" data-field="reason">✕ Reject payment</button>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<div class="board">
    <?php foreach ($titles as $key => $label): ?>
        <div class="col">
            <h3><span><?= $label ?></span><span class="badge"><?= count($board[$key]) ?></span></h3>
            <?php foreach ($board[$key] as $o): ?>
                <?= view('staff/orders/_card', ['o' => $o, 'col' => $key]) ?>
            <?php endforeach ?>
            <?php if (! $board[$key]): ?><p class="tiny muted center" style="padding:1rem 0">Nothing here</p><?php endif ?>
        </div>
    <?php endforeach ?>
</div>
