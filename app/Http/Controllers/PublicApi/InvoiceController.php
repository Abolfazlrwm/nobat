<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Mpdf\Mpdf;

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

    /**
     * The same frozen snapshot as the print sheet, rendered as a real PDF.
     * mPDF shapes Persian properly (bundled XB Riyaz, RTL) where the print
     * sheet depended on the visitor's own fonts, and the download is still
     * guarded by the signed URL — the invoice carries customer PII.
     */
    public function pdf(Request $request, Invoice $invoice): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $invoice->load(['appointment.service', 'appointment.staff', 'business']);

        $tempDir = storage_path('framework/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'margin_top' => 14,
            'margin_bottom' => 14,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetDirectionality('rtl');

        $mpdf->WriteHTML(view('invoice.pdf', ['invoice' => $invoice])->render());

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-'.$invoice->number.'.pdf"',
        ]);
    }
}
