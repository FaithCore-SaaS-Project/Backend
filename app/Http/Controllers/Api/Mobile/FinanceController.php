<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FinanceIncome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
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

        $income = FinanceIncome::create([
            'church_id' => $request->user()->church_id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'date' => now()->toDateString(),
            'description' => $request->note ?? 'Mobile App Donation',
            'payment_method' => $request->payment_method,
            'member_id' => $request->user()->member->id ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Donation recorded successfully', 'data' => $income], 201);
    }
}
