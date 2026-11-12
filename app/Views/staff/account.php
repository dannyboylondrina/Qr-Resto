<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="grid grid-2" style="align-items:start">
    <div class="card">
        <h3><?= esc($u['name']) ?></h3>
        <p class="muted"><?= esc($u['email']) ?> · <span class="badge rust"><?= esc(ucfirst($u['role'])) ?></span></p>
        <h4 class="mt-3"><?= $u['password_hash'] ? 'Change password' : 'Set a password' ?></h4>
        <form method="post" action="<?= site_url('staff/account/password') ?>">
            <?= csrf_field() ?>
            <?php if ($u['password_hash']): ?><div class="field"><label>Current password</label><input type="password" name="current" class="input" required autocomplete="current-password"></div><?php endif ?>
            <div class="field"><label>New password</label><input type="password" name="password" class="input" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label>Confirm new password</label><input type="password" name="password_confirm" class="input" required minlength="8" autocomplete="new-password"></div>
            <button class="btn">Save password</button>
        </form>
    </div>

    <div class="card">
        <h3>Two-step verification (MFA)</h3>
        <?php if ($u['mfa_enabled']): ?>
            <div class="alert alert-success">✓ On — you’ll enter a code from your authenticator app at each sign-in.</div>
            <form method="post" action="<?= site_url('staff/account/mfa/disable') ?>" class="flex" style="flex-wrap:nowrap">
                <?= csrf_field() ?>
                <input name="code" class="input" inputmode="numeric" placeholder="Current 6-digit code" required maxlength="7">
                <button class="btn btn-ghost">Turn off</button>
            </form>
        <?php elseif ($setupSecret): ?>
            <p>1. Scan this with Google Authenticator, Microsoft Authenticator or Authy:</p>
            <div id="mfaQr" style="width:200px;height:200px;background:#fff;padding:10px;border-radius:12px;border:1px solid var(--line)"></div>
            <p class="tiny muted mt-1">Or enter this key manually: <code style="word-break:break-all"><?= esc(trim(chunk_split($setupSecret, 4, ' '))) ?></code></p>
            <p>2. Enter the 6-digit code it shows:</p>
            <form method="post" action="<?= site_url('staff/account/mfa/enable') ?>" class="flex" style="flex-wrap:nowrap">
                <?= csrf_field() ?>
                <input name="code" class="input code-input" inputmode="numeric" required maxlength="7" autocomplete="one-time-code" placeholder="123456">
                <button class="btn">Turn on</button>
            </form>
        <?php else: ?>
            <p class="muted">Protect your account with a 6-digit code from your phone in addition to your password. Recommended for admins and cashiers.</p>
            <form method="post" action="<?= site_url('staff/account/mfa/start') ?>"><?= csrf_field() ?><button class="btn">Set up two-step verification</button></form>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($setupUri): ?>
<script src="<?= asset('vendor/qrcode.js') ?>"></script>
<script>
(function () { const q = qrcode(0, 'M'); q.addData(<?= json_encode($setupUri) ?>); q.make(); document.getElementById('mfaQr').innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }); })();
</script>
<?php endif ?>
<?= $this->endSection() ?>
