<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_documents', only: ['index', 'show']),
            new Middleware('permission:manage_documents', only: ['store', 'upload', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        // ChurchScope automatically filters by church_id
        return response()->json(
            Document::with(['category', 'uploader'])->latest()->paginate(20)
        );
    }

    public function store(Request $request)
    {
        // Self-heal / seed category on-the-fly to prevent exists validation failure
        \App\Models\DocumentCategory::firstOrCreate(['id' => 1]);

        $request->merge(['category_id' => $request->input('category_id', 1)]);

        $validated = $request->validate([
            'category_id' => 'required|exists:document_categories,id',
            'title' => 'required|string|max:255',
            'file_path' => 'required|string'
        ]);

        $validated['uploaded_by'] = $request->user()->id;
        
        $document = Document::create($validated);
        return response()->json($document->load(['category', 'uploader']), 201);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480' // max 20MB
        ]);

        $user = $request->user();
        $churchId = $user->church_id;
        $church = $user->church;
        $plan = $church ? $church->activePlan() : null;

        if ($plan) {
            // Calculate existing storage usage (bytes)
            $totalSize = 0;
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $files = $disk->allFiles("churches/{$churchId}");
            foreach ($files as $file) {
                try {
                    $totalSize += $disk->size($file);
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }

            $newFileSize = $request->file('file')->getSize();
            $totalSizeMb = ($totalSize + $newFileSize) / (1024 * 1024);

            if ($totalSizeMb > $plan->storage_limit_mb) {
                return response()->json([
                    'message' => 'Storage limit reached. Your plan (' . $plan->name . ') allows up to ' . $plan->storage_limit_mb . ' MB. Please upgrade.'
                ], 403);
            }
        }

        $path = $request->file('file')->store("churches/{$churchId}/documents", 'public');

        return response()->json(['file_path' => $path]);
    }

    public function show(Document $document)
    {
        return response()->json($document->load(['category', 'uploader']));
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:document_categories,id',
            'title' => 'sometimes|string|max:255',
        ]);

        $document->update($validated);
        return response()->json($document->load(['category', 'uploader']));
    }

    public function destroy(Document $document)
    {
        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);
        
        // Delete the record
        $document->delete();
        
        return response()->json(['message' => 'Document deleted successfully']);
    }
}
