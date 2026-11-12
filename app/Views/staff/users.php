<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<div class="card mb-3">
    <h3>Add staff</h3>
    <form method="post" data-ajax id="newUser" action="<?= site_url('staff/users/save') ?>" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));align-items:end">
        <div class="field mb-0"><label>Name</label><input name="name" class="input" required></div>
        <div class="field mb-0"><label>Email</label><input name="email" type="email" class="input" required></div>
        <div class="field mb-0"><label>Role</label><select name="role" class="input"><option value="cashier">Cashier</option><option value="kitchen">Kitchen</option><option value="admin">Admin</option></select></div>
        <input type="hidden" name="is_active" value="1">
        <button class="btn">Create account</button>
    </form>
    <p class="hint">A temporary password is shown once after creating. Staff can also self-register (or use Google) — new accounts wait here as <b>Pending</b> until you assign a role.</p>
</div>
<div class="table-wrap">
    <table class="data">
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>2-step</th><th>Last sign-in</th><th></th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td colspan="4" style="padding:.4rem .6rem">
                    <form method="post" data-ajax data-reload action="<?= site_url('staff/users/save') ?>" class="flex" style="flex-wrap:nowrap">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input name="name" value="<?= esc($u['name']) ?>" class="input" style="padding:.45rem;min-width:140px">
                        <span class="small muted" style="min-width:190px"><?= esc($u['email']) ?><?= $u['google_id'] ? ' <span class="badge">Google</span>' : '' ?></span>
                        <select name="role" class="input" style="padding:.45rem;width:120px">
                            <?php foreach (['pending', 'cashier', 'kitchen', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach ?>
                        </select>
                        <label class="check small"><input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?>> active</label>
                        <button class="btn btn-ghost btn-sm">Save</button>
                    </form>
                </td>
                <td><?= $u['mfa_enabled'] ? '<span class="badge green">On</span>' : '<span class="badge">Off</span>' ?></td>
                <td class="small"><?= $u['last_login_at'] ? time_ago($u['last_login_at']) : '—' ?></td>
                <td class="nowrap">
                    <button class="btn btn-ghost btn-sm" data-post="staff/users/<?= $u['id'] ?>/reset-password" data-confirm="Generate a new temporary password for <?= esc($u['name'], 'attr') ?>?" onclick="this.dataset.keep=1">Reset password</button>
                    <?php if ($u['mfa_enabled']): ?><button class="btn btn-ghost btn-sm" data-post="staff/users/<?= $u['id'] ?>/reset-mfa" data-confirm="Turn off two-step verification for <?= esc($u['name'], 'attr') ?>?" data-reload>Reset 2-step</button><?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Temporary passwords: show them in a prompt so they can be copied (toasts disappear).
const origToast = DF.toast.bind(DF);
DF.toast = (msg, type, ms) => {
  const m = /(?:password[^:]*:)\s*([A-Za-z]+-\d{4}-[a-f0-9]{4})/.exec(msg || '');
  if (m) { prompt('Copy this temporary password and share it privately:', m[1]); setTimeout(() => location.reload(), 300); }
  origToast(msg, type, ms);
};
</script>
<?= $this->endSection() ?>
