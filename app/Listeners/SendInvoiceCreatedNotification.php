<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInvoiceCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event): void
    {
        // Log invoice creation for audit trail
        Log::info('Invoice created', [
            'invoice_id' => $event->invoice->id,
            'invoice_number' => $event->invoice->invoice_number,
            'company_id' => $event->invoice->company_id,
            'amount' => $event->invoice->amount,
        ]);

        // Additional logic can be added here:
        // - Send notification to company owner
        // - Update analytics
        // - Trigger webhooks
    }
}
