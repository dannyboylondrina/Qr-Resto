<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?= view('auth/_wrap_top', ['heading' => 'Forgot password', 'sub' => 'We’ll email you a link to set a new one.']) ?>
<form method="post" action="<?= site_url('forgot-password') ?>">
    <?= csrf_field() ?>
    <div class="field"><label>Email</label><input name="email" type="email" class="input" required autofocus autocomplete="email"></div>
    <button class="btn btn-block btn-lg">Send reset link</button>
</form>
<p class="center small mt-2"><a class="link" href="<?= site_url('login') ?>">Back to sign in</a></p>
<?= view('auth/_wrap_bottom') ?>
<?= $this->endSection() ?>
