<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Models\Setting;

class FinanceSettingsController extends Controller
{
    /**
     * Get system overview stats for the finance settings dashboard.
     */
    public function overview(Request $request)
    {
        $churchId = $request->user()->church_id;
        
        // Active Bank Accounts
        $activeBankAccounts = BankAccount::where('church_id', $churchId)
            ->where('status', 'active')
            ->count();

        // Active Payment Methods
        // Defaulting to 3 for Cash, Card, Bank Transfer since there is no payment_methods table
        $activePaymentMethods = 3;

        // Fetch configured currency and fiscal year from settings
        $currency = Setting::where('church_id', $churchId)
            ->where('key', 'finance_currency')
            ->value('value') ?? 'USD';
            
        $fiscalYearStart = Setting::where('church_id', $churchId)
            ->where('key', 'finance_fiscal_year_start')
            ->value('value') ?? 'January';

        $financialYearDisplay = Setting::where('church_id', $churchId)
            ->where('key', 'finance_year_display')
            ->value('value') ?? date('Y');

        // Calculate the financial year string based on start month
        // e.g., "01 Jan - 31 Dec"
        $monthNum = date('m', strtotime($fiscalYearStart));
        $startDateStr = "01 " . substr($fiscalYearStart, 0, 3);
        
        // Find the end month (start month - 1, looping around)
        $endMonthNum = $monthNum == 1 ? 12 : $monthNum - 1;
        $endMonthName = date('M', mktime(0, 0, 0, $endMonthNum, 10));
        $endDay = date('t', mktime(0, 0, 0, $endMonthNum, 10));
        $endDateStr = "$endDay $endMonthName";
        
        $financialYearRange = "$startDateStr - $endDateStr";

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $currency,
                'financial_year_range' => $financialYearRange,
                'fiscal_year_start' => $fiscalYearStart,
                'active_bank_accounts' => $activeBankAccounts,
                'active_payment_methods' => $activePaymentMethods,
            ]
        ]);
    }
}
