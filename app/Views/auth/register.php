<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?= view('auth/_wrap_top', ['heading' => 'Create account', 'sub' => 'New staff accounts are activated by an admin.']) ?>
<form method="post" action="<?= site_url('register') ?>">
    <?= csrf_field() ?>
    <div class="field"><label>Full name</label><input name="name" class="input" required maxlength="120" value="<?= esc(old('name')) ?>" autocomplete="name"></div>
    <div class="field"><label>Email</label><input name="email" type="email" class="input" required value="<?= esc(old('email')) ?>" autocomplete="email"></div>
    <div class="field"><label>Password</label><input name="password" type="password" class="input" required minlength="8" autocomplete="new-password"><div class="hint">At least 8 characters.</div></div>
    <div class="field"><label>Confirm password</label><input name="password_confirm" type="password" class="input" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-block btn-lg">Create account</button>
</form>
<p class="center small mt-2">Already have one? <a class="link" href="<?= site_url('login') ?>">Sign in</a></p>
<?= view('auth/_wrap_bottom') ?>
<?= $this->endSection() ?>
