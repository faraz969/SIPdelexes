@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Application Details</h3>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body">
      <p><strong>Applicant:</strong> {{ $application->user->name }} ({{ $application->user->email }})</p>
      <p><strong>Application #:</strong> {{ $application->application_number }}</p>
      <p><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$application->status)) }}</p>
      <p><strong>Form Type:</strong> {{ ucfirst($application->form_type) }}</p>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Academic Year &amp; Programs</span>
      @if(!$canEditAcademicProgram)
        <span class="badge bg-secondary">Locked after registrar approval</span>
      @endif
    </div>
    <div class="card-body">
      @if($canEditAcademicProgram)
        <form method="post" action="{{ route('admin.applications.updateAcademicProgram', $application->id) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="academic_year" class="form-label">Academic Year</label>
              <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                     id="academic_year" name="academic_year" list="academic-year-options"
                     value="{{ old('academic_year', $application->academic_year) }}"
                     placeholder="e.g. 2025/2026" required>
              <datalist id="academic-year-options">
                @foreach($availableAcademicYears as $year)
                  <option value="{{ $year }}"></option>
                @endforeach
              </datalist>
              @error('academic_year')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <p class="text-muted mb-3">Edit the program names stored in this application. These are synced to ERPNext when the registrar approves.</p>

          @foreach($programFields as $field)
            <div class="row align-items-end mb-3">
              <div class="col-md-5">
                <label for="program_{{ $field['key'] }}" class="form-label">{{ $field['department_name'] }}</label>
                <input type="text"
                       class="form-control @error('programs.' . $field['key']) is-invalid @enderror"
                       id="program_{{ $field['key'] }}"
                       name="programs[{{ $field['key'] }}]"
                       value="{{ old('programs.' . $field['key'], $field['name']) }}"
                       placeholder="Program name">
                @error('programs.' . $field['key'])
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="mode_{{ $field['key'] }}" class="form-label">Mode</label>
                <input type="text"
                       class="form-control @error('program_modes.' . $field['key']) is-invalid @enderror"
                       id="mode_{{ $field['key'] }}"
                       name="program_modes[{{ $field['key'] }}]"
                       value="{{ old('program_modes.' . $field['key'], $field['mode']) }}"
                       placeholder="e.g. Regular (4yrs)">
                @error('program_modes.' . $field['key'])
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          @endforeach

          <button type="submit" class="btn btn-primary">Save Academic Year &amp; Programs</button>
          <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back</a>
        </form>
      @else
        <p><strong>Academic Year:</strong> {{ $application->academic_year }}</p>
        @php $primaryProgramName = $application->getPrimaryProgramName(); @endphp
        @if($primaryProgramName)
          <p class="mb-1"><strong>Program:</strong> {{ $primaryProgramName }}</p>
        @endif
        @foreach($programFields as $field)
          @if(!empty($field['name']))
            <p class="mb-1">
              <strong>{{ $field['department_name'] }}:</strong> {{ $field['name'] }}
              @if(!empty($field['mode']))
                <span class="text-muted">({{ $field['mode'] }})</span>
              @endif
            </p>
          @endif
        @endforeach
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary mt-3">Back</a>
      @endif
    </div>
  </div>

  @if($form)
  <div class="card mb-3">
    <div class="card-header">Personal Data</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6"><strong>Full Name:</strong> {{ $form->full_name }}</div>
        <div class="col-md-6"><strong>DOB:</strong> {{ optional($form->dob)->toDateString() }}</div>
        <div class="col-md-6"><strong>Age:</strong> {{ $form->age }}</div>
        <div class="col-md-6"><strong>Gender:</strong> {{ $form->gender }}</div>
        <div class="col-md-6"><strong>Birth Place:</strong> {{ $form->birth_place }}</div>
        <div class="col-md-6"><strong>Marital Status:</strong> {{ $form->marital_status }}</div>
        <div class="col-md-6"><strong>Nationality:</strong> {{ $form->nationality }}</div>
        <div class="col-md-6"><strong>Passport #:</strong> {{ $form->passport_number }}</div>
        <div class="col-md-12"><strong>Address:</strong> {{ $form->mailing_address }}</div>
        <div class="col-md-6"><strong>Emergency Contact:</strong> {{ $form->emergency_contact }}</div>
        <div class="col-md-6"><strong>Telephone:</strong> {{ $form->telephone }}</div>
        <div class="col-md-6"><strong>Email:</strong> {{ $form->email }}</div>
        <div class="col-md-6"><strong>Hostel Required:</strong> {{ $form->hostel_required ? 'Yes' : 'No' }}</div>
        <div class="col-md-12"><strong>Disability:</strong> {{ $form->has_disability ? 'Yes' : 'No' }} - {{ $form->disability_details }}</div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Institutions Attended / Qualifications</div>
    <div class="card-body">
      @if(is_array($form->institutions))
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead><tr><th>Institution</th><th>Qualification</th><th>Date</th></tr></thead>
            <tbody>
              @foreach($form->institutions as $inst)
                <tr>
                  <td>{{ $inst['name'] ?? '' }}</td>
                  <td>{{ $inst['qualification'] ?? '' }}</td>
                  <td>{{ $inst['date'] ?? '' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <em>No institutions provided.</em>
      @endif
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Employment</div>
    <div class="card-body">
      @if(is_array($form->employment))
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead><tr><th>Company</th><th>Duration</th></tr></thead>
            <tbody>
              @foreach($form->employment as $emp)
                <tr>
                  <td>{{ $emp['company'] ?? '' }}</td>
                  <td>{{ $emp['duration'] ?? '' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <em>No employment records.</em>
      @endif
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Uploads</div>
    <div class="card-body">
      @php $u = is_array($form->uploads) ? $form->uploads : [] @endphp
      @foreach(['ghana_card_front','ghana_card_back','official_transcript','passport_picture'] as $key)
        @if(!empty($u[$key]))
          <div><strong>{{ ucwords(str_replace('_',' ', $key)) }}:</strong> <a href="{{ asset('storage/'.$u[$key]) }}" target="_blank">View</a></div>
        @endif
      @endforeach
      @if(!empty($u['other_academic_records']) && is_array($u['other_academic_records']))
        <div class="mt-2"><strong>Other Academic Records:</strong>
          <ul>
            @foreach($u['other_academic_records'] as $path)
              <li><a href="{{ asset('storage/'.$path) }}" target="_blank">View file</a></li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>
  @endif

  @if(isset($examRecords) && $examRecords->count())
  <div class="card mb-3">
    <div class="card-header">Examination Details</div>
    <div class="card-body">
      @foreach($examRecords as $rec)
        <div class="mb-3" style="border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
          <div class="row">
            <div class="col-md-3"><strong>Exam Type:</strong> {{ $rec->exam_type }}</div>
            <div class="col-md-3"><strong>Sitting Exam:</strong> {{ $rec->sitting_exam }}</div>
            <div class="col-md-2"><strong>Year:</strong> {{ $rec->year }}</div>
            <div class="col-md-4">
              <strong>Index Number:</strong> {{ $rec->index_number }}
              @php
                $best6InSitting = $rec->subjects->where('is_best_six', true);
                $best6Subtotal = $best6InSitting->sum('grade_number');
              @endphp
              @if($best6Subtotal > 0)
                <span class="badge bg-secondary ms-2">This sitting — Best 6 grades: {{ $best6Subtotal }} ({{ $best6InSitting->count() }} subject(s))</span>
              @endif
            </div>
          </div>
          <div class="table-responsive mt-2">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Grade (Letter)</th>
                  <th>Grade (Number)</th>
                  <th>Best 6</th>
                </tr>
              </thead>
              <tbody>
                @foreach($rec->subjects as $row)
                <tr>
                  <td>{{ $row->subject }}</td>
                  <td>{{ $row->grade_letter }}</td>
                  <td>{{ $row->grade_number }}</td>
                  <td>{{ $row->is_best_six ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach
      <p class="mb-0 mt-2"><strong>Aggregate Best 6 (all sittings):</strong> {{ $application->getTotalBest6() }}</p>
    </div>
  </div>
  @endif

  <div class="card" style="display: none;">
    <div class="card-header">Update Status</div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.applications.updateStatus', $application->id) }}">
        @csrf
        <div class="mb-3">
          <select class="form-select" name="status" required>
            <option value="successful" {{ $application->status==='successful' ? 'selected' : '' }}>Accept</option>
            <option value="not_successful" {{ $application->status==='not_successful' ? 'selected' : '' }}>Reject</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back</a>
      </form>
    </div>
  </div>
</div>
@endsection

