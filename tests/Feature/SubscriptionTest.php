<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionService = app(SubscriptionService::class);
    }

    /** @test */
    public function free_plan_users_have_invoice_limit()
    {
        $company = Company::factory()->create();
        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        $limit = $this->subscriptionService->getInvoiceLimit('free');
        $this->assertEquals(10, $limit);

        // Create invoices up to limit
        Invoice::factory()->count(10)->create(['company_id' => $company->id]);

        $this->assertTrue($this->subscriptionService->hasReachedInvoiceLimit($company));
    }

    /** @test */
    public function pro_plan_users_have_unlimited_invoices()
    {
        $company = Company::factory()->create();
        Subscription::factory()->pro()->create(['company_id' => $company->id]);

        $limit = $this->subscriptionService->getInvoiceLimit('pro');
        $this->assertNull($limit);

        // Create many invoices
        Invoice::factory()->count(20)->create(['company_id' => $company->id]);

        $this->assertFalse($this->subscriptionService->hasReachedInvoiceLimit($company));
    }

    /** @test */
    public function can_upgrade_from_free_to_pro()
    {
        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
        ]);

        $updated = $this->subscriptionService->changePlan($subscription, 'pro');

        $this->assertEquals('pro', $updated->plan);
        $this->assertEquals('active', $updated->status);
    }

    /** @test */
    public function can_cancel_subscription()
    {
        $company = Company::factory()->create();
        $subscription = Subscription::factory()->pro()->create([
            'company_id' => $company->id,
        ]);

        $canceled = $this->subscriptionService->cancelSubscription($subscription, false);

        $this->assertEquals('canceled', $canceled->status);
        $this->assertNotNull($canceled->ends_at);
    }

    /** @test */
    public function can_resume_canceled_subscription()
    {
        $company = Company::factory()->create();
        $subscription = Subscription::factory()->canceled()->create([
            'company_id' => $company->id,
        ]);

        $resumed = $this->subscriptionService->resumeSubscription($subscription);

        $this->assertEquals('active', $resumed->status);
        $this->assertNull($resumed->ends_at);
    }

    /** @test */
    public function owner_can_access_subscription_management()
    {
        $company = Company::factory()->create();
        $owner = User::factory()->owner()->create(['company_id' => $company->id]);
        Subscription::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)
            ->get(route('subscriptions.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function member_cannot_access_subscription_management()
    {
        $company = Company::factory()->create();
        $member = User::factory()->member()->create(['company_id' => $company->id]);
        Subscription::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($member)
            ->get(route('subscriptions.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function subscription_status_methods_work_correctly()
    {
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);
        $this->assertTrue($activeSubscription->isActive());
        $this->assertFalse($activeSubscription->canceled());

        $canceledSubscription = Subscription::factory()->canceled()->create();
        $this->assertTrue($canceledSubscription->canceled());
        $this->assertFalse($canceledSubscription->isActive());

        $trialSubscription = Subscription::factory()->trial()->create();
        $this->assertTrue($trialSubscription->onTrial());
    }

    /** @test */
    public function invoice_creation_respects_plan_limits()
    {
        $company = Company::factory()->create();
        $user = User::factory()->owner()->create(['company_id' => $company->id]);
        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Create 10 invoices (the limit)
        Invoice::factory()->count(10)->create(['company_id' => $company->id]);

        // Try to create one more
        $response = $this->actingAs($user)
            ->post(route('invoices.store'), [
                'client_name' => 'Test Client',
                'amount' => 100,
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('error');
    }
}
