<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { float: right; width: 300px; }
        .summary table { width: 100%; }
        .summary th { text-align: left; }
        .summary td { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FaithCore SaaS</h1>
        <h2>Financial Report</h2>
        <p>Period: {{ $start_date }} to {{ $end_date }}</p>
    </div>
    
    <h3>Incomes</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomes as $income)
            <tr>
                <td>{{ $income->income_date }}</td>
                <td>{{ $income->category->name ?? 'N/A' }}</td>
                <td>{{ $income->description }}</td>
                <td>${{ number_format($income->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Expenses</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->expense_date }}</td>
                <td>{{ $expense->category->name ?? 'N/A' }}</td>
                <td>{{ $expense->description }}</td>
                <td>${{ number_format($expense->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <th>Total Income:</th>
                <td>${{ number_format($total_income, 2) }}</td>
            </tr>
            <tr>
                <th>Total Expense:</th>
                <td>${{ number_format($total_expense, 2) }}</td>
            </tr>
            <tr>
                <th>Net Balance:</th>
                <td><strong>${{ number_format($net_balance, 2) }}</strong></td>
            </tr>
        </table>
    </div>
</body>
</html>
