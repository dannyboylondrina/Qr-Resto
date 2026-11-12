<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="grid grid-3 mb-3">
    <div class="kpi"><span>Average rating</span><b><?= $avg['n'] ? number_format($avg['a'], 1) : '—' ?> <span class="stars">★</span></b><small><?= (int) $avg['n'] ?> reviews</small></div>
    <div class="kpi"><span>Waiting for approval</span><b><?= count(array_filter($reviews, static fn ($r) => ! $r['is_approved'])) ?></b><small>hidden until approved</small></div>
    <div class="kpi"><span>On the home page</span><b><?= min(6, count(array_filter($reviews, static fn ($r) => $r['is_approved']))) ?></b><small>latest 6 approved</small></div>
</div>
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
    <?php foreach ($reviews as $r): ?>
        <div class="card" style="padding:1.2rem;<?= $r['is_approved'] ? '' : 'border-color:var(--amber)' ?>">
            <div class="flex between">
                <span class="stars"><?= str_repeat('★', (int) $r['rating']) ?><span style="color:var(--line)"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span></span>
                <span class="badge <?= $r['is_approved'] ? 'green' : 'amber' ?>"><?= $r['is_approved'] ? 'Shown' : 'Pending' ?></span>
            </div>
            <p class="mt-1"><?= esc($r['comment'] ?: '(no comment)') ?></p>
            <div class="tiny muted">— <?= esc($r['guest_name'] ?: 'Guest') ?><?= $r['code'] ? ' · order ' . esc($r['code']) : '' ?> · <?= date('M j, Y', strtotime($r['created_at'])) ?></div>
            <div class="flex mt-1">
                <?php if ($r['is_approved']): ?>
                    <button class="btn btn-ghost btn-sm" data-post="staff/reviews/<?= $r['id'] ?>/hide" data-reload>Hide</button>
                <?php else: ?>
                    <button class="btn btn-green btn-sm" data-post="staff/reviews/<?= $r['id'] ?>/approve" data-reload>Approve</button>
                <?php endif ?>
                <button class="btn btn-ghost btn-sm" data-post="staff/reviews/<?= $r['id'] ?>/delete" data-confirm="Delete this review?" data-reload>🗑</button>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?php if (! $reviews): ?><div class="card muted">No reviews yet. Guests can rate their order on the tracking page after it’s served.</div><?php endif ?>
<?= $this->endSection() ?>
