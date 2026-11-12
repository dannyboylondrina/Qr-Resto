<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
    body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; color: #1c1917; }
    h1 { font-family: "DejaVu Serif", Georgia, serif; font-size: 22px; margin: 0; } h2 { font-size: 13px; margin: 16px 0 6px; color: #b5451b; }
    table { width: 100%; border-collapse: collapse; } th, td { padding: 4px 6px; border-bottom: 1px solid #e4dccd; text-align: left; }
    th { background: #f7f2ea; font-size: 9px; text-transform: uppercase; } .r { text-align: right; }
    .kpis td { border: 1px solid #e4dccd; padding: 8px; } .kpis b { font-size: 14px; display: block; }
</style></head>
<body>
    <h1>Salo — Sales report</h1>
    <div>Modern Filipino Kitchen · 24 Antonio Drive, Legazpi Village, Makati City</div>
    <div><?= date('M j, Y', strtotime($from)) ?> – <?= date('M j, Y', strtotime($to)) ?> · generated <?= date('M j, Y g:i A') ?></div>

    <h2>Summary</h2>
    <table class="kpis"><tr>
        <td>Net sales<b><?= peso($sum['net']) ?></b></td><td>Paid orders<b><?= (int) $sum['n'] ?></b></td>
        <td>Average ticket<b><?= peso($sum['avg']) ?></b></td><td>Gross<b><?= peso($sum['gross']) ?></b></td><td>Discounts<b><?= peso($sum['disc']) ?></b></td>
    </tr></table>

    <h2>By payment method</h2>
    <table><tr><th>Method</th><th class="r">Orders</th><th class="r">Amount</th></tr>
        <?php foreach ($methods as $m): ?><tr><td><?= esc(payment_method_label($m['m'])) ?></td><td class="r"><?= (int) $m['n'] ?></td><td class="r"><?= peso($m['t']) ?></td></tr><?php endforeach ?>
    </table>

    <h2>Discounts given</h2>
    <table><tr><th>Type</th><th class="r">Orders</th><th class="r">Amount</th></tr>
        <?php foreach ($discounts as $d): ?><tr><td><?= esc($d['t'] === 'promo' ? 'Promo ' . $d['code'] : discount_label($d['t'])) ?></td><td class="r"><?= (int) $d['n'] ?></td><td class="r"><?= peso($d['amt']) ?></td></tr><?php endforeach ?>
        <?php if (! $discounts): ?><tr><td colspan="3">None</td></tr><?php endif ?>
    </table>

    <h2>Best sellers</h2>
    <table><tr><th>#</th><th>Item</th><th class="r">Qty</th><th class="r">Revenue</th></tr>
        <?php foreach ($best as $i => $b): ?><tr><td><?= $i + 1 ?></td><td><?= esc($b['name']) ?></td><td class="r"><?= (int) $b['qty'] ?></td><td class="r"><?= peso($b['rev']) ?></td></tr><?php endforeach ?>
    </table>

    <h2>Daily breakdown</h2>
    <table><tr><th>Date</th><th class="r">Orders</th><th class="r">Gross</th><th class="r">Disc.</th><th class="r">Net</th><th class="r">Cash</th><th class="r">GCash</th><th class="r">Bank</th></tr>
        <?php foreach ($daily as $r): ?>
            <tr><td><?= date('D M j', strtotime($r['d'])) ?></td><td class="r"><?= (int) $r['n'] ?></td><td class="r"><?= number_format($r['gross'], 2) ?></td><td class="r"><?= number_format($r['disc'], 2) ?></td>
                <td class="r"><b><?= number_format($r['net'], 2) ?></b></td><td class="r"><?= number_format($r['cash'], 2) ?></td><td class="r"><?= number_format($r['gcash'], 2) ?></td><td class="r"><?= number_format($r['bank'], 2) ?></td></tr>
        <?php endforeach ?>
    </table>
</body></html>
