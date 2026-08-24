<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SipDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SipDocumentController extends Controller
{
    public function index()
    {
        $documents = SipDocument::with('creator')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.sip-documents.index', compact('documents'));
    }

    public function create()
    {
        return view('admin.sip-documents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $file = $request->file('document');
        $path = $file->store('sip-documents', 'public');

        SipDocument::create([
            'name' => $validated['name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.sip-documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function edit(SipDocument $sip_document)
    {
        return view('admin.sip-documents.edit', ['document' => $sip_document]);
    }

    public function update(Request $request, SipDocument $sip_document)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $data = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($request->hasFile('document')) {
            $this->deleteStoredFile($sip_document->file_path);
            $file = $request->file('document');
            $data['file_path'] = $file->store('sip-documents', 'public');
            $data['original_filename'] = $file->getClientOriginalName();
        }

        $sip_document->update($data);

        return redirect()->route('admin.sip-documents.index')
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(SipDocument $sip_document)
    {
        $this->deleteStoredFile($sip_document->file_path);
        $sip_document->delete();

        return redirect()->route('admin.sip-documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    public function file(SipDocument $sip_document)
    {
        if (!Storage::disk('public')->exists($sip_document->file_path)) {
            abort(404, 'File not found.');
        }

        $path = Storage::disk('public')->path($sip_document->file_path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $sip_document->original_filename . '"',
        ]);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
