<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Libraries\Totp;

class Account extends BaseController
{
    public function index()
    {
        $u = current_user();
        $pending = session('mfa_setup_secret');
        return view('staff/account', [
            'title' => 'My account', 'active' => 'account', 'u' => $u,
            'setupSecret' => $pending, 'setupUri' => $pending ? Totp::uri($pending, $u['email']) : null,
        ]);
    }

    public function password()
    {
        $u = current_user();
        if ($u['password_hash'] && ! password_verify((string) $this->request->getPost('current'), $u['password_hash'])) {
            return redirect()->back()->with('error', 'Your current password is wrong.');
        }
        if (! $this->validate(['password' => 'required|min_length[8]|max_length[200]', 'password_confirm' => 'required|matches[password]'])) {
            return redirect()->back()->with('error', first_error($this->validator->getErrors()));
        }
        $this->db->table('users')->where('id', $u['id'])->update(['password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity('password_changed', 'user', $u['id']);
        return redirect()->back()->with('success', 'Password changed.');
    }

    public function mfaStart()
    {
        session()->set('mfa_setup_secret', Totp::newSecret());
        return redirect()->to(site_url('staff/account'));
    }

    public function mfaEnable()
    {
        $secret = session('mfa_setup_secret');
        if (! $secret || ! Totp::verify($secret, (string) $this->request->getPost('code'))) {
            return redirect()->back()->with('error', 'That code didn’t match. Scan the QR again and enter the current 6-digit code.');
        }
        $u = current_user();
        $this->db->table('users')->where('id', $u['id'])->update(['mfa_secret' => $secret, 'mfa_enabled' => 1]);
        session()->remove('mfa_setup_secret');
        log_activity('mfa_enabled', 'user', $u['id']);
        return redirect()->to(site_url('staff/account'))->with('success', 'Two-step verification is on. You’ll need your authenticator app to sign in.');
    }

    public function mfaDisable()
    {
        $u = current_user();
        if (! Totp::verify((string) $u['mfa_secret'], (string) $this->request->getPost('code'))) {
            return redirect()->back()->with('error', 'Enter a valid current code to turn it off.');
        }
        $this->db->table('users')->where('id', $u['id'])->update(['mfa_secret' => null, 'mfa_enabled' => 0]);
        log_activity('mfa_disabled', 'user', $u['id']);
        return redirect()->back()->with('success', 'Two-step verification turned off.');
    }
}
