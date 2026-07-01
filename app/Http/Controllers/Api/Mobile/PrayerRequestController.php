<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PrayerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrayerRequestController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all'); // 'all', 'my', 'church', 'praise'
        $query = PrayerRequest::where('church_id', $request->user()->church_id);

        if ($type === 'my') {
            $query->where('member_id', $request->user()->member->id ?? 0);
        } elseif ($type === 'praise') {
            $query->where('status', 'answered');
        } elseif ($type === 'church') {
            $query->where('is_public', true);
        }

        $requests = $query->with('member:id,first_name,last_name')->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prayerRequest = PrayerRequest::create([
            'church_id' => $request->user()->church_id,
            'member_id' => $request->user()->member->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'is_public' => $request->is_public ?? false,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Prayer request submitted successfully', 'data' => $prayerRequest], 201);
    }

    public function show(string $id)
    {
        $prayerRequest = PrayerRequest::findOrFail($id);
        return response()->json($prayerRequest);
    }

    public function update(Request $request, string $id)
    {
        // Mobile users typically don't update requests, but we could allow editing pending ones
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy(string $id)
    {
        // Mobile users can delete their own
        $prayerRequest = PrayerRequest::where('id', $id)->where('member_id', request()->user()->member->id)->firstOrFail();
        $prayerRequest->delete();
        return response()->json(['message' => 'Prayer request deleted']);
    }
}
