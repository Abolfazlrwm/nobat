<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('invoice.brand') }} {{ $invoice->number }}</title>
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('/fonts/Vazirmatn-Regular.woff2') format('woff2');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('/fonts/Vazirmatn-Bold.woff2') format('woff2');
            font-weight: 700;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            background: #f5f5f4;
            color: #1c1917;
            margin: 0;
            padding: 24px 16px;
        }
        .sheet {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: 16px;
            padding: 32px;
        }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .brand { font-size: 22px; font-weight: 700; }
        .brand small { display: block; font-size: 12px; font-weight: 400; color: #78716c; margin-top: 4px; }
        .badge {
            display: inline-block; padding: 6px 14px; border-radius: 999px;
            font-size: 13px; font-weight: 700; border: 1px solid;
        }
        .paid   { color: #15803d; background: #f0fdf4; border-color: #bbf7d0; }
        .issued { color: #a16207; background: #fefce8; border-color: #fde68a; }
        .refunded { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        .canceled { color: #57534e; background: #fafaf9; border-color: #e7e5e4; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; margin-top: 24px; font-size: 14px; }
        .meta div b { color: #78716c; font-weight: 400; display: block; margin-bottom: 2px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 14px; }
        th, td { border: 1px solid #e7e5e4; padding: 10px 12px; text-align: right; }
        th { background: #fafaf9; font-weight: 700; font-size: 13px; }
        tfoot td { font-weight: 700; }
        .note { margin-top: 20px; font-size: 12px; color: #78716c; line-height: 1.9; }
        .actions { max-width: 720px; margin: 16px auto 0; text-align: center; }
        .actions button {
            font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
            background: #1c1917; color: #fff; border: 0; border-radius: 10px; padding: 10px 24px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: 0; border-radius: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div class="brand">
                {{ __('invoice.brand') }}
                <small>{{ $invoice->snapshot['business']['name'] ?? $invoice->business->name }}</small>
            </div>
            <span class="badge {{ $invoice->status }}">
                {{ $invoice->localizedStatusLabel() }}
            </span>
        </div>

        <div class="meta">
            <div><b>{{ __('invoice.number') }}</b><span dir="ltr">{{ $invoice->number }}</span></div>
            <div><b>{{ __('invoice.tracking_code') }}</b><span dir="ltr">{{ $invoice->snapshot['appointment_code'] ?? '' }}</span></div>
            <div><b>{{ __('invoice.issued_at') }}</b>{{ $invoice->issued_at?->format('Y-m-d H:i') }}</div>
            <div><b>{{ __('invoice.due_at') }}</b>{{ $invoice->due_at?->format('Y-m-d H:i') ?? '—' }}</div>
            <div><b>{{ __('invoice.customer') }}</b>{{ $invoice->snapshot['customer_name'] ?? '' }}</div>
            <div><b>{{ __('invoice.phone') }}</b><span dir="ltr">{{ $invoice->snapshot['customer_phone'] ?? '' }}</span></div>
            @if ($payment = $invoice->appointment->latestPayment)
                <div><b>{{ __('invoice.transaction_no') }}</b><span dir="ltr">{{ $payment->ref_id ?? '—' }}</span></div>
                <div><b>{{ __('invoice.gateway') }}</b>{{ $payment->gateway }}</div>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('invoice.description') }}</th>
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

        <p class="note">
            {{ __('invoice.note') }}
        </p>
    </div>

    <div class="actions">
        <button onclick="window.print()">{{ __('invoice.print_button') }}</button>
    </div>
</body>
</html>
