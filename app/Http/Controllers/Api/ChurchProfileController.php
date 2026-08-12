<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Church;
use Illuminate\Support\Facades\Storage;

class ChurchProfileController extends Controller
{
    /**
     * Get Church Profile
     */
    public function show(Request $request)
    {
        $church = $request->user()->church;
        
        if (!$church) {
            return response()->json(['message' => 'Church not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $church
        ]);
    }

    /**
     * Update Church Profile
     */
    public function update(Request $request)
    {
        $church = $request->user()->church;
        
        if (!$church) {
            return response()->json(['message' => 'Church not found'], 404);
        }

        $validated = $request->validate([
            'church_name' => 'sometimes|required|string|max:255',
            'pastor_name' => 'nullable|string|max:255',
            'year_established' => 'nullable|integer',
            'about' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'facebook' => 'nullable|url',
            'instagram' => 'nullable|url',
            'youtube' => 'nullable|url',
            'twitter' => 'nullable|url',
            'visibility_settings' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:4096',
        ]);

        $church->fill($request->except(['logo', 'cover_image']));

        if ($request->hasFile('logo')) {
            if ($church->logo && Storage::disk('public')->exists($church->logo)) {
                Storage::disk('public')->delete($church->logo);
            }
            $church->logo = $request->file('logo')->store('churches/logos', 'public');
            // Assuming logo_url is an accessor in model or we set a direct url field if needed
            // If the system expects logo to be an absolute URL, you can do:
            // $church->logo = asset('storage/' . $path);
        }

        if ($request->hasFile('cover_image')) {
            if ($church->cover_image && Storage::disk('public')->exists($church->cover_image)) {
                Storage::disk('public')->delete($church->cover_image);
            }
            $church->cover_image = $request->file('cover_image')->store('churches/covers', 'public');
        }

        if ($request->has('visibility_settings')) {
            // Merge with existing
            $existing = is_array($church->visibility_settings) ? $church->visibility_settings : [];
            $church->visibility_settings = array_merge($existing, $request->visibility_settings);
        }

        $church->save();

        return response()->json([
            'success' => true,
            'message' => 'Church profile updated successfully',
            'data' => $church
        ]);
    }
}
