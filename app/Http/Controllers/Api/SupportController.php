<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SupportArticle;
use App\Models\NewsletterSubscription;

class SupportController extends Controller
{
    public function getArticles()
    {
        $articles = SupportArticle::where('status', 'published')
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();
            
        return response()->json($articles);
    }

    public function getStatus()
    {
        return response()->json([
            'status' => 'operational',
            'message' => 'All systems are running normally.',
            'last_checked' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $churchId = $request->user() ? $request->user()->church_id : null;

        NewsletterSubscription::firstOrCreate(
            ['email' => $request->email],
            ['church_id' => $churchId]
        );

        return response()->json(['message' => 'Subscribed successfully.']);
    }
}
