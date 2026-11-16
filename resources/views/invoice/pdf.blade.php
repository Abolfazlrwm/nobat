<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoice.brand') }} {{ $invoice->number }}</title>
    <style>
        /* mPDF renders this sheet: no flex/grid — plain tables only, the
           bundled XB Riyaz font shapes the Persian properly. */
        body {
            font-family: xbriyaz;
            font-size: 12pt;
            color: #1c1917;
        }
        h1 { font-size: 15pt; margin: 0; }
        .brand-small { font-size: 10pt; color: #78716c; }
        .badge { font-size: 10pt; border: 1px solid #e7e5e4; padding: 2px 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #e7e5e4; padding: 6px 10px; text-align: right; font-size: 11pt; }
        th { background-color: #fafaf9; }
        .meta td { border: 0; padding: 3px 6px; font-size: 10.5pt; }
        .label { color: #78716c; }
        .ltr { direction: ltr; }
        .note { font-size: 9.5pt; color: #78716c; margin-top: 14pt; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td width="60%">
                <h1>{{ __('invoice.brand') }}</h1>
                <div class="brand-small">{{ $invoice->snapshot['business']['name'] ?? $invoice->business->name }}</div>
            </td>
            <td width="40%" align="left" valign="top">
                <span class="badge">{{ $invoice->localizedStatusLabel() }}</span>
            </td>
        </tr>
    </table>
    <hr>
    <table class="meta">
        <tr>
            <td class="label">{{ __('invoice.number') }}: <span class="ltr">{{ $invoice->number }}</span></td>
            <td class="label">{{ __('invoice.tracking_code') }}: <span class="ltr">{{ $invoice->snapshot['appointment_code'] ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="label">{{ __('invoice.customer') }}: {{ $invoice->snapshot['customer_name'] ?? '' }}</td>
            <td class="label">{{ __('invoice.phone') }}: <span class="ltr">{{ $invoice->snapshot['customer_phone'] ?? '' }}</span></td>
        </tr>
        @if ($payment = $invoice->appointment->latestPayment)
            <tr>
                <td class="label">{{ __('invoice.transaction_no') }}: <span class="ltr">{{ $payment->ref_id ?? '—' }}</span></td>
                <td class="label">{{ __('invoice.gateway') }}: {{ $payment->gateway }}</td>
            </tr>
        @endif
    </table>
    <table>
        <thead>
            <tr>
                <th width="60%">{{ __('invoice.description') }}</th>
                <th>{{ __('invoice.duration_minutes') }}</th>
                <th>{{ __('invoice.amount_toman') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->snapshot['service']['name'] ?? '' }}</td>
                <td>{{ $invoice->snapshot['service']['duration_minutes'] ?? '' }}</td>
                <td>{{ number_format($invoice->amount) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">{{ __('invoice.total') }}</td>
                <td>{{ number_format($invoice->amount) }} {{ __('invoice.currency') }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="note">{{ __('invoice.note') }}</p>
</body>
</html>
