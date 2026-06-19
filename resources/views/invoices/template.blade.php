<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .details { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-size: 1.2em; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FaithCore SaaS</h1>
        <h2>INVOICE</h2>
    </div>
    
    <div class="details">
        <p><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</p>
        <p><strong>Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}</p>
        <p><strong>Plan:</strong> {{ $subscription->plan->name ?? 'Custom Plan' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Subscription Charge for {{ $subscription->plan->name ?? 'Custom Plan' }}</td>
                <td>${{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Total Paid: ${{ number_format($invoice->total, 2) }}
    </div>
    
    <p style="margin-top: 50px; text-align: center; color: #777;">Thank you for using FaithCore!</p>
</body>
</html>
