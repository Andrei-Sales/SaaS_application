<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Subscription Management</h2>

                    @if($subscription)
                        <div class="mb-8">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                                <h3 class="text-2xl font-bold mb-2">{{ ucfirst($subscription->plan) }} Plan</h3>
                                <p class="mb-4">
                                    Status:
                                    <span class="px-3 py-1 bg-white text-indigo-600 rounded-full font-semibold">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </p>

                                @if($subscription->trial_ends_at)
                                    <p class="text-sm">Trial ends: {{ $subscription->trial_ends_at->format('M d, Y') }}</p>
                                @endif

                                @if($subscription->ends_at)
                                    <p class="text-sm">Subscription ends: {{ $subscription->ends_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Usage Stats -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Usage</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-700">Invoices Created</span>
                                    <span class="font-bold text-gray-900">
                                        {{ $invoiceCount }}
                                        @if($invoiceLimit)
                                            / {{ $invoiceLimit }}
                                        @else
                                            / Unlimited
                                        @endif
                                    </span>
                                </div>
                                @if($invoiceLimit && $invoiceLimit > 0)
                                    @php
                                        $percentage = min(($invoiceCount / $invoiceLimit) * 100, 100);
                                    @endphp
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-4">
                            @if($subscription->plan === 'free')
                                <a href="{{ route('subscriptions.upgrade') }}" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                    Upgrade to Pro
                                </a>
                            @endif

                            @if($subscription->isActive() && !$subscription->onTrial())
                                <form action="{{ route('subscriptions.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel your subscription?');">
                                    @csrf
                                    <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                                        Cancel Subscription
                                    </button>
                                </form>
                            @endif

                            @if($subscription->canceled())
                                <form action="{{ route('subscriptions.resume') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                                        Resume Subscription
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-600 mb-6">You don't have an active subscription.</p>
                            <a href="{{ route('subscriptions.upgrade') }}" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                Choose a Plan
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
