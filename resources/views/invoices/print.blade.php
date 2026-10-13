<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 14px; }
        .page { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 32px; }
        .kop { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 12px; }
        .kop h1 { margin: 0 0 4px; font-size: 22px; }
        .kop p { margin: 2px 0; font-size: 12px; color: #555; }
        .judul { text-align: center; margin: 16px 0; }
        .judul h2 { margin: 0; letter-spacing: 3px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 3px 4px; vertical-align: top; }
        .meta td.label { width: 160px; color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.items th, table.items td { border: 1px solid #999; padding: 8px; }
        table.items th { background: #f3f3f3; text-align: left; }
        td.angka, th.angka { text-align: right; }
        .totals { width: 320px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 5px; }
        .totals tr.grand td { font-weight: bold; border-top: 2px solid #111; font-size: 16px; }
        .catatan { margin-top: 16px; font-size: 12px; color: #333; }
        .ttd { width: 100%; margin-top: 48px; border-collapse: collapse; }
        .ttd td { text-align: center; width: 50%; vertical-align: top; }
        .ttd td.sign-area { height: 90px; vertical-align: bottom; }
        .toolbar { max-width: 800px; margin: 0 auto 16px; text-align: right; }
        .toolbar button { padding: 8px 20px; font-size: 14px; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .page { border: none; padding: 0; max-width: none; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">Cetak / Simpan PDF</button>
</div>
<div class="page">
    <div class="kop">
        <h1>{{ $settings->company_name }}</h1>
        @if($settings->company_address)<p>{{ $settings->company_address }}</p>@endif
        @if($settings->company_phone || $settings->company_email)
            <p>{{ trim($settings->company_phone.' '.$settings->company_email) }}</p>
        @endif
    </div>

    <div class="judul">
        <h2>INVOICE</h2>
        <p>No. {{ $invoice->invoice_number }} — {{ strtoupper(str_replace('_', ' ', $invoice->status->value)) }}</p>
    </div>

    <table class="meta">
        <tr><td class="label">Ditagihkan kepada</td><td><strong>{{ $invoice->client->name }}</strong>@if($invoice->client->company) ({{ $invoice->client->company }})@endif<br>{{ $invoice->billing_address ?? $invoice->client->address ?? '' }}@if($invoice->client->tax_id)<br>NPWP: {{ $invoice->client->tax_id }}@endif</td></tr>
        <tr><td class="label">Tanggal Terbit</td><td>{{ $invoice->issue_date->format('d M Y') }}</td></tr>
        <tr><td class="label">Jatuh Tempo</td><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
    </table>

    <table class="items">
        <tr><th style="width:5%;">No</th><th>Deskripsi</th><th style="width:8%;">Qty</th><th style="width:20%;" class="angka">Harga Satuan</th><th style="width:20%;" class="angka">Jumlah</th></tr>
        @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                <td class="angka">Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                <td class="angka">Rp {{ number_format((float) $item->line_total, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="angka">{{ number_format((float) $invoice->subtotal, 2, ',', '.') }}</td></tr>
        @if($invoice->tax_rate !== null)
            <tr><td>Pajak ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2, ',', '.'), '0'), ',') }}%)</td><td class="angka">{{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td>Terbayar</td><td class="angka">{{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}</td></tr>
        <tr class="grand"><td>Grand Total</td><td class="angka">Rp {{ number_format((float) $invoice->grand_total, 2, ',', '.') }}</td></tr>
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
            <td class="sign-area">@if($signatureUrl ?? null)<img src="{{ $signatureUrl }}" alt="Tanda tangan" style="height: 80px; max-height: 80px;">@endif</td>
        </tr>
        <tr>
            <td>@if($invoice->receiver_name)( <u><strong>{{ $invoice->receiver_name }}</strong></u> )@else( .......................... )@endif</td>
            <td>@if($settings->signature_name)( <u><strong>{{ $settings->signature_name }}</strong></u> )@else( .......................... )@endif</td>
        </tr>
    </table>
</div>
</body>
</html>
