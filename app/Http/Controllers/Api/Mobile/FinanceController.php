<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Models\FinanceIncome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
    public function givingHistory(Request $request)
    {
        $user   = $request->user();
        $member = $user->member;

        $query = FinanceIncome::where('church_id', $user->church_id)
            ->orderBy('created_at', 'desc');

        if ($member) {
            $query->where('member_id', $member->id);
        } else {
            $query->where('recorded_by', $user->id);
        }

        $history = $query->paginate(20);

        // Transform the response to match what the mobile app expects (mock category relation)
        $formattedItems = collect($history->items())->map(function ($item) {
            $arr = $item->toArray();
            $arr['category'] = [
                'id' => 0,
                'name' => $item->category
            ];
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedItems,
            'meta'    => [
                'total'        => $history->total(),
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
            ],
        ]);
    }

    public function getCategories(Request $request)
    {
        $categories = FinanceCategory::where('church_id', $request->user()->church_id)
            ->where('type', 'income')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function storeDonation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'category_id' => 'required|exists:finance_categories,id',
            'payment_method' => 'required|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ideally here we would process the actual payment via Stripe/PayHere etc.
        // For now, we just record the income as completed if payment_method is cash/transfer
        // or integrate the payment gateway hook.

        // Map Mobile fields to Web Backend schema
        $category = FinanceCategory::find($request->category_id);

        $income = FinanceIncome::create([
            'church_id' => $request->user()->church_id,
            'category' => $category ? $category->name : 'Donation',
            'amount' => $request->amount,
            'income_date' => now()->toDateString(),
            'description' => $request->note ?? 'Mobile App Donation',
            'method' => $request->payment_method,
            'member_id' => $request->user()->member?->id,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Donation recorded successfully', 'data' => $income], 201);
    }
}
