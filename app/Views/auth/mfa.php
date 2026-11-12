<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?= view('auth/_wrap_top', ['heading' => 'Two-step verification', 'sub' => 'Enter the 6-digit code from your authenticator app.']) ?>
<form method="post" action="<?= site_url('mfa') ?>">
    <?= csrf_field() ?>
    <div class="field"><input name="code" class="input code-input" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus autocomplete="one-time-code" placeholder="123456"></div>
    <button class="btn btn-block btn-lg">Verify</button>
</form>
<p class="center small mt-2">Lost your phone? Ask an admin to reset your two-step verification.</p>
<?= view('auth/_wrap_bottom') ?>
<?= $this->endSection() ?>
