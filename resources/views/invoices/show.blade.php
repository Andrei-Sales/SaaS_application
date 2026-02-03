@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Invoice #{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-gray-600 mt-1">Created on {{ $invoice->created_at->format('M d, Y') }}</p>
            </div>
            <div class="flex space-x-2">
                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Edit
                    </a>
                @endcan
                @can('send', $invoice)
                    @if(!$invoice->isPaid())
                        <form action="{{ route('invoices.mark-as-sent', $invoice) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                                Mark as Sent
                            </button>
                        </form>
                    @endif
                @endcan
                @can('markAsPaid', $invoice)
                    @if(!$invoice->isPaid())
                        <form action="{{ route('invoices.mark-as-paid', $invoice) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Mark as Paid
                            </button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('invoices.pdf', $invoice) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700" target="_blank">
                    Download PDF
                </a>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="mb-6">
            <span class="px-4 py-2 text-sm font-semibold rounded-full
                @if($invoice->status === 'paid') bg-green-100 text-green-800
                @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800
                @else bg-gray-100 text-gray-800
                @endif">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>

        <!-- Invoice Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Client Information</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Name:</span>
                        <span class="text-sm text-gray-900 ml-2">{{ $invoice->client_name }}</span>
                    </div>
                    @if($invoice->client_email)
                        <div>
                            <span class="text-sm font-medium text-gray-600">Email:</span>
                            <span class="text-sm text-gray-900 ml-2">{{ $invoice->client_email }}</span>
                        </div>
                    @endif
                    @if($invoice->client_address)
                        <div>
                            <span class="text-sm font-medium text-gray-600">Address:</span>
                            <span class="text-sm text-gray-900 ml-2">{{ $invoice->client_address }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Invoice Details</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Amount:</span>
                        <span class="text-lg font-bold text-gray-900 ml-2">${{ number_format($invoice->amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Due Date:</span>
                        <span class="text-sm text-gray-900 ml-2">{{ $invoice->due_date->format('M d, Y') }}</span>
                    </div>
                    @if($invoice->paid_at)
                        <div>
                            <span class="text-sm font-medium text-gray-600">Paid On:</span>
                            <span class="text-sm text-gray-900 ml-2">{{ $invoice->paid_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes</h3>
                <p class="text-sm text-gray-700">{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="mt-6 flex justify-between">
            <a href="{{ route('invoices.index') }}" class="text-indigo-600 hover:text-indigo-900">
                ← Back to Invoices
            </a>
            @can('delete', $invoice)
                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-900">
                        Delete Invoice
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>
@endsection
