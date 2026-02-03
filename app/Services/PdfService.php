<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service for generating PDF documents.
 */
class PdfService
{
    /**
     * Generate PDF for an invoice.
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
        ]);

        // Ensure directory exists
        $directory = storage_path('app/invoices');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'invoice-' . $invoice->id . '.pdf';
        $path = $directory . '/' . $filename;

        // Save PDF to storage
        $pdf->save($path);

        return $path;
    }

    /**
     * Get invoice PDF for download.
     */
    public function getInvoicePdfForDownload(Invoice $invoice)
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
        ]);

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Stream invoice PDF inline.
     */
    public function streamInvoicePdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
        ]);

        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
