<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for subscription management.
 *
 * Handles subscription creation, upgrades, cancellations, and plan checks.
 * In a production environment, this would integrate with Stripe API.
 */
class SubscriptionService
{
    /**
     * Create a new subscription for a company.
     */
    public function createSubscription(Company $company, string $plan, string $status = 'trial'): Subscription
    {
        return DB::transaction(function () use ($company, $plan, $status) {
            // Cancel any existing active subscription
            if ($company->subscription && $company->subscription->isActive()) {
                $this->cancelSubscription($company->subscription);
            }

            $data = [
                'company_id' => $company->id,
                'plan' => $plan,
                'status' => $status,
            ];

            // Set trial end date for new subscriptions
            if ($status === 'trial') {
                $data['trial_ends_at'] = now()->addDays(14);
            }

            $subscription = Subscription::create($data);

            Log::info('Subscription created', [
                'company_id' => $company->id,
                'plan' => $plan,
                'status' => $status,
            ]);

            return $subscription;
        });
    }

    /**
     * Upgrade or downgrade a subscription plan.
     */
    public function changePlan(Subscription $subscription, string $newPlan): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlan) {
            $oldPlan = $subscription->plan;

            $subscription->update([
                'plan' => $newPlan,
                'status' => 'active',
                'trial_ends_at' => null, // End trial when changing plans
            ]);

            Log::info('Subscription plan changed', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription
    {
        return DB::transaction(function () use ($subscription, $immediately) {
            $data = ['status' => 'canceled'];

            if (!$immediately) {
                // Grace period: subscription remains active until end of billing period
                $data['ends_at'] = now()->addDays(30);
            } else {
                $data['ends_at'] = now();
            }

            $subscription->update($data);

            Log::info('Subscription canceled', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'immediately' => $immediately,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Reactivate a canceled subscription.
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        if (!$subscription->canceled()) {
            throw new \Exception('Cannot resume: Subscription is not canceled.');
        }

        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'active',
                'ends_at' => null,
            ]);

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Mark subscription as expired (typically run by scheduled task).
     */
    public function expireSubscription(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'expired']);

        Log::info('Subscription expired', [
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
        ]);

        return $subscription->fresh();
    }

    /**
     * Check if a company can access a feature based on their plan.
     */
    public function canAccessFeature(Company $company, string $feature): bool
    {
        if (!$company->subscription || !$company->subscription->isActive()) {
            return false;
        }

        $plan = $company->subscription->plan;

        // Define feature access per plan
        $features = [
            'free' => [
                'create_invoices' => true,
                'unlimited_invoices' => false,
                'pdf_export' => true,
                'email_invoices' => false,
                'custom_branding' => false,
            ],
            'pro' => [
                'create_invoices' => true,
                'unlimited_invoices' => true,
                'pdf_export' => true,
                'email_invoices' => true,
                'custom_branding' => true,
            ],
        ];

        return $features[$plan][$feature] ?? false;
    }

    /**
     * Get invoice limit for a plan.
     */
    public function getInvoiceLimit(string $plan): ?int
    {
        return match ($plan) {
            'free' => 10,
            'pro' => null, // unlimited
            default => 0,
        };
    }

    /**
     * Check if company has reached invoice limit.
     */
    public function hasReachedInvoiceLimit(Company $company): bool
    {
        if (!$company->subscription) {
            return true;
        }

        $limit = $this->getInvoiceLimit($company->subscription->plan);

        // Unlimited invoices
        if ($limit === null) {
            return false;
        }

        $currentCount = $company->invoices()->count();

        return $currentCount >= $limit;
    }

    /**
     * Process subscription with Stripe (mock implementation).
     *
     * In production, this would use Stripe API:
     * - Create customer
     * - Attach payment method
     * - Create subscription
     * - Handle webhooks
     */
    public function processStripeSubscription(Company $company, string $plan, string $paymentMethodId): array
    {
        // Mock implementation for demonstration
        // In production, use: \Stripe\Subscription::create()

        Log::info('Mock Stripe subscription processed', [
            'company_id' => $company->id,
            'plan' => $plan,
            'payment_method_id' => $paymentMethodId,
        ]);

        return [
            'success' => true,
            'stripe_subscription_id' => 'sub_mock_' . uniqid(),
            'message' => 'Subscription created successfully (mock mode)',
        ];
    }
}
