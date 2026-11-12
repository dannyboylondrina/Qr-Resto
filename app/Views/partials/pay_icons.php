<?php $style = ! empty($dark) ? 'background:#292524;border-color:#3a3532;color:#fff' : ''; ?>
<div class="pay-icons">
    <span class="pay-chip" style="<?= $style ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="#2f7d4f" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/></svg>
        <?= t('cash') ?>
    </span>
    <span class="pay-chip" style="<?= $style ?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#0a5bd8"/><path d="M15.8 8.6A5 5 0 1 0 17 12h-4.6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>
        <?= t('gcash') ?>
    </span>
    <span class="pay-chip" style="<?= $style ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="#b5451b" stroke-width="2"><path d="M3 10 12 4l9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18"/></svg>
        <?= t('online_banking') ?>
    </span>
</div>
