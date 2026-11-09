<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\View\View;

/**
 * Renders the printable invoice from the snapshot frozen at issue time,
 * so later changes to the business/service never rewrite an issued bill.
 */
class InvoiceController extends Controller
{
    public function show(Invoice $invoice): View
    {
        return view('invoice.print', [
            'invoice' => $invoice->load(['appointment.service', 'appointment.staff', 'business']),
        ]);
    }
}
