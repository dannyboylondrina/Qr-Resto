<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use RuntimeException;

class MenuAdmin extends BaseController
{
    public function index()
    {
        $items = $this->db->table('menu_items m')->select('m.*, c.name AS category')->join('categories c', 'c.id = m.category_id', 'left')
            ->orderBy('c.sort_order')->orderBy('m.sort_order')->orderBy('m.name')->get()->getResultArray();
        $cats = $this->db->query('SELECT c.*, (SELECT COUNT(*) FROM menu_items m WHERE m.category_id = c.id) AS n FROM categories c ORDER BY sort_order')->getResultArray();
        return view('staff/menu/index', ['title' => 'Manage menu', 'active' => 'menu', 'items' => $items, 'cats' => $cats]);
    }

    public function form(?int $id = null)
    {
        $item = $id ? $this->db->table('menu_items')->where('id', $id)->get()->getRowArray() : null;
        if ($id && ! $item) {
            return redirect()->to(site_url('staff/menu'))->with('error', 'Item not found.');
        }
        return view('staff/menu/form', [
            'title'   => $item ? 'Edit ' . $item['name'] : 'New menu item', 'active' => 'menu', 'item' => $item,
            'cats'    => $this->db->table('categories')->orderBy('sort_order')->get()->getResultArray(),
            'optText' => $item ? self::optionsToText(json_decode($item['options'] ?: '[]', true) ?: []) : '',
        ]);
    }

    public function save()
    {
        $p  = $this->request->getPost();
        $id = (int) ($p['id'] ?? 0);
        if (! $this->validate([
            'name' => 'required|max_length[120]', 'category_id' => 'required|is_natural_no_zero',
            'price' => 'required|decimal|greater_than_equal_to[0]', 'prep_minutes' => 'required|is_natural_no_zero|less_than[240]',
            'photo_url' => 'permit_empty|valid_url_strict|max_length[500]',
        ])) {
            return redirect()->back()->withInput()->with('error', first_error($this->validator->getErrors()));
        }
        try {
            $options = self::textToOptions((string) ($p['options'] ?? ''));
            $photo   = save_uploaded_image($this->request->getFile('photo'), 'menu');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        $data = [
            'name' => trim(strip_tags($p['name'])), 'category_id' => (int) $p['category_id'], 'price' => round((float) $p['price'], 2),
            'description' => trim(strip_tags((string) ($p['description'] ?? ''))), 'prep_minutes' => (int) $p['prep_minutes'],
            'options' => json_encode($options), 'is_available' => empty($p['is_available']) ? 0 : 1,
            'sort_order' => (int) ($p['sort_order'] ?? 0), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($photo) {
            $data['photo'] = $photo;
        } elseif (! empty($p['photo_url'])) {
            $data['photo'] = $p['photo_url'];
        }
        if ($id) {
            $this->db->table('menu_items')->where('id', $id)->update($data);
            log_activity('menu_item_updated', 'menu_item', $id, $data['name'] . ' · ' . peso($data['price']));
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('menu_items')->insert($data);
            $id = (int) $this->db->insertID();
            log_activity('menu_item_created', 'menu_item', $id, $data['name'] . ' · ' . peso($data['price']));
        }
        return redirect()->to(site_url('staff/menu'))->with('success', $data['name'] . ' saved.');
    }

    public function delete(int $id)
    {
        $item = $this->db->table('menu_items')->where('id', $id)->get()->getRowArray();
        if ($item) {
            $this->db->table('menu_items')->where('id', $id)->delete();
            log_activity('menu_item_deleted', 'menu_item', $id, $item['name']);
        }
        return $this->reply(($item['name'] ?? 'Item') . ' deleted. Past orders keep their copy of it.');
    }

    public function toggle(int $id)
    {
        $item = $this->db->table('menu_items')->where('id', $id)->get()->getRowArray();
        if (! $item) {
            return $this->reply('Item not found.', false);
        }
        $new = $item['is_available'] ? 0 : 1;
        $this->db->table('menu_items')->where('id', $id)->update(['is_available' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($new ? 'item_back_in_stock' : 'item_sold_out', 'menu_item', $id, $item['name']);
        return $this->reply($item['name'] . ($new ? ' is available.' : ' is sold out.'));
    }

    public function saveCategory()
    {
        $id   = (int) $this->request->getPost('id');
        $name = trim(strip_tags((string) $this->request->getPost('name')));
        if ($name === '' || mb_strlen($name) > 80) {
            return $this->reply('Enter a category name.', false);
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'cat';
        $dupe = $this->db->table('categories')->where('slug', $slug)->where('id !=', $id)->countAllResults();
        if ($dupe) {
            return $this->reply('That category already exists.', false);
        }
        $data = ['name' => $name, 'slug' => $slug, 'sort_order' => (int) $this->request->getPost('sort_order')];
        if ($id) {
            $this->db->table('categories')->where('id', $id)->update($data);
        } else {
            $this->db->table('categories')->insert($data);
        }
        log_activity('category_saved', 'category', $id ?: $this->db->insertID(), $name);
        return $this->reply('Category saved.');
    }

    public function deleteCategory(int $id)
    {
        if ($this->db->table('menu_items')->where('category_id', $id)->countAllResults()) {
            return $this->reply('Move or delete the items in this category first.', false);
        }
        $this->db->table('categories')->where('id', $id)->delete();
        log_activity('category_deleted', 'category', $id);
        return $this->reply('Category deleted.');
    }

    // ---- Options: simple text format admins can edit ----
    //   Spice level*: Mild, Medium, Hot          (* = required, pick one)
    //   Add-ons+: Extra rice=40, Fried egg=30     (+ = pick any)
    //   Size: Regular, Large=40                   (optional, pick one)

    public static function textToOptions(string $text): array
    {
        $groups = [];
        foreach (preg_split('/\R/', $text) as $n => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! str_contains($line, ':')) {
                throw new RuntimeException('Options line ' . ($n + 1) . ' needs a colon, e.g. "Spice level*: Mild, Hot".');
            }
            [$head, $list] = array_map('trim', explode(':', $line, 2));
            $multi    = str_ends_with($head, '+');
            $required = str_ends_with($head, '*');
            $name     = trim(rtrim($head, '*+'));
            $choices  = [];
            foreach (array_filter(array_map('trim', explode(',', $list)), 'strlen') as $c) {
                [$label, $price] = array_pad(array_map('trim', explode('=', $c, 2)), 2, '0');
                $choices[] = ['label' => mb_substr(strip_tags($label), 0, 60), 'price' => max(0, round((float) $price, 2))];
            }
            if ($name === '' || ! $choices) {
                throw new RuntimeException('Options line ' . ($n + 1) . ' needs a name and at least one choice.');
            }
            $groups[] = ['name' => mb_substr(strip_tags($name), 0, 60), 'type' => $multi ? 'multi' : 'single', 'required' => $required && ! $multi, 'choices' => $choices];
        }
        return $groups;
    }

    public static function optionsToText(array $groups): string
    {
        return implode("\n", array_map(static function ($g) {
            $suffix = $g['type'] === 'multi' ? '+' : (! empty($g['required']) ? '*' : '');
            return $g['name'] . $suffix . ': ' . implode(', ', array_map(static fn ($c) => $c['label'] . ((float) $c['price'] > 0 ? '=' . (float) $c['price'] : ''), $g['choices']));
        }, $groups));
    }
}
