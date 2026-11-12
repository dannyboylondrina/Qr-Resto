<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Kitchen extends BaseController
{
    public function index()
    {
        $items = $this->db->table('menu_items m')->select('m.id, m.name, m.is_available, c.name AS category')
            ->join('categories c', 'c.id = m.category_id', 'left')->orderBy('c.sort_order')->orderBy('m.sort_order')->get()->getResultArray();
        return view('staff/kitchen/index', ['title' => 'Kitchen display', 'active' => 'kitchen', 'bodyClass' => 'kitchen-body',
            'tickets' => $this->tickets(), 'items' => $items]);
    }

    public function board()
    {
        return view('staff/kitchen/_board', ['tickets' => $this->tickets()]);
    }

    private function tickets(): array
    {
        $orders = $this->db->table('orders o')->select('o.*, t.code AS table_code')->join('dining_tables t', 't.id = o.table_id', 'left')
            ->where('o.released_at IS NOT NULL')->whereIn('o.status', ['received', 'preparing'])
            ->orderBy('o.released_at', 'ASC')->get()->getResultArray();
        foreach ($orders as &$o) {
            $o['items'] = $this->orders->items((int) $o['id']);
        }
        return $orders;
    }

    public function setStatus(int $id, string $status)
    {
        $o = $this->orders->find($id);
        if (! $o) {
            return $this->reply('Order not found.', false);
        }
        if ($err = $this->orders->changeStatus($o, $status)) {
            return $this->reply($err, false);
        }
        return $this->reply(($o['type'] === 'dine_in' ? 'Order ' : 'Take-away ') . $o['code'] . ' → ' . order_status_label($status));
    }

    public function toggleItem(int $id)
    {
        $item = $this->db->table('menu_items')->where('id', $id)->get()->getRowArray();
        if (! $item) {
            return $this->reply('Item not found.', false);
        }
        $new = $item['is_available'] ? 0 : 1;
        $this->db->table('menu_items')->where('id', $id)->update(['is_available' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($new ? 'item_back_in_stock' : 'item_sold_out', 'menu_item', $id, $item['name']);
        return $this->reply($item['name'] . ($new ? ' is available again.' : ' marked as sold out.'));
    }
}
