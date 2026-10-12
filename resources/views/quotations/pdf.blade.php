<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penawaran {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }
        .cover-title { text-align: center; color: #1e3a8a; font-size: 28px; font-weight: bold; margin: 30px 0 4px; letter-spacing: 2px; }
        .cover-sub { text-align: center; font-size: 14px; font-weight: bold; margin: 0; }
        .cover-type { text-align: center; font-size: 12px; color: #2563eb; margin: 2px 0; }
        .cover-mvp { text-align: center; font-size: 11px; color: #64748b; font-style: italic; margin: 0 0 16px; }
        hr.blue { border: none; border-top: 2px solid #2563eb; margin: 12px 0; }
        table.parties { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.parties td { border: 1px solid #94a3b8; padding: 8px; vertical-align: top; width: 50%; font-size: 11px; background: #f1f5f9; }
        table.meta { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.meta td, table.meta th { border: 1px solid #94a3b8; padding: 6px; font-size: 11px; }
        table.meta td.label { background: #e2e8f0; font-weight: bold; width: 22%; }
        h3.section { color: #1e3a8a; font-size: 13px; border-bottom: 1px solid #2563eb; padding-bottom: 3px; margin: 16px 0 6px; }
        h4.subsection { color: #c2410c; font-size: 11px; margin: 10px 0 4px; }
        p, li { font-size: 11px; line-height: 1.5; }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.items th, table.items td { border: 1px solid #333; padding: 6px; font-size: 11px; }
        table.items th { background: #1e3a8a; color: #fff; text-align: left; }
        table.items td.angka, table.items th.angka { text-align: right; }
        table.totals { width: 55%; margin-left: 45%; border-collapse: collapse; }
        table.totals td { padding: 4px; font-size: 11px; }
        table.totals td.angka { text-align: right; }
        table.totals tr.grand td { font-weight: bold; border-top: 1px solid #333; font-size: 12px; color: #1e3a8a; }
        .catatan { margin-top: 10px; font-size: 10px; color: #475569; font-style: italic; }
        table.ttd { width: 100%; margin-top: 30px; border-collapse: collapse; }
        table.ttd td { border: 1px solid #94a3b8; text-align: center; font-size: 11px; width: 50%; padding: 8px; vertical-align: top; }
        .footer { text-align: center; font-size: 9px; color: #64748b; margin-top: 18px; border-top: 1px solid #cbd5e1; padding-top: 6px; }
    </style>
</head>
<body>
@php
    $sender = $snapshot ?: [
        'company_name' => $settings->company_name,
        'company_address' => $settings->company_address,
        'company_phone' => $settings->company_phone,
        'company_email' => $settings->company_email,
    ];
    $fmt = fn ($v) => number_format((float) $v, 2, ',', '.');
    $fmtInt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $dateFmt = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('d F Y');
@endphp

<div class="cover-title">PENAWARAN</div>
<p class="cover-sub">{{ $quotation->title ?? 'Penawaran Harga' }}</p>
@if($quotation->template?->project_type)<p class="cover-type">{{ $quotation->template->project_type }}</p>@endif
<p class="cover-mvp">No. {{ $quotation->quotation_number }}</p>

<hr class="blue">

<table class="parties">
    <tr>
        <td>
            <strong>DARI</strong><br>
            {{ $sender['company_name'] ?? $settings->company_name }}<br>
            @if(!empty($sender['company_address'])){{ $sender['company_address'] }}<br>@endif
            @if(!empty($sender['company_phone']))Telp: {{ $sender['company_phone'] }}@endif
            @if(!empty($sender['company_email'])) | Email: {{ $sender['company_email'] }}@endif
        </td>
        <td>
            <strong>KEPADA</strong><br>
            {{ $quotation->client->company ?? $quotation->client->name }}<br>
            @if($quotation->client->company){{ $quotation->client->name }}<br>@endif
            @if($quotation->client->address){{ $quotation->client->address }}<br>@endif
            @if($quotation->client->phone)Telp: {{ $quotation->client->phone }}@endif
            @if($quotation->client->email) | Email: {{ $quotation->client->email }}@endif
        </td>
    </tr>
</table>

<table class="meta">
    <tr><td class="label">No. Quotation</td><td>{{ $quotation->quotation_number }}</td></tr>
    <tr><td class="label">Tanggal</td><td>{{ $dateFmt($quotation->issue_date) }}</td></tr>
    <tr><td class="label">Berlaku Hingga</td><td>{{ $dateFmt($quotation->valid_until) }}</td></tr>
    <tr><td class="label">Mata Uang</td><td>{{ $quotation->currency === 'IDR' ? 'Rupiah (IDR)' : $quotation->currency }}, belum termasuk PPN {{ $fmtInt($quotation->tax_rate ?? 0) }}% kecuali disebutkan lain</td></tr>
</table>

@if($quotation->project_summary)
<h3 class="section">1. Ringkasan Proyek</h3>
<p>{{ $quotation->project_summary }}</p>
@endif

@if($groupedItems->isNotEmpty())
<h3 class="section">2. Rincian Penawaran</h3>
@foreach($groupedItems as $section => $items)
<h4 class="subsection">{{ $section }}</h4>
<table class="items">
    <tr><th style="width:5%;">No</th><th>Deskripsi</th><th style="width:8%;">Qty</th><th style="width:18%;" class="angka">Harga Satuan</th><th style="width:20%;" class="angka">Jumlah</th></tr>
    @foreach($items as $i => $item)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $item->description }}</td>
        <td>{{ $fmtInt($item->qty) }}</td>
        <td class="angka">{{ $fmt($item->unit_price) }}</td>
        <td class="angka">{{ $fmt($item->line_total) }}</td>
    </tr>
    @endforeach
</table>
@endforeach

<table class="totals">
    <tr><td>Subtotal</td><td class="angka">{{ $fmt($quotation->subtotal) }}</td></tr>
    @php $discountAmount = (float) $quotation->subtotal + (float) $quotation->tax_amount - (float) $quotation->grand_total; @endphp
    @if($discountAmount > 0.005)
    <tr><td>Diskon</td><td class="angka">-{{ $fmt($discountAmount) }}</td></tr>
    @endif
    @if($quotation->tax_rate !== null)
    <tr><td>PPN ({{ $fmtInt($quotation->tax_rate) }}%)</td><td class="angka">{{ $fmt($quotation->tax_amount) }}</td></tr>
    @endif
    <tr class="grand"><td>Grand Total</td><td class="angka">Rp {{ $fmt($quotation->grand_total) }}</td></tr>
</table>
@endif

@if($quotation->exclusions)
<h3 class="section">3. Yang Tidak Termasuk</h3>
<p>{!! nl2br(e($quotation->exclusions)) !!}</p>
@endif

@if($quotation->terms || $quotation->notes)
<h3 class="section">4. Syarat &amp; Ketentuan</h3>
@if($quotation->terms)<p>{!! nl2br(e($quotation->terms)) !!}</p>@endif
@if($quotation->notes)<p><strong>Catatan:</strong> {!! nl2br(e($quotation->notes)) !!}</p>@endif
@endif

<p class="catatan">Harga di atas indikatif dan dapat disesuaikan. Penawaran ini berlaku hingga {{ $dateFmt($quotation->valid_until) }}.</p>

<h3 class="section">5. Persetujuan</h3>
<p>Dengan menandatangani dokumen ini, kedua belah pihak menyatakan setuju atas keseluruhan isi penawaran.</p>

<table class="ttd">
    <tr>
        <td>
            {{ $sender['company_name'] ?? $settings->company_name }}<br>
            Tanggal: {{ $dateFmt($quotation->issue_date) }}<br><br>
            @if($signatureBase64)<img src="{{ $signatureBase64 }}" alt="Tanda tangan" style="height: 70px;"><br>@else<br><br><br>@endif
            ( _______________________ )<br>
            {{ $quotation->sender_name ?? $settings->signature_name ?? '' }}
        </td>
        <td>
            {{ $quotation->client->company ?? $quotation->client->name }}<br>
            Tanggal: ....................<br><br><br><br><br>
            ( _______________________ )<br>
            {{ $quotation->receiver_name ?? '' }}
        </td>
    </tr>
</table>

<div class="footer">Penawaran — {{ $quotation->title ?? $quotation->quotation_number }} &nbsp;|&nbsp; {{ $quotation->quotation_number }} &nbsp;|&nbsp; Terima kasih atas kepercayaan Anda.</div>
</body>
</html>
