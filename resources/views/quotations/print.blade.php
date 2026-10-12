<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 14px; }
        .page { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 32px; }
        .cover-title { text-align: center; color: #1e3a8a; font-size: 28px; font-weight: bold; margin: 0 0 4px; letter-spacing: 2px; }
        .cover-sub { text-align: center; font-size: 15px; font-weight: bold; margin: 0; }
        .cover-no { text-align: center; font-size: 12px; color: #64748b; margin: 2px 0 0; }
        hr.blue { border: none; border-top: 2px solid #2563eb; margin: 12px 0; }
        .parties { display: flex; gap: 12px; margin: 8px 0; }
        .parties > div { flex: 1; background: #f1f5f9; border: 1px solid #94a3b8; padding: 10px; font-size: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .meta td { padding: 4px; vertical-align: top; border: 1px solid #94a3b8; font-size: 12px; }
        .meta td.label { width: 160px; background: #e2e8f0; font-weight: bold; }
        h3.section { color: #1e3a8a; border-bottom: 1px solid #2563eb; padding-bottom: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.items th, table.items td { border: 1px solid #999; padding: 8px; font-size: 12px; }
        table.items th { background: #1e3a8a; color: #fff; text-align: left; }
        td.angka, th.angka { text-align: right; }
        .totals { width: 320px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 5px; font-size: 12px; }
        .totals td.angka { text-align: right; }
        .totals tr.grand td { font-weight: bold; border-top: 2px solid #111; font-size: 15px; color: #1e3a8a; }
        .toolbar { max-width: 800px; margin: 0 auto 16px; text-align: right; }
        .toolbar button, .toolbar a { padding: 8px 20px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #1e3a8a; color: #fff; border: none; border-radius: 4px; }
        .toolbar a { border: 1px solid #1e3a8a; color: #1e3a8a; border-radius: 4px; }
        @media print { .toolbar { display: none; } .page { border: none; padding: 0; } body { padding: 0; } }
    </style>
</head>
<body>
@php
    $sender = $quotation->sender_snapshot ?? [];
    $fmt = fn ($v) => number_format((float) $v, 2, ',', '.');
@endphp
<div class="toolbar">
    <button onclick="window.print()">Cetak / Simpan PDF</button>
    <a href="{{ route('quotations.pdf', $quotation) }}">Unduh PDF</a>
</div>
<div class="page">
    <div class="cover-title">PENAWARAN</div>
    <p class="cover-sub">{{ $quotation->title ?? 'Penawaran Harga' }}</p>
    <p class="cover-no">No. {{ $quotation->quotation_number }}</p>
    <hr class="blue">

    <div class="parties">
        <div>
            <strong>DARI</strong><br>
            {{ $sender['company_name'] ?? $settings->company_name }}<br>
            {{ $sender['company_address'] ?? $settings->company_address }}<br>
            {{ $sender['company_phone'] ?? $settings->company_phone }} | {{ $sender['company_email'] ?? $settings->company_email }}
        </div>
        <div>
            <strong>KEPADA</strong><br>
            {{ $quotation->client->company ?? $quotation->client->name }}<br>
            {{ $quotation->client->address }}<br>
            {{ $quotation->client->phone }} | {{ $quotation->client->email }}
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Tanggal</td><td>{{ $quotation->issue_date->format('d M Y') }}</td></tr>
        <tr><td class="label">Berlaku Hingga</td><td>{{ $quotation->valid_until->format('d M Y') }}</td></tr>
        <tr><td class="label">Status</td><td>{{ $quotation->status->value }}</td></tr>
    </table>

    @if($quotation->project_summary)
        <h3 class="section">Ringkasan Proyek</h3>
        <p>{{ $quotation->project_summary }}</p>
    @endif

    <h3 class="section">Item</h3>
    <table class="items">
        <tr><th>Section</th><th>Deskripsi</th><th>Qty</th><th class="angka">Harga</th><th class="angka">Jumlah</th></tr>
        @foreach($quotation->items as $item)
            <tr>
                <td>{{ $item->section }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->qty }}</td>
                <td class="angka">{{ $fmt($item->unit_price) }}</td>
                <td class="angka">{{ $fmt($item->line_total) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="angka">{{ $fmt($quotation->subtotal) }}</td></tr>
        <tr><td>Pajak</td><td class="angka">{{ $fmt($quotation->tax_amount) }}</td></tr>
        <tr class="grand"><td>Grand Total</td><td class="angka">Rp {{ $fmt($quotation->grand_total) }}</td></tr>
    </table>
</div>
</body>
</html>
