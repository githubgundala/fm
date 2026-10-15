<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Pembayaran {{ $payment->payment_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 14px; }
        .slip { max-width: 720px; margin: 0 auto; border: 1px solid #333; padding: 32px; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .header p { margin: 2px 0; font-size: 12px; color: #444; }
        .title { text-align: center; margin: 16px 0; }
        .title h2 { margin: 0; font-size: 17px; text-decoration: underline; }
        .title p { margin: 4px 0 0; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.meta td { padding: 3px 4px; vertical-align: top; }
        table.meta td.label { width: 180px; color: #444; }
        table.rincian { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.rincian th, table.rincian td { border: 1px solid #333; padding: 8px; text-align: left; }
        table.rincian th { background: #f0f0f0; }
        table.rincian td.angka, table.rincian th.angka { text-align: right; }
        tr.total td { font-weight: bold; }
        .sign { display: table; width: 100%; margin-top: 40px; }
        .sign div.col { display: table-cell; text-align: center; width: 50%; vertical-align: bottom; }
        .sign p { margin: 4px 0; }
        .sign .ttd { height: 80px; line-height: 80px; }
        .sign .ttd img { height: 80px; vertical-align: middle; }
        .toolbar { max-width: 720px; margin: 0 auto 16px; text-align: right; }
        .toolbar button { padding: 8px 20px; font-size: 14px; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .slip { border: none; padding: 0; max-width: none; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">Cetak Slip</button>
</div>
<div class="slip">
    <div class="header">
        <h1>{{ $settings->company_name }}</h1>
        @if($settings->company_address)<p>{{ $settings->company_address }}</p>@endif
        @if($settings->company_phone || $settings->company_email)
            <p>{{ trim($settings->company_phone.' '.$settings->company_email) }}</p>
        @endif
    </div>

    <div class="title">
        <h2>SLIP PEMBAYARAN</h2>
        <p>No. {{ $payment->payment_number }}</p>
    </div>

    <table class="meta">
        <tr><td class="label">Tanggal Bayar</td><td>: {{ $payment->payment_date->format('d M Y') }}</td></tr>
        <tr><td class="label">Invoice</td><td>: {{ $invoice->invoice_number }} (Jatuh tempo {{ $invoice->due_date->format('d M Y') }})</td></tr>
        <tr><td class="label">Diterima dari</td><td>: {{ $invoice->client->name }}@if($invoice->client->company) ({{ $invoice->client->company }})@endif</td></tr>
        <tr><td class="label">Metode</td><td>: {{ $payment->method->value }}@if($payment->reference) — {{ $payment->reference }}@endif</td></tr>
        @if($payment->notes)<tr><td class="label">Catatan</td><td>: {{ $payment->notes }}</td></tr>@endif
    </table>

    <table class="rincian">
        <tr><th>Uraian</th><th class="angka">Nominal (Rp)</th></tr>
        <tr><td>Total tagihan invoice</td><td class="angka">{{ number_format((float) $invoice->grand_total, 2, ',', '.') }}</td></tr>
        <tr><td>Pembayaran ini</td><td class="angka">{{ number_format((float) $payment->amount, 2, ',', '.') }}</td></tr>
        <tr><td>Total terbayar s/d slip ini</td><td class="angka">{{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}</td></tr>
        <tr class="total"><td>Sisa tagihan</td><td class="angka">{{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</td></tr>
    </table>

    <div class="sign">
        <div class="col">
            <p>&nbsp;</p>
            <p>Dibayar oleh,</p>
            <div class="ttd">&nbsp;</div>
            <p>( {{ $invoice->client->name }} )</p>
        </div>
        <div class="col">
            <p>{{ $settings->company_name }}, {{ $payment->payment_date->format('d M Y') }}</p>
            <p>Diterima oleh,</p>
            <div class="ttd">@if($signatureUrl ?? null)<img src="{{ $signatureUrl }}" alt="Tanda tangan">@endif</div>
            @if($settings->slip_receiver_name)<p>( {{ $settings->slip_receiver_name }} )</p>@else<p>( .......................... )</p>@endif
        </div>
    </div>
</div>
</body>
</html>
