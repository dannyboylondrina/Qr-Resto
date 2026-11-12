<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?= view('auth/_wrap_top', ['heading' => 'New password']) ?>
<form method="post" action="<?= site_url('reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= esc($token) ?>">
    <div class="field"><label>New password</label><input name="password" type="password" class="input" required minlength="8" autocomplete="new-password" autofocus></div>
    <div class="field"><label>Confirm password</label><input name="password_confirm" type="password" class="input" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-block btn-lg">Save password</button>
</form>
<?= view('auth/_wrap_bottom') ?>
<?= $this->endSection() ?>
