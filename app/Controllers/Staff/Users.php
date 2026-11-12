<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Users extends BaseController
{
    public function index()
    {
        return view('staff/users', ['title' => 'Staff accounts', 'active' => 'users',
            'users' => $this->db->table('users')->orderBy("FIELD(role,'pending','admin','cashier','kitchen')", '', false)->orderBy('name')->get()->getResultArray()]);
    }

    public function save()
    {
        $p    = $this->request->getPost();
        $id   = (int) ($p['id'] ?? 0);
        $me   = current_user();
        $role = in_array($p['role'] ?? '', ['admin', 'cashier', 'kitchen', 'pending'], true) ? $p['role'] : 'cashier';
        $data = ['name' => trim(strip_tags((string) ($p['name'] ?? ''))), 'role' => $role, 'is_active' => empty($p['is_active']) ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')];
        if ($data['name'] === '') {
            return $this->reply('Name is required.', false);
        }
        if ($id === (int) $me['id'] && ($role !== 'admin' || ! $data['is_active'])) {
            return $this->reply('You can’t remove your own admin access.', false);
        }
        if ($id) {
            $this->db->table('users')->where('id', $id)->update($data);
            log_activity('staff_updated', 'user', $id, $data['name'] . ' · ' . $role . ($data['is_active'] ? '' : ' · disabled'));
            return $this->reply($data['name'] . ' updated.');
        }
        $email = strtolower(trim((string) ($p['email'] ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->reply('Enter a valid email.', false);
        }
        if ($this->db->table('users')->where('email', $email)->countAllResults()) {
            return $this->reply('That email already has an account.', false);
        }
        $temp = $this->tempPassword();
        $this->db->table('users')->insert($data + ['email' => $email, 'password_hash' => password_hash($temp, PASSWORD_DEFAULT), 'created_at' => date('Y-m-d H:i:s')]);
        log_activity('staff_created', 'user', $this->db->insertID(), $data['name'] . ' · ' . $email . ' · ' . $role);
        return $this->json(['ok' => true, 'message' => 'Account created. Temporary password: ' . $temp . ' — share it privately; they should change it in My account.', 'temp' => $temp]);
    }

    public function resetPassword(int $id)
    {
        $u = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $u) {
            return $this->reply('Not found.', false);
        }
        $temp = $this->tempPassword();
        $this->db->table('users')->where('id', $id)->update(['password_hash' => password_hash($temp, PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity('staff_password_reset', 'user', $id, $u['name']);
        return $this->json(['ok' => true, 'message' => 'New temporary password for ' . $u['name'] . ': ' . $temp, 'temp' => $temp]);
    }

    public function resetMfa(int $id)
    {
        $u = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $u) {
            return $this->reply('Not found.', false);
        }
        $this->db->table('users')->where('id', $id)->update(['mfa_enabled' => 0, 'mfa_secret' => null]);
        log_activity('staff_mfa_reset', 'user', $id, $u['name']);
        return $this->reply('Two-step verification turned off for ' . $u['name'] . '.');
    }

    private function tempPassword(): string
    {
        $words = ['Adobo', 'Sinigang', 'Kalamansi', 'Pandesal', 'Ube', 'Tablea', 'Lechon', 'Bibingka'];
        return $words[random_int(0, count($words) - 1)] . '-' . random_int(1000, 9999) . '-' . substr(bin2hex(random_bytes(3)), 0, 4);
    }
}
