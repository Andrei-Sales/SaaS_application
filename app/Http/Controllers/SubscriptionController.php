<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->middleware(['auth', 'verified', 'tenant']);
    }

    /**
     * Show subscription management page.
     */
    public function index()
    {
        $company = auth()->user()->company;
        $this->authorize('manageSubscription', $company);

        $subscription = $company->subscription;
        $invoiceCount = $company->invoices()->count();
        $invoiceLimit = $this->subscriptionService->getInvoiceLimit(
            $subscription ? $subscription->plan : 'free'
        );

        return view('subscriptions.index', compact('subscription', 'invoiceCount', 'invoiceLimit'));
    }

    /**
     * Show upgrade page.
     */
    public function showUpgrade()
    {
        $company = auth()->user()->company;
        $this->authorize('manageSubscription', $company);

        return view('subscriptions.upgrade');
    }

    /**
     * Process subscription upgrade.
     */
    public function upgrade(Request $request)
    {
        $company = auth()->user()->company;
        $this->authorize('manageSubscription', $company);

        $request->validate([
            'plan' => 'required|in:pro',
            'payment_method' => 'nullable|string',
        ]);

        try {
            if ($company->subscription) {
                $this->subscriptionService->changePlan($company->subscription, $request->plan);
            } else {
                $this->subscriptionService->createSubscription($company, $request->plan, 'active');
            }

            return redirect()
                ->route('subscriptions.index')
                ->with('success', 'Successfully upgraded to Pro plan!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to upgrade subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $company = auth()->user()->company;
        $this->authorize('manageSubscription', $company);

        if (!$company->subscription) {
            return redirect()
                ->route('subscriptions.index')
                ->with('error', 'No active subscription to cancel.');
        }

        $immediately = $request->boolean('immediately', false);

        $this->subscriptionService->cancelSubscription($company->subscription, $immediately);

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'Subscription canceled successfully.');
    }

    /**
     * Resume subscription.
     */
    public function resume()
    {
        $company = auth()->user()->company;
        $this->authorize('manageSubscription', $company);

        if (!$company->subscription || !$company->subscription->canceled()) {
            return redirect()
                ->route('subscriptions.index')
                ->with('error', 'No canceled subscription to resume.');
        }

        try {
            $this->subscriptionService->resumeSubscription($company->subscription);

            return redirect()
                ->route('subscriptions.index')
                ->with('success', 'Subscription resumed successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to resume subscription: ' . $e->getMessage());
        }
    }
}
