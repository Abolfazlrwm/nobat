<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renders the printable invoice from the snapshot frozen at issue time,
 * so later changes to the business/service never rewrite an issued bill.
 *
 * The invoice carries PII — customer name, phone, the business' contact
 * details and the full purchase snapshot — so the URL is signed. A bare
 * /invoices/{number} link that leaked through a Referer header or a log
 * file cannot be replayed: a missing or expired signature aborts with 403.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice): View
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        return view('invoice.print', [
            'invoice' => $invoice->load(['appointment.service', 'appointment.staff', 'business']),
        ]);
    }
}
