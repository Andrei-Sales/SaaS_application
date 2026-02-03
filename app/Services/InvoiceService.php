<?php

namespace App\Services;

use App\Models\Invoice;
use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for invoice business logic.
 *
 * This keeps controllers thin and centralizes invoice-related operations.
 */
class InvoiceService
{
    /**
     * Get paginated invoices for the authenticated user's company.
     */
    public function getPaginatedInvoices(int $perPage = 15)
    {
        return Invoice::with('company')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new invoice.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Generate invoice number if not provided
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber();
            }

            $invoice = Invoice::create($data);

            // Dispatch invoice created event
            event(new InvoiceCreated($invoice));

            // Clear cache
            $this->clearInvoiceCache();

            return $invoice;
        });
    }

    /**
     * Update an existing invoice.
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        DB::transaction(function () use ($invoice, $data) {
            $invoice->update($data);

            // Clear cache
            $this->clearInvoiceCache();
        });

        return $invoice->fresh();
    }

    /**
     * Delete an invoice (soft delete).
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        $deleted = $invoice->delete();

        if ($deleted) {
            $this->clearInvoiceCache();
        }

        return $deleted;
    }

    /**
     * Mark an invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        $invoice->markAsSent();
        $this->clearInvoiceCache();

        return $invoice->fresh();
    }

    /**
     * Mark an invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        DB::transaction(function () use ($invoice) {
            $invoice->markAsPaid();

            // Dispatch invoice paid event
            event(new InvoicePaid($invoice));

            $this->clearInvoiceCache();
        });

        return $invoice->fresh();
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = now()->year;
        $companyId = auth()->user()->company_id;

        // Get the last invoice number for this company and year
        $lastInvoice = Invoice::where('company_id', $companyId)
            ->where('invoice_number', 'like', "{$prefix}{$year}%")
            ->latest('id')
            ->first();

        if ($lastInvoice) {
            // Extract sequence number and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice statistics for dashboard.
     */
    public function getInvoiceStats(): array
    {
        $companyId = auth()->user()->company_id;

        return Cache::remember("invoice_stats_{$companyId}", 300, function () use ($companyId) {
            return [
                'total' => Invoice::count(),
                'draft' => Invoice::draft()->count(),
                'sent' => Invoice::sent()->count(),
                'paid' => Invoice::paid()->count(),
                'total_amount' => Invoice::sum('amount'),
                'paid_amount' => Invoice::paid()->sum('amount'),
                'pending_amount' => Invoice::whereIn('status', ['draft', 'sent'])->sum('amount'),
            ];
        });
    }

    /**
     * Clear invoice-related cache.
     */
    protected function clearInvoiceCache(): void
    {
        $companyId = auth()->user()->company_id;
        Cache::forget("invoice_stats_{$companyId}");
    }

    /**
     * Search invoices by various criteria.
     */
    public function searchInvoices(array $filters)
    {
        $query = Invoice::with('company');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_name'])) {
            $query->where('client_name', 'like', '%' . $filters['client_name'] . '%');
        }

        if (!empty($filters['invoice_number'])) {
            $query->where('invoice_number', 'like', '%' . $filters['invoice_number'] . '%');
        }

        if (!empty($filters['from_date'])) {
            $query->where('due_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('due_date', '<=', $filters['to_date']);
        }

        return $query->latest()->paginate(15);
    }
}
