<table class="kop">
    <tr>
        <td>
            <h1>{{ $settings->company_name }}</h1>
            @if($settings->company_address)<p>{{ $settings->company_address }}</p>@endif
            @if($settings->company_phone)<p>Telp: {{ $settings->company_phone }}</p>@endif
            @if($settings->company_email)<p>{{ $settings->company_email }}</p>@endif
        </td>
        <td class="kop-logo">
            @if(! empty($logoSrc))<img class="logo" src="{{ $logoSrc }}" alt="Logo">@endif
        </td>
    </tr>
</table>

<div class="judul">
    <h2>INVOICE</h2>
    <p>No. {{ $invoice->invoice_number }}</p>
</div>

<table class="meta">
    <colgroup>
        <col class="col-label" style="width: 17%;">
        <col class="col-colon" style="width: 3%;">
        <col class="col-value" style="width: 30%;">
        <col class="col-label" style="width: 17%;">
        <col class="col-colon" style="width: 3%;">
        <col class="col-value" style="width: 30%;">
    </colgroup>
    <tr>
        <td class="label">Ditagihkan kepada</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $invoice->client->name }}</strong>@if($invoice->client->company) ({{ $invoice->client->company }})@endif</td>
        <td class="label">Tanggal Terbit</td>
        <td class="colon">:</td>
        <td class="value nowrap">{{ $invoice->issue_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="colon">:</td>
        <td class="value">{{ $invoice->billing_address ?? $invoice->client->address ?? '-' }}@if($invoice->client->tax_id)<br>NPWP: {{ $invoice->client->tax_id }}@endif</td>
        <td class="label">Jatuh Tempo</td>
        <td class="colon">:</td>
        <td class="value nowrap">{{ $invoice->due_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper(str_replace('_', ' ', $invoice->status->value)) }}</td>
        <td class="label">Mata Uang</td>
        <td class="colon">:</td>
        <td class="value nowrap">{{ $invoice->currency }}</td>
    </tr>
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
        <td class="sign-area">@if(! empty($signatureSrc))<img src="{{ $signatureSrc }}" alt="Tanda tangan" class="signature">@endif</td>
    </tr>
    <tr>
        <td>@if($invoice->receiver_name)( <u><strong>{{ $invoice->receiver_name }}</strong></u> )@else( .......................... )@endif</td>
        <td>@if($settings->signature_name)( <u><strong>{{ $settings->signature_name }}</strong></u> )@else( .......................... )@endif</td>
    </tr>
</table>
