<?= $this->extend('layouts/staff') ?>
<?= $this->section('actions') ?>
<div class="segmented">
    <a class="btn btn-sm <?= $view === 'list' && ! $date ? '' : 'btn-ghost' ?>" href="<?= site_url('staff/reservations') ?>">List</a>
    <a class="btn btn-sm <?= $view === 'calendar' ? '' : 'btn-ghost' ?>" href="<?= site_url('staff/reservations?view=calendar&month=' . $month) ?>">Calendar</a>
</div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $badges = ['pending' => 'amber', 'confirmed' => 'green', 'seated' => 'blue', 'cancelled' => 'red', 'no_show' => 'red']; ?>

<?php if ($view === 'calendar'): ?>
    <?php
    $first = strtotime($month . '-01');
    $start = strtotime('monday this week', $first);
    $prev  = date('Y-m', strtotime('-1 month', $first));
    $next  = date('Y-m', strtotime('+1 month', $first));
    ?>
    <div class="card">
        <div class="flex between mb-2">
            <a class="btn btn-ghost btn-sm" href="<?= site_url('staff/reservations?view=calendar&month=' . $prev) ?>">← Prev</a>
            <h3 class="mb-0"><?= date('F Y', $first) ?></h3>
            <a class="btn btn-ghost btn-sm" href="<?= site_url('staff/reservations?view=calendar&month=' . $next) ?>">Next →</a>
        </div>
        <div class="cal">
            <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?><div class="dh"><?= $d ?></div><?php endforeach ?>
            <?php for ($i = 0; $i < 42; $i++): $day = strtotime("+$i days", $start); $ds = date('Y-m-d', $day); $c = $counts[$ds] ?? null; ?>
                <?php if ($i >= 35 && date('m', $day) !== date('m', $first)) { break; } ?>
                <a href="<?= site_url('staff/reservations?view=calendar&month=' . $month . '&date=' . $ds) ?>"
                   class="<?= date('m', $day) !== date('m', $first) ? 'other' : '' ?> <?= $ds === date('Y-m-d') ? 'today' : '' ?> <?= $ds === $date ? 'sel' : '' ?>">
                    <span class="n"><?= date('j', $day) ?></span>
                    <?php if ($c): ?>
                        <span class="badge rust"><?= (int) $c['n'] ?> res · <?= (int) $c['g'] ?> pax</span>
                        <?php if ($c['p']): ?><span class="badge amber"><?= (int) $c['p'] ?> pending</span><?php endif ?>
                    <?php elseif (date('N', $day) == 1): ?><span class="tiny muted">Closed</span><?php endif ?>
                </a>
            <?php endfor ?>
        </div>
    </div>
<?php endif ?>

<?php if ($view === 'list' || $date): ?>
    <div class="flex between mt-3 mb-2">
        <h3 class="mb-0"><?= $date ? date('l, F j', strtotime($date)) : 'Upcoming & pending' ?></h3>
        <div class="flex">
            <?php foreach (['' => 'Active', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'seated' => 'Seated', 'all' => 'All'] as $k => $l): ?>
                <a class="btn btn-sm <?= $status === $k ? 'btn-dark' : 'btn-ghost' ?>" href="<?= site_url('staff/reservations?' . http_build_query(array_filter(['view' => $date ? 'calendar' : null, 'month' => $date ? $month : null, 'date' => $date, 'status' => $k]))) ?>"><?= $l ?></a>
            <?php endforeach ?>
        </div>
    </div>
    <?php if (! $list): ?><div class="card muted">No reservations here.</div><?php endif ?>
    <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr))">
        <?php foreach ($list as $r): ?>
            <div class="card" style="padding:1.2rem">
                <div class="flex between">
                    <div><b style="font-size:1.1rem"><?= esc($r['name']) ?></b> <span class="order-code" style="font-size:.75rem;padding:.1rem .4rem"><?= esc($r['code']) ?></span></div>
                    <span class="badge <?= $badges[$r['status']] ?>"><?= ucfirst(str_replace('_', '-', $r['status'])) ?></span>
                </div>
                <div class="serif mt-1" style="font-size:1.4rem"><?= date('D, M j', strtotime($r['res_date'])) ?> · <?= date('g:i A', strtotime($r['res_time'])) ?></div>
                <div class="small muted"><?= (int) $r['guests'] ?> guests · <?= esc($r['mobile']) ?><?= $r['email'] ? ' · ' . esc($r['email']) : '' ?></div>
                <?php if ($r['notes']): ?><div class="small mt-1">💬 <?= esc($r['notes']) ?></div><?php endif ?>

                <?php if ($r['items']): ?>
                    <div class="small mt-1"><b>Pre-order:</b> <?= esc(implode(', ', array_map(static fn ($i) => $i['quantity'] . '× ' . $i['name'], $r['items']))) ?> (<?= peso($r['preorder_total']) ?>)</div>
                <?php endif ?>
                <?php if ($r['amount_due'] > 0): ?>
                    <div class="small mt-1">
                        <b>Due ahead:</b> <?= peso($r['amount_due']) ?><?= $r['deposit_amount'] > 0 ? ' (incl. deposit ' . peso($r['deposit_amount']) . ')' : '' ?>
                        · <?= esc(payment_method_label($r['payment_method'])) ?><?= $r['bank_name'] ? ' · ' . esc($r['bank_name']) : '' ?>
                        <span class="badge <?= ['paid' => 'green', 'for_verification' => 'blue', 'rejected' => 'red'][$r['payment_status']] ?? '' ?>"><?= esc(payment_status_label($r['payment_status'])) ?></span>
                    </div>
                    <?php if ($r['payment_status'] === 'for_verification'): ?>
                        <div class="small">Ref <b><?= esc($r['reference_no']) ?></b></div>
                        <?php if ($r['proof_image']): ?><a href="<?= esc(img_url($r['proof_image'])) ?>" target="_blank"><img class="proof-thumb" src="<?= esc(img_url($r['proof_image'])) ?>" alt="Proof"></a><?php endif ?>
                    <?php endif ?>
                <?php endif ?>

                <form class="flex mt-1" method="post" data-ajax action="<?= site_url('staff/reservations/' . $r['id'] . '/assign') ?>" style="flex-wrap:nowrap">
                    <select name="table_id" class="input" style="padding:.45rem .6rem">
                        <option value="">No table</option>
                        <?php foreach ($tables as $t): ?><option value="<?= $t['id'] ?>" <?= $r['table_id'] == $t['id'] ? 'selected' : '' ?> <?= $t['seats'] < $r['guests'] ? 'disabled' : '' ?>><?= esc($t['code']) ?> · <?= $t['seats'] ?> seats · <?= esc($t['zone']) ?></option><?php endforeach ?>
                    </select>
                    <button class="btn btn-ghost btn-sm">Assign</button>
                </form>

                <div class="actions flex mt-1" style="gap:.35rem">
                    <?php if ($r['payment_status'] === 'for_verification'): ?>
                        <button class="btn btn-green btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/approve" data-confirm="Approve <?= esc(peso($r['amount_due']), 'attr') ?> payment ref <?= esc($r['reference_no'], 'attr') ?>?">✓ Approve payment</button>
                        <button class="btn btn-red btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/reject" data-prompt="Reason for rejecting the payment:" data-field="reason">✕ Reject</button>
                    <?php endif ?>
                    <?php if ($r['status'] === 'pending' && $r['payment_status'] !== 'for_verification'): ?>
                        <button class="btn btn-green btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/confirm">Confirm</button>
                    <?php endif ?>
                    <?php if (in_array($r['status'], ['pending', 'confirmed'], true)): ?>
                        <button class="btn btn-dark btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/seat" data-confirm="Seat <?= esc($r['name'], 'attr') ?> now?">Seat party</button>
                        <button class="btn btn-ghost btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/noshow" data-confirm="Mark as no-show?">No-show</button>
                        <button class="btn btn-ghost btn-sm" data-post="staff/reservations/<?= $r['id'] ?>/cancel" data-confirm="Cancel this reservation? The guest will be notified.">Cancel</button>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// reservation actions change the list: reload after success
document.addEventListener('click', (e) => { const b = e.target.closest('[data-post]'); if (b) b.dataset.reload = ''; }, true);
document.querySelectorAll('form[data-ajax]').forEach((f) => (f.dataset.reload = ''));
</script>
<?= $this->endSection() ?>
