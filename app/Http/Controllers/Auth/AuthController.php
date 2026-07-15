<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Enforce Rate Limiting (Failed Login Protection)
        $throttleKey = \Illuminate\Support\Str::lower($request->email) . '|' . $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            $user = \App\Models\User::where('email', $request->email)->first();
            \App\Models\AuditLog::create([
                'church_id' => $user ? $user->church_id : null,
                'user_id' => $user ? $user->id : null,
                'event' => 'login_locked',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);

            return response()->json([
                'message' => "Too many login attempts. Your account has been temporarily locked. Please try again in {$minutes} minutes."
            ], 429);
        }

        $isDesktop = $request->filled('church_id');

        if ($isDesktop) {
            // DESKTOP/WEB LOGIN FLOW
            $request->validate([
                'church_id' => 'required|string',
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $church = \App\Models\Church::where('registration_no', $request->church_id)
                                        ->orWhere('id', $request->church_id)
                                        ->first();

            if (!$church) {
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 900);
                return response()->json(['message' => 'Invalid Church/Activation ID.'], 401);
            }

            if ($church->status !== 'active') {
                return response()->json(['message' => 'This church account is inactive. Please contact support.'], 403);
            }

            $subscription = \App\Models\Subscription::where('church_id', $church->id)->latest()->first();
            $subscriptionStatus = 'none';
            $activePlan = null;
            if ($subscription) {
                if (in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
                    $subscriptionStatus = 'expired';
                } else {
                    $subscriptionStatus = 'active';
                    $activePlan = $subscription->plan;
                }
            }

            if ($subscriptionStatus !== 'active') {
                return response()->json(['message' => 'The subscription for this church has expired.'], 403);
            }

            $user = \App\Models\User::where('email', $request->email)
                                    ->where('church_id', $church->id)
                                    ->first();

            if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 900);

                \App\Models\AuditLog::create([
                    'church_id' => $church->id,
                    'user_id' => $user ? $user->id : null,
                    'event' => 'login_failed',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);

                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

            $user->last_login = now();
            $user->save();

            \App\Models\AuditLog::create([
                'church_id' => $church->id,
                'user_id' => $user->id,
                'event' => 'login_success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
                'church' => $church,
                'subscription_status' => $subscriptionStatus,
                'plan' => $activePlan,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]);

        } else {
            // MOBILE APP LOGIN FLOW
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 900);

                \App\Models\AuditLog::create([
                    'church_id' => $user ? $user->church_id : null,
                    'user_id' => $user ? $user->id : null,
                    'event' => 'login_failed',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);

                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            $member = \App\Models\Member::where('email', $user->email)
                                        ->where('church_id', $user->church_id)
                                        ->first();

            if (!$member || !$member->status) {
                return response()->json(['message' => 'Your member profile is inactive. Please contact your church administrator.'], 403);
            }

            $church = $user->church;
            if (!$church || $church->status !== 'active') {
                return response()->json(['message' => 'This church account is inactive.'], 403);
            }

            $subscription = \App\Models\Subscription::where('church_id', $church->id)->latest()->first();
            $subscriptionStatus = 'none';
            $activePlan = null;
            if ($subscription) {
                if (in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
                    $subscriptionStatus = 'expired';
                } else {
                    $subscriptionStatus = 'active';
                    $activePlan = $subscription->plan;
                }
            }

            if ($subscriptionStatus !== 'active') {
                return response()->json(['message' => 'The subscription for this church has expired.'], 403);
            }

            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

            $user->last_login = now();
            $user->save();

            \App\Models\AuditLog::create([
                'church_id' => $church->id,
                'user_id' => $user->id,
                'event' => 'login_success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
                'church' => $church,
                'subscription_status' => $subscriptionStatus,
                'plan' => $activePlan,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]);
        }
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
            'church_code' => $user->church ? $user->church->registration_no : null,
            'subscription_status' => $subscriptionStatus,
            'plan' => $activePlan,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            // Church info
            'church_name' => 'required|string|max:255',
            'denomination' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'church_phone' => 'required|string|max:50',
            'church_email' => 'required|email|max:255',

            // Admin info
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'required|string|max:50',
            'password' => 'required|string|min:6',
            'plan_name' => 'nullable|string'
        ]);

        // Generate a unique registration number (Activation ID) starting with 'FC-'
        do {
            $registrationNo = 'FC-' . mt_rand(100000, 999999);
        } while (\App\Models\Church::where('registration_no', $registrationNo)->exists());

        // 1. Create Church
        $church = \App\Models\Church::create([
            'church_name' => $request->church_name,
            'registration_no' => $registrationNo,
            'pastor_name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->church_email,
            'phone' => $request->church_phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'status' => 'active'
        ]);

        // 2. Create Admin User
        $user = \App\Models\User::create([
            'church_id' => $church->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'status' => true
        ]);

        // Assign 'Church Administrator' Spatie role
        $user->assignRole('Church Administrator');

        // 3. Create Subscription
        $planName = $request->input('plan_name', 'Free');
        $plan = \App\Models\Plan::where('name', $planName)->first();
        if (!$plan) {
            $plan = \App\Models\Plan::where('name', 'Free')->first();
        }

        if ($plan) {
            \App\Models\Subscription::create([
                'church_id' => $church->id,
                'plan_id' => $plan->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'amount' => $plan->price,
                'status' => 'active'
            ]);
        }

        // Generate Auth Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Church registered successfully.',
            'token' => $token,
            'user' => $user,
            'church' => $church,
            'subscription_status' => 'active',
            'plan' => $plan
        ], 201);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'activation_code' => 'required|string'
        ]);

        $church = \App\Models\Church::where('registration_no', $request->activation_code)->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid activation code.'
            ], 404);
        }

        if ($church->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This church account is inactive. Please contact support.'
            ], 403);
        }

        // Check if there is an active subscription
        $subscription = \App\Models\Subscription::where('church_id', $church->id)->latest()->first();
        if (!$subscription || in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'The subscription for this church has expired.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'tenantId' => $church->id,
            'churchName' => $church->church_name
        ]);
    }
}
