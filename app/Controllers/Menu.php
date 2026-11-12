<?php

namespace App\Controllers;

use RuntimeException;

class Menu extends BaseController
{
    public function index()
    {
        $cats  = $this->db->table('categories')->orderBy('sort_order')->get()->getResultArray();
        $items = $this->db->table('menu_items m')->select('m.*, c.name AS category, c.slug AS cat_slug')
            ->join('categories c', 'c.id = m.category_id', 'left')
            ->orderBy('c.sort_order')->orderBy('m.sort_order')->orderBy('m.name')->get()->getResultArray();

        $jsItems = array_map(static fn ($i) => [
            'id' => (int) $i['id'], 'name' => $i['name'], 'cat' => $i['cat_slug'], 'category' => $i['category'],
            'price' => (float) $i['price'], 'prep' => (int) $i['prep_minutes'], 'desc' => $i['description'],
            'photo' => img_url($i['photo']), 'available' => (bool) $i['is_available'],
            'options' => json_decode($i['options'] ?: '[]', true) ?: [],
        ], $items);

        $tables  = table_effective_statuses();
        $scanned    = null;
        $tableError = null;
        if ($code = $this->request->getGet('table')) {
            $scanned = $this->orders->tableByCode($code);
            if (! $scanned) {
                $tableError = 'We couldn’t find table “' . mb_substr($code, 0, 10) . '”. Please pick your table below or ask our staff.';
            }
        }

        $gcashQr = setting('gcash_qr');
        return view('menu', [
            'title'      => 'Menu — Salo',
            'bodyClass'  => 'menu-page',
            'categories' => $cats,
            'items'      => $items,
            'tableError' => $tableError,
            'jsData'     => [
                'items'   => $jsItems,
                'tables'  => array_map(static fn ($t) => ['code' => $t['code'], 'zone' => $t['zone'], 'seats' => (int) $t['seats'], 'status' => $t['effective']], $tables),
                'scanned' => $scanned ? ['code' => $scanned['code'], 'zone' => $scanned['zone'], 'seats' => (int) $scanned['seats']] : null,
                'type'    => $this->request->getGet('type') === 'take_away' ? 'take_away' : null,
                'methods' => enabled_payment_methods(),
                'gcash'   => ['name' => setting('gcash_name'), 'number' => setting('gcash_number'), 'qr' => $gcashQr ? img_url($gcashQr) : null],
                'banks'   => array_map(static fn ($b) => ['id' => (int) $b['id'], 'bank' => $b['bank_name'], 'name' => $b['account_name'], 'number' => $b['account_number']], bank_accounts()),
                'i18n'    => [
                    'add' => t('add'), 'sold_out' => t('sold_out'), 'min' => t('min'), 'required' => t('required'), 'choose_any' => t('choose_any'),
                    'add_to_tray' => t('add_to_tray'), 'tray_empty' => t('tray_empty'), 'place_order' => t('place_order'),
                    'add_to_table' => t('add_to_table'), 'seated_at' => t('seated_at', ['{code}', '{zone}', '{seats}']),
                    'request_sent' => t('request_sent'), 'note' => t('note'), 'note_ph' => t('note_ph'),
                ],
            ],
        ]);
    }

    /** Table status for the guest's device (used after scanning or when the page reloads). */
    public function tableInfo(string $code)
    {
        $table = $this->orders->tableByCode($code);
        if (! $table) {
            return $this->jsonError('Table not found', 404);
        }
        $eff = array_values(array_filter(table_effective_statuses(), static fn ($t) => $t['id'] == $table['id']))[0] ?? $table;
        return $this->json([
            'ok'          => true,
            'table'       => ['code' => $table['code'], 'zone' => $table['zone'], 'seats' => (int) $table['seats'], 'status' => $eff['effective'] ?? $table['status']],
            'hasVisit'    => (bool) $this->orders->openVisit((int) $table['id']),
            'canPayLater' => $this->orders->canPayLater((int) $table['id']),
        ]);
    }

    public function checkPromo()
    {
        try {
            $cart          = json_decode((string) $this->request->getPost('cart'), true) ?: [];
            $priced        = $this->orders->priceCart($cart);
            [$disc, $promo] = $this->orders->checkPromo((string) $this->request->getPost('code'), $priced['subtotal']);
            return $this->json(['ok' => true, 'code' => $promo['code'], 'discount' => $disc, 'subtotal' => $priced['subtotal'],
                'message' => 'Promo ' . $promo['code'] . ' applied: −' . peso($disc)]);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    public function serviceRequest()
    {
        $type  = (string) $this->request->getPost('type');
        $table = $this->orders->tableByCode((string) $this->request->getPost('table'));
        if (! $table || ! in_array($type, ['call_staff', 'bill', 'water'], true)) {
            return $this->jsonError('Please set your table first.');
        }
        // Don't stack duplicates: one open request per type per table.
        $open = $this->db->table('service_requests')->where(['table_id' => $table['id'], 'type' => $type, 'status' => 'open'])->countAllResults();
        $visit = $this->orders->openVisit((int) $table['id']);
        if (! $open) {
            $this->db->table('service_requests')->insert([
                'table_id' => $table['id'], 'visit_id' => $visit['id'] ?? null, 'type' => $type, 'status' => 'open', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        if ($type === 'bill' && $visit) {
            $this->db->table('table_visits')->where('id', $visit['id'])->update(['status' => 'bill_requested']);
        }
        return $this->json(['ok' => true, 'message' => t('request_sent')]);
    }
}
