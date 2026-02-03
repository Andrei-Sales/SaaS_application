<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-600">Welcome back, <strong>{{ Auth::user()->name }}</strong>!</p>
                        <p class="text-sm text-gray-500 mt-1">Company: <strong>{{ Auth::user()->company->name }}</strong></p>
                        <p class="text-sm text-gray-500">
                            Plan: <strong class="text-indigo-600">
                                {{ Auth::user()->company->subscription ? ucfirst(Auth::user()->company->subscription->plan) : 'No Active Plan' }}
                            </strong>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                            <h3 class="text-lg font-semibold mb-2">Total Invoices</h3>
                            <p class="text-3xl font-bold">{{ Auth::user()->company->invoices()->count() }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                            <h3 class="text-lg font-semibold mb-2">Paid Invoices</h3>
                            <p class="text-3xl font-bold">{{ Auth::user()->company->invoices()->paid()->count() }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-md p-6 text-white">
                            <h3 class="text-lg font-semibold mb-2">Pending</h3>
                            <p class="text-3xl font-bold">{{ Auth::user()->company->invoices()->whereIn('status', ['draft', 'sent'])->count() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('invoices.index') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                            View All Invoices
                        </a>
                        <a href="{{ route('invoices.create') }}" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition">
                            Create New Invoice
                        </a>
                    </div>

                    @if(Auth::user()->company->subscription && Auth::user()->company->subscription->plan === 'free')
                        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        You're on the <strong>Free Plan</strong>. <a href="{{ route('subscriptions.upgrade') }}" class="underline hover:text-yellow-800">Upgrade to Pro</a> to unlock unlimited invoices and more features.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
