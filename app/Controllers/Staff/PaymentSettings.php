<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use RuntimeException;

class PaymentSettings extends BaseController
{
    public function index()
    {
        return view('staff/payment_settings', ['title' => 'Payment settings', 'active' => 'payments', 'banks' => bank_accounts(false)]);
    }

    public function save()
    {
        $p = $this->request->getPost();
        foreach (['cash_enabled', 'gcash_enabled', 'bank_enabled'] as $k) {
            save_setting($k, empty($p[$k]) ? '0' : '1');
        }
        if (empty($p['cash_enabled']) && empty($p['gcash_enabled']) && empty($p['bank_enabled'])) {
            save_setting('cash_enabled', '1');
            session()->setFlashdata('info', 'At least one payment method must stay on — Cash was kept enabled.');
        }
        save_setting('gcash_name', trim(strip_tags((string) ($p['gcash_name'] ?? ''))));
        save_setting('gcash_number', trim(preg_replace('/[^0-9+ \-]/', '', (string) ($p['gcash_number'] ?? ''))));
        save_setting('deposit_amount', max(0, round((float) ($p['deposit_amount'] ?? 0), 2)));
        save_setting('deposit_min_guests', max(2, min(12, (int) ($p['deposit_min_guests'] ?? 8))));
        try {
            if ($qr = save_uploaded_image($this->request->getFile('gcash_qr'), 'settings')) {
                save_setting('gcash_qr', $qr);
            }
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', 'GCash QR: ' . $e->getMessage());
        }
        if (! empty($p['remove_qr'])) {
            save_setting('gcash_qr', '');
        }
        log_activity('payment_settings_updated', 'settings', null, 'Cash ' . (setting('cash_enabled') === '1' ? 'on' : 'off') . ', GCash ' . (setting('gcash_enabled') === '1' ? 'on' : 'off') . ', Bank ' . (setting('bank_enabled') === '1' ? 'on' : 'off') . ', deposit ' . peso(setting('deposit_amount')));
        return redirect()->to(site_url('staff/payment-settings'))->with('success', 'Payment settings saved.');
    }

    public function saveBank()
    {
        $p    = $this->request->getPost();
        $id   = (int) ($p['id'] ?? 0);
        $data = [
            'bank_name' => trim(strip_tags((string) ($p['bank_name'] ?? ''))), 'account_name' => trim(strip_tags((string) ($p['account_name'] ?? ''))),
            'account_number' => trim(strip_tags((string) ($p['account_number'] ?? ''))), 'is_active' => empty($p['is_active']) ? 0 : 1,
            'sort_order' => (int) ($p['sort_order'] ?? 0),
        ];
        if ($data['bank_name'] === '' || $data['account_name'] === '' || $data['account_number'] === '') {
            return $this->reply('Bank, account name and account number are required.', false);
        }
        if ($id) {
            $this->db->table('bank_accounts')->where('id', $id)->update($data);
        } else {
            $this->db->table('bank_accounts')->insert($data);
        }
        log_activity('bank_account_saved', 'bank_account', $id ?: $this->db->insertID(), $data['bank_name'] . ' · ' . $data['account_number']);
        return $this->reply($data['bank_name'] . ' account saved.');
    }

    public function deleteBank(int $id)
    {
        $b = $this->db->table('bank_accounts')->where('id', $id)->get()->getRowArray();
        $this->db->table('bank_accounts')->where('id', $id)->delete();
        log_activity('bank_account_deleted', 'bank_account', $id, $b['bank_name'] ?? '');
        return $this->reply('Bank account removed.');
    }
}
