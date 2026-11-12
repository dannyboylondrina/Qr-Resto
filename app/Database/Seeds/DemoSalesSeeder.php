<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * OPTIONAL demo data: ~35 days of paid, served orders so the dashboard and reports have something to show.
 * Run: php spark db:seed DemoSalesSeeder   (don't run on a live restaurant database)
 */
class DemoSalesSeeder extends Seeder
{
    public function run()
    {
        $items = $this->db->table('menu_items')->get()->getResultArray();
        $tables = $this->db->table('dining_tables')->get()->getResultArray();
        if (! $items || ! $tables) {
            echo "Run DatabaseSeeder first.\n";
            return;
        }
        mt_srand(42);
        $methods = ['cash', 'cash', 'gcash', 'gcash', 'gcash', 'online_banking'];
        $hours = [11, 12, 12, 13, 13, 18, 18, 19, 19, 19, 20, 20, 21];
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        for ($d = 35; $d >= 1; $d--) {
            $day = strtotime("-$d days");
            if (date('N', $day) == 1) {
                continue; // closed Mondays
            }
            $n = mt_rand(8, 22);
            for ($i = 0; $i < $n; $i++) {
                $created = date('Y-m-d', $day) . ' ' . sprintf('%02d:%02d:00', $hours[array_rand($hours)], mt_rand(0, 59));
                $isDine = mt_rand(1, 100) <= 75;
                $table = $isDine ? $tables[array_rand($tables)] : null;
                $lines = [];
                $subtotal = 0;
                $count = mt_rand(1, 4);
                for ($j = 0; $j < $count; $j++) {
                    $it = $items[array_rand($items)];
                    $qty = mt_rand(1, 2);
                    $lines[] = [$it, $qty];
                    $subtotal += $it['price'] * $qty;
                }
                $discType = 'none';
                $disc = 0;
                $r = mt_rand(1, 100);
                if ($r <= 7) {
                    $discType = 'senior';
                    $disc = round($subtotal * 0.2, 2);
                } elseif ($r <= 10) {
                    $discType = 'pwd';
                    $disc = round($subtotal * 0.2, 2);
                } elseif ($r <= 16 && $subtotal >= 800) {
                    $discType = 'promo';
                    $disc = round($subtotal * 0.1, 2);
                }
                $method = $methods[array_rand($methods)];
                do {
                    $code = '';
                    for ($k = 0; $k < 6; $k++) {
                        $code .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
                    }
                } while ($this->db->table('orders')->where('code', $code)->countAllResults() > 0);

                $paidAt = date('Y-m-d H:i:s', strtotime($created) + mt_rand(60, 400));
                $this->db->table('orders')->insert([
                    'code' => $code, 'type' => $isDine ? 'dine_in' : 'take_away', 'table_id' => $table['id'] ?? null,
                    'guests' => $isDine ? mt_rand(1, 6) : 1, 'customer_name' => 'Demo guest',
                    'subtotal' => $subtotal, 'discount_type' => $discType, 'discount_amount' => $disc,
                    'promo_code' => $discType === 'promo' ? 'SALO10' : null, 'total' => $subtotal - $disc,
                    'status' => 'served', 'payment_method' => $method, 'payment_status' => 'paid',
                    'reference_no' => $method === 'cash' ? null : (string) mt_rand(1000000000, 9999999999),
                    'paid_at' => $paidAt, 'released_at' => $paidAt,
                    'estimated_ready_at' => date('Y-m-d H:i:s', strtotime($paidAt) + 1200),
                    'ready_at' => date('Y-m-d H:i:s', strtotime($paidAt) + mt_rand(900, 1800)),
                    'served_at' => date('Y-m-d H:i:s', strtotime($paidAt) + 1900),
                    'created_at' => $created, 'updated_at' => $created,
                ]);
                $oid = $this->db->insertID();
                foreach ($lines as [$it, $qty]) {
                    $this->db->table('order_items')->insert([
                        'order_id' => $oid, 'menu_item_id' => $it['id'], 'name' => $it['name'], 'unit_price' => $it['price'],
                        'quantity' => $qty, 'line_total' => $it['price'] * $qty, 'prep_minutes' => $it['prep_minutes'],
                    ]);
                }
            }
        }
        echo "Demo sales inserted.\n";
    }
}
