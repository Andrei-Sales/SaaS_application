<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PdfService;
use App\Jobs\SendInvoiceEmail;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;
    protected PdfService $pdfService;

    public function __construct(InvoiceService $invoiceService, PdfService $pdfService)
    {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
        
        // Apply middleware
        $this->middleware(['auth', 'verified', 'tenant']);
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        if ($request->has('search')) {
            $invoices = $this->invoiceService->searchInvoices($request->only([
                'status', 'client_name', 'invoice_number', 'from_date', 'to_date'
            ]));
        } else {
            $invoices = $this->invoiceService->getPaginatedInvoices();
        }

        $stats = $this->invoiceService->getInvoiceStats();

        return view('invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create()
    {
        $this->authorize('create', Invoice::class);

        // Check if user can create more invoices based on plan
        if (!auth()->user()->can('can-create-invoice')) {
            return redirect()
                ->route('invoices.index')
                ->with('error', 'You have reached your invoice limit. Please upgrade to Pro plan.');
        }

        return view('invoices.create');
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request)
    {
        // Check invoice limit
        if (!auth()->user()->can('can-create-invoice')) {
            return redirect()
                ->route('invoices.index')
                ->with('error', 'You have reached your invoice limit. Please upgrade to Pro plan.');
        }

        $invoice = $this->invoiceService->createInvoice($request->validated());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $invoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->deleteInvoice($invoice);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice)
    {
        $this->authorize('send', $invoice);

        $this->invoiceService->markAsSent($invoice);

        return redirect()
            ->back()
            ->with('success', 'Invoice marked as sent.');
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice)
    {
        $this->authorize('markAsPaid', $invoice);

        $this->invoiceService->markAsPaid($invoice);

        return redirect()
            ->back()
            ->with('success', 'Invoice marked as paid.');
    }

    /**
     * Download invoice PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return $this->pdfService->getInvoicePdfForDownload($invoice);
    }

    /**
     * Send invoice via email.
     */
    public function sendEmail(Invoice $invoice)
    {
        $this->authorize('send', $invoice);

        if (!$invoice->client_email) {
            return redirect()
                ->back()
                ->with('error', 'Cannot send email: Client email is missing.');
        }

        // Generate PDF first
        $this->pdfService->generateInvoicePdf($invoice);

        // Dispatch email job to queue
        SendInvoiceEmail::dispatch($invoice);

        return redirect()
            ->back()
            ->with('success', 'Invoice email has been queued for sending.');
    }
}
