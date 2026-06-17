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
        return $this->registrar_status === 'pending' && $this->isApprovedByPresident();
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
        return $this->isApprovedByHOD() && $this->isApprovedByPresident() && $this->isApprovedByRegistrar();
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

        if ($this->isFullyApproved()) {
            return 'approved';
        }

        if ($this->isPendingHOD()) {
            return 'hod_pending';
        }

        if ($this->isPendingPresident()) {
            return 'president_pending';
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
            case 'president_pending':
                return 'Pending President Review';
            case 'registrar_pending':
                return 'Pending Registrar Review';
            case 'approved':
                return 'Approved';
            case 'rejected':
                return 'Rejected';
            default:
                return ucfirst($this->status);
        }
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
