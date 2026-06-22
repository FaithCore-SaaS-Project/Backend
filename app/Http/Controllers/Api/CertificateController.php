<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CertificateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_certificates', only: ['index', 'show', 'generatePdf']),
            new Middleware('permission:create_certificates', only: ['store']),
            new Middleware('permission:edit_certificates', only: ['update']),
            new Middleware('permission:delete_certificates', only: ['destroy']),
        ];
    }

    public function index()
    {
        $certificates = Certificate::latest()->get();
        return response()->json($certificates->map(fn($c) => $this->formatCertificate($c)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'recipient' => 'required|string',
            'recipientEmail' => 'nullable|email',
            'recipientPhone' => 'nullable|string',
            'issuedDate' => 'required|date',
            'issuedBy' => 'required|string',
            'status' => 'required|string',
        ]);

        $certificate = Certificate::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'recipient' => $validated['recipient'],
            'recipient_email' => $validated['recipientEmail'] ?? null,
            'recipient_phone' => $validated['recipientPhone'] ?? null,
            'issued_date' => $validated['issuedDate'],
            'issued_by' => $validated['issuedBy'],
            'status' => $validated['status'],
        ]);

        return response()->json($this->formatCertificate($certificate), 201);
    }

    public function show(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        return response()->json($this->formatCertificate($certificate));
    }

    public function update(Request $request, string $id)
    {
        $certificate = Certificate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'recipient' => 'required|string',
            'recipientEmail' => 'nullable|email',
            'recipientPhone' => 'nullable|string',
            'issuedDate' => 'required|date',
            'issuedBy' => 'required|string',
            'status' => 'required|string',
        ]);

        $certificate->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'recipient' => $validated['recipient'],
            'recipient_email' => $validated['recipientEmail'] ?? null,
            'recipient_phone' => $validated['recipientPhone'] ?? null,
            'issued_date' => $validated['issuedDate'],
            'issued_by' => $validated['issuedBy'],
            'status' => $validated['status'],
        ]);

        return response()->json($this->formatCertificate($certificate));
    }

    public function destroy(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        $certificate->delete();
        return response()->json(['success' => true]);
    }

    public function generatePdf(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        
        $data = [
            'certificate' => $certificate,
            'church' => $certificate->church
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("certificate_{$certificate->id}.pdf");
    }

    public function verify(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        return response()->json([
            'valid' => true,
            'certificate' => $this->formatCertificate($certificate)
        ]);
    }

    private function formatCertificate($c)
    {
        return [
            'id' => (string) $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'recipient' => $c->recipient,
            'recipientEmail' => $c->recipient_email ?? '',
            'recipientPhone' => $c->recipient_phone ?? '',
            'issuedDate' => $c->issued_date instanceof \DateTimeInterface 
                ? $c->issued_date->format('Y-m-d') 
                : substr($c->issued_date, 0, 10),
            'issuedBy' => $c->issued_by,
            'status' => $c->status,
            'tenantId' => (string) $c->church_id,
            'createdOn' => $c->created_at->format('Y-m-d')
        ];
    }
}
