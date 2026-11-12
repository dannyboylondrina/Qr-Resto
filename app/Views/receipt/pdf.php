<?php /** Receipt used for PDF download and the staff print view. @var array $o */ ?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Receipt <?= esc($o['code']) ?></title>
<style>
    @page { margin: 14px; }
    body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; color: #000; margin: 0; }
    .c { text-align: center; } h1 { font-family: "DejaVu Serif", Georgia, serif; font-size: 22px; margin: 0; }
    .muted { color: #444; } hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; } td { padding: 2px 0; vertical-align: top; } .r { text-align: right; white-space: nowrap; }
    .big { font-size: 13px; font-weight: bold; } .opt { font-size: 8.5px; color: #333; }
</style></head>
<body>
    <div class="c">
        <h1>Salo</h1>
        <div>MODERN FILIPINO KITCHEN</div>
        <div class="muted">24 Antonio Drive, Legazpi Village, Makati City<br>+63 2 8555 0101</div>
    </div>
    <hr>
    <table>
        <tr><td>Order</td><td class="r"><b><?= esc($o['code']) ?></b></td></tr>
        <tr><td><?= $o['type'] === 'dine_in' ? 'Table' : 'Type' ?></td><td class="r"><?= $o['type'] === 'dine_in' ? esc($o['table']['code'] ?? '-') . ' · Dine-in' : 'Take-away' ?></td></tr>
        <?php if ($o['customer_name']): ?><tr><td>Guest</td><td class="r"><?= esc($o['customer_name']) ?></td></tr><?php endif ?>
        <tr><td>Date</td><td class="r"><?= date('M j, Y g:i A', strtotime($o['paid_at'] ?: $o['created_at'])) ?></td></tr>
    </table>
    <hr>
    <table>
        <?php foreach ($o['items'] as $it): ?>
            <tr><td><?= (int) $it['quantity'] ?> × <?= esc($it['name']) ?>
                <?php if ($it['options_text']): ?><div class="opt"><?= esc($it['options_text']) ?></div><?php endif ?></td>
                <td class="r"><?= number_format($it['line_total'], 2) ?></td></tr>
        <?php endforeach ?>
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r"><?= number_format($o['subtotal'], 2) ?></td></tr>
        <?php if ($o['discount_amount'] > 0): ?>
            <tr><td>Discount (<?= esc($o['discount_type'] === 'promo' ? 'Promo ' . $o['promo_code'] : discount_label($o['discount_type'])) ?>)</td><td class="r">-<?= number_format($o['discount_amount'], 2) ?></td></tr>
            <?php if (in_array($o['discount_type'], ['senior', 'pwd'], true)): ?>
                <tr><td colspan="2" class="opt">ID: <?= esc($o['discount_id_no']) ?> · <?= esc($o['discount_id_name']) ?></td></tr>
            <?php endif ?>
        <?php endif ?>
        <tr class="big"><td>TOTAL (PHP)</td><td class="r"><?= number_format($o['total'], 2) ?></td></tr>
        <tr><td>Paid via</td><td class="r"><?= esc(payment_method_label($o['payment_method'])) ?></td></tr>
        <?php if ($o['reference_no']): ?><tr><td>Reference no.</td><td class="r"><?= esc($o['reference_no']) ?></td></tr><?php endif ?>
        <tr><td>Status</td><td class="r"><?= esc(payment_status_label($o['payment_status'])) ?></td></tr>
    </table>
    <hr>
    <div class="c">Salamat po! See you again at Salo.<br><span class="muted">This is not an official receipt (BIR OR issued at the counter on request).</span></div>
</body></html>
