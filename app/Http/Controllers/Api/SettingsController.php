<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $churchId = $request->user()->church_id;
        $settings = \App\Models\Setting::where('church_id', $churchId)->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Store or update settings in bulk.
     */
    public function store(Request $request)
    {
        $data = $request->except(['church_id', 'id', 'created_at', 'updated_at']);
        $churchId = $request->user()->church_id;

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['church_id' => $churchId, 'key' => $key],
                ['value' => $value]
            );

            // Keep church master currency column perfectly synchronized
            if ($key === 'finance_currency' && !empty($value)) {
                \App\Models\Church::where('id', $churchId)->update(['currency' => $value]);
            }
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }
}
