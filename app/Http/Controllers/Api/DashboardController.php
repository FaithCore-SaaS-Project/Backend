<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Family;
use App\Models\FinanceIncome;
use App\Models\FinanceExpense;
use App\Models\Event;
use App\Models\Subscription;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard statistics.
     */
    public function stats(Request $request)
    {
        // ChurchScope is automatically applied to all these queries via the BelongsToChurch trait!
        
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $totalMembers = Member::count();
        $totalFamilies = Family::count();
        
        $monthlyIncome = FinanceIncome::whereMonth('income_date', $currentMonth)
            ->whereYear('income_date', $currentYear)
            ->sum('amount');
            
        $monthlyExpense = FinanceExpense::whereMonth('expense_date', $currentMonth)
            ->whereYear('expense_date', $currentYear)
            ->sum('amount');

        $upcomingEvents = Event::where('event_date', '>=', Carbon::today())
            ->orderBy('event_date', 'asc')
            ->take(5)
            ->get();

        $recentMembers = Member::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentDonations = FinanceIncome::orderBy('income_date', 'desc')
            ->take(5)
            ->get();

        $subscription = Subscription::with('plan')->first();

        return response()->json([
            'stats' => [
                'total_members' => $totalMembers,
                'total_families' => $totalFamilies,
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
            ],
            'upcoming_events' => $upcomingEvents,
            'recent_members' => $recentMembers,
            'recent_donations' => $recentDonations,
            'subscription_status' => $subscription ? $subscription->status : 'No Active Subscription',
            'current_plan' => $subscription?->plan ? $subscription->plan->name : 'N/A'
        ]);
    }
}
