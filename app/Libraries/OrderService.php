<?php

namespace App\Libraries;

use Config\Database;
use RuntimeException;

/**
 * All order/pricing/payment rules live here so guest and staff controllers share them.
 *
 * Payment-first rule: an order reaches the kitchen (released_at set) only when
 *  - payment_status becomes 'paid' (cash confirmed, or GCash/bank approved), or
 *  - it is a dine-in "pay at the end" tab order added to an already-open, already-paid table visit.
 */
class OrderService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
        helper('dineflow');
    }

    public function generateCode(string $table = 'orders'): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while ($this->db->table($table)->where('code', $code)->countAllResults() > 0);
        return $code;
    }

    public function tableByCode(?string $code): ?array
    {
        if (! $code) {
            return null;
        }
        return $this->db->table('dining_tables')->where('code', strtoupper(trim($code)))->get()->getRowArray();
    }

    public function openVisit(int $tableId): ?array
    {
        return $this->db->table('table_visits')->where('table_id', $tableId)->whereIn('status', ['open', 'bill_requested'])
            ->orderBy('id', 'DESC')->get()->getRowArray();
    }

    /** A table may run a tab once at least one order in the open visit is already paid. */
    public function canPayLater(int $tableId): bool
    {
        $visit = $this->openVisit($tableId);
        if (! $visit) {
            return false;
        }
        return $this->db->table('orders')->where('visit_id', $visit['id'])->where('payment_status', 'paid')->countAllResults() > 0;
    }

    /**
     * Validates and prices a cart. Each line: {id, qty, choices: {groupIndex: [choiceIndex,...]}, note}
     * Returns ['lines' => [...], 'subtotal' => float, 'minutes' => int].
     */
    public function priceCart(array $cart): array
    {
        if (! $cart) {
            throw new RuntimeException('Your tray is empty.');
        }
        if (count($cart) > 40) {
            throw new RuntimeException('Too many lines in one order.');
        }
        $ids   = array_unique(array_map(static fn ($l) => (int) ($l['id'] ?? 0), $cart));
        $items = [];
        foreach ($this->db->table('menu_items')->whereIn('id', $ids ?: [0])->get()->getResultArray() as $it) {
            $items[$it['id']] = $it;
        }

        $lines    = [];
        $subtotal = 0.0;
        foreach ($cart as $l) {
            $item = $items[(int) ($l['id'] ?? 0)] ?? null;
            if (! $item) {
                throw new RuntimeException('An item in your tray is no longer on the menu.');
            }
            if (! $item['is_available']) {
                throw new RuntimeException($item['name'] . ' just sold out — please remove it from your tray.');
            }
            $qty = max(1, min(50, (int) ($l['qty'] ?? 1)));

            $groups    = json_decode($item['options'] ?: '[]', true) ?: [];
            $picked    = is_array($l['choices'] ?? null) ? $l['choices'] : [];
            $unit      = (float) $item['price'];
            $selected  = [];
            $textParts = [];
            foreach ($groups as $gi => $g) {
                $choiceIdx = array_values(array_unique(array_map('intval', (array) ($picked[$gi] ?? []))));
                if (($g['type'] ?? 'single') === 'single' && count($choiceIdx) > 1) {
                    $choiceIdx = [$choiceIdx[0]];
                }
                if (! empty($g['required']) && ! $choiceIdx) {
                    throw new RuntimeException('Please choose ' . strtolower($g['name']) . ' for ' . $item['name'] . '.');
                }
                $labels = [];
                foreach ($choiceIdx as $ci) {
                    $c = $g['choices'][$ci] ?? null;
                    if (! $c) {
                        throw new RuntimeException('An option for ' . $item['name'] . ' changed — please re-add it.');
                    }
                    $unit      += (float) ($c['price'] ?? 0);
                    $selected[] = ['group' => $g['name'], 'label' => $c['label'], 'price' => (float) ($c['price'] ?? 0)];
                    $labels[]   = $c['label'] . ((float) ($c['price'] ?? 0) > 0 ? ' (+' . peso0($c['price']) . ')' : '');
                }
                if ($labels) {
                    $textParts[] = $g['name'] . ': ' . implode(', ', $labels);
                }
            }
            $note = trim(mb_substr(strip_tags((string) ($l['note'] ?? '')), 0, 200));

            $lineTotal = round($unit * $qty, 2);
            $subtotal += $lineTotal;
            $lines[]   = [
                'menu_item_id' => $item['id'],
                'name'         => $item['name'],
                'unit_price'   => $unit,
                'quantity'     => $qty,
                'options_json' => json_encode($selected),
                'options_text' => implode(' · ', $textParts) ?: null,
                'note'         => $note ?: null,
                'line_total'   => $lineTotal,
                'prep_minutes' => (int) $item['prep_minutes'],
            ];
        }
        return ['lines' => $lines, 'subtotal' => round($subtotal, 2), 'minutes' => $this->estimateMinutes($lines)];
    }

    /** Longest dish + 2 min per extra line, capped at +10. */
    public function estimateMinutes(array $lines): int
    {
        if (! $lines) {
            return 10;
        }
        $max = max(array_map(static fn ($l) => (int) $l['prep_minutes'], $lines));
        return $max + min(10, 2 * (count($lines) - 1));
    }

    /** Returns [discountAmount, promoRow]. */
    public function checkPromo(string $code, float $subtotal): array
    {
        $code  = strtoupper(trim($code));
        $promo = $this->db->table('promo_codes')->where('code', $code)->get()->getRowArray();
        if (! $promo || ! $promo['is_active']) {
            throw new RuntimeException('That promo code is not valid.');
        }
        if ($promo['expires_at'] && $promo['expires_at'] < date('Y-m-d')) {
            throw new RuntimeException('That promo code has expired.');
        }
        if ($promo['usage_limit'] !== null && (int) $promo['used_count'] >= (int) $promo['usage_limit']) {
            throw new RuntimeException('That promo code has reached its usage limit.');
        }
        if ($subtotal < (float) $promo['min_order']) {
            throw new RuntimeException('This code needs a minimum order of ' . peso0($promo['min_order']) . '.');
        }
        $discount = $promo['type'] === 'percent' ? $subtotal * ((float) $promo['value'] / 100) : (float) $promo['value'];
        return [round(min($discount, $subtotal), 2), $promo];
    }

    /** Sends a paid (or tab) order to the kitchen. */
    public function release(array $order): void
    {
        if ($order['released_at']) {
            return;
        }
        $lines   = $this->db->table('order_items')->where('order_id', $order['id'])->get()->getResultArray();
        $minutes = $this->estimateMinutes($lines);
        $now     = time();
        $this->db->table('orders')->where('id', $order['id'])->update([
            'released_at'        => date('Y-m-d H:i:s', $now),
            'estimated_ready_at' => date('Y-m-d H:i:s', $now + $minutes * 60),
            'updated_at'         => date('Y-m-d H:i:s', $now),
        ]);
    }

    public function find(int $id): ?array
    {
        return $this->db->table('orders')->where('id', $id)->get()->getRowArray();
    }

    public function findByCode(string $code): ?array
    {
        return $this->db->table('orders')->where('code', strtoupper(trim($code)))->get()->getRowArray();
    }

    public function items(int $orderId): array
    {
        return $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
    }

    /** Cash received, or a GCash/bank payment approved. */
    public function markPaid(array $order, ?int $userId, ?string $method = null, ?string $reference = null): void
    {
        $now  = date('Y-m-d H:i:s');
        $data = ['payment_status' => 'paid', 'paid_at' => $now, 'verified_by' => $userId, 'verified_at' => $now,
            'rejection_reason' => null, 'updated_at' => $now];
        if ($method) {
            $data['payment_method'] = $method;
        }
        if ($reference !== null && $reference !== '') {
            $data['reference_no'] = $reference;
        }
        $this->db->table('orders')->where('id', $order['id'])->update($data);
        $this->release($this->find((int) $order['id']));
        $this->attachToVisit($this->find((int) $order['id']));
    }

    /** Dine-in orders join (or open) the table's visit; the table becomes Occupied. */
    public function attachToVisit(array $order): ?int
    {
        if ($order['type'] !== 'dine_in' || ! $order['table_id']) {
            return null;
        }
        if ($order['visit_id']) {
            return (int) $order['visit_id'];
        }
        $visit = $this->openVisit((int) $order['table_id']);
        if (! $visit) {
            $this->db->table('table_visits')->insert(['table_id' => $order['table_id'], 'status' => 'open', 'opened_at' => date('Y-m-d H:i:s')]);
            $visitId = (int) $this->db->insertID();
        } else {
            $visitId = (int) $visit['id'];
        }
        $this->db->table('orders')->where('id', $order['id'])->update(['visit_id' => $visitId]);
        $this->db->table('dining_tables')->where('id', $order['table_id'])->update(['status' => 'occupied', 'updated_at' => date('Y-m-d H:i:s')]);
        return $visitId;
    }

    /** Kitchen flow: received → preparing → ready → served. Returns an error message or null. */
    public function changeStatus(array $order, string $status): ?string
    {
        $flow = ['preparing' => ['received'], 'ready' => ['received', 'preparing'], 'served' => ['ready', 'preparing', 'received']];
        if (! isset($flow[$status])) {
            return 'Unknown status.';
        }
        if (! $order['released_at']) {
            return 'This order is not paid yet — it can’t be cooked.';
        }
        if (! in_array($order['status'], $flow[$status], true)) {
            return 'Order ' . $order['code'] . ' is already ' . $order['status'] . '.';
        }
        $now  = date('Y-m-d H:i:s');
        $data = ['status' => $status, 'updated_at' => $now, $status . '_at' => $now];
        $this->db->table('orders')->where('id', $order['id'])->update($data);
        if ($status === 'ready') {
            (new Notifier())->orderReady($order);
        }
        return null;
    }

    /** Applies (or replaces) a discount and recomputes the total. */
    public function applyDiscount(array $order, string $type, float $amount, array $extra = []): void
    {
        $amount = round(min($amount, (float) $order['subtotal']), 2);
        $this->db->table('orders')->where('id', $order['id'])->update(array_merge([
            'discount_type'   => $type,
            'discount_amount' => $amount,
            'total'           => round((float) $order['subtotal'] - $amount, 2),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], $extra));
    }

    /** Everything a table has ordered during a visit. */
    public function visitBill(int $visitId): array
    {
        $visit  = $this->db->table('table_visits')->where('id', $visitId)->get()->getRowArray();
        $orders = $this->db->table('orders')->where('visit_id', $visitId)->where('status !=', 'cancelled')->orderBy('id')->get()->getResultArray();
        $total = $paid = $unpaid = 0.0;
        foreach ($orders as &$o) {
            $o['items'] = $this->items((int) $o['id']);
            $total += (float) $o['total'];
            if ($o['payment_status'] === 'paid') {
                $paid += (float) $o['total'];
            } else {
                $unpaid += (float) $o['total'];
            }
        }
        $credit = (float) ($visit['deposit_credit'] ?? 0);
        return [
            'visit' => $visit, 'orders' => $orders, 'total' => $total, 'paid' => $paid, 'unpaid' => $unpaid,
            'deposit_credit' => $credit, 'balance' => max(0, round($unpaid - $credit, 2)),
        ];
    }
}
