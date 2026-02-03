<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Invoice;
use App\Policies\CompanyPolicy;
use App\Policies\InvoicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        Company::class => CompanyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate to check if user's company is on free plan
        Gate::define('on-free-plan', function ($user) {
            return $user->company
                && $user->company->subscription
                && $user->company->subscription->plan === 'free';
        });

        // Gate to check if user's company is on pro plan
        Gate::define('on-pro-plan', function ($user) {
            return $user->company
                && $user->company->subscription
                && $user->company->subscription->plan === 'pro';
        });

        // Gate to check if user's company has active subscription
        Gate::define('has-active-subscription', function ($user) {
            return $user->company
                && $user->company->subscription
                && $user->company->subscription->isActive();
        });

        // Gate to check invoice limit for free plan (example: max 10 invoices)
        Gate::define('can-create-invoice', function ($user) {
            if (!$user->company) {
                return false;
            }

            $subscription = $user->company->subscription;

            // Pro plan has unlimited invoices
            if ($subscription && $subscription->plan === 'pro') {
                return true;
            }

            // Free plan: check if under limit (10 invoices)
            $invoiceCount = $user->company->invoices()->count();
            return $invoiceCount < 10;
        });
    }
}
