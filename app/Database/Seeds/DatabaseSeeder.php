<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Base data: staff accounts, categories, menu, tables, payment settings, a promo and sample reviews.
 * Run: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Staff accounts (change these passwords after first login) ----
        $users = [
            ['Salo Admin', 'admin@dineflow.test', 'Admin@12345', 'admin'],
            ['Front Cashier', 'cashier@dineflow.test', 'Cashier@12345', 'cashier'],
            ['Kitchen Pass', 'kitchen@dineflow.test', 'Kitchen@12345', 'kitchen'],
        ];
        foreach ($users as [$name, $email, $pass, $role]) {
            $this->db->table('users')->insert([
                'name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                'role' => $role, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- Categories ----
        $cats = ['Starters', 'Mains', 'Grill', 'Desserts', 'Drinks'];
        $catIds = [];
        foreach ($cats as $i => $c) {
            $this->db->table('categories')->insert(['name' => $c, 'slug' => strtolower($c), 'sort_order' => $i + 1]);
            $catIds[$c] = $this->db->insertID();
        }

        $spice = ['name' => 'Spice level', 'type' => 'single', 'required' => true, 'choices' => [
            ['label' => 'Mild', 'price' => 0], ['label' => 'Medium', 'price' => 0], ['label' => 'Hot', 'price' => 0],
        ]];
        $rice = ['name' => 'Add-ons', 'type' => 'multi', 'required' => false, 'choices' => [
            ['label' => 'Extra rice', 'price' => 40], ['label' => 'Garlic rice', 'price' => 60], ['label' => 'Fried egg', 'price' => 30],
        ]];
        $size = ['name' => 'Size', 'type' => 'single', 'required' => true, 'choices' => [
            ['label' => 'Regular', 'price' => 0], ['label' => 'Large', 'price' => 40],
        ]];

        // name, category, price, prep, description, photo, options
        $items = [
            ['Lumpiang Shanghai', 'Starters', 285, 12, 'Twelve crisp pork spring rolls, sweet chili dip.', 'lumpia.jpg', []],
            ['Kinilaw na Tanigue', 'Starters', 340, 10, 'Tanigue cured in cane vinegar and coconut cream, shallot, ginger, chili.', 'kinilaw.jpg', [$spice]],
            ['Sinigang na Baboy', 'Mains', 395, 18, 'Pork belly in a sour tamarind broth with kangkong, radish and long beans.', 'sinigang.jpg', [$rice]],
            ['Kare-Kare sa Bagoong', 'Mains', 520, 22, 'Oxtail and tripe in a toasted peanut sauce, served with sautéed shrimp paste.', 'kare-kare.jpg', [$rice]],
            ['Pancit Bihon Guisado', 'Mains', 320, 14, 'Rice noodles wok-tossed with chicken, shrimp, cabbage and calamansi.', 'pancit.jpg', []],
            ['Adobong Baka sa Gata', 'Mains', 465, 20, 'Beef braised in vinegar, soy and coconut milk with siling labuyo.', 'adobo.jpg', [$spice, $rice]],
            ['Inasal na Manok', 'Grill', 420, 24, 'Bacolod-style chicken marinated in lemongrass and annatto, grilled over coals.', 'inasal.jpg', [$rice]],
            ['Crispy Pata', 'Grill', 780, 30, 'Twice-cooked pork knuckle, soy-vinegar dip. Good for three.', 'crispy-pata.jpg', [$rice]],
            ['Leche Flan', 'Desserts', 180, 5, 'Silky egg custard under a dark caramel.', 'leche-flan.jpg', []],
            ['Halo-Halo', 'Desserts', 220, 8, 'Shaved ice, sweet beans, ube halaya, leche flan and evaporated milk.', 'halo-halo.jpg', []],
            ['Calamansi Iced Tea', 'Drinks', 120, 3, 'House-brewed tea, fresh calamansi, muscovado.', 'iced-tea.jpg', [$size]],
            ['Tablea Tsokolate', 'Drinks', 150, 5, 'Thick Batangas tablea hot chocolate, whisked with a batirol.', 'tsokolate.jpg', []],
        ];
        foreach ($items as $i => [$name, $cat, $price, $prep, $desc, $photo, $options]) {
            $this->db->table('menu_items')->insert([
                'category_id' => $catIds[$cat], 'name' => $name, 'description' => $desc, 'price' => $price,
                'prep_minutes' => $prep, 'photo' => 'uploads/menu/' . $photo, 'options' => json_encode($options),
                'is_available' => 1, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- Tables ----
        $tables = [
            ['P01', 10, 'Private'],
            ['T01', 2, 'Main Hall'], ['T02', 2, 'Main Hall'], ['T03', 4, 'Main Hall'], ['T04', 4, 'Main Hall'],
            ['T05', 4, 'Main Hall'], ['T06', 6, 'Main Hall'],
            ['T07', 6, 'Terrace'], ['T08', 4, 'Terrace'], ['T09', 4, 'Terrace'], ['T10', 2, 'Terrace'],
        ];
        foreach ($tables as $i => [$code, $seats, $zone]) {
            $this->db->table('dining_tables')->insert([
                'code' => $code, 'seats' => $seats, 'zone' => $zone, 'status' => 'available', 'sort_order' => $i + 1, 'updated_at' => $now,
            ]);
        }

        // ---- Payment settings ----
        $settings = [
            'cash_enabled' => '1', 'gcash_enabled' => '1', 'bank_enabled' => '1',
            'gcash_name' => 'SALO MODERN FILIPINO KITCHEN', 'gcash_number' => '0917 555 0101', 'gcash_qr' => '',
            'deposit_amount' => '2000', 'deposit_min_guests' => '8',
        ];
        foreach ($settings as $k => $v) {
            $this->db->table('settings')->insert(['key' => $k, 'value' => $v]);
        }
        $banks = [
            ['BDO', 'Salo Kitchen Inc.', '0012 3456 7890'],
            ['BPI', 'Salo Kitchen Inc.', '1234 5678 90'],
            ['Metrobank', 'Salo Kitchen Inc.', '123 4 56789012 3'],
            ['UnionBank', 'Salo Kitchen Inc.', '1094 2000 5555'],
        ];
        foreach ($banks as $i => [$b, $n, $no]) {
            $this->db->table('bank_accounts')->insert(['bank_name' => $b, 'account_name' => $n, 'account_number' => $no, 'is_active' => 1, 'sort_order' => $i + 1]);
        }

        // ---- Promo ----
        $this->db->table('promo_codes')->insert([
            'code' => 'SALO10', 'type' => 'percent', 'value' => 10, 'min_order' => 800,
            'expires_at' => date('Y-m-d', strtotime('+90 days')), 'usage_limit' => 200, 'used_count' => 0, 'is_active' => 1, 'created_at' => $now,
        ]);

        // ---- Sample approved reviews for the landing page ----
        $reviews = [
            ['Bea R.', 5, 'The kare-kare tastes like Sunday at my lola\'s, only better plated. Ordering from the table was effortless.'],
            ['Miguel S.', 5, 'Crispy pata for three that actually fed three. Paid with GCash and the food was out in 25 minutes.'],
            ['Anne L.', 4, 'Lovely room, warm service. The halo-halo is a must. Would book the terrace again.'],
        ];
        foreach ($reviews as [$n, $r, $c]) {
            $this->db->table('reviews')->insert(['guest_name' => $n, 'rating' => $r, 'comment' => $c, 'is_approved' => 1, 'created_at' => $now]);
        }
    }
}
