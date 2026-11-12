<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="flex between mb-2">
    <div class="flex">
        <a class="btn btn-sm <?= $tab === 'log' ? 'btn-dark' : 'btn-ghost' ?>" href="<?= site_url('staff/activity') ?>">Staff activity</a>
        <a class="btn btn-sm <?= $tab === 'notifications' ? 'btn-dark' : 'btn-ghost' ?>" href="<?= site_url('staff/activity?tab=notifications') ?>">Guest notifications (SMS / email)</a>
    </div>
    <form method="get" class="flex" style="flex-wrap:nowrap">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        <?php if ($tab === 'log'): ?>
            <select name="action" class="input" style="padding:.45rem"><option value="">All actions</option>
                <?php foreach ($actions as $a): ?><option <?= service('request')->getGet('action') === $a ? 'selected' : '' ?>><?= esc($a) ?></option><?php endforeach ?></select>
        <?php endif ?>
        <input name="q" value="<?= esc($q) ?>" class="input" style="padding:.45rem" placeholder="Search…">
        <button class="btn btn-sm">Filter</button>
    </form>
</div>
<div class="table-wrap">
    <table class="data">
        <?php if ($tab === 'log'): ?>
            <tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr><td class="nowrap small"><?= date('M j, g:i:s A', strtotime($r['created_at'])) ?></td><td><b><?= esc($r['user_name']) ?></b></td>
                    <td><span class="badge <?= str_contains($r['action'], 'reject') || str_contains($r['action'], 'cancel') || str_contains($r['action'], 'delete') ? 'red' : (str_contains($r['action'], 'approve') || str_contains($r['action'], 'paid') ? 'green' : '') ?>"><?= esc($r['action']) ?></span></td>
                    <td class="small"><?= esc($r['details']) ?></td><td class="tiny muted"><?= esc($r['ip']) ?></td></tr>
            <?php endforeach ?>
        <?php else: ?>
            <tr><th>When</th><th>Channel</th><th>To</th><th>Message</th><th>Status</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr><td class="nowrap small"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td><td><?= strtoupper(esc($r['channel'])) ?></td><td class="small"><?= esc($r['recipient']) ?></td>
                    <td class="small"><?= $r['subject'] ? '<b>' . esc($r['subject']) . '</b><br>' : '' ?><?= esc(character_limiter($r['message'], 180)) ?></td>
                    <td><span class="badge <?= ['sent' => 'green', 'failed' => 'red'][$r['status']] ?? 'amber' ?>" title="<?= esc($r['error'] ?? '') ?>"><?= $r['status'] === 'logged' ? 'Logged (not configured)' : esc($r['status']) ?></span></td></tr>
            <?php endforeach ?>
        <?php endif ?>
        <?php if (! $rows): ?><tr><td colspan="5" class="muted">Nothing yet.</td></tr><?php endif ?>
    </table>
</div>
<div class="flex between mt-2 small">
    <span class="muted"><?= number_format($total) ?> entries</span>
    <div class="flex">
        <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="?<?= http_build_query(array_merge(service('request')->getGet(), ['page' => $page - 1])) ?>">← Newer</a><?php endif ?>
        <span>Page <?= $page ?> / <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="?<?= http_build_query(array_merge(service('request')->getGet(), ['page' => $page + 1])) ?>">Older →</a><?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
