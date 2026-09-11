<?php

namespace App\Http\Controllers;

use App\Models\CourseResultSheet;
use App\Services\CourseEnrollmentService;
use App\Services\LecturerResultsService;
use App\Services\ResultsApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HODResultsController extends Controller
{
    protected ResultsApprovalService $approvalService;
    protected LecturerResultsService $resultsService;
    protected CourseEnrollmentService $enrollmentService;

    public function __construct(
        ResultsApprovalService $approvalService,
        LecturerResultsService $resultsService,
        CourseEnrollmentService $enrollmentService
    ) {
        $this->approvalService = $approvalService;
        $this->resultsService = $resultsService;
        $this->enrollmentService = $enrollmentService;
    }

    public function index(Request $request)
    {
        $department = $this->requireDepartment();
        $status = $request->get('status', 'submitted');
        if ($status === 'all') {
            $status = null;
        }

        $sheets = $this->approvalService->sheetsForHodDepartment($department->id, $status);

        return view('results.review-index', [
            'sheets' => $sheets,
            'status' => $request->get('status', 'submitted'),
            'role' => 'hod',
            'pageTitle' => 'Course Results — ' . $department->name,
            'filterRoute' => 'hod.results.index',
            'showRoute' => 'hod.results.show',
            'pendingStatuses' => [
                'submitted' => 'Pending HOD',
                'hod_approved' => 'HOD Approved',
                'returned' => 'Returned',
                'approved' => 'Registrar Approved',
                'published' => 'Published',
                'all' => 'All',
            ],
        ]);
    }

    public function show(CourseResultSheet $sheet)
    {
        $department = $this->requireDepartment();
        $this->approvalService->assertHodCanAccess($sheet, $department->id);

        $sheet->load(['course.program', 'course.assessmentComponents', 'lecturer', 'approvals.user', 'records.student.user']);
        $roster = $this->enrollmentService->getRegisteredStudents(
            $sheet->course,
            $sheet->semester,
            $sheet->academic_year
        );
        $grid = $this->resultsService->buildResultGrid($sheet, $roster);

        return view('results.review-show', [
            'sheet' => $sheet,
            'components' => $grid['components'],
            'rows' => $grid['rows'],
            'role' => 'hod',
            'approveRoute' => 'hod.results.approve',
            'returnRoute' => 'hod.results.return',
            'indexRoute' => 'hod.results.index',
            'canApprove' => $sheet->status === 'submitted',
            'canReturn' => in_array($sheet->status, ['submitted', 'hod_approved'], true),
            'canPublish' => false,
        ]);
    }

    public function approve(Request $request, CourseResultSheet $sheet)
    {
        $department = $this->requireDepartment();
        $this->approvalService->assertHodCanAccess($sheet, $department->id);

        $request->validate(['comment' => 'nullable|string|max:1000']);

        try {
            $this->approvalService->hodApprove($sheet, $request->input('comment'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('hod.results.index')
            ->with('success', 'Result sheet approved and sent to Registrar.');
    }

    public function returnSheet(Request $request, CourseResultSheet $sheet)
    {
        $department = $this->requireDepartment();
        $this->approvalService->assertHodCanAccess($sheet, $department->id);

        $request->validate(['comment' => 'required|string|max:1000']);

        try {
            $this->approvalService->hodReturn($sheet, $request->input('comment'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('hod.results.index')
            ->with('success', 'Result sheet returned to lecturer.');
    }

    protected function requireDepartment()
    {
        $department = Auth::user()->department;
        if (!$department) {
            abort(403, 'No department assigned to your account.');
        }
        return $department;
    }
}
