<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0;
        }
        .company-info {
            margin-bottom: 10px;
        }
        .invoice-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details .left,
        .invoice-details .right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-block h3 {
            color: #4F46E5;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-block p {
            margin: 5px 0;
            font-size: 14px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th {
            background-color: #4F46E5;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-table .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 18px;
        }
        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 4px solid #4F46E5;
        }
        .notes h3 {
            margin-top: 0;
            color: #4F46E5;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #6B7280;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #DEF7EC;
            color: #03543F;
        }
        .status-sent {
            background-color: #E1EFFE;
            color: #1E429F;
        }
        .status-draft {
            background-color: #F3F4F6;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <div class="company-info">
            <strong>{{ $company->name }}</strong><br>
            @if($company->address)
                {{ $company->address }}<br>
            @endif
            @if($company->phone)
                Phone: {{ $company->phone }}<br>
            @endif
            @if($company->email)
                Email: {{ $company->email }}<br>
            @endif
            @if($company->tax_id)
                Tax ID: {{ $company->tax_id }}
            @endif
        </div>
    </div>

    <div class="invoice-details">
        <div class="left">
            <div class="info-block">
                <h3>Bill To</h3>
                <p><strong>{{ $invoice->client_name }}</strong></p>
                @if($invoice->client_email)
                    <p>{{ $invoice->client_email }}</p>
                @endif
                @if($invoice->client_address)
                    <p>{{ $invoice->client_address }}</p>
                @endif
            </div>
        </div>
        
        <div class="right">
            <div class="info-block">
                <h3>Invoice Details</h3>
                <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Invoice Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                <p>
                    <strong>Status:</strong>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </p>
                @if($invoice->paid_at)
                    <p><strong>Paid On:</strong> {{ $invoice->paid_at->format('M d, Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Invoice {{ $invoice->invoice_number }}</td>
                <td style="text-align: right;">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Amount Due</td>
                <td style="text-align: right;">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($invoice->notes)
    <div class="notes">
        <h3>Notes</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated on {{ now()->format('M d, Y \a\t H:i:s') }}</p>
    </div>
</body>
</html>
