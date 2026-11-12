<?php

use Config\Database;

if (! function_exists('peso')) {
    function peso($amount): string
    {
        return '₱' . number_format((float) $amount, 2);
    }
}

if (! function_exists('peso0')) {
    /** Whole-peso display for menus (₱285, or ₱285.50 when there are centavos). */
    function peso0($amount): string
    {
        $amount = (float) $amount;
        return '₱' . (floor($amount) == $amount ? number_format($amount) : number_format($amount, 2));
    }
}

if (! function_exists('t')) {
    /** Translate a site string (app/Language/{en,fil}/Site.php). */
    function t(string $key, array $args = []): string
    {
        return lang('Site.' . $key, $args);
    }
}

if (! function_exists('asset')) {
    /** URL to a file in /public/assets with a cache-busting version. */
    function asset(string $path): string
    {
        $file = FCPATH . 'assets/' . ltrim($path, '/');
        $v    = is_file($file) ? filemtime($file) : 1;
        return base_url('assets/' . ltrim($path, '/')) . '?v=' . $v;
    }
}

if (! function_exists('img_url')) {
    /** Photo field may be a path under /public or a full URL. */
    function img_url(?string $path): string
    {
        if (! $path) {
            return base_url('assets/img/placeholder.svg');
        }
        return preg_match('#^https?://#i', $path) ? $path : base_url($path);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $cache = null;
        if ($key === '__flush') {
            $cache = null;
            return null;
        }
        if ($cache === null) {
            $cache = [];
            foreach (Database::connect()->table('settings')->get()->getResultArray() as $row) {
                $cache[$row['key']] = $row['value'];
            }
        }
        return $cache[$key] ?? $default;
    }
}

if (! function_exists('save_setting')) {
    function save_setting(string $key, $value): void
    {
        $db = Database::connect();
        $db->query('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, (string) $value]);
        setting('__flush');
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?array
    {
        static $user = false;
        if ($user === false) {
            $id   = session('user_id');
            $user = $id ? Database::connect()->table('users')->where(['id' => $id, 'is_active' => 1])->get()->getRowArray() : null;
        }
        return $user;
    }
}

if (! function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        $u = current_user();
        if (! $u) {
            return false;
        }
        return $u['role'] === 'admin' || in_array($u['role'], $roles, true);
    }
}

if (! function_exists('log_activity')) {
    function log_activity(string $action, ?string $entity = null, $entityId = null, ?string $details = null): void
    {
        $u = current_user();
        Database::connect()->table('activity_logs')->insert([
            'user_id'   => $u['id'] ?? null,
            'user_name' => $u['name'] ?? 'Guest',
            'action'    => $action,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'details'   => $details ? mb_substr($details, 0, 500) : null,
            'ip'        => service('request')->getIPAddress(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);
    }
}

if (! function_exists('payment_method_label')) {
    function payment_method_label(?string $m): string
    {
        return [
            'cash' => 'Cash', 'gcash' => 'GCash', 'online_banking' => 'Online Banking',
            'pay_later' => 'Pay at the end (tab)', 'none' => '—',
        ][$m] ?? (string) $m;
    }
}

if (! function_exists('payment_status_label')) {
    function payment_status_label(?string $s): string
    {
        return [
            'awaiting' => t('ps_awaiting'), 'for_verification' => t('ps_for_verification'),
            'paid' => t('ps_paid'), 'rejected' => t('ps_rejected'), 'tab' => t('ps_tab'), 'none' => '—',
        ][$s] ?? (string) $s;
    }
}

if (! function_exists('order_status_label')) {
    function order_status_label(?string $s): string
    {
        return [
            'received' => t('st_received'), 'preparing' => t('st_preparing'), 'ready' => t('st_ready'),
            'served' => t('st_served'), 'cancelled' => t('st_cancelled'),
        ][$s] ?? (string) $s;
    }
}

if (! function_exists('discount_label')) {
    function discount_label(?string $s): string
    {
        return ['promo' => 'Promo', 'senior' => 'Senior Citizen (20%)', 'pwd' => 'PWD (20%)'][$s] ?? '';
    }
}

if (! function_exists('reservation_slots')) {
    /** 11:30–13:00 and 17:30–20:30, 30-minute steps. */
    function reservation_slots(): array
    {
        return ['11:30', '12:00', '12:30', '13:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'];
    }
}

if (! function_exists('enabled_payment_methods')) {
    function enabled_payment_methods(bool $includeCash = true): array
    {
        $m = [];
        if ($includeCash && setting('cash_enabled', '1') === '1') {
            $m[] = 'cash';
        }
        if (setting('gcash_enabled', '1') === '1') {
            $m[] = 'gcash';
        }
        if (setting('bank_enabled', '1') === '1') {
            $m[] = 'online_banking';
        }
        return $m;
    }
}

if (! function_exists('bank_accounts')) {
    function bank_accounts(bool $activeOnly = true): array
    {
        $b = Database::connect()->table('bank_accounts')->orderBy('sort_order')->orderBy('id');
        if ($activeOnly) {
            $b->where('is_active', 1);
        }
        return $b->get()->getResultArray();
    }
}

if (! function_exists('time_ago')) {
    function time_ago(?string $dt): string
    {
        if (! $dt) {
            return '';
        }
        $s = time() - strtotime($dt);
        if ($s < 60) {
            return 'just now';
        }
        if ($s < 3600) {
            return floor($s / 60) . ' min ago';
        }
        if ($s < 86400) {
            return floor($s / 3600) . ' h ago';
        }
        return date('M j, g:i A', strtotime($dt));
    }
}

if (! function_exists('table_effective_statuses')) {
    /**
     * Returns tables with an "effective" status: a table set Available shows as Reserved when a confirmed
     * reservation starts within the next 60 minutes (or started up to 15 minutes ago — the hold window).
     */
    function table_effective_statuses(): array
    {
        $db     = Database::connect();
        $tables = $db->table('dining_tables')->orderBy('sort_order')->orderBy('code')->get()->getResultArray();
        $today  = date('Y-m-d');
        $res    = $db->table('reservations')->where(['res_date' => $today])->whereIn('status', ['confirmed'])
            ->where('table_id IS NOT NULL')->get()->getResultArray();
        $now    = time();
        $held   = [];
        foreach ($res as $r) {
            $start = strtotime($today . ' ' . $r['res_time']);
            if ($start - 3600 <= $now && $now <= $start + 15 * 60) {
                $held[$r['table_id']] = $r['name'];
            }
        }
        foreach ($tables as &$t) {
            $t['effective']     = $t['status'];
            $t['reserved_for']  = $held[$t['id']] ?? null;
            if ($t['status'] === 'available' && isset($held[$t['id']])) {
                $t['effective'] = 'reserved';
            }
        }
        return $tables;
    }
}

if (! function_exists('save_uploaded_image')) {
    /**
     * Safely stores an uploaded image (JPG/PNG/WEBP/GIF, max 5 MB) under public/uploads/{dir}.
     * Detects the real type from file contents and uses a random filename. Returns the relative path or null.
     * Throws \RuntimeException with a guest-friendly message on invalid files.
     */
    function save_uploaded_image($file, string $dir, int $maxBytes = 5242880): ?string
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid()) {
            throw new \RuntimeException('Upload failed: ' . $file->getErrorString());
        }
        if ($file->getSize() > $maxBytes) {
            throw new \RuntimeException('Image is too large (max 5 MB).');
        }
        $info = @getimagesize($file->getTempName());
        $exts = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
        if (! $info || ! isset($exts[$info[2]])) {
            throw new \RuntimeException('Please upload a JPG, PNG, WEBP or GIF image.');
        }
        $dir    = trim(preg_replace('/[^a-z0-9_\-]/i', '', $dir));
        $target = FCPATH . 'uploads/' . $dir;
        if (! is_dir($target)) {
            mkdir($target, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $exts[$info[2]];
        $file->move($target, $name);
        return 'uploads/' . $dir . '/' . $name;
    }
}

if (! function_exists('json_flash_errors')) {
    function first_error(array $errors): string
    {
        return (string) (array_values($errors)[0] ?? 'Please check the form.');
    }
}
