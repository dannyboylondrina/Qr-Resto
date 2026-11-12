<?php
$zones = [];
foreach ($tables as $t) {
    $zones[$t['zone']][] = $t;
}
$colors = ['available' => 'green', 'occupied' => 'red', 'reserved' => 'amber'];
?>
<?php foreach ($zones as $zone => $list): ?>
<h3 class="mt-3" style="font-family:var(--sans);font-size:.8rem;letter-spacing:.16em;text-transform:uppercase;color:var(--muted)"><?= esc($zone) ?></h3>
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(290px,1fr))">
    <?php foreach ($list as $t): $b = $t['bill']; $c = $colors[$t['effective']]; ?>
        <div class="card" style="border-top:6px solid var(--<?= $c ?>);padding:1.1rem">
            <div class="flex between">
                <div><span class="serif" style="font-size:2.2rem;line-height:1"><?= esc($t['code']) ?></span> <span class="muted small"><?= (int) $t['seats'] ?> seats</span></div>
                <span class="badge <?= $c ?>"><?= ucfirst($t['effective']) ?></span>
            </div>
            <?php if ($t['reserved_for']): ?><div class="tiny mt-1" style="color:var(--amber)">📅 Reserved for <?= esc($t['reserved_for']) ?> (soon)</div><?php endif ?>
            <div class="flex mt-1" style="gap:.3rem">
                <?php foreach (['available', 'occupied', 'reserved'] as $s): ?>
                    <button class="btn btn-sm <?= $t['status'] === $s ? 'btn-dark' : 'btn-ghost' ?>" data-post="staff/floor/<?= $t['id'] ?>/status" data-val-status="<?= $s ?>"
                        <?= $s === 'available' && $b && $b['unpaid'] > 0 ? 'disabled title="Settle the bill first"' : '' ?>><?= ucfirst($s) ?></button>
                <?php endforeach ?>
            </div>

            <?php if ($b): ?>
                <div class="mt-2" style="border-top:1px dashed var(--line);padding-top:.75rem">
                    <div class="flex between small">
                        <span class="muted">Seated <?= date('g:i A', strtotime($b['visit']['opened_at'])) ?> · <?= count($b['orders']) ?> order(s)</span>
                        <?php if ($b['visit']['status'] === 'bill_requested'): ?><span class="badge blue">🧾 Bill requested</span><?php endif ?>
                        <?php if ($t['requests']): ?><span class="badge rust"><?= $t['requests'] ?> request(s)</span><?php endif ?>
                    </div>
                    <?php foreach ($b['orders'] as $o): ?>
                        <div class="sum-row small"><span><span class="order-code" style="font-size:.7rem;padding:.05rem .35rem"><?= esc($o['code']) ?></span>
                            <?= array_sum(array_column($o['items'], 'quantity')) ?> items · <?= esc(payment_status_label($o['payment_status'])) ?></span><span><?= peso($o['total']) ?></span></div>
                    <?php endforeach ?>
                    <div class="sum-row"><span>Total</span><b><?= peso($b['total']) ?></b></div>
                    <div class="sum-row small" style="color:var(--green)"><span>Paid</span><span><?= peso($b['paid']) ?></span></div>
                    <?php if ($b['deposit_credit'] > 0): ?><div class="sum-row small" style="color:var(--green)"><span>Deposit credit</span><span>−<?= peso($b['deposit_credit']) ?></span></div><?php endif ?>
                    <div class="sum-row" style="color:<?= $b['balance'] > 0 ? 'var(--red)' : 'var(--green)' ?>"><span>Balance due</span><b><?= peso($b['balance']) ?></b></div>
                    <div class="flex mt-1">
                        <?php if ($b['unpaid'] > 0): ?>
                            <button class="btn btn-sm" onclick="openSettle(<?= (int) $b['visit']['id'] ?>, '<?= esc($t['code'], 'js') ?>', <?= (float) $b['balance'] ?>)">💵 Settle bill</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-dark" data-post="staff/floor/visit/<?= $b['visit']['id'] ?>/close" data-confirm="Close table <?= esc($t['code'], 'attr') ?> and set it to Available?">Close table</button>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
<?php endforeach ?>
