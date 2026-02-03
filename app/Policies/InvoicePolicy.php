<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with a company can view their company's invoices
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // User can view invoice if it belongs to their company
        return $user->company_id === $invoice->company_id;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        // All users can create invoices for their company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // User can update invoice if it belongs to their company
        // and the invoice is not paid (business rule)
        return $user->company_id === $invoice->company_id && !$invoice->isPaid();
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only owners can delete invoices and only if not paid
        return $user->isOwner()
            && $user->company_id === $invoice->company_id
            && !$invoice->isPaid();
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Only owners can restore invoices
        return $user->isOwner() && $user->company_id === $invoice->company_id;
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Only owners can force delete invoices
        return $user->isOwner() && $user->company_id === $invoice->company_id;
    }

    /**
     * Determine whether the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // User can send invoice if it belongs to their company and is draft or sent
        return $user->company_id === $invoice->company_id
            && ($invoice->isDraft() || $invoice->isSent());
    }

    /**
     * Determine whether the user can mark invoice as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        // User can mark as paid if invoice belongs to their company
        return $user->company_id === $invoice->company_id && !$invoice->isPaid();
    }
}
