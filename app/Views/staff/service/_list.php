<?php $types = ['call_staff' => ['🙋', 'Call staff', 'rust'], 'bill' => ['🧾', 'Request bill', 'blue'], 'water' => ['💧', 'Water / utensils', 'green']]; ?>
<?php if (! $data['open']): ?>
    <div class="card center" style="padding:3rem"><h3>No open requests 👌</h3><p class="muted mb-0">Guests’ “Call staff”, “Request bill” and “Water/utensils” taps show up here with a sound alert.</p></div>
<?php else: ?>
<div class="grid grid-3">
    <?php foreach ($data['open'] as $r): [$icon, $label, $color] = $types[$r['type']]; ?>
        <div class="card" style="border-left:6px solid var(--<?= $color ?>)">
            <div class="flex between">
                <div class="serif" style="font-size:2.6rem;line-height:1"><?= esc($r['code']) ?></div>
                <span class="badge <?= $color ?>"><?= $icon ?> <?= $label ?></span>
            </div>
            <p class="muted small mt-1"><?= esc($r['zone']) ?> · <?= time_ago($r['created_at']) ?></p>
            <div class="flex">
                <button class="btn btn-green" data-post="staff/service/<?= $r['id'] ?>/done">✓ Done</button>
                <?php if ($r['type'] === 'bill'): ?><a class="btn btn-ghost" href="<?= site_url('staff/floor') ?>">Open bill</a><?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($data['done']): ?>
<h3 class="mt-4">Recently handled</h3>
<div class="table-wrap"><table class="data">
    <tr><th>Table</th><th>Request</th><th>Asked</th><th>Handled by</th><th>Done</th></tr>
    <?php foreach ($data['done'] as $r): ?>
        <tr><td><b><?= esc($r['code']) ?></b></td><td><?= $types[$r['type']][1] ?></td><td><?= date('g:i A', strtotime($r['created_at'])) ?></td>
            <td><?= esc($r['handler'] ?? '—') ?></td><td><?= $r['done_at'] ? date('g:i A', strtotime($r['done_at'])) : '' ?></td></tr>
    <?php endforeach ?>
</table></div>
<?php endif ?>
