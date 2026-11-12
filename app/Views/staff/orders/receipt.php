<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Receipt <?= esc($o['code']) ?></title>
<style>
    body { margin: 0; background: #eee; }
    .bar { text-align: center; padding: 12px; font-family: Arial, sans-serif; }
    .bar button { padding: 10px 18px; font-size: 15px; border-radius: 999px; border: 0; background: #b5451b; color: #fff; cursor: pointer; }
    .paper { width: 300px; margin: 0 auto 30px; background: #fff; padding: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
    @media print { body { background: #fff; } .bar { display: none; } .paper { box-shadow: none; margin: 0; width: auto; padding: 0; } @page { size: 80mm auto; margin: 4mm; } }
</style></head>
<body>
<div class="bar"><button onclick="window.print()">🖨 Print receipt</button></div>
<?php
$html = view('receipt/pdf', ['o' => $o]);
preg_match('#<style>(.*?)</style>#s', $html, $css);
preg_match('#<body>(.*)</body>#s', $html, $body);
?>
<style><?= str_replace(['body {', 'margin: 0; }'], ['.paper {', '}'], $css[1] ?? '') ?></style>
<div class="paper"><?= $body[1] ?? '' ?></div>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body></html>
