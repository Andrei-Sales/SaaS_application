<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company with a subscription
        $this->company = Company::factory()->create();
        Subscription::factory()->pro()->create(['company_id' => $this->company->id]);

        // Create a user for this company
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'owner',
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_invoices_list()
    {
        Invoice::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)->get(route('invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('Invoices');
    }

    /** @test */
    public function authenticated_user_can_create_invoice()
    {
        $invoiceData = [
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'amount' => 1000.00,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $invoiceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'client_name' => 'Test Client',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function user_can_only_view_their_company_invoices()
    {
        // Create invoices for the user's company
        $myInvoice = Invoice::factory()->create(['company_id' => $this->company->id]);

        // Create another company with its own invoice
        $otherCompany = Company::factory()->create();
        $otherInvoice = Invoice::factory()->create(['company_id' => $otherCompany->id]);

        // Act as our user and view invoice details
        $response = $this->actingAs($this->user)
            ->get(route('invoices.show', $myInvoice));

        $response->assertStatus(200);

        // Try to access another company's invoice (should fail)
        $response = $this->actingAs($this->user)
            ->get(route('invoices.show', $otherInvoice));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_edit_paid_invoice()
    {
        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.edit', $invoice));

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_delete_draft_invoice()
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect(route('invoices.index'));
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    /** @test */
    public function member_cannot_delete_invoice()
    {
        $member = User::factory()->member()->create([
            'company_id' => $this->company->id,
        ]);

        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($member)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertStatus(403);
    }

    /** @test */
    public function invoice_can_be_marked_as_sent()
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.mark-as-sent', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function invoice_can_be_marked_as_paid()
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.mark-as-paid', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        $invoice->refresh();
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function invoice_requires_valid_data()
    {
        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), [
                'client_name' => '', // Required field
                'amount' => -100, // Invalid amount
            ]);

        $response->assertSessionHasErrors(['client_name', 'amount', 'due_date']);
    }

    /** @test */
    public function invoice_number_is_auto_generated_if_not_provided()
    {
        $invoiceData = [
            'client_name' => 'Test Client',
            'amount' => 500.00,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $invoiceData);

        $invoice = Invoice::where('client_name', 'Test Client')->first();

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringContainsString('INV-', $invoice->invoice_number);
    }
}
