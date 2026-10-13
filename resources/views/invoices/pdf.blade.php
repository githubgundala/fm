<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, DejaVu Sans, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 11px; }
        .page { padding: 32px; }
        .kop { width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .kop td { vertical-align: top; }
        .kop h1 { font-size: 18px; margin: 0 0 4px; }
        .kop p { font-size: 10px; margin: 1px 0; color: #444; }
        .kop-logo { text-align: right; width: 110px; }
        .logo { width: 90px; }
        .judul { text-align: center; margin: 10px 0; }
        .judul h2 { font-size: 16px; margin: 0; letter-spacing: 2px; }
        .judul p { margin: 2px 0 0; font-size: 11px; }
        .meta-wrap { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 10px; }
        .meta-wrap td.meta-half { vertical-align: top; padding: 0; }
        .meta-wrap td.meta-half-right { padding-left: 16px; }
        .meta { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .meta td { vertical-align: top; padding: 2px 4px; font-size: 11px; }
        .meta td.label { color: #444; white-space: nowrap; }
        .meta td.colon { color: #444; }
        .meta td.value { word-wrap: break-word; overflow-wrap: break-word; }
        .meta td.nowrap { white-space: nowrap; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.items th, table.items td { border: 1px solid #333; padding: 6px; font-size: 11px; }
        table.items th { background: #eee; text-align: left; }
        table.items td.angka, table.items th.angka { text-align: right; }
        table.totals { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.totals td { padding: 4px; font-size: 11px; }
        table.totals td.angka { text-align: right; }
        table.totals tr.grand td { font-weight: bold; border-top: 1px solid #333; font-size: 12px; }
        .catatan { margin-top: 14px; font-size: 10px; }
        .ttd { width: 100%; margin-top: 36px; border-collapse: collapse; }
        .ttd td { text-align: center; font-size: 11px; width: 50%; vertical-align: top; }
        .ttd td.sign-area { height: 85px; vertical-align: bottom; }
        .ttd img.signature { height: 80px; max-height: 80px; }
    </style>
</head>
<body>
<div class="page">
@include('invoices.partials.document', [
    'invoice' => $invoice,
    'settings' => $settings,
    'logoSrc' => $logoBase64 ?? null,
    'signatureSrc' => $signatureBase64 ?? null,
])
</div>
</body>
</html>
