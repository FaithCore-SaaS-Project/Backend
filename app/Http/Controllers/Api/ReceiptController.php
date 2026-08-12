<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receipt;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_finance', only: ['index', 'show']),
            new Middleware('permission:create_income', only: ['store']),
            new Middleware('permission:edit_finance', only: ['update']),
            new Middleware('permission:delete_finance', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $receipts = Receipt::latest('receipt_date')->get();
        return response()->json($receipts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receipt_no' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'member_name' => 'required|string|max:255',
            'member_email' => 'nullable|email|max:255',
            'member_phone' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'received_by' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $receipt = Receipt::create($validated);
        return response()->json($receipt, 201);
    }

    public function show($id)
    {
        $receipt = Receipt::findOrFail($id);
        return response()->json($receipt);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'receipt_no' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'member_name' => 'required|string|max:255',
            'member_email' => 'nullable|email|max:255',
            'member_phone' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'received_by' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $receipt = Receipt::findOrFail($id);
        $receipt->update($validated);
        return response()->json($receipt);
    }

    public function destroy($id)
    {
        $receipt = Receipt::findOrFail($id);
        $receipt->delete();
        return response()->json(['success' => true]);
    }
}
