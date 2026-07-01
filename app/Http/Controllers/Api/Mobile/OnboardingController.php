<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Church;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                        ->where('status', 'active')
                        ->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found with the provided invite code.'
            ], 404);
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
     * Mock Send OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        // In a real scenario, integrate SMS Gateway (e.g., Twilio) here.
        // For v1, we mock it. The OTP is hardcoded as '1234' on the client side,
        // but we just return success.

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to ' . $request->phone
        ]);
    }

    /**
     * Mock Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string'
        ]);

        if ($request->otp !== '1234') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully.'
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
