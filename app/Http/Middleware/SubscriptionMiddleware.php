<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $churchId = $request->user()->church_id;
        
        $subscription = Subscription::where('church_id', $churchId)->latest()->first();

        // If no subscription or it is expired/cancelled, block access
        if (!$subscription || in_array($subscription->status, ['expired', 'cancelled'])) {
            return response()->json([
                'message' => 'Subscription expired or cancelled. Please upgrade your plan to restore access.'
            ], 403);
        }

        return $next($request);
    }
}
