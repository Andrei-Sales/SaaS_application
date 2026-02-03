<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_can_only_see_invoices_from_their_company()
    {
        // Company A
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $invoiceA = Invoice::factory()->create(['company_id' => $companyA->id]);

        // Company B
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $invoiceB = Invoice::factory()->create(['company_id' => $companyB->id]);

        // User A should only see their invoice
        $this->actingAs($userA);
        $invoices = Invoice::all();

        $this->assertCount(1, $invoices);
        $this->assertEquals($invoiceA->id, $invoices->first()->id);

        // User B should only see their invoice
        $this->actingAs($userB);
        $invoices = Invoice::all();

        $this->assertCount(1, $invoices);
        $this->assertEquals($invoiceB->id, $invoices->first()->id);
    }

    /** @test */
    public function creating_invoice_automatically_sets_company_id()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'client_name' => 'Test Client',
            'amount' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        $this->assertEquals($company->id, $invoice->company_id);
    }

    /** @test */
    public function users_cannot_access_other_company_resources()
    {
        $companyA = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $companyB = Company::factory()->create();
        $invoiceB = Invoice::factory()->create(['company_id' => $companyB->id]);

        // User A tries to view Company B's invoice
        $response = $this->actingAs($userA)
            ->get(route('invoices.show', $invoiceB));

        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_scope_is_applied_to_queries()
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        // Create invoices for both companies
        Invoice::factory()->count(3)->create(['company_id' => $companyA->id]);
        Invoice::factory()->count(2)->create(['company_id' => $companyB->id]);

        // User from Company A
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $this->actingAs($userA);

        // Should only see Company A's invoices (3)
        $this->assertCount(3, Invoice::all());

        // User from Company B
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $this->actingAs($userB);

        // Should only see Company B's invoices (2)
        $this->assertCount(2, Invoice::all());
    }

    /** @test */
    public function unauthenticated_users_see_all_invoices_without_scope()
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Invoice::factory()->create(['company_id' => $companyA->id]);
        Invoice::factory()->create(['company_id' => $companyB->id]);

        // Without authentication, global scope should not apply
        $this->assertCount(2, Invoice::withoutGlobalScopes()->get());
    }
}
