<?php

namespace App\Libraries;

use Config\Database;
use Throwable;

/**
 * Guest notifications by SMS (Semaphore) and email (SMTP).
 * When a channel is not configured in .env the message is only recorded in the `notifications` table.
 */
class Notifier
{
    public function notify(?string $mobile, ?string $email, string $subject, string $message): void
    {
        if ($mobile) {
            $this->sms($mobile, $message);
        }
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email($email, $subject, nl2br(esc($message)));
        }
    }

    public function sms(string $mobile, string $message): void
    {
        $key    = env('sms.semaphoreKey');
        $status = 'logged';
        $error  = null;
        if ($key) {
            try {
                $res = service('curlrequest', ['timeout' => 10, 'http_errors' => false])->post('https://api.semaphore.co/api/v4/messages', [
                    'form_params' => [
                        'apikey' => $key, 'number' => preg_replace('/[^0-9+]/', '', $mobile),
                        'message' => mb_substr($message, 0, 450), 'sendername' => env('sms.senderName', 'SALO'),
                    ],
                ]);
                $status = $res->getStatusCode() < 300 ? 'sent' : 'failed';
                $error  = $status === 'failed' ? mb_substr($res->getBody(), 0, 250) : null;
            } catch (Throwable $e) {
                $status = 'failed';
                $error  = mb_substr($e->getMessage(), 0, 250);
            }
        }
        $this->record('sms', $mobile, null, $message, $status, $error);
    }

    /** Returns true when actually sent via SMTP. */
    public function email(string $to, string $subject, string $html): bool
    {
        $status = 'logged';
        $error  = null;
        if (env('email.SMTPHost')) {
            try {
                $email = service('email');
                $email->setFrom(env('email.fromEmail', 'no-reply@salo.ph'), env('email.fromName', 'Salo Kitchen'));
                $email->setTo($to);
                $email->setSubject($subject);
                $email->setMessage('<div style="font-family:Arial,sans-serif;font-size:15px;color:#1c1917">' . $html . '</div>');
                $status = $email->send(false) ? 'sent' : 'failed';
                if ($status === 'failed') {
                    $error = mb_substr(strip_tags($email->printDebugger(['headers'])), 0, 250);
                }
            } catch (Throwable $e) {
                $status = 'failed';
                $error  = mb_substr($e->getMessage(), 0, 250);
            }
        }
        $this->record('email', $to, $subject, strip_tags(str_replace(['<br>', '<br />'], "\n", $html)), $status, $error);
        return $status === 'sent';
    }

    private function record(string $channel, string $to, ?string $subject, string $message, string $status, ?string $error): void
    {
        Database::connect()->table('notifications')->insert([
            'channel' => $channel, 'recipient' => $to, 'subject' => $subject, 'message' => $message,
            'status' => $status, 'error' => $error, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ---- Ready-made guest messages ----

    public function orderPaymentApproved(array $o): void
    {
        $this->notify($o['customer_mobile'], $o['customer_email'], 'Payment approved — order ' . $o['code'],
            "Salo: Your payment for order {$o['code']} (" . peso($o['total']) . ") is approved. The kitchen is now cooking. Track: " . site_url('track/' . $o['code']));
    }

    public function orderPaymentRejected(array $o, string $reason): void
    {
        $this->notify($o['customer_mobile'], $o['customer_email'], 'Payment not approved — order ' . $o['code'],
            "Salo: We couldn't verify the payment for order {$o['code']}. Reason: {$reason}. Please re-submit here: " . site_url('track/' . $o['code']));
    }

    public function orderReady(array $o): void
    {
        $where = $o['type'] === 'take_away' ? 'Please collect it at the counter.' : 'It is on its way to your table.';
        $this->notify($o['customer_mobile'], $o['customer_email'], 'Your order ' . $o['code'] . ' is ready', "Salo: Order {$o['code']} is ready! {$where}");
    }

    public function reservationStatus(array $r, string $status, string $extra = ''): void
    {
        $when = date('D, M j', strtotime($r['res_date'])) . ' at ' . date('g:i A', strtotime($r['res_time']));
        $msg  = $status === 'confirmed'
            ? "Salo: Your reservation {$r['code']} for {$r['guests']} on {$when} is confirmed. We hold tables for 15 minutes. See you!"
            : "Salo: Your reservation {$r['code']} for {$when} has been cancelled. {$extra} Questions? Call +63 2 8555 0101.";
        $this->notify($r['mobile'], $r['email'], 'Reservation ' . $r['code'] . ' ' . $status, trim($msg));
    }
}
