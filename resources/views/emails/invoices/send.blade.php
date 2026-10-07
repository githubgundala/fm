<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111;">
<p>{!! nl2br(e($body)) !!}</p>

<table style="border-collapse: collapse; margin: 16px 0;">
    <tr><td style="padding: 4px 12px 4px 0; color: #555;">Invoice</td><td><strong>{{ $invoice->invoice_number }}</strong></td></tr>
    <tr><td style="padding: 4px 12px 4px 0; color: #555;">Grand Total</td><td>Rp {{ number_format((float) $invoice->grand_total, 2, ',', '.') }}</td></tr>
    <tr><td style="padding: 4px 12px 4px 0; color: #555;">Sisa Tagihan</td><td><strong>Rp {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</strong></td></tr>
    <tr><td style="padding: 4px 12px 4px 0; color: #555;">Jatuh Tempo</td><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
</table>

<p>Terima kasih atas kerja sama Anda.</p>
</body>
</html>
