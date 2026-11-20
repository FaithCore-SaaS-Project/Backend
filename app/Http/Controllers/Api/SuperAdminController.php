<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Church;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Get platform analytics
     */
    public function analytics()
    {
        $totalChurches = Church::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        
        // Calculate MRR (Monthly Recurring Revenue) roughly based on active standard subscriptions
        $mrr = Subscription::where('status', 'active')->sum('amount');

        return response()->json([
            'total_churches' => $totalChurches,
            'active_subscriptions' => $activeSubscriptions,
            'total_revenue' => $totalRevenue,
            'mrr' => $mrr,
            'status' => 'success'
        ]);
    }

    /**
     * Get list of all registered churches
     */
    public function churches()
    {
        $churches = Church::with(['subscriptions.plan'])->withCount(['users', 'members'])->get();
        return response()->json($churches);
    }

    /**
     * Get list of all subscriptions
     */
    public function subscriptions()
    {
        $subscriptions = Subscription::with(['church', 'plan'])->latest()->get();
        return response()->json($subscriptions);
    }

    /**
     * Get list of all payment logs
     */
    public function payments()
    {
        $payments = Payment::with(['church', 'plan'])->latest()->get();
        return response()->json($payments);
    }

    /**
     * Toggle status (active/inactive) of a church account
     */
    public function toggleChurchStatus(Request $request, $id)
    {
        $church = Church::findOrFail($id);
        $church->status = $church->status === 'active' ? 'inactive' : 'active';
        $church->save();

        return response()->json([
            'message' => 'Church status updated successfully',
            'church' => $church
        ]);
    }

    /**
     * Update Church SMS Settings (Sender ID and manually add Top-up Balance)
     */
    public function updateSmsSettings(Request $request, $id)
    {
        $request->validate([
            'sender_id' => 'nullable|string|max:15',
            'add_topup' => 'nullable|integer'
        ]);

        $church = Church::findOrFail($id);
        
        if ($request->has('sender_id')) {
            $church->sms_sender_id = $request->sender_id;
        }

        if ($request->has('add_topup') && $request->add_topup > 0) {
            $church->topup_sms_balance += $request->add_topup;
        }

        $church->save();

        return response()->json([
            'message' => 'SMS Settings updated successfully',
            'church' => $church
        ]);
    }

    /**
     * Permanently delete a church and its associated cascading data
     */
    public function deleteChurch(Request $request, $id)
    {
        $church = Church::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($church) {
            // Delete all child relational data across all modules
            \Illuminate\Support\Facades\DB::table('users')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('members')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('families')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('departments')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('department_members')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('events')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('event_registrations')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('event_attendances')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('finance_income')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('finance_expenses')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('finance_categories')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('bank_accounts')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('budgets')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('documents')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('letters')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('certificates')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('receipts')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('subscriptions')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('payments')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('invoices')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('audit_logs')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('settings')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('prayer_requests')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('announcements')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('church_services')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('sms_topup_requests')->where('church_id', $church->id)->delete();
            \Illuminate\Support\Facades\DB::table('saved_reports')->where('church_id', $church->id)->delete();

            // Delete Church
            $church->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Church and all associated records deleted permanently.'
        ]);
    }
}
