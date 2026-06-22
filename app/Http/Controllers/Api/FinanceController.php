<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinanceIncome;
use App\Models\FinanceExpense;
use App\Models\FinanceCategory;
use App\Models\BankAccount;
use App\Models\Budget;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FinanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_finance', only: [
                'recordsIndex', 'categoriesIndex', 'bankAccountsIndex', 'budgetIndex'
            ]),
            new Middleware('permission:create_income', only: ['incomeStore']),
            new Middleware('permission:create_expense', only: ['expenseStore']),
        ];
    }

    // --- Unified Ledger Records ---
    public function recordsIndex(Request $request)
    {
        $incomes = FinanceIncome::latest('income_date')->get();
        $expenses = FinanceExpense::latest('expense_date')->get();

        $formatted = [];

        foreach ($incomes as $inc) {
            $formatted[] = [
                'id' => 'income-' . $inc->id,
                'type' => 'income',
                'category' => $inc->category,
                'amount' => (float) $inc->amount,
                'date' => $inc->income_date,
                'description' => $inc->description ?? '',
                'method' => $inc->method,
                'receipt' => $inc->receipt,
                'tenantId' => (string) $inc->church_id
            ];
        }

        foreach ($expenses as $exp) {
            $formatted[] = [
                'id' => 'expense-' . $exp->id,
                'type' => 'expense',
                'category' => $exp->category,
                'amount' => (float) $exp->amount,
                'date' => $exp->expense_date,
                'description' => $exp->description ?? '',
                'method' => $exp->method,
                'receipt' => $exp->receipt,
                'tenantId' => (string) $exp->church_id
            ];
        }

        return response()->json($formatted);
    }

    public function recordsDestroy(Request $request, $id)
    {
        // Expects format: type-integerId, e.g. income-5 or expense-3
        $parts = explode('-', $id);
        if (count($parts) !== 2) {
            return response()->json(['message' => 'Invalid transaction ID format'], 400);
        }

        $type = $parts[0];
        $realId = $parts[1];

        if ($type === 'income') {
            $record = FinanceIncome::findOrFail($realId);
            $record->delete();
        } elseif ($type === 'expense') {
            $record = FinanceExpense::findOrFail($realId);
            $record->delete();
        } else {
            return response()->json(['message' => 'Invalid transaction type'], 400);
        }

        return response()->json(['success' => true]);
    }

    // --- Income ---
    public function incomeStore(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'method' => 'required|string|in:Cash,Bank Transfer',
            'receipt' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $income = FinanceIncome::create($validated);
        return response()->json($income, 201);
    }

    public function incomeUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'method' => 'required|string|in:Cash,Bank Transfer',
            'receipt' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $income = FinanceIncome::findOrFail($id);
        $income->update($validated);
        return response()->json($income);
    }

    // --- Expenses ---
    public function expenseStore(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'method' => 'required|string|in:Cash,Bank Transfer',
            'receipt' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $expense = FinanceExpense::create($validated);
        return response()->json($expense, 201);
    }

    public function expenseUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'method' => 'required|string|in:Cash,Bank Transfer',
            'receipt' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $expense = FinanceExpense::findOrFail($id);
        $expense->update($validated);
        return response()->json($expense);
    }

    // --- Categories ---
    public function categoriesIndex()
    {
        $categories = FinanceCategory::all();
        return response()->json($categories);
    }

    public function categoriesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Income,Expense',
            'description' => 'required|string',
            'status' => 'required|string|in:Active,Inactive',
            'created_on' => 'nullable|date',
            'created_by' => 'nullable|string|max:255'
        ]);

        if (empty($validated['created_on'])) {
            $validated['created_on'] = date('Y-m-d');
        }

        $category = FinanceCategory::create($validated);
        return response()->json($category, 201);
    }

    public function categoriesUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Income,Expense',
            'description' => 'required|string',
            'status' => 'required|string|in:Active,Inactive',
            'created_on' => 'nullable|date',
            'created_by' => 'nullable|string|max:255'
        ]);

        $category = FinanceCategory::findOrFail($id);
        $category->update($validated);
        return response()->json($category);
    }

    public function categoriesDestroy($id)
    {
        $category = FinanceCategory::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true]);
    }

    // --- Bank Accounts ---
    public function bankAccountsIndex()
    {
        $accounts = BankAccount::all();
        return response()->json($accounts);
    }

    public function bankAccountsStore(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|string|in:Current,Savings',
            'balance' => 'required|numeric',
            'ledger_balance' => 'nullable|numeric',
            'status' => 'required|string|in:Active,Inactive',
            'branch' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'last_statement_date' => 'nullable|date',
            'created_on' => 'nullable|date',
            'created_by' => 'nullable|string|max:255'
        ]);

        if (is_null($validated['ledger_balance'])) {
            $validated['ledger_balance'] = $validated['balance'];
        }
        if (empty($validated['created_on'])) {
            $validated['created_on'] = date('Y-m-d');
        }
        if (empty($validated['last_statement_date'])) {
            $validated['last_statement_date'] = date('Y-m-d');
        }

        $account = BankAccount::create($validated);
        return response()->json($account, 201);
    }

    public function bankAccountsUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|string|in:Current,Savings',
            'balance' => 'required|numeric',
            'ledger_balance' => 'nullable|numeric',
            'status' => 'required|string|in:Active,Inactive',
            'branch' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'last_statement_date' => 'nullable|date',
            'created_on' => 'nullable|date',
            'created_by' => 'nullable|string|max:255'
        ]);

        $account = BankAccount::findOrFail($id);
        $account->update($validated);
        return response()->json($account);
    }

    public function bankAccountsDestroy($id)
    {
        $account = BankAccount::findOrFail($id);
        $account->delete();
        return response()->json(['success' => true]);
    }

    // --- Budgets ---
    public function budgetIndex()
    {
        $budgets = Budget::all();
        return response()->json($budgets);
    }

    public function budgetStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Operating,Capital,Ministry',
            'budget_amount' => 'required|numeric|min:0',
            'spent_amount' => 'nullable|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'status' => 'required|string|in:In Progress,Completed',
            'description' => 'nullable|string',
            'created_on' => 'nullable|date'
        ]);

        if (is_null($validated['spent_amount'])) {
            $validated['spent_amount'] = 0;
        }
        if (empty($validated['created_on'])) {
            $validated['created_on'] = date('Y-m-d');
        }

        $budget = Budget::create($validated);
        return response()->json($budget, 201);
    }

    public function budgetUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Operating,Capital,Ministry',
            'budget_amount' => 'required|numeric|min:0',
            'spent_amount' => 'nullable|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'status' => 'required|string|in:In Progress,Completed',
            'description' => 'nullable|string',
            'created_on' => 'nullable|date'
        ]);

        $budget = Budget::findOrFail($id);
        $budget->update($validated);
        return response()->json($budget);
    }

    public function budgetDestroy($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->delete();
        return response()->json(['success' => true]);
    }
}
