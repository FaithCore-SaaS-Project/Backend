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
}
