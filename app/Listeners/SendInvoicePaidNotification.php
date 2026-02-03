<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInvoicePaidNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InvoicePaid $event): void
    {
        // Log invoice payment for audit trail
        Log::info('Invoice paid', [
            'invoice_id' => $event->invoice->id,
            'invoice_number' => $event->invoice->invoice_number,
            'company_id' => $event->invoice->company_id,
            'amount' => $event->invoice->amount,
            'paid_at' => $event->invoice->paid_at,
        ]);

        // Additional logic can be added here:
        // - Send payment confirmation to client
        // - Update revenue tracking
        // - Trigger accounting system webhook
    }
}
