<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationRule;
use App\Services\ActivityLogService;

class RegistrationRuleController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rules = RegistrationRule::orderBy('created_at', 'desc')->get();
        return view('admin.registration-rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.registration-rules.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rule_name' => 'required|string|max:255',
            'minimum_payment_percentage' => 'required|numeric|min:0|max:100',
            'late_registration_fee' => 'required|numeric|min:0',
            'late_registration_days' => 'required|integer|min:1',
        ]);

        // Deactivate all other rules if this one is active
        if ($request->is_active) {
            RegistrationRule::where('is_active', true)->update(['is_active' => false]);
        }

        $rule = RegistrationRule::create($request->all());

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'registration_rule_created',
            'model_type' => RegistrationRule::class,
            'model_id' => $rule->id,
            'system_source' => 'SIP',
        ]);

        return redirect()->route('admin.registration-rules.index')
            ->with('success', 'Registration rule created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistrationRule $registrationRule)
    {
        return view('admin.registration-rules.edit', compact('registrationRule'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistrationRule $registrationRule)
    {
        $request->validate([
            'rule_name' => 'required|string|max:255',
            'minimum_payment_percentage' => 'required|numeric|min:0|max:100',
            'late_registration_fee' => 'required|numeric|min:0',
            'late_registration_days' => 'required|integer|min:1',
        ]);

        // Deactivate all other rules if this one is being activated
        if ($request->is_active && !$registrationRule->is_active) {
            RegistrationRule::where('is_active', true)->where('id', '!=', $registrationRule->id)->update(['is_active' => false]);
        }

        $registrationRule->update($request->all());

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'registration_rule_updated',
            'model_type' => RegistrationRule::class,
            'model_id' => $registrationRule->id,
            'system_source' => 'SIP',
        ]);

        return redirect()->route('admin.registration-rules.index')
            ->with('success', 'Registration rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistrationRule $registrationRule)
    {
        $registrationRule->delete();

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'registration_rule_deleted',
            'model_type' => RegistrationRule::class,
            'model_id' => $registrationRule->id,
            'system_source' => 'SIP',
        ]);

        return redirect()->route('admin.registration-rules.index')
            ->with('success', 'Registration rule deleted successfully.');
    }
}

