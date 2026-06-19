<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinanceIncome;
use App\Models\FinanceExpense;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_finance')->only(['incomeIndex', 'expenseIndex', 'budgetIndex', 'bankAccountsIndex']);
        $this->middleware('permission:create_income')->only(['incomeStore']);
        $this->middleware('permission:create_expense')->only(['expenseStore']);
    }

    public function incomeIndex(Request $request)
    {
        $query = FinanceIncome::with('category')->latest('income_date');
        
        // Optional month/year filters for charts and reporting
        if ($request->has('month')) {
            $query->whereMonth('income_date', $request->month);
        }
        if ($request->has('year')) {
            $query->whereYear('income_date', $request->year);
        }
        
        return response()->json($query->paginate(20));
    }

    public function incomeStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $income = FinanceIncome::create($validated);
        return response()->json($income->load('category'), 201);
    }

    public function expenseIndex(Request $request)
    {
        $query = FinanceExpense::with('category')->latest('expense_date');
        
        if ($request->has('month')) {
            $query->whereMonth('expense_date', $request->month);
        }
        if ($request->has('year')) {
            $query->whereYear('expense_date', $request->year);
        }
        
        return response()->json($query->paginate(20));
    }

    public function expenseStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $expense = FinanceExpense::create($validated);
        return response()->json($expense->load('category'), 201);
    }

    public function budgetIndex()
    {
        if (class_exists(\App\Models\Budget::class)) {
            return response()->json(\App\Models\Budget::with('category')->get());
        }
        return response()->json([]);
    }

    public function budgetStore(Request $request)
    {
        if (class_exists(\App\Models\Budget::class)) {
            $validated = $request->validate([
                'category_id' => 'required|exists:finance_categories,id',
                'budget_amount' => 'required|numeric|min:0',
                'financial_year' => 'required|string|max:4'
            ]);
            $budget = \App\Models\Budget::create($validated);
            return response()->json($budget->load('category'), 201);
        }
        return response()->json(['message' => 'Budget model not implemented'], 501);
    }

    public function bankAccountsIndex()
    {
        if (class_exists(\App\Models\BankAccount::class)) {
            return response()->json(\App\Models\BankAccount::all());
        }
        return response()->json([]);
    }

    public function bankAccountsStore(Request $request)
    {
        if (class_exists(\App\Models\BankAccount::class)) {
            $validated = $request->validate([
                'bank_name' => 'required|string|max:255',
                'account_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'balance' => 'required|numeric'
            ]);
            $account = \App\Models\BankAccount::create($validated);
            return response()->json($account, 201);
        }
        return response()->json(['message' => 'BankAccount model not implemented'], 501);
    }
}
