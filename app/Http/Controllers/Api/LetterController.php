<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LetterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_letters', only: ['index', 'show', 'generatePdf']),
            new Middleware('permission:create_letters', only: ['store']),
            new Middleware('permission:edit_letters', only: ['update']),
            new Middleware('permission:delete_letters', only: ['destroy']),
        ];
    }

    public function index()
    {
        $letters = Letter::latest()->get();
        return response()->json($letters->map(fn($l) => $this->formatLetter($l)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'recipient' => 'required|string',
            'recipientEmail' => 'nullable|email',
            'recipientPhone' => 'nullable|string',
            'date' => 'required|date',
            'status' => 'required|string',
            'sentBy' => 'required|string',
            'content' => 'required|string',
        ]);

        $letter = Letter::create([
            'title' => $validated['title'],
            'letter_type' => $validated['type'],
            'recipient' => $validated['recipient'],
            'recipient_email' => $validated['recipientEmail'] ?? null,
            'recipient_phone' => $validated['recipientPhone'] ?? null,
            'issue_date' => $validated['date'],
            'status' => $validated['status'],
            'sent_by' => $validated['sentBy'],
            'content' => $validated['content'],
        ]);

        return response()->json($this->formatLetter($letter), 201);
    }

    public function show(string $id)
    {
        $letter = Letter::findOrFail($id);
        return response()->json($this->formatLetter($letter));
    }

    public function update(Request $request, string $id)
    {
        $letter = Letter::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'recipient' => 'required|string',
            'recipientEmail' => 'nullable|email',
            'recipientPhone' => 'nullable|string',
            'date' => 'required|date',
            'status' => 'required|string',
            'sentBy' => 'required|string',
            'content' => 'required|string',
        ]);

        $letter->update([
            'title' => $validated['title'],
            'letter_type' => $validated['type'],
            'recipient' => $validated['recipient'],
            'recipient_email' => $validated['recipientEmail'] ?? null,
            'recipient_phone' => $validated['recipientPhone'] ?? null,
            'issue_date' => $validated['date'],
            'status' => $validated['status'],
            'sent_by' => $validated['sentBy'],
            'content' => $validated['content'],
        ]);

        return response()->json($this->formatLetter($letter));
    }

    public function destroy(string $id)
    {
        $letter = Letter::findOrFail($id);
        $letter->delete();
        return response()->json(['success' => true]);
    }

    public function generatePdf(string $id)
    {
        $letter = Letter::findOrFail($id);
        
        $data = [
            'letter' => $letter,
            'church' => $letter->church
        ];

        $pdf = Pdf::loadView('pdf.letter', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download("letter_{$letter->id}.pdf");
    }

    private function formatLetter($l)
    {
        return [
            'id' => (string) $l->id,
            'title' => $l->title,
            'type' => $l->letter_type,
            'recipient' => $l->recipient,
            'recipientEmail' => $l->recipient_email ?? '',
            'recipientPhone' => $l->recipient_phone ?? '',
            'date' => $l->issue_date instanceof \DateTimeInterface 
                ? $l->issue_date->format('Y-m-d') 
                : substr($l->issue_date, 0, 10),
            'status' => $l->status,
            'sentBy' => $l->sent_by,
            'content' => $l->content,
            'tenantId' => (string) $l->church_id,
            'createdOn' => $l->created_at->format('Y-m-d')
        ];
    }
}
