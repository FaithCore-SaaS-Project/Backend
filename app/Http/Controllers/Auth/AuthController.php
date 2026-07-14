<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'church_id' => 'nullable|string'
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($request->filled('church_id')) {
            $churchIdMatches = ($user->church_id == $request->church_id) || 
                               ($user->church && $user->church->registration_no == $request->church_id);
            
            if (!$churchIdMatches) {
                return response()->json(['message' => 'The provided Church/Activation ID does not match this account.'], 401);
            }
        }

        $user->last_login = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Get subscription status
        $subscription = \App\Models\Subscription::where('church_id', $user->church_id)->latest()->first();
        $subscriptionStatus = 'none';
        $activePlan = null;
        if ($subscription) {
            if (in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
                $subscriptionStatus = 'expired';
                if ($subscription->status !== 'expired') {
                    $subscription->update(['status' => 'expired']);
                }
            } else {
                $subscriptionStatus = 'active';
                $activePlan = $subscription->plan;
            }
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
            'church' => $user->church,
            'subscription_status' => $subscriptionStatus,
            'plan' => $activePlan,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('church');
        $activePlan = $user->church ? $user->church->activePlan() : null;
        $subscriptionStatus = 'none';
        if ($user->church) {
            $subscription = \App\Models\Subscription::where('church_id', $user->church_id)->latest()->first();
            if ($subscription) {
                if (in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
                    $subscriptionStatus = 'expired';
                } else {
                    $subscriptionStatus = 'active';
                }
            }
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'role' => $user->getRoleNames()->first(),
            'church' => $user->church ? $user->church->church_name : null,
            'subscription_status' => $subscriptionStatus,
            'plan' => $activePlan,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
