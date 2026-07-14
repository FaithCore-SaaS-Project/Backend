<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        if (!$user || !$user->church_id) {
            return response()->json(['message' => 'Church session context is missing.'], 404);
        }

        $church = $user->church;
        if (!$church) {
            return response()->json(['message' => 'Church not found.'], 404);
        }

        $plan = $church->activePlan();
        if (!$plan) {
            return response()->json([
                'message' => 'No active subscription plan found. Please upgrade to use this feature.'
            ], 403);
        }

        if (!$plan->hasFeature($feature)) {
            return response()->json([
                'message' => 'This feature is not available in your current plan (' . $plan->name . '). Please upgrade your plan to unlock this.'
            ], 403);
        }

        return $next($request);
    }
}
