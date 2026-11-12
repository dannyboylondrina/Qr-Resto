<?php if (session('success') || session('error') || session('info')): ?>
<div class="<?= isset($inStaff) ? '' : 'container' ?> mt-2">
    <?php if (session('success')): ?><div class="alert alert-success" data-autohide><?= esc(session('success')) ?></div><?php endif ?>
    <?php if (session('error')): ?><div class="alert alert-error"><?= esc(session('error')) ?></div><?php endif ?>
    <?php if (session('info')): ?><div class="alert alert-info"><?= session('info') /* trusted, built by controllers */ ?></div><?php endif ?>
</div>
<?php endif ?>
