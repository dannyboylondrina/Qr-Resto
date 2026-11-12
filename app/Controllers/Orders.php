<?php

namespace App\Controllers;

use RuntimeException;
use Throwable;

/** Guest order placement. */
class Orders extends BaseController
{
    public function place()
    {
        // Basic abuse protection: 10 orders per minute per device/IP.
        $throttler = service('throttler');
        if (! $throttler->check('order-' . md5($this->request->getIPAddress()), 10, MINUTE)) {
            return $this->jsonError('Too many orders at once — please wait a moment.', 429);
        }

        $p      = $this->request->getPost();
        $type   = ($p['type'] ?? '') === 'take_away' ? 'take_away' : 'dine_in';
        $method = (string) ($p['payment_method'] ?? '');
        $name   = trim(mb_substr(strip_tags((string) ($p['name'] ?? '')), 0, 120));
        $mobile = trim(preg_replace('/[^0-9+ \-]/', '', (string) ($p['mobile'] ?? '')));
        $email  = trim((string) ($p['email'] ?? ''));
        $guests = max(1, min(30, (int) ($p['guests'] ?? 1)));
        $ref    = trim(mb_substr(preg_replace('/[^A-Za-z0-9\- ]/', '', (string) ($p['reference_no'] ?? '')), 0, 60));

        try {
            $table = null;
            if ($type === 'dine_in') {
                $table = $this->orders->tableByCode((string) ($p['table'] ?? ''));
                if (! $table) {
                    throw new RuntimeException('Please choose your table (check the number card on your table, or ask our staff).');
                }
            } else {
                if ($name === '' || strlen(preg_replace('/\D/', '', $mobile)) < 10) {
                    throw new RuntimeException('For take-away, please enter your name and a valid mobile number.');
                }
            }
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email or leave it blank.');
            }

            $allowed = enabled_payment_methods();
            if ($method === 'pay_later') {
                if (! $table || ! $this->orders->canPayLater((int) $table['id'])) {
                    throw new RuntimeException('“Pay at the end” is available after your table’s first paid order.');
                }
            } elseif (! in_array($method, $allowed, true)) {
                throw new RuntimeException('Please choose a payment method.');
            }

            $cart   = json_decode((string) ($p['cart'] ?? '[]'), true) ?: [];
            $priced = $this->orders->priceCart($cart);

            $discount = 0.0;
            $promo    = null;
            if (! empty($p['promo_code'])) {
                [$discount, $promo] = $this->orders->checkPromo((string) $p['promo_code'], $priced['subtotal']);
            }

            // GCash / bank: reference + screenshot together, or neither (guest can send them later from the tracking page).
            $proofPath = null;
            $bankId    = null;
            $paymentStatus = 'awaiting';
            if (in_array($method, ['gcash', 'online_banking'], true)) {
                $file = $this->request->getFile('proof');
                $hasFile = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
                if ($ref !== '' || $hasFile) {
                    if ($ref === '' || ! $hasFile) {
                        throw new RuntimeException('Please enter the reference number AND upload the screenshot of your payment.');
                    }
                    $proofPath     = save_uploaded_image($file, 'proofs');
                    $paymentStatus = 'for_verification';
                }
                if ($method === 'online_banking' && ! empty($p['bank_account_id'])) {
                    $bankId = (int) $p['bank_account_id'];
                }
            } elseif ($method === 'pay_later') {
                $paymentStatus = 'tab';
            }

            $now  = date('Y-m-d H:i:s');
            $code = $this->orders->generateCode();

            $this->db->transStart();
            $this->db->table('orders')->insert([
                'code' => $code, 'type' => $type, 'table_id' => $table['id'] ?? null, 'guests' => $type === 'dine_in' ? $guests : 1,
                'customer_name' => $name ?: null, 'customer_mobile' => $mobile ?: null, 'customer_email' => $email ?: null,
                'subtotal' => $priced['subtotal'], 'discount_type' => $promo ? 'promo' : 'none', 'discount_amount' => $discount,
                'promo_code' => $promo['code'] ?? null, 'total' => round($priced['subtotal'] - $discount, 2),
                'status' => 'received', 'payment_method' => $method, 'payment_status' => $paymentStatus,
                'bank_account_id' => $bankId, 'reference_no' => $ref ?: null, 'proof_image' => $proofPath,
                'payment_submitted_at' => $paymentStatus === 'for_verification' ? $now : null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $orderId = (int) $this->db->insertID();
            foreach ($priced['lines'] as $line) {
                $line['order_id'] = $orderId;
                $this->db->table('order_items')->insert($line);
            }
            if ($promo) {
                $this->db->table('promo_codes')->where('id', $promo['id'])->set('used_count', 'used_count + 1', false)->update();
            }
            $this->db->transComplete();
            if (! $this->db->transStatus()) {
                throw new RuntimeException('Could not save your order — please try again.');
            }

            $order = $this->orders->find($orderId);
            $this->orders->attachToVisit($order);
            if ($paymentStatus === 'tab') {
                $this->orders->release($this->orders->find($orderId)); // tab orders go straight to the kitchen
            }

            $mine   = (array) session('my_orders');
            $mine[] = $code;
            session()->set('my_orders', array_slice(array_unique($mine), -10));

            return $this->json(['ok' => true, 'code' => $code, 'redirect' => site_url('track/' . $code)]);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Order placement failed: ' . $e->getMessage());
            return $this->jsonError('Something went wrong placing your order. Please try again or ask our staff.', 500);
        }
    }
}
