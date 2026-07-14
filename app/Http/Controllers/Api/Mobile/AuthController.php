<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('church', 'role')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Return token
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'email'      => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone'      => 'sometimes|nullable|string|max:30',
            'gender'     => 'sometimes|nullable|in:Male,Female,Other',
            'dob'        => 'sometimes|nullable|date',
            'address'    => 'sometimes|nullable|string|max:255',
        ]);

        // Update user table fields
        $userFields = array_filter([
            'first_name' => $validated['first_name'] ?? null,
            'last_name'  => $validated['last_name'] ?? null,
            'email'      => $validated['email'] ?? null,
        ]);
        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Update linked member profile if exists
        $member = $user->member;
        if ($member) {
            $memberFields = array_filter([
                'first_name' => $validated['first_name'] ?? null,
                'last_name'  => $validated['last_name'] ?? null,
                'email'      => $validated['email'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'gender'     => $validated['gender'] ?? null,
                'dob'        => $validated['dob'] ?? null,
                'address'    => $validated['address'] ?? null,
            ], fn($v) => !is_null($v));
            if (!empty($memberFields)) {
                $member->update($memberFields);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $user->fresh()->load('member', 'church'),
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Member profile not found.'], 404);
        }

        // Delete old photo if exists
        if ($member->photo) {
            Storage::disk('public')->delete($member->photo);
        }

        $path = $request->file('avatar')->store('member-photos', 'public');
        $member->update(['photo' => $path]);

        return response()->json([
            'success'   => true,
            'message'   => 'Avatar updated successfully.',
            'photo_url' => $member->fresh()->photo_url,
        ]);
    }

    public function savePushToken(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string',
        ]);

        $request->user()->update([
            'push_token' => $request->push_token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token saved successfully.'
        ]);
    }
}
