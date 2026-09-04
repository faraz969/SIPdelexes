<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_number',
        'academic_year',
        'form_type',
        'applicant_type',
        'status',
        'data',
        'department_id',
        'department_ids',
        'hod_status',
        'president_status',
        'registrar_status',
        'hod_comments',
        'president_comments',
        'registrar_comments',
        'hod_reviewed_at',
        'president_reviewed_at',
        'registrar_reviewed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'department_ids' => 'array',
        'hod_reviewed_at' => 'datetime',
        'president_reviewed_at' => 'datetime',
        'registrar_reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Broad text search across application, user, department, admission form, and student fields.
     */
    public function scopeSearch($query, $searchTerm)
    {
        $searchTerm = trim((string) $searchTerm);
        if ($searchTerm === '') {
            return $query;
        }

        $like = '%' . $searchTerm . '%';

        return $query->where(function ($q) use ($like, $searchTerm) {
            $q->where('id', 'like', $like)
                ->orWhere('application_number', 'like', $like)
                ->orWhere('academic_year', 'like', $like)
                ->orWhere('form_type', 'like', $like)
                ->orWhere('applicant_type', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhere('hod_status', 'like', $like)
                ->orWhere('registrar_status', 'like', $like)
                ->orWhere('president_status', 'like', $like)
                ->orWhere('hod_comments', 'like', $like)
                ->orWhere('registrar_comments', 'like', $like)
                ->orWhere('president_comments', 'like', $like)
                ->orWhere('data', 'like', $like)
                ->orWhereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('serial_number', 'like', $like)
                        ->orWhere('nationality', 'like', $like);
                })
                ->orWhereHas('department', function ($departmentQuery) use ($like) {
                    $departmentQuery->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                })
                ->orWhereHas('admissionForm', function ($formQuery) use ($like) {
                    $formQuery->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telephone', 'like', $like)
                        ->orWhere('nationality', 'like', $like)
                        ->orWhere('preferred_session', 'like', $like)
                        ->orWhere('preferred_campus', 'like', $like)
                        ->orWhere('intake_option', 'like', $like)
                        ->orWhere('pref1', 'like', $like)
                        ->orWhere('pref2', 'like', $like)
                        ->orWhere('pref3', 'like', $like)
                        ->orWhere('passport_number', 'like', $like)
                        ->orWhere('mailing_address', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('country', 'like', $like);
                })
                ->orWhereHas('student', function ($studentQuery) use ($like) {
                    $studentQuery->where('student_id', 'like', $like)
                        ->orWhere('academic_year', 'like', $like)
                        ->orWhere('level', 'like', $like)
                        ->orWhere('academic_status', 'like', $like);
                });

            if (ctype_digit($searchTerm)) {
                $q->orWhere('id', (int) $searchTerm);
            }
        });
    }

    /**
     * Filter applications that selected a given program (by program id).
     */
    public function scopeWhereSelectedProgram($query, $programId)
    {
        $program = Program::find($programId);
        if (!$program) {
            return $query->whereRaw('1 = 0');
        }

        $programName = $program->name;
        $programKeys = Department::query()->pluck('id')->map(function ($id) {
            return 'prog_' . $id;
        })->push('prog_' . $program->department_id)->unique()->values();

        return $query->where(function ($q) use ($programName, $programKeys) {
            foreach ($programKeys as $programKey) {
                $q->orWhere("data->{$programKey}", $programName);
            }

            $q->orWhere('data->pref1', $programName)
                ->orWhere('data->pref2', $programName)
                ->orWhere('data->pref3', $programName)
                ->orWhereHas('admissionForm', function ($formQuery) use ($programName) {
                    $formQuery->where('pref1', $programName)
                        ->orWhere('pref2', $programName)
                        ->orWhere('pref3', $programName)
                        ->orWhere('prog_eng', $programName)
                        ->orWhere('prog_focis', $programName)
                        ->orWhere('prog_business', $programName);
                });
        });
    }

    public function admissionForm()
    {
        return $this->hasOne(AdmissionForm::class);
    }

    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class);
    }

    /**
     * Check if this application belongs to a specific department
     */
    public function belongsToDepartment($departmentId)
    {
        // Check the legacy department_id field first
        if ($this->department_id == $departmentId) {
            return true;
        }
        
        // Check the new department_ids array
        if ($this->department_ids && is_array($this->department_ids)) {
            return in_array($departmentId, $this->department_ids);
        }
        
        return false;
    }

    /**
     * Get all department IDs associated with this application
     */
    public function getAllDepartmentIds()
    {
        $ids = [];
        
        // Add legacy department_id if it exists
        if ($this->department_id) {
            $ids[] = $this->department_id;
        }
        
        // Add department_ids from the array
        if ($this->department_ids && is_array($this->department_ids)) {
            $ids = array_merge($ids, $this->department_ids);
        }
        
        // Remove duplicates and return
        return array_unique($ids);
    }

    // Workflow status methods
    public function isPendingHOD()
    {
        return $this->hod_status === 'pending';
    }

    public function isApprovedByHOD()
    {
        return $this->hod_status === 'approved';
    }

    public function isRejectedByHOD()
    {
        return $this->hod_status === 'rejected';
    }

    public function isPendingPresident()
    {
        return $this->president_status === 'pending' && $this->isApprovedByHOD();
    }

    public function isApprovedByPresident()
    {
        return $this->president_status === 'approved';
    }

    public function isRejectedByPresident()
    {
        return $this->president_status === 'rejected';
    }

    public function isPendingRegistrar()
    {
        return $this->registrar_status === 'pending' && $this->isApprovedByHOD();
    }

    public function isApprovedByRegistrar()
    {
        return $this->registrar_status === 'approved';
    }

    public function isRejectedByRegistrar()
    {
        return $this->registrar_status === 'rejected';
    }

    public function isFullyApproved()
    {
        return $this->isApprovedByHOD() && $this->isApprovedByRegistrar();
    }

    public function isRejected()
    {
        return $this->isRejectedByHOD() || $this->isRejectedByPresident() || $this->isRejectedByRegistrar();
    }

    public function getCurrentStageAttribute()
    {
        if ($this->isRejected()) {
            return 'rejected';
        }

        if ($this->isApprovedByRegistrar()) {
            return 'approved';
        }

        if ($this->isPendingHOD()) {
            return 'hod_pending';
        }

        if ($this->isPendingRegistrar()) {
            return 'registrar_pending';
        }

        return 'processing';
    }

    public function getStatusDisplayAttribute()
    {
        switch ($this->current_stage) {
            case 'hod_pending':
                return 'Pending HOD Review';
            case 'registrar_pending':
                return 'Pending Registrar Review';
            case 'approved':
                return 'Admitted';
            case 'rejected':
                return 'Rejected';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Student-facing application timeline steps.
     * Registrar approval also means the student is admitted.
     */
    public function getApplicationTimeline()
    {
        $isDraft = $this->status === 'draft';
        $isSubmitted = !$isDraft;

        $hodApproved = $this->hod_status === 'approved';
        $hodRejected = $this->hod_status === 'rejected';
        $hodPending = $isSubmitted && $this->hod_status === 'pending';

        $registrarApproved = $this->registrar_status === 'approved';
        $registrarRejected = $this->registrar_status === 'rejected';
        $registrarPending = $hodApproved && $this->registrar_status === 'pending';
        $admitted = $registrarApproved;

        if ($isDraft) {
            $currentIndex = 0;
        } elseif ($hodPending || $hodRejected) {
            $currentIndex = 2;
        } elseif ($registrarPending || $registrarRejected) {
            $currentIndex = 3;
        } elseif ($admitted) {
            $currentIndex = 4;
        } else {
            $currentIndex = 1;
        }

        $hodLabel = 'HOD Review Pending';
        if ($hodApproved) {
            $hodLabel = 'HOD Review Accepted';
        } elseif ($hodRejected) {
            $hodLabel = 'HOD Review Rejected';
        }

        $registrarLabel = 'Registrar Review Pending';
        if ($registrarApproved) {
            $registrarLabel = 'Registrar Review Accepted';
        } elseif ($registrarRejected) {
            $registrarLabel = 'Registrar Review Rejected';
        }

        return [
            [
                'key' => 'pending_submission',
                'label' => 'Pending Submission',
                'icon' => 'fas fa-edit',
                'state' => $isDraft ? 'current' : 'completed',
            ],
            [
                'key' => 'submitted',
                'label' => 'Submitted',
                'icon' => 'fas fa-paper-plane',
                'state' => $this->timelineState(1, $currentIndex, $isSubmitted),
            ],
            [
                'key' => 'hod',
                'label' => $hodLabel,
                'icon' => $hodRejected ? 'fas fa-times' : ($hodApproved ? 'fas fa-check' : 'fas fa-hourglass-half'),
                'state' => $hodRejected ? 'rejected' : $this->timelineState(2, $currentIndex, $hodApproved),
            ],
            [
                'key' => 'registrar',
                'label' => $registrarLabel,
                'icon' => $registrarRejected ? 'fas fa-times' : ($registrarApproved ? 'fas fa-check' : 'fas fa-user-tie'),
                'state' => $registrarRejected ? 'rejected' : $this->timelineState(3, $currentIndex, $registrarApproved),
            ],
            [
                'key' => 'admitted',
                'label' => 'Admitted',
                'icon' => 'fas fa-graduation-cap',
                'state' => $this->timelineState(4, $currentIndex, $admitted),
            ],
        ];
    }

    private function timelineState($index, $currentIndex, $isCompleted)
    {
        if ($isCompleted) {
            return 'completed';
        }

        if ($index === $currentIndex) {
            return 'current';
        }

        return 'pending';
    }

    /**
     * Update the main status based on workflow stages
     */
    public function updateMainStatus()
    {
        $newStatus = $this->current_stage;
        
        // Map workflow stages to main status values
        switch ($newStatus) {
            case 'approved':
                $this->status = 'successful';
                break;
            case 'rejected':
                $this->status = 'not_successful';
                break;
            case 'hod_pending':
            case 'president_pending':
            case 'registrar_pending':
            case 'processing':
                $this->status = 'submitted';
                break;
            default:
                $this->status = 'submitted';
        }
        
        $this->save();
    }

    /**
     * Calculate total Best 6 grade points for this application
     */
    public function getTotalBest6()
    {
        $examRecords = $this->examRecords()->with('subjects')->get();

        $best6Subjects = collect();
        foreach ($examRecords as $examRecord) {
            $best6Subjects = $best6Subjects->concat(
                $examRecord->subjects->where('is_best_six', true)
            );
        }
        $totalBest6 = $best6Subjects->sum('grade_number');

        return $totalBest6;
    }

    /**
     * First program name stored in application data (used for ERP sync).
     */
    public function getPrimaryProgramName()
    {
        $data = $this->data ?? [];

        foreach ($data as $key => $value) {
            if (strpos($key, 'prog_') === 0 && !empty($value) && strpos($key, '_mode') === false) {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Program fields from application data keyed by department.
     */
    public function getProgramFieldsFromData()
    {
        $data = $this->data ?? [];
        $fields = [];

        $departments = Department::orderBy('sort_order')->orderBy('name')->get();
        foreach ($departments as $department) {
            $key = 'prog_' . $department->id;
            $fields[] = [
                'key' => $key,
                'mode_key' => $key . '_mode',
                'department_name' => $department->name,
                'name' => $data[$key] ?? '',
                'mode' => $data[$key . '_mode'] ?? '',
            ];
        }

        return $fields;
    }

    /**
     * Update department_id / department_ids from prog_* values in data.
     */
    public function syncDepartmentsFromProgramData()
    {
        $data = $this->data ?? [];
        $departmentIds = [];

        $departments = Department::with('programs')->get();
        foreach ($departments as $department) {
            foreach ($department->programs as $program) {
                foreach ($data as $key => $value) {
                    if (strpos($key, 'prog_') === 0 && strpos($key, '_mode') === false && $value === $program->name) {
                        $departmentIds[] = $department->id;
                        break 2;
                    }
                }
            }
        }

        if (empty($departmentIds)) {
            foreach ($data as $key => $value) {
                if (preg_match('/^prog_(\d+)$/', $key, $matches) && !empty($value)) {
                    $departmentIds[] = (int) $matches[1];
                }
            }
        }

        $departmentIds = array_values(array_unique($departmentIds));

        if (!empty($departmentIds)) {
            $this->department_id = $departmentIds[0];
            $this->department_ids = $departmentIds;
        }
    }

    /**
     * Get all selected programs from application data
     */
    public function getSelectedPrograms()
    {
        $programs = [];
        $data = $this->data ?? [];

        // Get all programs from database
        $allPrograms = \App\Models\Program::with('department')->get();

        // Check each program field in application data
        foreach ($data as $key => $value) {
            if (strpos($key, 'prog_') === 0 && !empty($value) && strpos($key, '_mode') === false) {
                // Find matching program by name
                $program = $allPrograms->firstWhere('name', $value);
                if ($program) {
                    $programs[] = $program;
                }
            }
        }

        return collect($programs);
    }

    /**
     * Check if application is qualified based on cut off grades
     * Returns true if ANY selected program qualifies (total best 6 <= cut off grade)
     */
    public function isQualified()
    {
        $totalBest6 = $this->getTotalBest6();
        
        // If no exam records or total is 0, consider unqualified
        if ($totalBest6 === 0) {
            return false;
        }

        $selectedPrograms = $this->getSelectedPrograms();

        // If no programs selected, consider unqualified
        if ($selectedPrograms->isEmpty()) {
            return false;
        }

        // Check each program: if ANY program qualifies, return true
        foreach ($selectedPrograms as $program) {
            // If cut_off_grade is null, skip this program
            if ($program->cut_off_grade === null) {
                continue;
            }

            // Qualified if total best 6 <= cut off grade
            if ($totalBest6 <= $program->cut_off_grade) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get programs for which this application is qualified
     * Returns collection of Program models
     */
    public function getQualifiedPrograms()
    {
        $totalBest6 = $this->getTotalBest6();
        
        // If no exam records or total is 0, return empty collection
        if ($totalBest6 === 0) {
            return collect([]);
        }

        $selectedPrograms = $this->getSelectedPrograms();
        $qualifiedPrograms = [];

        foreach ($selectedPrograms as $program) {
            // If cut_off_grade is null, skip this program
            if ($program->cut_off_grade === null) {
                continue;
            }

            // Qualified if total best 6 <= cut off grade
            if ($totalBest6 <= $program->cut_off_grade) {
                $qualifiedPrograms[] = $program;
            }
        }

        return collect($qualifiedPrograms);
    }

    /**
     * Get qualification status display
     */
    public function getQualificationStatusAttribute()
    {
        return $this->isQualified() ? 'qualified' : 'unqualified';
    }
}
