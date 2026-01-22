<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $intakes = Intake::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.intakes.index', compact('intakes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.intakes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        Intake::create($validated);

        return redirect()->route('admin.intakes.index')
            ->with('success', 'Intake created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Intake $intake)
    {
        return view('admin.intakes.edit', compact('intake'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Intake $intake)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $intake->update($validated);

        return redirect()->route('admin.intakes.index')
            ->with('success', 'Intake updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Intake $intake)
    {
        $intake->delete();

        return redirect()->route('admin.intakes.index')
            ->with('success', 'Intake deleted successfully.');
    }
}
