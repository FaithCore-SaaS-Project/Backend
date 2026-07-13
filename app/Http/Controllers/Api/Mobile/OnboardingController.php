<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Church;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OtpMail;

class OnboardingController extends Controller
{
    /**
     * Find Church by Registration No (Invite Code)
     */
    public function findChurch(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string'
        ]);

        $church = Church::where('registration_no', $request->invite_code)
                        ->orWhere('id', $request->invite_code) // fallback for testing
                        ->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found with the provided invite code.'
            ], 404);
        }

        // 1. Check if the church/invite code is active
        if ($church->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This invite code is inactive or has been revoked.'
            ], 403);
        }

        // 2. Check if the church subscription is active
        $subscription = \App\Models\Subscription::where('church_id', $church->id)->latest()->first();
        if (!$subscription || in_array($subscription->status, ['expired', 'cancelled']) || now()->gt($subscription->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'This church\'s FaithCore subscription has expired. Please contact the administrator.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $church->id,
                'name' => $church->church_name,
                'address' => $church->address . ', ' . $church->city,
                'logo' => $church->logo
            ]
        ]);
    }

    /**
     * Send OTP to Email
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Generate a random 4-digit OTP
        $otp = (string) mt_rand(1000, 9999);

        // Store OTP in cache for 10 minutes
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

        try {
            // Send OTP email
            Mail::to($request->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Log error but fallback in local development if needed
            logger()->error('Failed to send OTP email: ' . $e->getMessage());
            
            if (!app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP email. Please try again later.'
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to ' . $request->email
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string'
        ]);

        $cachedOtp = Cache::get('otp_' . $request->email);

        // Fallback to '1234' only in local development environment for easier testing
        $isLocalMock = app()->environment('local') && $request->otp === '1234';

        if ($cachedOtp !== $request->otp && !$isLocalMock) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.'
            ], 400);
        }

        // Clear OTP from cache on success
        Cache::forget('otp_' . $request->email);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.'
        ]);
    }

    /**
     * Register New Member and User
     */
    public function register(Request $request)
    {
        $request->validate([
            'church_id' => 'required|exists:churches,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|min:6'
        ]);

        $church = Church::find($request->church_id);
        if (!$church) {
            return response()->json(['success' => false, 'message' => 'Church not found.'], 404);
        }

        // 1. Create User
        $user = User::create([
            'church_id' => $church->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => true,
        ]);

        // Assign 'member' role if Spatie permissions are used. For now, rely on role context.

        // 2. Create Member
        $memberNo = 'MEM-' . strtoupper(Str::random(6)); // Auto generate member no
        
        $member = Member::create([
            'church_id' => $church->id,
            'member_no' => $memberNo,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => true,
            // defaults
            'gender' => 'male', // Requires default in schema or nullable, adjust if needed
        ]);

        // 3. Generate Auth Token
        $token = $user->createToken('mobile-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'token' => $token,
            'user' => $user->load('church')
        ]);
    }
}
