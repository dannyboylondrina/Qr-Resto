<?php if (! $tickets): ?>
    <div class="card-flat center" style="padding:4rem 1rem"><h2 style="color:#f5f0e8">All clear 🔥</h2><p class="muted">Paid orders appear here the moment payment is confirmed.</p></div>
<?php endif ?>
<div class="k-grid" data-now="<?= time() ?>">
    <?php foreach ($tickets as $o): ?>
        <div class="kcard" data-start="<?= strtotime($o['released_at']) ?>" data-eta="<?= strtotime($o['estimated_ready_at']) ?>">
            <div class="flex between" style="align-items:flex-start">
                <div>
                    <div class="tno"><?= $o['type'] === 'dine_in' ? esc($o['table_code']) : 'TAKE-AWAY' ?></div>
                    <div class="tiny muted"><?= $o['type'] === 'dine_in' ? (int) $o['guests'] . ' guests · Dine-in' : esc($o['customer_name']) ?> · <?= esc($o['code']) ?></div>
                </div>
                <div class="right">
                    <div class="timer" data-timer>0:00</div>
                    <div class="tiny muted">ETA <?= date('g:i A', strtotime($o['estimated_ready_at'])) ?></div>
                </div>
            </div>
            <div class="mt-1"><span class="badge"><?= $o['status'] === 'preparing' ? '🔥 Preparing' : '🆕 New' ?></span>
                <?php if ($o['payment_status'] === 'tab'): ?><span class="badge">Tab</span><?php endif ?></div>
            <ul>
                <?php foreach ($o['items'] as $it): ?>
                    <li><span class="q"><?= (int) $it['quantity'] ?>×</span><?= esc($it['name']) ?>
                        <?php if ($it['options_text']): ?><span class="o"><?= esc($it['options_text']) ?></span><?php endif ?>
                        <?php if ($it['note']): ?><span class="n">⚠ <?= esc($it['note']) ?></span><?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="flex">
                <?php if ($o['status'] === 'received'): ?>
                    <button class="btn btn-amber" style="flex:1" data-post="kitchen/<?= $o['id'] ?>/preparing">Preparing</button>
                <?php endif ?>
                <button class="btn btn-green" style="flex:1" data-post="kitchen/<?= $o['id'] ?>/ready">Ready ✓</button>
            </div>
        </div>
    <?php endforeach ?>
</div>
