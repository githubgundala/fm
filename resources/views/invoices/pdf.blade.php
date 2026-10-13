<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }
        .kop { width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .kop td { vertical-align: top; }
        .kop h1 { font-size: 18px; margin: 0 0 4px; }
        .kop p { font-size: 10px; margin: 1px 0; color: #444; }
        .logo { width: 90px; }
        .judul { text-align: center; margin: 10px 0; }
        .judul h2 { font-size: 16px; margin: 0; letter-spacing: 2px; }
        .judul p { margin: 2px 0 0; font-size: 11px; }
        .meta { width: 100%; margin-bottom: 10px; }
        .meta td { vertical-align: top; padding: 2px 4px; font-size: 11px; }
        .meta td.label { width: 130px; color: #444; }
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
    </style>
</head>
<body>
<table class="kop">
    <tr>
        <td>
            <h1>{{ $settings->company_name }}</h1>
            @if($settings->company_address)<p>{{ $settings->company_address }}</p>@endif
            @if($settings->company_phone)<p>Telp: {{ $settings->company_phone }}</p>@endif
            @if($settings->company_email)<p>{{ $settings->company_email }}</p>@endif
        </td>
        <td style="text-align: right;">
            @if($logoBase64)<img class="logo" src="{{ $logoBase64 }}" alt="Logo">@endif
        </td>
    </tr>
</table>

<div class="judul">
    <h2>INVOICE</h2>
    <p>No. {{ $invoice->invoice_number }}</p>
</div>

<table class="meta">
    <tr>
        <td class="label">Ditagihkan kepada</td>
        <td>: <strong>{{ $invoice->client->name }}</strong>@if($invoice->client->company) ({{ $invoice->client->company }})@endif</td>
        <td class="label">Tanggal Terbit</td>
        <td>: {{ $invoice->issue_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td>: {{ $invoice->billing_address ?? $invoice->client->address ?? '-' }}@if($invoice->client->tax_id)<br>&nbsp;&nbsp;NPWP: {{ $invoice->client->tax_id }}@endif</td>
        <td class="label">Jatuh Tempo</td>
        <td>: {{ $invoice->due_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td>: {{ strtoupper(str_replace('_', ' ', $invoice->status->value)) }}</td>
        <td class="label">Mata Uang</td>
        <td>: {{ $invoice->currency }}</td>
    </tr>
</table>

<table class="items">
    <tr><th style="width:5%;">No</th><th>Deskripsi</th><th style="width:8%;">Qty</th><th style="width:18%;" class="angka">Harga Satuan</th><th style="width:20%;" class="angka">Jumlah</th></tr>
    @foreach($invoice->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
            <td class="angka">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
            <td class="angka">{{ number_format((float) $item->line_total, 2, ',', '.') }}</td>
        </tr>
    @endforeach
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="angka">{{ number_format((float) $invoice->subtotal, 2, ',', '.') }}</td></tr>
    @if(((float) $invoice->subtotal + (float) $invoice->tax_amount - (float) $invoice->grand_total) > 0.005)
        <tr><td>Diskon</td><td class="angka">-{{ number_format((float) $invoice->subtotal + (float) $invoice->tax_amount - (float) $invoice->grand_total, 2, ',', '.') }}</td></tr>
    @endif
    @if($invoice->tax_rate !== null)
        <tr><td>Pajak ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2, ',', '.'), '0'), ',') }}%)</td><td class="angka">{{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td></tr>
    @endif
    <tr class="grand"><td>Grand Total</td><td class="angka">Rp {{ number_format((float) $invoice->grand_total, 2, ',', '.') }}</td></tr>
    <tr><td>Terbayar</td><td class="angka">{{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}</td></tr>
    <tr class="grand"><td>Sisa Tagihan</td><td class="angka">Rp {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</td></tr>
</table>

<div class="catatan">
    @if($invoice->notes)<p><strong>Catatan:</strong> {{ $invoice->notes }}</p>@endif
    @if($invoice->terms)<p><strong>Syarat &amp; Ketentuan:</strong> {{ $invoice->terms }}</p>@endif
</div>

<table class="ttd">
    <tr>
        <td>Penerima,</td>
        <td>{{ $settings->company_name }}, {{ $invoice->issue_date->format('d M Y') }}<br>Hormat kami,</td>
    </tr>
    <tr>
        <td class="sign-area"></td>
        <td class="sign-area">@if($signatureBase64 ?? null)<img src="{{ $signatureBase64 }}" alt="Tanda tangan" style="height: 80px; max-height: 80px;">@endif</td>
    </tr>
    <tr>
        <td>@if($invoice->receiver_name)( <u><strong>{{ $invoice->receiver_name }}</strong></u> )@else( .......................... )@endif</td>
        <td>@if($settings->signature_name)( <u><strong>{{ $settings->signature_name }}</strong></u> )@else( .......................... )@endif</td>
    </tr>
</table>
</body>
</html>
