<?php

namespace App\Http\Controllers;

use App\Models\CourseResultSheet;
use App\Models\SiteSetting;
use App\Services\CourseEnrollmentService;
use App\Services\LecturerResultsService;
use App\Services\ResultsApprovalService;
use Illuminate\Http\Request;

class RegistrarResultsController extends Controller
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
        $status = $request->get('status', 'hod_approved');
        $filterStatus = $status === 'all' ? null : $status;
        $sheets = $this->approvalService->sheetsForRegistrar($filterStatus);
        $options = $this->enrollmentService->filterOptions();

        return view('results.review-index', [
            'sheets' => $sheets,
            'status' => $status,
            'role' => 'registrar',
            'pageTitle' => 'Course Results — Registrar',
            'filterRoute' => 'registrar.results.index',
            'showRoute' => 'registrar.results.show',
            'pendingStatuses' => [
                'hod_approved' => 'Pending Final Approval',
                'approved' => 'Ready to Publish',
                'published' => 'Published',
                'submitted' => 'With HOD',
                'returned' => 'Returned',
                'all' => 'All',
            ],
            'academicYears' => $options['academicYears'],
            'semesters' => $options['semesters'],
            'showPublishSemester' => true,
            'publishSemesterRoute' => 'registrar.results.publish-semester',
            'defaultAcademicYear' => SiteSetting::currentAcademicYear(),
        ]);
    }

    public function show(CourseResultSheet $sheet)
    {
        $sheet->load(['course.program.department', 'course.assessmentComponents', 'lecturer', 'approvals.user', 'records.student.user']);
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
            'role' => 'registrar',
            'approveRoute' => 'registrar.results.approve',
            'returnRoute' => 'registrar.results.return',
            'publishRoute' => 'registrar.results.publish',
            'indexRoute' => 'registrar.results.index',
            'canApprove' => $sheet->status === 'hod_approved',
            'canReturn' => in_array($sheet->status, ['hod_approved', 'approved'], true),
            'canPublish' => $sheet->status === 'approved',
        ]);
    }

    public function approve(Request $request, CourseResultSheet $sheet)
    {
        $request->validate(['comment' => 'nullable|string|max:1000']);

        try {
            $this->approvalService->registrarApprove($sheet, $request->input('comment'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('registrar.results.show', $sheet)
            ->with('success', 'Final approval recorded. You can now publish this sheet.');
    }

    public function returnSheet(Request $request, CourseResultSheet $sheet)
    {
        $request->validate(['comment' => 'required|string|max:1000']);

        try {
            $this->approvalService->registrarReturn($sheet, $request->input('comment'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('registrar.results.index')
            ->with('success', 'Result sheet returned to lecturer.');
    }

    public function publish(CourseResultSheet $sheet)
    {
        try {
            $this->approvalService->publish($sheet);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('registrar.results.show', $sheet)
            ->with('success', 'Results published to SIP for students on this course.');
    }

    public function publishSemester(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|max:100',
        ]);

        $count = $this->approvalService->publishSemester(
            $request->academic_year,
            $request->semester
        );

        return redirect()->route('registrar.results.index', ['status' => 'published'])
            ->with('success', "Published {$count} approved result sheet(s) for {$request->semester} {$request->academic_year}.");
    }
}
