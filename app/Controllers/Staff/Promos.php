<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Promos extends BaseController
{
    public function index()
    {
        return view('staff/promos', ['title' => 'Promo codes', 'active' => 'promos',
            'promos' => $this->db->table('promo_codes')->orderBy('id', 'DESC')->get()->getResultArray()]);
    }

    public function save()
    {
        $p    = $this->request->getPost();
        $id   = (int) ($p['id'] ?? 0);
        $code = strtoupper(trim((string) ($p['code'] ?? '')));
        if (! preg_match('/^[A-Z0-9\-]{3,40}$/', $code)) {
            return $this->reply('Code: 3–40 letters, numbers or dashes.', false);
        }
        $type  = ($p['type'] ?? '') === 'fixed' ? 'fixed' : 'percent';
        $value = round((float) ($p['value'] ?? 0), 2);
        if ($value <= 0 || ($type === 'percent' && $value > 100)) {
            return $this->reply('Enter a valid discount value (percent 1–100, or a peso amount).', false);
        }
        if ($this->db->table('promo_codes')->where('code', $code)->where('id !=', $id)->countAllResults()) {
            return $this->reply('That code already exists.', false);
        }
        $data = [
            'code' => $code, 'type' => $type, 'value' => $value, 'min_order' => max(0, (float) ($p['min_order'] ?? 0)),
            'expires_at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($p['expires_at'] ?? '')) ? $p['expires_at'] : null,
            'usage_limit' => ($p['usage_limit'] ?? '') === '' ? null : max(1, (int) $p['usage_limit']),
            'is_active' => 1,
        ];
        if ($id) {
            unset($data['is_active']);
            $this->db->table('promo_codes')->where('id', $id)->update($data);
        } else {
            $this->db->table('promo_codes')->insert($data + ['created_at' => date('Y-m-d H:i:s')]);
        }
        log_activity('promo_saved', 'promo', $id ?: $this->db->insertID(), $code . ' · ' . ($type === 'percent' ? $value . '%' : peso($value)));
        return $this->reply('Promo ' . $code . ' saved.');
    }

    public function toggle(int $id)
    {
        $p = $this->db->table('promo_codes')->where('id', $id)->get()->getRowArray();
        if (! $p) {
            return $this->reply('Not found.', false);
        }
        $this->db->table('promo_codes')->where('id', $id)->update(['is_active' => $p['is_active'] ? 0 : 1]);
        log_activity($p['is_active'] ? 'promo_disabled' : 'promo_enabled', 'promo', $id, $p['code']);
        return $this->reply($p['code'] . ($p['is_active'] ? ' disabled.' : ' enabled.'));
    }

    public function delete(int $id)
    {
        $p = $this->db->table('promo_codes')->where('id', $id)->get()->getRowArray();
        $this->db->table('promo_codes')->where('id', $id)->delete();
        log_activity('promo_deleted', 'promo', $id, $p['code'] ?? '');
        return $this->reply('Promo deleted.');
    }
}
