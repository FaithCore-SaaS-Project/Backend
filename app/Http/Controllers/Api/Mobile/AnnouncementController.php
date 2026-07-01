<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all'); // 'all', 'featured', 'archived'
        $query = Announcement::where('church_id', $request->user()->church_id)
            ->where('is_published', true);

        if ($type === 'featured') {
            $query->where('priority', 'high');
        } elseif ($type === 'archived') {
            // Archived could be older than a month, or if we had an archived field. 
            // We'll just filter older ones for now
            $query->where('created_at', '<', now()->subDays(30));
        } else {
            // Active announcements
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $announcements = $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($announcements);
    }

    public function show(string $id)
    {
        $announcement = Announcement::where('church_id', request()->user()->church_id)->findOrFail($id);
        return response()->json($announcement);
    }
}
