<?php

namespace App\Controllers;

use App\Libraries\Notifier;
use App\Libraries\Totp;
use Throwable;

class Auth extends BaseController
{
    private function home(array $user): string
    {
        return site_url($user['role'] === 'kitchen' ? 'kitchen' : 'staff');
    }

    public function login()
    {
        if ($u = current_user()) {
            return redirect()->to($this->home($u));
        }
        return view('auth/login', ['title' => 'Staff sign in — Salo', 'googleEnabled' => (bool) env('google.clientId')]);
    }

    public function attempt()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');
        if (! service('throttler')->check('login-' . md5($this->request->getIPAddress() . $email), 5, MINUTE)) {
            return redirect()->back()->withInput()->with('error', 'Too many sign-in attempts. Please wait a minute and try again.');
        }
        $user = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if (! $user || ! $user['password_hash'] || ! password_verify($pass, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Wrong email or password.');
        }
        return $this->proceed($user);
    }

    /** Shared by password and Google sign-in. */
    private function proceed(array $user)
    {
        if (! $user['is_active']) {
            return redirect()->to(site_url('login'))->with('error', 'This account is disabled. Please ask an admin.');
        }
        if ($user['role'] === 'pending') {
            return redirect()->to(site_url('login'))->with('info', 'Your account exists but is waiting for an admin to assign your role (cashier, kitchen or admin).');
        }
        if ($user['mfa_enabled'] && $user['mfa_secret']) {
            session()->regenerate(true);
            session()->set('mfa_pending', (int) $user['id']);
            return redirect()->to(site_url('mfa'));
        }
        return $this->complete($user);
    }

    private function complete(array $user)
    {
        $after = session('redirect_after_login');
        session()->regenerate(true);
        session()->remove(['mfa_pending', 'redirect_after_login']);
        session()->set('user_id', (int) $user['id']);
        $this->db->table('users')->where('id', $user['id'])->update(['last_login_at' => date('Y-m-d H:i:s')]);
        $this->db->table('activity_logs')->insert([
            'user_id' => $user['id'], 'user_name' => $user['name'], 'action' => 'login', 'entity' => 'user', 'entity_id' => $user['id'],
            'ip' => $this->request->getIPAddress(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $to = ($after && str_starts_with($after, base_url()) && $user['role'] !== 'kitchen') ? $after : $this->home($user);
        return redirect()->to($to)->with('success', 'Welcome back, ' . $user['name'] . '!');
    }

    public function mfa()
    {
        if (! session('mfa_pending')) {
            return redirect()->to(site_url('login'));
        }
        return view('auth/mfa', ['title' => 'Two-step verification — Salo']);
    }

    public function verifyMfa()
    {
        $id = (int) session('mfa_pending');
        if (! $id) {
            return redirect()->to(site_url('login'));
        }
        if (! service('throttler')->check('mfa-' . $id, 5, MINUTE)) {
            return redirect()->back()->with('error', 'Too many attempts. Please wait a minute.');
        }
        $user = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $user || ! Totp::verify((string) $user['mfa_secret'], (string) $this->request->getPost('code'))) {
            return redirect()->back()->with('error', 'That code didn’t match. Use the 6-digit code from your authenticator app.');
        }
        return $this->complete($user);
    }

    public function logout()
    {
        if ($u = current_user()) {
            log_activity('logout', 'user', $u['id']);
        }
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'You’re signed out.');
    }

    // ---------------------------------------------------------------- Register
    public function register()
    {
        return view('auth/register', ['title' => 'Create staff account — Salo']);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[120]',
            'email'    => 'required|valid_email|max_length[190]|is_unique[users.email]',
            'password' => 'required|min_length[8]|max_length[200]',
            'password_confirm' => 'required|matches[password]',
        ];
        $messages = ['email' => ['is_unique' => 'That email already has an account.'], 'password_confirm' => ['matches' => 'Passwords do not match.']];
        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('error', first_error($this->validator->getErrors()));
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('users')->insert([
            'name' => trim(strip_tags((string) $this->request->getPost('name'))), 'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'pending', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return redirect()->to(site_url('login'))->with('success', 'Account created. An admin needs to assign your role before you can sign in.');
    }

    // ---------------------------------------------------------------- Forgot / reset
    public function forgot()
    {
        return view('auth/forgot', ['title' => 'Forgot password — Salo']);
    }

    public function sendReset()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (! service('throttler')->check('forgot-' . md5($this->request->getIPAddress()), 3, MINUTE)) {
            return redirect()->back()->with('error', 'Please wait a minute before trying again.');
        }
        $user = $this->db->table('users')->where(['email' => $email, 'is_active' => 1])->get()->getRowArray();
        $msg  = 'If that email has an account, we’ve sent a reset link. It expires in 1 hour.';
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $this->db->table('password_resets')->insert([
                'user_id' => $user['id'], 'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $link = site_url('reset-password/' . $token);
            $sent = (new Notifier())->email($user['email'], 'Reset your Salo staff password',
                'Hi ' . esc($user['name']) . ',<br><br>Reset your password here (valid for 1 hour):<br><a href="' . $link . '">' . $link . '</a><br><br>If you didn’t ask for this, ignore this email.');
            if (! $sent && ENVIRONMENT === 'development') {
                // Local development without SMTP: show the link so the flow can be tested.
                return redirect()->to(site_url('login'))->with('info', 'Development mode (no SMTP configured) — reset link: <a class="link" href="' . esc($link, 'attr') . '">open reset page</a>');
            }
        }
        return redirect()->to(site_url('login'))->with('success', $msg);
    }

    private function validReset(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->db->table('password_resets')->where('token_hash', hash('sha256', $token))
            ->where('used_at IS NULL')->where('expires_at >', date('Y-m-d H:i:s'))->get()->getRowArray();
    }

    public function reset(string $token)
    {
        if (! $this->validReset($token)) {
            return redirect()->to(site_url('forgot-password'))->with('error', 'That reset link is invalid or expired. Please request a new one.');
        }
        return view('auth/reset', ['title' => 'Reset password — Salo', 'token' => $token]);
    }

    public function doReset()
    {
        $token = (string) $this->request->getPost('token');
        $row   = $this->validReset($token);
        if (! $row) {
            return redirect()->to(site_url('forgot-password'))->with('error', 'That reset link is invalid or expired.');
        }
        if (! $this->validate(['password' => 'required|min_length[8]|max_length[200]', 'password_confirm' => 'required|matches[password]'])) {
            return redirect()->back()->with('error', first_error($this->validator->getErrors()));
        }
        $this->db->table('users')->where('id', $row['user_id'])->update([
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('password_resets')->where('user_id', $row['user_id'])->where('used_at IS NULL')->update(['used_at' => date('Y-m-d H:i:s')]);
        return redirect()->to(site_url('login'))->with('success', 'Password updated. Please sign in.');
    }

    // ---------------------------------------------------------------- Google
    public function google()
    {
        if (! env('google.clientId')) {
            return redirect()->to(site_url('login'))->with('error', 'Google sign-in isn’t set up yet. Add google.clientId and google.clientSecret to the .env file.');
        }
        $state = bin2hex(random_bytes(16));
        session()->set('oauth_state', $state);
        $q = http_build_query([
            'client_id' => env('google.clientId'), 'redirect_uri' => site_url('auth/google/callback'), 'response_type' => 'code',
            'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account',
        ]);
        return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth?' . $q);
    }

    public function googleCallback()
    {
        $state = (string) $this->request->getGet('state');
        if (! $state || ! hash_equals((string) session('oauth_state'), $state) || ! $this->request->getGet('code')) {
            return redirect()->to(site_url('login'))->with('error', 'Google sign-in was cancelled or expired. Please try again.');
        }
        session()->remove('oauth_state');
        try {
            $http  = service('curlrequest', ['timeout' => 15, 'http_errors' => false]);
            $token = json_decode($http->post('https://oauth2.googleapis.com/token', ['form_params' => [
                'code' => $this->request->getGet('code'), 'client_id' => env('google.clientId'), 'client_secret' => env('google.clientSecret'),
                'redirect_uri' => site_url('auth/google/callback'), 'grant_type' => 'authorization_code',
            ]])->getBody(), true);
            if (empty($token['access_token'])) {
                throw new \RuntimeException('No access token');
            }
            $info = json_decode($http->get('https://openidconnect.googleapis.com/v1/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $token['access_token']],
            ])->getBody(), true);
        } catch (Throwable $e) {
            log_message('error', 'Google OAuth failed: ' . $e->getMessage());
            return redirect()->to(site_url('login'))->with('error', 'Couldn’t reach Google. Please try again.');
        }
        if (empty($info['sub']) || empty($info['email']) || empty($info['email_verified'])) {
            return redirect()->to(site_url('login'))->with('error', 'Your Google account email isn’t verified.');
        }
        $email = strtolower($info['email']);
        $user  = $this->db->table('users')->where('google_id', $info['sub'])->get()->getRowArray()
            ?: $this->db->table('users')->where('email', $email)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        if (! $user) {
            $this->db->table('users')->insert(['name' => $info['name'] ?? $email, 'email' => $email, 'google_id' => $info['sub'],
                'role' => 'pending', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            return redirect()->to(site_url('login'))->with('info', 'Account created with Google. An admin must assign your role before you can sign in.');
        }
        if (! $user['google_id']) {
            $this->db->table('users')->where('id', $user['id'])->update(['google_id' => $info['sub'], 'updated_at' => $now]);
        }
        return $this->proceed($user);
    }
}
