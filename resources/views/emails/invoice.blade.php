<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9fafb;
        }
        .invoice-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 8px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6B7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $invoice->client_name }},</p>
            
            <p>Thank you for your business. Please find attached your invoice.</p>
            
            <div class="invoice-details">
                <h2>Invoice Details</h2>
                <table>
                    <tr>
                        <td><strong>Invoice Number:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Invoice Date:</strong></td>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Amount Due:</strong></td>
                        <td class="amount">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                </table>
            </div>
            
            @if($invoice->notes)
            <div class="invoice-details">
                <h3>Notes</h3>
                <p>{{ $invoice->notes }}</p>
            </div>
            @endif
            
            <p>If you have any questions about this invoice, please contact us.</p>
            
            <p>
                Best regards,<br>
                <strong>{{ $company->name }}</strong><br>
                @if($company->email)
                    {{ $company->email }}<br>
                @endif
                @if($company->phone)
                    {{ $company->phone }}
                @endif
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
