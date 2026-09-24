<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SipNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SipNoteController extends Controller
{
    public function index()
    {
        $notes = SipNote::with('creator')->ordered()->get();

        return view('admin.sip-notes.index', compact('notes'));
    }

    public function create()
    {
        return view('admin.sip-notes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:20000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        SipNote::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.sip-notes.index')
            ->with('success', 'SIP note published successfully.');
    }

    public function edit(SipNote $sip_note)
    {
        return view('admin.sip-notes.edit', ['note' => $sip_note]);
    }

    public function update(Request $request, SipNote $sip_note)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:20000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $sip_note->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.sip-notes.index')
            ->with('success', 'SIP note updated successfully.');
    }

    public function destroy(SipNote $sip_note)
    {
        $sip_note->delete();

        return redirect()->route('admin.sip-notes.index')
            ->with('success', 'SIP note deleted.');
    }
}
