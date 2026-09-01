@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.prefillExamSections = @json($prefill['exam_sections'] ?? []);
  window.prefillData = @json($prefill ?? []);
  window.isSubmittedView = @json(!empty($submitted));
  // Note: in submitted view we don't render the editable form
  (function setupSweetAlerts() {
    if (!window.Swal) return;
    const nativeAlert = window.alert.bind(window);
    window.alert = function (message) {
      if (!window.Swal) {
        nativeAlert(message);
        return;
      }
      window.Swal.fire({
        icon: 'warning',
        title: 'Notice',
        text: String(message ?? ''),
        confirmButtonText: 'OK'
      });
    };
  })();
</script>
<style>
    :root { --gap: 14px; }
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; line-height: 1.4; }
    h1 {
      text-align: left;
      margin: 0 0 14px;
      font-size: 1.55rem;
      font-weight: 700;
      color: #1f2937;
    }
    fieldset {
      
      
      
      margin-bottom: 18px;
      background: #fff;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    legend { padding: 0 6px; font-weight: 700; color: #374151; }
    label { display: block; margin: 10px 0 4px; font-weight: 600; color: #4b5563; }
    input, select, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 0;
      border-bottom: 2px solid #d7dbe3;
      border-radius: 0;
      font: inherit;
      background: transparent;
      transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-bottom-color: #5b5ff6;
      box-shadow: 0 1px 0 0 #5b5ff6;
      background-color: rgba(91, 95, 246, 0.03);
    }
    .row { display: grid; grid-template-columns: 1fr; gap: var(--gap); }
    @media (min-width: 720px) { .row.two { grid-template-columns: repeat(2, 1fr); } .row.three { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 960px) { .row.four { grid-template-columns: repeat(4, 1fr); } }
    .inline-options { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .inline-options label { margin: 0; font-weight: 500; }
    .hint { font-size: 12px; color: #6b7280; margin-top: 6px; }
    .actions { display: flex; gap: 10px; }
    .btn-primary { background: #4f46e5; color: #fff; border: none; cursor: pointer; }
    .btn-secondary { background: #f5f6fa; cursor: pointer; }
    .btn-link { background: #fff; border: 1px solid #d7dbe3; padding: 8px 10px; border-radius: 8px; cursor: pointer; }
    .preview-box { margin-top: 8px; border: 1px dashed #cfd5e4; border-radius: 8px; padding: 10px; display: none; background: #fafbff; }
    .preview-box.active { display: block; }
    .preview-box img { max-width: 100%; height: auto; border-radius: 6px; }
    .file-row { display: grid; gap: 10px; }
    @media (min-width: 720px) { .file-row.two { grid-template-columns: 1fr auto; align-items: end; } .file-row.three { align-items: end; } }
    .file-names { font-size: 13px; color: #4b5563; margin-top: 6px; }
    .inst-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 10px; overflow: hidden; }
    .inst-table th, .inst-table td { border: 1px solid #e8ebf2; padding: 9px; vertical-align: top; }
    .inst-table th { background: #f8f9fc; text-align: left; font-weight: 600; color: #374151; }
    .inst-row-actions { display: flex; gap: 8px; }

    .container.py-2 {
      background: #f8fafc;
      border: 1px solid #e9edf5;
      border-radius: 14px;
      padding: 18px !important;
      box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
    }

    .app-form-layout {
      display: grid;
      grid-template-columns: 290px minmax(0, 1fr);
      gap: 0;
      align-items: start;
      background: #fff;
      border: 1px solid #eaedf3;
      border-radius: 14px;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
      overflow: hidden;
    }

    .side-nav {
      background: transparent;
      border: 0;
      border-right: 1px solid #eef1f6;
      padding: 16px;
      position: sticky;
      top: 12px;
      align-self: stretch;
    }
    .tab-container {
      margin-bottom: 0;
      background: transparent;
      border: 0;
      border-radius: 0;
      padding: 16px;
      min-height: 560px;
    }
    .side-nav-header {
      padding: 0 0 12px;
      margin-bottom: 8px;
      border-bottom: 1px solid #eef1f6;
      font-weight: 700;
      color: #374151;
      font-size: 15px;
    }
    .side-nav-list {
      list-style: none;
      margin: 0;
      padding: 2px 0 2px 24px;
      position: relative;
    }
    .side-nav-list::before {
      content: "";
      position: absolute;
      left: 11px;
      top: 8px;
      bottom: 8px;
      width: 2px;
      background: #e5e7eb;
    }
    .side-item {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      padding: 12px 10px;
      border-radius: 8px;
      cursor: pointer;
      position: relative;
      background: transparent;
      margin-bottom: 4px;
    }
    .side-item::before {
      content: "";
      position: absolute;
      left: -24px;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #c7cedd;
      background: radial-gradient(circle at center, #c7cedd 3px, #ffffff 4px);
      z-index: 1;
      transition: all .2s ease;
    }
    .side-item:hover { background: #f8f9fc; }
    .side-item.active { background: #f5f3ff; }
    .side-item.active::before {
      border-color: #6d5efc;
      background: #6d5efc;
      box-shadow: 0 0 0 4px rgba(109, 94, 252, 0.18);
    }
    .side-item.completed::before {
      content: "\f00c";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      font-size: 11px;
      color: #16a34a;
      display: flex;
      align-items: center;
      justify-content: center;
      border-color: #86efac;
      background: #ecfdf3;
      box-shadow: none;
    }
    .side-item.pending::before {
      content: "";
      border-color: #c7cedd;
      background: radial-gradient(circle at center, #c7cedd 3px, #ffffff 4px);
      box-shadow: none;
    }
    .side-item.completed.active::before,
    .side-item.completed::before {
      content: "\f00c";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      font-size: 11px;
      color: #16a34a;
      display: flex;
      align-items: center;
      justify-content: center;
      border-color: #86efac;
      background: #ecfdf3;
      box-shadow: none;
    }
    .side-item .left { display: flex; align-items: center; gap: 10px; }
    .side-item .left i { color: #7b8794; width: 16px; text-align: center; }
    .side-item.active .left i,
    .side-item.active .left span { color: #4c1d95; font-weight: 700; }

    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .tab-navigation-buttons {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 22px;
      padding: 14px 0 2px;
      border-top: 1px solid #e9edf5;
      position: sticky;
      bottom: 0;
      background: #fff;
      z-index: 5;
    }
    .tab-nav-btn {
      padding: 10px 18px;
      border: 1px solid #d8ddea;
      border-radius: 8px;
      background: #fff;
      cursor: pointer;
      transition: all .2s ease;
      width: auto;
      min-width: 110px;
      font-weight: 600;
    }
    .tab-nav-btn:hover { background-color: #f7f8fc; }
    .tab-nav-btn:disabled { opacity: .55; cursor: not-allowed; background-color: #f9fafb; }
    .tab-nav-btn.btn-primary { background: #4f46e5; color: #fff; border-color: #4f46e5; }
    .tab-nav-btn.btn-primary:hover { background: #4338ca; border-color: #4338ca; }
    .tab-nav-btn.btn-success { background: #16a34a; color: #fff; border-color: #16a34a; }
    .tab-nav-btn.btn-success:hover { background: #15803d; border-color: #15803d; }

    .progress-indicator {
      text-align: right;
      margin-bottom: 14px;
      color: #6b7280;
      font-size: 13px;
      font-weight: 600;
    }

    @media (max-width: 991px) {
      .app-form-layout { grid-template-columns: 1fr; }
      .side-nav { position: relative; top: auto; border-right: 0; border-bottom: 1px solid #eef1f6; }
      .side-nav-list { padding-left: 0; }
      .side-nav-list::before { display: none; }
      .side-item::before { display: none; }
      .tab-container { min-height: auto; }
      .progress-indicator { text-align: left; }
    }
</style>

<div class="container py-2">
  <h1>Delexes University College <br/>Undergraduate Admission Form</h1>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if(!empty($submitted))
    <div class="alert alert-info">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span>Application submitted. You can still edit your personal data and documents below.</span>
        <a href="{{ route('portal.application.print') }}" target="_blank" class="btn btn-primary" style="background: #1a73e8; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; width:auto; text-decoration: none;">
          <i class="fas fa-print"></i> Print Application
        </a>
      </div>
    </div>
  @endif

  <form action="{{ $action ?? route('portal.application.save') }}" method="post" enctype="multipart/form-data" novalidate id="applicationForm">
    @csrf
    @if(!empty($submitted))
      <input type="hidden" name="is_update" value="1">
    @endif
    
    <!-- Progress Indicator -->
    <div class="progress-indicator">
      Step <span id="currentStep">1</span> of <span id="totalSteps">5</span>
      <span id="draftStatus" style="margin-left:10px; color:#16a34a; display:none;">Saved</span>
    </div>

    <div class="app-form-layout">
      <!-- Left Side Navigation -->
      <div class="side-nav">
        <div class="side-nav-header">Application Information</div>
        <ul class="side-nav-list" id="sideNavList">
          <li class="side-item active pending" data-tab="personal"><div class="left"><i class="fas fa-user me-2"></i><span>Personal Data</span></div></li>
          <li class="side-item pending" data-tab="education"><div class="left"><i class="fas fa-graduation-cap me-2"></i><span>Education</span></div></li>
          <li class="side-item pending" data-tab="programs"><div class="left"><i class="fas fa-book me-2"></i><span>Programs</span></div></li>
          <li class="side-item pending" data-tab="employment"><div class="left"><i class="fas fa-briefcase me-2"></i><span>Employment</span></div></li>
          <li class="side-item pending" data-tab="documents"><div class="left"><i class="fas fa-file-alt me-2"></i><span>Documents</span></div></li>
        </ul>
      </div>

      <!-- Right Content -->
      <div class="tab-container" style="flex:1;">

      <!-- Tab 1: Personal Data -->
      <div class="tab-content active" id="personal">
    <fieldset>
      <legend>Personal Data</legend>
      <div class="row two">
        <script>
          // Sync hidden full_name from split fields
          document.addEventListener('DOMContentLoaded', function(){
            const surname = document.getElementById('surname');
            const middle = document.getElementById('middle_name');
            const other = document.getElementById('other_name');
            const full = document.getElementById('full_name');
            function syncFull(){
              if (!full) return;
              const parts = [other && other.value ? other.value.trim() : '', middle && middle.value ? middle.value.trim() : '', surname && surname.value ? surname.value.trim() : ''].filter(Boolean);
              full.value = parts.join(' ').trim();
            }
            if (surname) surname.addEventListener('input', () => { syncFull(); autosaveDraft(); });
            if (middle) middle.addEventListener('input', () => { syncFull(); autosaveDraft(); });
            if (other) other.addEventListener('input', () => { syncFull(); autosaveDraft(); });
            syncFull();
          });

          // Auto-calculate age from Date of Birth
          document.addEventListener('DOMContentLoaded', function(){
            const dobField = document.getElementById('dob');
            const ageField = document.getElementById('age');
            
            function calculateAge(dateOfBirth) {
              if (!dateOfBirth) return '';
              
              const today = new Date();
              const birthDate = new Date(dateOfBirth);
              
              // Check if the date is valid
              if (isNaN(birthDate.getTime())) return '';
              
              let age = today.getFullYear() - birthDate.getFullYear();
              const monthDiff = today.getMonth() - birthDate.getMonth();
              
              // If birthday hasn't occurred this year yet, subtract 1
              if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
              }
              
              // Ensure age is not negative
              return age >= 0 ? age : '';
            }
            
            function updateAge() {
              if (dobField && ageField) {
                const calculatedAge = calculateAge(dobField.value);
                if (calculatedAge !== '') {
                  ageField.value = calculatedAge;
                  autosaveDraft(); // Auto-save when age is calculated
                }
              }
            }
            
            if (dobField) {
              dobField.addEventListener('change', updateAge);
              dobField.addEventListener('input', updateAge);
              
              // Calculate age on page load if DOB is already filled
              if (dobField.value) {
                updateAge();
              }
            }
          });
        </script>
        @php
          $prefillFullName = trim($prefill['full_name'] ?? '');
          $prefillSurname = '';
          $prefillMiddle = '';
          $prefillOther = '';
          if ($prefillFullName !== '') {
            $parts = preg_split('/\s+/', $prefillFullName);
            if (count($parts) === 1) {
              $prefillOther = $parts[0];
            } elseif (count($parts) === 2) {
              $prefillOther = $parts[0];
              $prefillSurname = $parts[1];
            } else {
              $prefillOther = array_shift($parts);
              $prefillSurname = array_pop($parts);
              $prefillMiddle = implode(' ', $parts);
            }
          }
        @endphp
        <div>
          <label for="surname">Surname <span style="color:red">*</span></label>
          <input id="surname" name="surname" type="text" value="{{ $prefillSurname }}" required />
        </div>
        <div>
          <label for="middle_name">Middle Name</label>
          <input id="middle_name" name="middle_name" type="text" value="{{ $prefillMiddle }}" />
        </div>
        <div>
          <label for="other_name">Other Name <span style="color:red">*</span></label>
          <input id="other_name" name="other_name" type="text" value="{{ $prefillOther }}" required />
        </div>
        <input id="full_name" name="full_name" type="hidden" value="{{ $prefill['full_name'] ?? '' }}" />
        <div>
          <label for="dob">Date of Birth <span style="color:red">*</span></label>
          <input id="dob" name="dob" type="date" value="{{ $prefill['dob'] ?? '' }}" required />
        </div>
        <div>
          <label for="age">Age <span style="color:red">*</span></label>
          <input id="age" name="age" type="number" min="0" value="{{ $prefill['age'] ?? '' }}" required />
        </div>
        <div>
          <label for="gender">Gender <span style="color:red">*</span></label>
          <select id="gender" name="gender" required>
            <option value="">-- Select --</option>
            <option {{ ($prefill['gender'] ?? '')==='Male' ? 'selected' : '' }}>Male</option>
            <option {{ ($prefill['gender'] ?? '')==='Female' ? 'selected' : '' }}>Female</option>
            <option {{ ($prefill['gender'] ?? '')==='Other / Prefer not to say' ? 'selected' : '' }}>Other / Prefer not to say</option>
          </select>
        </div>
        <div>
          <label for="birth_place">Place of Birth (Country) <span style="color:red">*</span></label>
          <input id="birth_place" name="birth_place" type="text" value="{{ $prefill['birth_place'] ?? '' }}" required />
        </div>
        <div>
          <label for="marital_status">Marital Status <span style="color:red">*</span></label>
          <select id="marital_status" name="marital_status" required>
            <option value="">-- Select --</option>
            @php $ms = $prefill['marital_status'] ?? '' @endphp
            <option {{ $ms==='Single' ? 'selected' : '' }}>Single</option>
            <option {{ $ms==='Married' ? 'selected' : '' }}>Married</option>
            <option {{ $ms==='Divorced' ? 'selected' : '' }}>Divorced</option>
            <option {{ $ms==='Widowed' ? 'selected' : '' }}>Widowed</option>
          </select>
        </div>
        <div>
          <label for="nationality">Nationality <span style="color:red">*</span></label>
          <input id="nationality" name="nationality" type="text" value="{{ $prefill['nationality'] ?? '' }}" required />
        </div>
        <div>
          <label for="passport_number">Passport Number (if International)</label>
          <input id="passport_number" name="passport_number" type="text" value="{{ $prefill['passport_number'] ?? '' }}" />
        </div>
      </div>

      <label for="mailing_address">Applicant's Mailing Address <span style="color:red">*</span></label>
      <textarea id="mailing_address" name="mailing_address" rows="2" required>{{ $prefill['mailing_address'] ?? '' }}</textarea>

      <div class="row two" style="margin-top:12px;">
        <div>
          <label for="street_address">Street Address</label>
          <input id="street_address" name="street_address" type="text" value="{{ $prefill['street_address'] ?? '' }}" />
        </div>
        <div>
          <label for="post_code">Post Code</label>
          <input id="post_code" name="post_code" type="text" value="{{ $prefill['post_code'] ?? '' }}" />
        </div>
        <div>
          <label for="city">City</label>
          <input id="city" name="city" type="text" value="{{ $prefill['city'] ?? '' }}" />
        </div>
        <div>
          <label for="country">Country</label>
          <input id="country" name="country" type="text" value="{{ $prefill['country'] ?? '' }}" />
        </div>
      </div>

      <div class="row two">
        <div>
          <label for="emergency_contact">Emergency Contact - Name <span style="color:red">*</span></label>
          <input id="emergency_contact" name="emergency_contact" type="text" value="{{ $prefill['emergency_contact'] ?? '' }}" required />
        </div>
        <div>
          <label for="telephone">Emergency Contact - Telephone <span style="color:red">*</span></label>
          <input id="telephone" name="telephone" type="tel" value="{{ $prefill['telephone'] ?? '' }}" required />
        </div>
        <div>
          <label for="email">Emergency Contact - E-mail <span style="color:red">*</span></label>
          <input id="email" name="email" type="email" value="{{ $prefill['email'] ?? '' }}" required />
        </div>
        <div>
          <label>Hostel Required? <span style="color:red">*</span></label>
          <div class="inline-options">
            <label><input type="radio" name="hostel_required" value="Yes" {{ ($prefill['hostel_required'] ?? '')==='Yes' ? 'checked' : '' }} required /> Yes</label>
            <label><input type="radio" name="hostel_required" value="No" {{ ($prefill['hostel_required'] ?? '')==='No' ? 'checked' : '' }} required /> No</label>
          </div>
        </div>
      </div>

      <label>Any disability that requires special attention? <span style="color:red">*</span></label>
      <div class="inline-options">
        <label><input type="radio" name="has_disability" value="Yes" {{ ($prefill['has_disability'] ?? '')==='Yes' ? 'checked' : '' }} required /> Yes</label>
        <label><input type="radio" name="has_disability" value="No" {{ ($prefill['has_disability'] ?? '')==='No' ? 'checked' : '' }} required /> No</label>
      </div>
      <textarea id="disability_details" name="disability_details" rows="2" placeholder="If Yes, please explain">{{ $prefill['disability_details'] ?? '' }}</textarea>
    </fieldset>

    <fieldset>
      <legend>Guardian Details</legend>
      <div class="row two">
        <div>
          <label for="guardian_name">Guardian Name</label>
          <input id="guardian_name" name="guardian_name" type="text" value="{{ $prefill['guardian_name'] ?? '' }}" />
        </div>
        <div>
          <label for="guardian_email">Guardian Email</label>
          <input id="guardian_email" name="guardian_email" type="email" value="{{ $prefill['guardian_email'] ?? '' }}" />
        </div>
        <div>
          <label for="guardian_phone">Guardian Phone Number</label>
          <input id="guardian_phone" name="guardian_phone" type="tel" value="{{ $prefill['guardian_phone'] ?? '' }}" />
        </div>
        <div>
          <label for="guardian_alternate_phone">Guardian Alternate Number</label>
          <input id="guardian_alternate_phone" name="guardian_alternate_phone" type="tel" value="{{ $prefill['guardian_alternate_phone'] ?? '' }}" />
        </div>
        <div>
          <label for="guardian_education">Guardian Education</label>
          <input id="guardian_education" name="guardian_education" type="text" value="{{ $prefill['guardian_education'] ?? '' }}" placeholder="e.g., Bachelor's Degree" />
        </div>
        <div>
          <label for="guardian_occupation">Guardian Occupation</label>
          <input id="guardian_occupation" name="guardian_occupation" type="text" value="{{ $prefill['guardian_occupation'] ?? '' }}" placeholder="e.g., Teacher, Doctor" />
        </div>
        <div>
          <label for="guardian_designation">Guardian Designation</label>
          <input id="guardian_designation" name="guardian_designation" type="text" value="{{ $prefill['guardian_designation'] ?? '' }}" placeholder="e.g., Senior Manager" />
        </div>
      </div>

      <label for="guardian_work_address">Guardian Work Address</label>
      <textarea id="guardian_work_address" name="guardian_work_address" rows="2" placeholder="Enter guardian's work address">{{ $prefill['guardian_work_address'] ?? '' }}</textarea>
    </fieldset>
      </div>

      <!-- Tab 2: Education -->
      <div class="tab-content" id="education">
    <fieldset>
      <legend>Institutions Attended / Qualifications</legend>
      <div class="hint">Add each institution separately. Include the qualification and the date.</div>

      <table class="inst-table" id="institutionsTable" aria-describedby="instHelp">
        <thead>
          <tr>
            <th style="width:45%">Institution Name</th>
            <th style="width:35%">Qualification</th>
            <th style="width:15%">Date</th>
            <th style="width:5%">Actions</th>
          </tr>
        </thead>
        <tbody id="institutionsBody">
          @php $institutions = isset($prefill['institutions']) && is_array($prefill['institutions']) ? $prefill['institutions'] : []; @endphp
          @if(count($institutions))
            @foreach($institutions as $i => $inst)
              <tr>
                <td><input type="text" name="institutions[{{ $i }}][name]" value="{{ $inst['name'] ?? '' }}" placeholder="e.g., Accra High School" required></td>
                <td><input type="text" name="institutions[{{ $i }}][qualification]" value="{{ $inst['qualification'] ?? '' }}" placeholder="e.g., WASSCE / Diploma / HND" required></td>
                <td><input type="date" name="institutions[{{ $i }}][date]" value="{{ $inst['date'] ?? '' }}" required></td>
                <td class="inst-row-actions">
                  <button type="button" class="btn-link" onclick="removeInstitutionRow(this)" aria-label="Remove row">Remove</button>
                </td>
              </tr>
            @endforeach
          @else
              <tr>
                <td><input type="text" name="institutions[0][name]" placeholder="e.g., Accra High School" required></td>
                <td><input type="text" name="institutions[0][qualification]" placeholder="e.g., WASSCE / Diploma / HND" required></td>
                <td><input type="date" name="institutions[0][date]" required></td>
                <td class="inst-row-actions">
                  <button type="button" class="btn-link" onclick="removeInstitutionRow(this)" aria-label="Remove row">Remove</button>
                </td>
              </tr>
          @endif
        </tbody>
      </table>

      <div style="margin-top:10px;">
        <button type="button" class="btn btn-primary" onclick="addInstitutionRow()">Add another</button>
      </div>

      <div id="instHelp" class="hint">
          If you are awaiting results, you can add another row with the same institution and put "Awaiting (Index: XXXXXXXX)" in the Qualification field.
      </div>
    </fieldset>

    <fieldset>
      <legend>Enrollment Options</legend>
      <div class="row two">
        <div>
          <label>Applicant Type (select one) <span style="color:red">*</span></label>
          <div class="inline-options" id="applicantTypeGroup">
            @php
              $selectedEntryType = null;
              if (!empty($prefill['entry_wassce'])) {
                  $selectedEntryType = 'entry_wassce';
              } elseif (!empty($prefill['entry_sssce'])) {
                  $selectedEntryType = 'entry_sssce';
              } elseif (!empty($prefill['entry_ib'])) {
                  $selectedEntryType = 'entry_ib';
              } elseif (!empty($prefill['entry_transfer'])) {
                  $selectedEntryType = 'entry_transfer';
              } elseif (!empty($prefill['entry_other'])) {
                  $selectedEntryType = 'entry_other';
              }
            @endphp
            <label>
              <input type="radio" name="entry_type" value="entry_wassce" class="applicant-type-radio"
                     {{ $selectedEntryType === 'entry_wassce' ? 'checked' : '' }} /> WASSCE
            </label>
            <label>
              <input type="radio" name="entry_type" value="entry_sssce" class="applicant-type-radio"
                     {{ $selectedEntryType === 'entry_sssce' ? 'checked' : '' }} /> SSSCE
            </label>
            <label>
              <input type="radio" name="entry_type" value="entry_ib" class="applicant-type-radio"
                     {{ $selectedEntryType === 'entry_ib' ? 'checked' : '' }} /> International Baccalaureate
            </label>
            <label>
              <input type="radio" name="entry_type" value="entry_transfer" class="applicant-type-radio"
                     {{ $selectedEntryType === 'entry_transfer' ? 'checked' : '' }} /> Transfer
            </label>
            <label>
              <input type="radio" name="entry_type" value="entry_other" class="applicant-type-radio"
                     {{ $selectedEntryType === 'entry_other' ? 'checked' : '' }} /> Other
            </label>
          </div>
          <input type="hidden" name="entry_wassce" id="entry_wassce_hidden" value="{{ !empty($prefill['entry_wassce']) ? 1 : 0 }}">
          <input type="hidden" name="entry_sssce" id="entry_sssce_hidden" value="{{ !empty($prefill['entry_sssce']) ? 1 : 0 }}">
          <input type="hidden" name="entry_ib" id="entry_ib_hidden" value="{{ !empty($prefill['entry_ib']) ? 1 : 0 }}">
          <input type="hidden" name="entry_transfer" id="entry_transfer_hidden" value="{{ !empty($prefill['entry_transfer']) ? 1 : 0 }}">
          <input type="hidden" name="entry_other" id="entry_other_hidden" value="{{ !empty($prefill['entry_other']) ? 1 : 0 }}">
          <div id="entry_type_error" style="color:red; font-size:0.9rem; display:none; margin-top:4px;"></div>
        </div>
        <div>
          <label for="other_entry_detail" class="hint">If "Other", please specify</label>
          <input id="other_entry_detail" name="other_entry_detail" value="{{ $prefill['other_entry_detail'] ?? '' }}" />
        </div>
      </div>

      
        
        <!-- Examination details: WASSCE / SSSCE only. IB, Transfer, Other use optional upload below. -->
        <div id="examSectionsWrapper" style="display:none; margin-top:16px;">
          <div class="row">
            <h4 style="margin:0;">Examination Details</h4>
          </div>

          <div class="alert" style="margin-top:10px; background:#0f4c5c; color:#fff; border-radius:8px; padding:12px;">
            <strong>Note:</strong> Please Note Enrollment Options (ie. Index No., Year of exam, etc.) will be used to pick RESULTS directly from your examination body to CHECK Validity
          </div>

          <div id="examSectionsContainer" style="margin-top:12px; display:grid; gap:16px;"></div>

          <div style="margin-top:12px;">
            <button type="button" class="btn btn-primary" id="addExamSectionBtn">+ Add New Section</button>
          </div>

          <template id="examSectionTemplate">
            <div class="exam-section" style="border:1px solid #cfd8dc; border-radius:8px; padding:14px;">
              <div class="row four">
        <div>
                  <label>Exam Type <span style="color:red">*</span></label>
                  <select class="exam_type">
                    <option value="">-- Select --</option>
                    <option value="1">WASSCE School</option>
                    <option value="2">WASSCE Private</option>
                    <option value="3">BECE School</option>
                    <option value="4">BECE Private</option>
                    <option value="5">SSCE School</option>
                    <option value="6">SSCE Private</option>
                    <option value="7">School ABCE</option>
                    <option value="8">School GBCE</option>
                    <option value="9">Private ABCE</option>
                    <option value="10">Private GBCE</option>
                  </select>
        </div>
        <div>
                  <label>Sitting Exam <span style="color:red">*</span></label>
                  <input type="text" class="sitting_exam" placeholder="e.g. May/June (School)" />
        </div>
        <div>
                  <label>Year <span style="color:red">*</span></label>
                  <input type="number" class="exam_year" min="1900" max="2100" placeholder="2021" />
        </div>
                <div>
                  <label>Index Number <span style="color:red">*</span></label>
                  <div class="inline-options" style="gap:8px; align-items:end;">
                    <input type="text" class="index_number" placeholder="0010408006" style="flex:1;" />
                    <button type="button" class="btn-link fetch_waec_btn" title="Fetch WAEC Results">Fetch</button>
                  </div>
                </div>
              </div>

              <div style="margin-top:12px;">
                <div class="inline-options" style="justify-content:space-between; width:100%;">
                  <h5 style="margin:0;">Subjects & Grades</h5>
                  <button type="button" class="btn-link addSubjectBtn">+ Add More Fields</button>
        </div>
                <div class="hint">Tick exactly 6 subjects as your Best 6 in total across all examinations (required).</div>
                <table class="inst-table" style="margin-top:8px;">
                  <thead>
                    <tr>
                      <th style="width:50%">Subject <span style="color:red">*</span></th>
                      <th style="width:18%">Grade (Letter) <span style="color:red">*</span></th>
                      <th style="width:12%">Grade (Number) <span style="color:red">*</span></th>
                      <th style="width:10%">Best 6 <span style="color:red">*</span></th>
                      <th style="width:10%; text-align:center;">Remove</th>
                    </tr>
                  </thead>
                  <tbody class="subjectsBody"></tbody>
                  <tfoot>
                    <tr>
                      <td colspan="3" style="text-align:right; font-weight:600;">Total Best 6</td>
                      <td style="text-align:center; font-weight:700;">
                        <span class="best6TotalValue">0</span>
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
        </div>

              <div class="inline-options" style="justify-content:flex-end; margin-top:10px;">
                <button type="button" class="btn-link removeExamSectionBtn">Remove Section</button>
        </div>
            </div>
          </template>

          <template id="subjectRowTemplate">
            <tr>
              <td><input type="text" class="subject_input" placeholder="e.g., Core Mathematics" /></td>
              <td><input type="text" class="grade_letter_input" placeholder="e.g., A1, B2" /></td>
              <td><input type="number" class="grade_number_input" min="1" max="9" placeholder="1" /></td>
              <td style="text-align:center;">
                <input type="checkbox" class="best_six_chk" />
              </td>
              <td style="text-align:center; vertical-align:middle;">
                <button type="button" class="btn-link removeSubjectRowBtn" title="Remove this row">Remove</button>
              </td>
            </tr>
          </template>
      </div>

        <div id="alternativeEntryDocsWrapper" style="display:none; margin-top:16px;">
          <fieldset style="border:1px solid #cfd8dc; border-radius:8px; padding:14px;">
            <legend>Supporting qualification document</legend>
            <p class="hint">Upload a copy of your transcript, IB diploma, transfer letter, or other qualification (PDF or image). This is optional.</p>
            @if(!empty($submitted) && !empty($uploadedFiles['alternative_entry_document']))
              <div class="mb-2">
                <a href="{{ asset('storage/'.$uploadedFiles['alternative_entry_document']) }}" target="_blank">View uploaded file</a>
              </div>
            @endif
            @if(empty($submitted))
              <label for="alternative_entry_document">Upload document</label>
              <input type="file" id="alternative_entry_document" name="alternative_entry_document" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
              <small class="hint" style="display:block;margin-top:4px;">Max file size: 1MB</small>
            @endif
          </fieldset>
        </div>
    </fieldset>
      </div>

      <!-- Tab 3: Programs -->
      <div class="tab-content" id="programs">
    <fieldset>
      <legend>Departments</legend>
      <div class="row two">
        @if(isset($departments) && $departments->count() > 0)
          @foreach($departments as $index => $department)
            <div>
              <label for="prog_{{ $department->id }}">{{ $department->name }}</label>
              <select id="prog_{{ $department->id }}" name="prog_{{ $department->id }}">
                <option value="">-- Select Programme --</option>
                @php $selectedProgram = $prefill['prog_' . $department->id] ?? '' @endphp
                @foreach($department->activePrograms as $program)
                  <option value="{{ $program->name }}" {{ $selectedProgram == $program->name ? 'selected' : '' }}>
                    {{ $program->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="prog_{{ $department->id }}_mode">Mode</label>
              <select id="prog_{{ $department->id }}_mode" name="prog_{{ $department->id }}_mode">
                <option value="">-- Select --</option>
                @php $selectedMode = $prefill['prog_' . $department->id . '_mode'] ?? '' @endphp
                <option value="Regular (4yrs)" {{ $selectedMode == 'Regular (4yrs)' ? 'selected' : '' }}>Regular (4yrs)</option>
                <option value="Top-up" {{ $selectedMode == 'Top-up' ? 'selected' : '' }}>Top-up</option>
              </select>
            </div>
          @endforeach
        @else
          <div class="col-12">
            <p class="text-muted">No departments available at the moment. Please contact the administration.</p>
          </div>
        @endif
      </div>
      <div class="hint">Tip: If you want applicants to choose multiple programmes, keep these dropdowns and also collect "Order of Preference" below.</div>

      <hr style="margin:16px 0;">
      <legend style="font-size:1rem;">Preferences</legend>
      <div class="row three">
        <div>
          <label for="preferred_session">Preferred Session</label>
          <select id="preferred_session" name="preferred_session">
            <option value="">-- Select --</option>
            @php $ps = $prefill['preferred_session'] ?? '' @endphp
            @if(isset($sessions) && $sessions->count() > 0)
              @foreach($sessions as $session)
                <option value="{{ $session->name }}" {{ $ps === $session->name ? 'selected' : '' }}>{{ $session->name }}</option>
              @endforeach
            @else
              <option {{ $ps==='Morning' ? 'selected' : '' }}>Morning</option>
              <option {{ $ps==='Evening' ? 'selected' : '' }}>Evening</option>
              <option {{ $ps==='Weekend' ? 'selected' : '' }}>Weekend</option>
            @endif
          </select>
        </div>
        <div>
          <label for="preferred_campus">Preferred Campus</label>
          <select id="preferred_campus" name="preferred_campus">
            <option value="">-- Select --</option>
            @php $pc = $prefill['preferred_campus'] ?? '' @endphp
            @if(isset($campuses) && $campuses->count() > 0)
              @foreach($campuses as $campus)
                <option value="{{ $campus->name }}" {{ $pc === $campus->name ? 'selected' : '' }}>{{ $campus->name }}</option>
              @endforeach
            @else
              <option {{ $pc==='Delexes (Dawhenya)' ? 'selected' : '' }}>Delexes (Dawhenya)</option>
            @endif
          </select>
        </div>
        <div>
          <label for="intake_option">Intake</label>
          <select id="intake_option" name="intake_option">
            <option value="">-- Select --</option>
            @php $io = $prefill['intake_option'] ?? '' @endphp
            @if(isset($intakes) && $intakes->count() > 0)
              @foreach($intakes as $intake)
                <option value="{{ $intake->name }}" {{ $io === $intake->name ? 'selected' : '' }}>{{ $intake->name }}</option>
              @endforeach
            @else
              <option {{ $io==='January' ? 'selected' : '' }}>January</option>
              <option {{ $io==='May' ? 'selected' : '' }}>May</option>
              <option {{ $io==='September' ? 'selected' : '' }}>September</option>
            @endif
          </select>
        </div>
      </div>

      

      <hr style="margin:16px 0;">
      
    </fieldset>
      </div>

      <!-- Tab 4: Employment -->
      <div class="tab-content" id="employment">
    <fieldset>
      <legend>Record of Employment (if applicable)</legend>
      <div class="hint">Add each employment record separately. If you enter a company or duration, you must upload the appointment letter for that row.</div>

      <table class="inst-table" id="employmentTable">
        <thead>
          <tr>
            <th style="width:35%">Company / Organisation</th>
            <th style="width:25%">Duration</th>
            <th style="width:25%">Appointment Letter</th>
            <th style="width:15%">Actions</th>
          </tr>
        </thead>
        <tbody id="employmentBody">
          @php $employment = isset($prefill['employment']) && is_array($prefill['employment']) ? $prefill['employment'] : []; @endphp
          @if(count($employment))
            @foreach($employment as $i => $emp)
              <tr>
                <td><input type="text" name="employment[{{ $i }}][company]" value="{{ $emp['company'] ?? '' }}" placeholder="e.g., ABC Ltd"></td>
                <td><input type="text" name="employment[{{ $i }}][duration]" value="{{ $emp['duration'] ?? '' }}" placeholder="e.g., Jan 2020 – Dec 2022"></td>
                <td>
                  <input type="file" id="employment_file_{{ $i }}" name="employment[{{ $i }}][file]" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576">
                  <small class="hint" style="display:block;margin-top:4px;">Max: 1MB</small>
                  <button type="button" class="btn-link" onclick="previewEmploymentFile({{ $i }})">Preview</button>
                  <div id="employment_preview_{{ $i }}" class="preview-box"></div>
                </td>
                <td class="inst-row-actions">
                  <button type="button" class="btn-link" onclick="removeEmploymentRow(this)">Remove</button>
                </td>
              </tr>
            @endforeach
          @else
              <tr>
                <td><input type="text" name="employment[0][company]" placeholder="e.g., ABC Ltd"></td>
                <td><input type="text" name="employment[0][duration]" placeholder="e.g., Jan 2020 – Dec 2022"></td>
                <td>
                  <input type="file" id="employment_file_0" name="employment[0][file]" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576">
                  <small class="hint" style="display:block;margin-top:4px;">Max: 1MB</small>
                  <button type="button" class="btn-link" onclick="previewEmploymentFile(0)">Preview</button>
                  <div id="employment_preview_0" class="preview-box"></div>
                </td>
                <td class="inst-row-actions">
                  <button type="button" class="btn-link" onclick="removeEmploymentRow(this)">Remove</button>
                </td>
              </tr>
          @endif
        </tbody>
      </table>

      <div style="margin-top:10px;">
        <button type="button" class="btn btn-primary" onclick="addEmploymentRow()">Add another</button>
      </div>
    </fieldset>
      </div>

      <!-- Tab 5: Documents -->
      @php
        $hasGhanaFront = !empty($uploadedFiles['ghana_card_front']);
        $hasGhanaBack = !empty($uploadedFiles['ghana_card_back']);
        $hasPassportPicture = !empty($uploadedFiles['passport_picture']);
      @endphp
      <div class="tab-content" id="documents"
           data-uploaded-ghana-card-front="{{ $hasGhanaFront ? '1' : '0' }}"
           data-uploaded-ghana-card-back="{{ $hasGhanaBack ? '1' : '0' }}"
           data-uploaded-passport-picture="{{ $hasPassportPicture ? '1' : '0' }}">
    <fieldset>
      <legend>Checklist</legend>
      @if(!empty($submitted))
        <p class="hint" style="margin-bottom: 12px;">Upload a new file only when you want to replace an existing document. Required documents must remain on file.</p>
      @endif

      <div class="file-row three">
        <div>
          <label for="ghana_card_front">Ghana Card (Front) <span style="color:red">*</span></label>
          @if(!empty($uploadedFiles['ghana_card_front']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['ghana_card_front']) }}" target="_blank">View uploaded file</a>
              @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['ghana_card_front']))
                <img src="{{ asset('storage/'.$uploadedFiles['ghana_card_front']) }}" alt="Ghana Card Front" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
            </div>
          @endif
          <input id="ghana_card_front" name="ghana_card_front" type="file" accept="image/*,application/pdf" class="file-upload" data-max-size="1048576" {{ (empty($submitted) || empty($uploadedFiles['ghana_card_front'])) ? 'required' : '' }} />
          <small class="hint">
            Max file size: 1MB
            @if(!empty($submitted) && !empty($uploadedFiles['ghana_card_front']))
              . Leave empty to keep current file.
            @endif
          </small>
        </div>
        <div>
          <label for="ghana_card_back">Ghana Card (Back) <span style="color:red">*</span></label>
          @if(!empty($uploadedFiles['ghana_card_back']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['ghana_card_back']) }}" target="_blank">View uploaded file</a>
              @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['ghana_card_back']))
                <img src="{{ asset('storage/'.$uploadedFiles['ghana_card_back']) }}" alt="Ghana Card Back" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
            </div>
          @endif
          <input id="ghana_card_back" name="ghana_card_back" type="file" accept="image/*,application/pdf" class="file-upload" data-max-size="1048576" {{ (empty($submitted) || empty($uploadedFiles['ghana_card_back'])) ? 'required' : '' }} />
          <small class="hint">
            Max file size: 1MB
            @if(!empty($submitted) && !empty($uploadedFiles['ghana_card_back']))
              . Leave empty to keep current file.
            @endif
          </small>
        </div>
        <div class="inline-options" style="justify-content:flex-end;">
          <button type="button" class="btn-link" onclick="previewFiles(['ghana_card_front','ghana_card_back'],'preview_ghana_card')">Preview</button>
          <button type="button" class="btn-link" onclick="clearUploads(['ghana_card_front','ghana_card_back'], 'preview_ghana_card')">Remove</button>
        </div>
      </div>
      <div id="preview_ghana_card" class="preview-box" aria-live="polite"></div>

      <div class="file-row two" style="margin-top:12px;">
        <div>
          <label for="official_transcript">Official Transcript (PDF or Image)</label>
          @if(!empty($uploadedFiles['official_transcript']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['official_transcript']) }}" target="_blank">View uploaded file</a>
              @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['official_transcript']))
                <img src="{{ asset('storage/'.$uploadedFiles['official_transcript']) }}" alt="Official Transcript" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
            </div>
          @endif
          <input id="official_transcript" name="official_transcript" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">
            Max file size: 1MB
            @if(!empty($submitted) && !empty($uploadedFiles['official_transcript']))
              . Leave empty to keep current file.
            @endif
          </small>
        </div>
        <div class="inline-options" style="justify-content:flex-end;">
          <button type="button" class="btn-link" onclick="previewFiles(['official_transcript'],'preview_transcript')">Preview</button>
          <button type="button" class="btn-link" onclick="clearUploads(['official_transcript'],'preview_transcript')">Remove</button>
        </div>
      </div>
      <div id="preview_transcript" class="preview-box" aria-live="polite"></div>

      <div class="file-row two" style="margin-top:12px;">
        <div>
          <label for="passport_picture">Passport Picture (Image) <span style="color:red">*</span></label>
          @if(!empty($uploadedFiles['passport_picture']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['passport_picture']) }}" target="_blank">View uploaded file</a>
              @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['passport_picture']))
                <img src="{{ asset('storage/'.$uploadedFiles['passport_picture']) }}" alt="Passport Picture" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
            </div>
          @endif
          <input id="passport_picture" name="passport_picture" type="file" accept="image/*" class="file-upload" data-max-size="1048576" {{ (empty($submitted) || empty($uploadedFiles['passport_picture'])) ? 'required' : '' }} />
          <small class="hint">
            Max file size: 1MB
            @if(!empty($submitted) && !empty($uploadedFiles['passport_picture']))
              . Leave empty to keep current file.
            @endif
          </small>
        </div>
        <div class="inline-options" style="justify-content:flex-end;">
          <button type="button" class="btn-link" onclick="previewFiles(['passport_picture'],'preview_passport')">Preview</button>
          <button type="button" class="btn-link" onclick="clearUploads(['passport_picture'],'preview_passport')">Remove</button>
        </div>
      </div>
      <div id="preview_passport" class="preview-box" aria-live="polite"></div>

      <hr style="margin:16px 0;">
      <legend style="font-size:1rem;">Additional Supporting Documents (Optional)</legend>

      <div class="file-row three" style="margin-top:8px;">
        <div>
          <label for="gazette_document">Gazette (for change of name or date of birth)</label>
          @if(!empty($uploadedFiles['gazette_document']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['gazette_document']) }}" target="_blank">View uploaded file</a>
            </div>
          @endif
          <input id="gazette_document" name="gazette_document" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">Max file size: 1MB</small>
        </div>
        <div>
          <label for="marriage_certificate">Marriage Certificate</label>
          @if(!empty($uploadedFiles['marriage_certificate']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['marriage_certificate']) }}" target="_blank">View uploaded file</a>
            </div>
          @endif
          <input id="marriage_certificate" name="marriage_certificate" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">Max file size: 1MB</small>
        </div>
        <div>
          <label for="recommendation_letter">Recommendation Letter</label>
          @if(!empty($uploadedFiles['recommendation_letter']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['recommendation_letter']) }}" target="_blank">View uploaded file</a>
            </div>
          @endif
          <input id="recommendation_letter" name="recommendation_letter" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">Max file size: 1MB</small>
        </div>
      </div>

      <div class="file-row two" style="margin-top:12px;">
        <div>
          <label for="birth_certificate">Birth Certificate</label>
          @if(!empty($uploadedFiles['birth_certificate']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['birth_certificate']) }}" target="_blank">View uploaded file</a>
            </div>
          @endif
          <input id="birth_certificate" name="birth_certificate" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">Max file size: 1MB</small>
        </div>
        <div>
          <label for="exam_results_document">Examination Results (e.g., WASSCE, Diploma or equivalent)</label>
          @if(!empty($uploadedFiles['exam_results_document']))
            <div class="mb-2">
              <a href="{{ asset('storage/'.$uploadedFiles['exam_results_document']) }}" target="_blank">View uploaded file</a>
            </div>
          @endif
          <input id="exam_results_document" name="exam_results_document" type="file" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576" />
          <small class="hint">Max file size: 1MB</small>
        </div>
      </div>

      <div class="file-row two" style="margin-top:12px;">
        <div>
          <label for="other_academic_records">Other Academic Records (PDFs or Images)</label>
          @if(!empty($uploadedFiles['other_academic_records']) && is_array($uploadedFiles['other_academic_records']))
            <div class="mb-2">
              @foreach($uploadedFiles['other_academic_records'] as $path)
                <div style="margin-bottom: 15px;">
                  <a href="{{ asset('storage/'.$path) }}" target="_blank">View file</a>
                  @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $path))
                    <img src="{{ asset('storage/'.$path) }}" alt="Academic Record" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
                  @endif
                </div>
              @endforeach
            </div>
          @endif
          <input id="other_academic_records" name="other_academic_records" type="file" accept="application/pdf,image/*" multiple class="file-upload" data-max-size="1048576" />
          <small class="hint">
            Max file size: 1MB per file
            @if(!empty($submitted))
              . New uploads will be added to your existing records.
            @endif
          </small>
          <div id="other_files_list" class="file-names"></div>
        </div>
        <div class="inline-options" style="justify-content:flex-end;">
          <button type="button" class="btn-link" onclick="previewFiles(['other_academic_records'],'preview_other')">Preview</button>
          <button type="button" class="btn-link" onclick="clearUploads(['other_academic_records'],'preview_other', true)">Clear all</button>
        </div>
      </div>
      <div id="preview_other" class="preview-box" aria-live="polite"></div>

      <div class="hint" style="margin-top:10px;">
        <span style="color:red">*</span> Ghana Card (Front & Back), and Passport Picture, are required.  
      </div>
    </fieldset>
      </div>
      <!-- Navigation Buttons -->
      <div class="tab-navigation-buttons">
        @if(empty($submitted))
        <button type="button" class="tab-nav-btn" id="prevBtn" onclick="changeTab(-1)" disabled>
          ← Previous
        </button>
        <button type="button" class="tab-nav-btn" id="nextBtn" onclick="changeTab(1)">
          Save &Next →
        </button>
        <button type="submit" class="tab-nav-btn btn-success" id="submitBtn" style="display: none;">
          Submit Application
        </button>
        @else
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <button type="button" class="tab-nav-btn" id="prevBtn" onclick="changeTab(-1)" disabled>
            ← Previous
          </button>
          <button type="button" class="tab-nav-btn" id="nextBtn" onclick="changeTab(1)">
            Next →
          </button>
          <button type="submit" name="update_section" value="personal" class="tab-nav-btn btn-primary" id="updatePersonalDataBtn" style="display: none;">
            Update Personal Data
          </button>
          <button type="submit" name="update_section" value="documents" class="tab-nav-btn btn-primary" id="updateDocumentsBtn" style="display: none;">
            Update Documents
          </button>
        </div>
        @endif
      </div>
    </div>
  </form>

  @if(!empty($submitted) && false)
    <!-- Read-only Summary (Hidden - using editable form instead) -->
    <fieldset>
      <legend>Application Summary</legend>
      <div class="row two">
        <div>
          <label>Application Number</label>
          <input type="text" value="{{ $application->application_number ?? '' }}" readonly>
        </div>
        <div>
          <label>Academic Year</label>
          <input type="text" value="{{ $application->academic_year ?? '' }}" readonly>
        </div>
      </div>

      <div class="row three" style="margin-top:12px;">
        <div>
          <label>Preferred Session</label>
          <input type="text" value="{{ $application->data['preferred_session'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Preferred Campus</label>
          <input type="text" value="{{ $application->data['preferred_campus'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Intake</label>
          <input type="text" value="{{ $application->data['intake_option'] ?? '' }}" readonly>
        </div>
      </div>

      <div style="margin-top:16px;">
        <h4>Examination Details</h4>
        @forelse(($examRecords ?? []) as $rec)
          <div style="border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:12px;">
            <div class="row four">
              <div>
                <label>Exam Type</label>
                <input type="text" value="{{ $rec->exam_type }}" readonly>
              </div>
              <div>
                <label>Sitting Exam</label>
                <input type="text" value="{{ $rec->sitting_exam }}" readonly>
              </div>
              <div>
                <label>Year</label>
                <input type="text" value="{{ $rec->year }}" readonly>
              </div>
              <div>
                <label>Index Number</label>
                <input type="text" value="{{ $rec->index_number }}" readonly>
              </div>
            </div>
            <table class="inst-table" style="margin-top:10px;">
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
        @empty
          <div class="hint">No exam records captured.</div>
        @endforelse
      </div>
    </fieldset>

    <fieldset>
      <legend>Personal Data</legend>
      <div class="row two">
        <div>
          <label>Full Name</label>
          <input type="text" value="{{ $application->data['full_name'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Date of Birth</label>
          <input type="text" value="{{ $application->data['dob'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Age</label>
          <input type="text" value="{{ $application->data['age'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Gender</label>
          <input type="text" value="{{ $application->data['gender'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Place of Birth</label>
          <input type="text" value="{{ $application->data['birth_place'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Marital Status</label>
          <input type="text" value="{{ $application->data['marital_status'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Nationality</label>
          <input type="text" value="{{ $application->data['nationality'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Passport Number</label>
          <input type="text" value="{{ $application->data['passport_number'] ?? '' }}" readonly>
        </div>
      </div>
      <div class="row two">
        <div>
          <label>Mailing Address</label>
          <textarea rows="2" readonly>{{ $application->data['mailing_address'] ?? '' }}</textarea>
        </div>
        <div>
          <label>Emergency Contact</label>
          <input type="text" value="{{ $application->data['emergency_contact'] ?? '' }}" readonly>
        </div>
      </div>
      <div class="row two" style="margin-top:12px;">
        <div>
          <label>Street Address</label>
          <input type="text" value="{{ $application->data['street_address'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Post Code</label>
          <input type="text" value="{{ $application->data['post_code'] ?? '' }}" readonly>
        </div>
        <div>
          <label>City</label>
          <input type="text" value="{{ $application->data['city'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Country</label>
          <input type="text" value="{{ $application->data['country'] ?? '' }}" readonly>
        </div>
      </div>
      <div class="row two">
        <div>
          <label>Telephone</label>
          <input type="text" value="{{ $application->data['telephone'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Email</label>
          <input type="text" value="{{ $application->data['email'] ?? '' }}" readonly>
        </div>
      </div>
      <div class="row two">
        <div>
          <label>Hostel Required</label>
          <input type="text" value="{{ !empty($application->data['hostel_required']) ? 'Yes' : 'No' }}" readonly>
        </div>
        <div>
          <label>Disability</label>
          <input type="text" value="{{ !empty($application->data['has_disability']) ? 'Yes' : 'No' }}" readonly>
        </div>
      </div>
      @if(!empty($application->data['disability_details']))
      <div>
        <label>Disability Details</label>
        <textarea rows="2" readonly>{{ $application->data['disability_details'] }}</textarea>
      </div>
      @endif
    </fieldset>

    <fieldset>
      <legend>Guardian Details</legend>
      <div class="row two">
        <div>
          <label>Guardian Name</label>
          <input type="text" value="{{ $application->data['guardian_name'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Email</label>
          <input type="text" value="{{ $application->data['guardian_email'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Phone Number</label>
          <input type="text" value="{{ $application->data['guardian_phone'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Alternate Number</label>
          <input type="text" value="{{ $application->data['guardian_alternate_phone'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Education</label>
          <input type="text" value="{{ $application->data['guardian_education'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Occupation</label>
          <input type="text" value="{{ $application->data['guardian_occupation'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Guardian Designation</label>
          <input type="text" value="{{ $application->data['guardian_designation'] ?? '' }}" readonly>
        </div>
      </div>
      @if(!empty($application->data['guardian_work_address']))
      <div>
        <label>Guardian Work Address</label>
        <textarea rows="2" readonly>{{ $application->data['guardian_work_address'] }}</textarea>
      </div>
      @endif
    </fieldset>

    <fieldset>
      <legend>Education</legend>
      <table class="inst-table">
        <thead>
          <tr>
            <th>Institution</th>
            <th>Qualification</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($application->data['institutions'] ?? []) as $inst)
            <tr>
              <td>{{ $inst['name'] ?? '' }}</td>
              <td>{{ $inst['qualification'] ?? '' }}</td>
              <td>{{ $inst['date'] ?? '' }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="hint">No institutions provided.</td></tr>
          @endforelse
        </tbody>
      </table>
    </fieldset>

    <fieldset>
      <legend>Programme Preferences</legend>
      <div class="row three">
        <div>
          <label>1st Preference</label>
          <input type="text" value="{{ $application->data['pref1'] ?? '' }}" readonly>
        </div>
        <div>
          <label>2nd Preference</label>
          <input type="text" value="{{ $application->data['pref2'] ?? '' }}" readonly>
        </div>
        <div>
          <label>3rd Preference</label>
          <input type="text" value="{{ $application->data['pref3'] ?? '' }}" readonly>
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Languages</legend>
      <div class="row three">
        <div>
          <label>English Proficiency</label>
          <input type="text" value="{{ $application->data['english_level'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Mother Tongue</label>
          <input type="text" value="{{ $application->data['mother_tongue'] ?? '' }}" readonly>
        </div>
        <div>
          <label>Other Languages</label>
          <input type="text" value="{{ $application->data['other_languages'] ?? '' }}" readonly>
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Employment</legend>
      <table class="inst-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Duration</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($application->data['employment'] ?? []) as $emp)
            <tr>
              <td>{{ $emp['company'] ?? '' }}</td>
              <td>{{ $emp['duration'] ?? '' }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="hint">No employment records provided.</td></tr>
          @endforelse
        </tbody>
      </table>
    </fieldset>

    <fieldset>
      <legend>Uploads</legend>
      <div class="row two">
        <div>
          <label>Ghana Card (Front)</label>
          @if(!empty($uploadedFiles['ghana_card_front']))
            
            @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['ghana_card_front']))
                <img src="{{ asset('storage/'.$uploadedFiles['ghana_card_front']) }}" alt="Ghana Card Front" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
      @else
            <span class="hint">Not uploaded</span>
      @endif
    </div>
        <div>
          <label>Ghana Card (Back)</label>
          @if(!empty($uploadedFiles['ghana_card_back']))
           
            @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['ghana_card_back']))
                <img src="{{ asset('storage/'.$uploadedFiles['ghana_card_back']) }}" alt="Ghana Card Back" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
          @else
            <span class="hint">Not uploaded</span>
          @endif
        </div>
        <div>
          <label>Official Transcript</label>
          @if(!empty($uploadedFiles['official_transcript']))
            <a target="_blank" href="{{ asset('storage/'.$uploadedFiles['official_transcript']) }}">View</a>
            @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['official_transcript']))
                <img src="{{ asset('storage/'.$uploadedFiles['official_transcript']) }}" alt="Official Transcript" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
          @else
            <span class="hint">Not uploaded</span>
          @endif
        </div>
        <div>
          <label>Passport Picture</label>
          @if(!empty($uploadedFiles['passport_picture']))
           
            @if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $uploadedFiles['passport_picture']))
                <img src="{{ asset('storage/'.$uploadedFiles['passport_picture']) }}" alt="Passport Picture" style="max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
              @endif
          @else
            <span class="hint">Not uploaded</span>
          @endif
        </div>
      </div>
      @if(!empty($uploadedFiles['other_academic_records']) && is_array($uploadedFiles['other_academic_records']))
        <div style="margin-top:8px;">
          <label>Other Academic Records</label>
          <ul>
            @foreach($uploadedFiles['other_academic_records'] as $path)
              <li><a target="_blank" href="{{ asset('storage/'.$path) }}">View file</a></li>
            @endforeach
          </ul>
        </div>
      @endif
    </fieldset>
  @endif
</div>

<script>
// Tab Management
let currentTab = 0;
const totalTabs = 5;
const isSubmitted = {{ !empty($submitted) ? 'true' : 'false' }};

// Make non-personal-data fields readonly when submitted (documents tab stays editable)
if (isSubmitted) {
  document.addEventListener('DOMContentLoaded', function() {
    const lockedTabs = ['education', 'programs', 'employment'];
    
    lockedTabs.forEach(tabId => {
      const tab = document.getElementById(tabId);
      if (tab) {
        const inputs = tab.querySelectorAll('input, select, textarea');
        const buttons = tab.querySelectorAll('button');
        
        inputs.forEach(input => {
          if (input.type !== 'hidden' && input.id !== 'updatePersonalDataBtn' && input.id !== 'updateDocumentsBtn') {
            input.disabled = true;
            input.readOnly = true;
            input.style.backgroundColor = '#f5f5f5';
            input.style.cursor = 'not-allowed';
          }
        });
        
        buttons.forEach(button => {
          if (button.type !== 'submit' && button.id !== 'updatePersonalDataBtn' && button.id !== 'updateDocumentsBtn') {
            button.disabled = true;
            button.style.opacity = '0.5';
            button.style.cursor = 'not-allowed';
          }
        });
      }
    });
  });
}

function showTab(n) {
  const tabs = document.querySelectorAll('.tab-content');
  const buttons = document.querySelectorAll('.tab-button');
  
  // Hide all tabs
  tabs.forEach(tab => tab.classList.remove('active'));
  // Update side nav active
  document.querySelectorAll('.side-item').forEach(it => it.classList.remove('active'));
  
  // Show current tab
  if (tabs[n]) tabs[n].classList.add('active');
  const sideItems = document.querySelectorAll('.side-item');
  if (sideItems[n]) sideItems[n].classList.add('active');
  
  // Update progress indicator
  document.getElementById('currentStep').textContent = n + 1;
  
  // Update navigation buttons
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const submitBtn = document.getElementById('submitBtn');
  const updatePersonalDataBtn = document.getElementById('updatePersonalDataBtn');
  const updateDocumentsBtn = document.getElementById('updateDocumentsBtn');
  
  if (prevBtn) prevBtn.disabled = n === 0;
  
  if (isSubmitted) {
    if (updatePersonalDataBtn) {
      updatePersonalDataBtn.style.display = n === 0 ? 'inline-block' : 'none';
    }
    if (updateDocumentsBtn) {
      updateDocumentsBtn.style.display = n === totalTabs - 1 ? 'inline-block' : 'none';
    }
    if (nextBtn) nextBtn.disabled = n === totalTabs - 1;
  } else {
    if (n === totalTabs - 1) {
      if (nextBtn) nextBtn.style.display = 'none';
      if (submitBtn) submitBtn.style.display = 'inline-block';
    } else {
      if (nextBtn) nextBtn.style.display = 'inline-block';
      if (submitBtn) submitBtn.style.display = 'none';
    }
  }
  
  // Check completion status
  updateTabCompletionStatus();
}

function changeTab(direction) {
  const newTab = currentTab + direction;
  
  if (newTab >= 0 && newTab < totalTabs) {
    // Validate current tab before moving forward
    if (direction > 0 && !validateCurrentTab()) {
      return;
    }

    // Auto-save draft when moving to next tab
    if (direction > 0) {
      autosaveDraft()
        .finally(() => {
          currentTab = newTab;
          showTab(currentTab);
        });
      return; // navigation continues in finally
    }

    currentTab = newTab;
    showTab(currentTab);
  }
}

function validateCurrentTab() {
  const currentTabContent = document.querySelector('.tab-content.active');
  const requiredFields = currentTabContent.querySelectorAll('input[required], select[required], textarea[required]');
  
  // Special validation for Education tab - Applicant Type (radio) must be selected
  if (currentTab === 1) { // Education tab
    const selectedRadio = document.querySelector('input[name="entry_type"]:checked');
    
    if (!selectedRadio) {
      const applicantTypeGroup = document.getElementById('applicantTypeGroup');
      if (applicantTypeGroup) {
        applicantTypeGroup.style.outline = '2px solid #e53935';
        applicantTypeGroup.style.borderRadius = '4px';
        applicantTypeGroup.style.padding = '4px';
        setTimeout(() => {
          const firstRadio = document.querySelector('.applicant-type-radio');
          if (firstRadio) firstRadio.focus();
        }, 100);
      }
      alert('Please select your Applicant Type (WASSCE, SSSCE, International Baccalaureate, Transfer, or Other). This field is required.');
      return false;
    } else {
      // Remove error styling if validation passes
      const applicantTypeGroup = document.getElementById('applicantTypeGroup');
      if (applicantTypeGroup) {
        applicantTypeGroup.style.outline = '';
        applicantTypeGroup.style.borderRadius = '';
        applicantTypeGroup.style.padding = '';
      }
      const entryValue = selectedRadio.value;
      if ((entryValue === 'entry_wassce' || entryValue === 'entry_sssce') && typeof window.validateWassceExamSections === 'function') {
        if (!window.validateWassceExamSections()) {
          return false;
        }
      }
    }
  }
  
  for (let field of requiredFields) {
    if (!field.value.trim()) {
      field.focus();
      alert(`Please fill in all required fields in the ${getTabName(currentTab)} section.`);
      return false;
    }
  }
  
  return true;
}

function getTabName(tabIndex) {
  const tabNames = [
    'Personal Data', 'Education', 'Programs', 
    'Employment', 'Documents'
  ];
  return tabNames[tabIndex] || 'Unknown';
}

function hasUploadedDocument(tabContent, fieldId, dataAttr) {
  const input = tabContent.querySelector('#' + fieldId);
  if (input && input.type === 'file' && input.files && input.files.length > 0) {
    return true;
  }
  if (input && input.type === 'file' && String(input.value || '').trim()) {
    return true;
  }
  return !!(dataAttr && tabContent.getAttribute(dataAttr) === '1');
}

function isDocumentsTabComplete(tabContent) {
  return hasUploadedDocument(tabContent, 'ghana_card_front', 'data-uploaded-ghana-card-front')
    && hasUploadedDocument(tabContent, 'ghana_card_back', 'data-uploaded-ghana-card-back')
    && hasUploadedDocument(tabContent, 'passport_picture', 'data-uploaded-passport-picture');
}

function updateTabCompletionStatus() {
  const tabButtons = document.querySelectorAll('.side-item');
  tabButtons.forEach((button, index) => {
    const tabContent = document.getElementById(button.dataset.tab);
    if (!tabContent) return;

    const tabId = button.dataset.tab;
    let completed = true;

    if (tabId === 'documents') {
      completed = isDocumentsTabComplete(tabContent);
    } else {
    const requiredFields = tabContent.querySelectorAll('input[required], select[required], textarea[required]');

    if (requiredFields.length > 0) {
      for (let field of requiredFields) {
        if (field.type === 'file') {
          if (!hasUploadedDocument(tabContent, field.id, '')) {
            completed = false;
            break;
          }
          continue;
        }
        if (!String(field.value || '').trim()) {
          completed = false;
          break;
        }
      }
    } else {
      // If no required fields, treat completion per-tab with custom rules
      if (tabId === 'programs') {
        // Programs considered completed only if at least one program dropdown has a non-empty value
        const selects = tabContent.querySelectorAll('select');
        completed = Array.from(selects).some(s => String(s.value || '').trim().length > 0);
      } else if (tabId === 'education') {
        // Education tab: Must have applicant type (radio) selected
        const selectedRadio = tabContent.querySelector('input[name="entry_type"]:checked');
        const hasApplicantType = !!selectedRadio;
        
        // Also check if there are any other fields filled
        const anyFields = tabContent.querySelectorAll('input, select, textarea');
        const hasOtherFields = Array.from(anyFields).some(f => {
          if (f.name === 'entry_type' && f.type === 'radio') return false;
          if (f.classList && f.classList.contains('applicant-type-radio')) return false;
          if (f.type === 'checkbox' || f.type === 'radio') return f.checked;
          return String(f.value || '').trim().length > 0;
        });
        
        completed = hasApplicantType && hasOtherFields;
      } else {
        const anyFields = tabContent.querySelectorAll('input, select, textarea');
        completed = Array.from(anyFields).some(f => {
          if (f.type === 'checkbox' || f.type === 'radio') return f.checked;
          return String(f.value || '').trim().length > 0;
        });
      }
    }
    }
    
    // Special check: Education tab must have applicant type selected
    if (button.dataset.tab === 'education') {
      const selectedRadio = tabContent.querySelector('input[name="entry_type"]:checked');
      if (!selectedRadio) {
        completed = false;
      }
    }

    if (completed) {
      button.classList.remove('pending');
      button.classList.add('completed');
    } else {
      button.classList.remove('completed');
      button.classList.add('pending');
    }
  });
}

// Tab click handlers
// Side navigation click handlers
document.getElementById('sideNavList').addEventListener('click', function(e){
  const item = e.target.closest('.side-item');
  if (!item) return;
  const tabs = ['personal','education','programs','employment','documents'];
  const idx = tabs.indexOf(item.dataset.tab);
  if (idx === -1) return;
  // Allow navigation to any tab, but validation only required when not submitted or when moving forward
  if (!isSubmitted && idx > currentTab && !validateCurrentTab()) {
    return;
  }
  currentTab = idx;
  showTab(currentTab);
});

// Field change handlers for completion status
document.addEventListener('input', updateTabCompletionStatus);
document.addEventListener('change', updateTabCompletionStatus);

// Clear error styling when applicant type (radio) is selected
document.addEventListener('DOMContentLoaded', function() {
  const applicantTypeRadios = document.querySelectorAll('.applicant-type-radio');
  applicantTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      const applicantTypeGroup = document.getElementById('applicantTypeGroup');
      if (applicantTypeGroup && document.querySelector('input[name="entry_type"]:checked')) {
        applicantTypeGroup.style.outline = '';
        applicantTypeGroup.style.borderRadius = '';
        applicantTypeGroup.style.padding = '';
      }
      autosaveDraft();
    });
  });
});

// Initialize tabs
showTab(0);

// Auto-save draft on Next
function autosaveDraft() {
  const form = document.getElementById('applicationForm');
  if (!form) return Promise.resolve();

  const formData = new FormData(form);
  const json = {};

  // Helper: set nested value using bracket path e.g., institutions[0][name]
  function setNested(target, path, value) {
    const parts = [];
    path.replace(/\]/g, '').split('[').forEach((segment) => {
      segment.split('.').forEach(s => { if (s !== '') parts.push(s); });
    });
    const first = parts.shift();
    let obj = target;
    const all = [first, ...parts];
    for (let i = 0; i < all.length; i++) {
      const key = all[i];
      const nextKey = all[i+1];
      const isLast = i === all.length - 1;
      if (isLast) {
        if (key === '') {
          if (!Array.isArray(obj)) obj = []; // shouldn't occur
          obj.push(value);
        } else {
          obj[key] = value;
        }
      } else {
        const shouldBeArray = nextKey !== undefined && /^\d+$/.test(nextKey);
        if (obj[key] === undefined) {
          obj[key] = shouldBeArray ? [] : {};
        }
        obj = obj[key];
      }
    }
    return target;
  }

  for (const [rawName, rawVal] of formData.entries()) {
    const name = rawName;
    const value = rawVal;
    if (name.endsWith('[]')) {
      const base = name.slice(0, -2);
      if (!json[base]) json[base] = [];
      json[base].push(value);
      continue;
    }
    if (name.includes('[')) {
      setNested(json, name, value);
    } else {
      json[name] = value;
    }
  }

  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  return fetch('{{ route('portal.application.save') }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    body: JSON.stringify(json)
  })
  .then(() => {
    const s = document.getElementById('draftStatus');
    if (s) { s.style.display = 'inline'; setTimeout(() => s.style.display = 'none', 1500); }
  })
  .catch(() => { /* ignore draft errors */ });
}

// ============ Enrollment -> Dynamic Exam Sections ============
(function initExamSections(){
  const wrapper = document.getElementById('examSectionsWrapper');
  const container = document.getElementById('examSectionsContainer');
  const addExamBtn = document.getElementById('addExamSectionBtn');
  const altWrapper = document.getElementById('alternativeEntryDocsWrapper');
  const applicantTypeRadios = Array.from(document.querySelectorAll('.applicant-type-radio'));

  function getSelectedEntryType(){
    const checked = applicantTypeRadios.find(r => r.checked);
    return checked ? checked.value : null;
  }

  function isWassceOrSssce(){
    const v = getSelectedEntryType();
    return v === 'entry_wassce' || v === 'entry_sssce';
  }

  function isAlternativeEntry(){
    const v = getSelectedEntryType();
    return v === 'entry_ib' || v === 'entry_transfer' || v === 'entry_other';
  }

  function syncEntryTypeHiddenFields(){
    const selected = getSelectedEntryType();
    const map = ['entry_wassce', 'entry_sssce', 'entry_ib', 'entry_transfer', 'entry_other'];
    map.forEach(name => {
      const hidden = document.getElementById(name + '_hidden');
      if (hidden) {
        hidden.value = (selected === name) ? 1 : 0;
      }
    });
  }

  function updateEntryTypePanels(){
    const showExam = isWassceOrSssce();
    const oldMode = wrapper ? wrapper.dataset.lastMode : '';
    if (wrapper) {
      if (!showExam && container && oldMode === 'wassce') {
        container.innerHTML = '';
      }
      wrapper.style.display = showExam ? 'block' : 'none';
      wrapper.dataset.lastMode = showExam ? 'wassce' : 'other';
      if (showExam && addExamBtn) addExamBtn.style.display = '';
    }
    if (altWrapper) {
      altWrapper.style.display = isAlternativeEntry() ? 'block' : 'none';
    }
  }

  function ensureDefaultExamSection(){
    if (window.isSubmittedView) return;
    if (!isWassceOrSssce() || !container) return;
    if (container.querySelectorAll('.exam-section').length === 0) {
      addExamSection();
    }
  }

  applicantTypeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
      syncEntryTypeHiddenFields();
      updateEntryTypePanels();
      ensureDefaultExamSection();
    });
  });

  syncEntryTypeHiddenFields();
  updateEntryTypePanels();

  function addSubjectRow(sectionEl){
    const tmpl = document.getElementById('subjectRowTemplate');
    const tbody = sectionEl.querySelector('.subjectsBody');
    const clone = tmpl.content.cloneNode(true);
    tbody.appendChild(clone);

    reindexSubjectRows(sectionEl);

    const lastRow = tbody.querySelector('tr:last-child');
    if (lastRow) {
      const subjectInput = lastRow.querySelector('.subject_input');
      const letterInput = lastRow.querySelector('.grade_letter_input');
      const numberInput = lastRow.querySelector('.grade_number_input');

      if (subjectInput) { subjectInput.addEventListener('change', () => autosaveDraft()); }
      if (letterInput) { letterInput.addEventListener('change', () => autosaveDraft()); }
      if (numberInput) { numberInput.addEventListener('input', () => { computeBest6Total(sectionEl); autosaveDraft(); }); }
    }

    computeBest6Total(sectionEl);
  }

  function clampGlobalBestSixToMax(){
    if (!container) return;
    const checked = Array.from(container.querySelectorAll('.best_six_chk')).filter(c => c.checked);
    if (checked.length > 6) {
      checked.slice(6).forEach(c => { c.checked = false; });
    }
    container.querySelectorAll('.exam-section').forEach(computeBest6Total);
  }

  function addExamSection(){
    const tmpl = document.getElementById('examSectionTemplate');
    const section = tmpl.content.cloneNode(true);
    const sectionEl = section.querySelector('.exam-section');

    // Add an initial subject row
    const sectionIdx = document.querySelectorAll('#examSectionsContainer .exam-section').length;
    sectionEl.dataset.sectionIndex = sectionIdx;

    // Set names for the section header inputs
    const typeInput = sectionEl.querySelector('.exam_type');
    const sittingInput = sectionEl.querySelector('.sitting_exam');
    const yearInput = sectionEl.querySelector('.exam_year');
    const indexInput = sectionEl.querySelector('.index_number');
    if (typeInput) { typeInput.name = `exam_sections[${sectionIdx}][exam_type]`; typeInput.addEventListener('change', () => autosaveDraft()); }
    if (sittingInput) { sittingInput.name = `exam_sections[${sectionIdx}][sitting_exam]`; sittingInput.addEventListener('change', () => autosaveDraft()); }
    if (yearInput) { yearInput.name = `exam_sections[${sectionIdx}][year]`; yearInput.addEventListener('change', () => autosaveDraft()); }
    if (indexInput) { indexInput.name = `exam_sections[${sectionIdx}][index_number]`; indexInput.addEventListener('change', () => autosaveDraft()); }

    for (let i = 0; i < 6; i++) {
      addSubjectRow(sectionEl);
    }

    // Recompute total if number value changes
    sectionEl.addEventListener('input', (e) => {
      if (e.target && e.target.classList.contains('grade_number_input')) {
        computeBest6Total(sectionEl);
      }
    });

    // Bind add subject button
    sectionEl.querySelector('.addSubjectBtn').addEventListener('click', () => addSubjectRow(sectionEl));

    sectionEl.addEventListener('click', function (ev) {
      const btn = ev.target.closest('.removeSubjectRowBtn');
      if (!btn) return;
      ev.preventDefault();
      const tbody = sectionEl.querySelector('.subjectsBody');
      const tr = btn.closest('tr');
      if (!tbody || !tr || !tbody.contains(tr)) return;
      if (tbody.querySelectorAll('tr').length <= 1) {
        alert('You must keep at least one subject row. To clear it, edit the fields or remove the whole exam section instead.');
        return;
      }
      tr.remove();
      reindexSubjectRows(sectionEl);
      autosaveDraft();
    });

    // Bind WAEC fetch button
    const fetchBtn = sectionEl.querySelector('.fetch_waec_btn');
    if (fetchBtn) {
      fetchBtn.addEventListener('click', async () => {
        const examTypeEl = sectionEl.querySelector('.exam_type');
        const examYearEl = sectionEl.querySelector('.exam_year');
        const indexEl = sectionEl.querySelector('.index_number');
        const examtype = (examTypeEl && examTypeEl.value) ? String(examTypeEl.value).trim() : '';
        const examyear = (examYearEl && examYearEl.value) ? String(examYearEl.value).trim() : '';
        const cindex = (indexEl && indexEl.value) ? String(indexEl.value).trim() : '';
        if (!examtype || !examyear || !cindex) {
          alert('Please select Exam Type, enter Year and Index Number before fetching.');
          return;
        }
        fetchBtn.disabled = true;
        fetchBtn.textContent = 'Fetching...';
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          const res = await fetch('{{ route('portal.waec.fetch') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ cindex, examyear, examtype: Number(examtype) })
          });
          const data = await res.json();
          if (!res.ok) {
            throw new Error((data && (data.message || data.error)) ? (data.message || 'Failed') : 'Failed to fetch results');
          }
          const ok = data && data.reqstatus && Number(data.reqstatus.msgcode) === 0 && Array.isArray(data.resultdetails);
          if (!ok) {
            alert('No results found or verification failed. You can fill subjects manually.');
            return;
          }
          // Clear existing subject rows
          const tbody = sectionEl.querySelector('.subjectsBody');
          if (tbody) tbody.innerHTML = '';
          // Add rows from resultdetails
          function mapWaecLetterToNumber(letter) {
            if (!letter) return '';
            const t = String(letter).trim().toUpperCase();
            const map = { 'A1':1, 'B2':2, 'B3':3, 'C4':4, 'C5':5, 'C6':6, 'D7':7, 'E8':8, 'F9':9 };
            return map[t] ?? '';
          }
          data.resultdetails.forEach((row) => {
            addSubjectRow(sectionEl);
            const lastRow = sectionEl.querySelector('.subjectsBody tr:last-child');
            if (!lastRow) return;
            const subjectInput = lastRow.querySelector('.subject_input');
            const letterInput = lastRow.querySelector('.grade_letter_input');
            const numberInput = lastRow.querySelector('.grade_number_input');
            if (subjectInput) subjectInput.value = row.subject || '';
            if (letterInput) letterInput.value = row.grade || '';
            if (numberInput) numberInput.value = mapWaecLetterToNumber(row.grade);
          });
          reindexSubjectRows(sectionEl);
          computeBest6Total(sectionEl);
          autosaveDraft();
          alert('WAEC results fetched successfully. Please review and complete any missing fields.');
        } catch (e) {
          alert('Error fetching results. You can fill subjects manually.');
        } finally {
          fetchBtn.disabled = false;
          fetchBtn.textContent = 'Fetch';
        }
      });
    }

    // Bind remove section button
    sectionEl.querySelector('.removeExamSectionBtn').addEventListener('click', () => {
      if (confirm('Remove this exam section?')) {
        sectionEl.remove();
        if (isWassceOrSssce() && container.querySelectorAll('.exam-section').length === 0) {
          addExamSection();
        }
      }
    });

    container.appendChild(sectionEl);
  }

  function computeBest6Total(sectionEl){
    const rows = sectionEl.querySelectorAll('.subjectsBody tr');
    let selected = [];
    rows.forEach(tr => {
      const chk = tr.querySelector('.best_six_chk');
      const num = tr.querySelector('.grade_number_input');
      const val = num && num.value !== '' ? parseInt(num.value, 10) : null;
      if (chk && chk.checked && val !== null && !isNaN(val)) {
        selected.push(val);
      }
    });
    // Consider only top 6 selections (if user checks more we'll clamp earlier, but be safe)
    selected = selected.slice(0,6);
    const total = selected.reduce((a,b) => a + b, 0);
    const out = sectionEl.querySelector('.best6TotalValue');
    if (out) out.textContent = total;
  }

  function reindexSubjectRows(sectionEl){
    const sectionIdx = parseInt(sectionEl.dataset.sectionIndex, 10);
    if (isNaN(sectionIdx)) return;
    const tbody = sectionEl.querySelector('.subjectsBody');
    if (!tbody) return;
    tbody.querySelectorAll('tr').forEach((tr, rowIdx) => {
      const subjectInput = tr.querySelector('.subject_input');
      const letterInput = tr.querySelector('.grade_letter_input');
      const numberInput = tr.querySelector('.grade_number_input');
      const bestChk = tr.querySelector('.best_six_chk');
      if (subjectInput) subjectInput.name = 'exam_sections[' + sectionIdx + '][subjects][' + rowIdx + '][subject]';
      if (letterInput) letterInput.name = 'exam_sections[' + sectionIdx + '][subjects][' + rowIdx + '][grade_letter]';
      if (numberInput) numberInput.name = 'exam_sections[' + sectionIdx + '][subjects][' + rowIdx + '][grade_number]';
      if (bestChk) bestChk.name = 'exam_sections[' + sectionIdx + '][subjects][' + rowIdx + '][is_best_six]';
    });
    computeBest6Total(sectionEl);
  }

  if (addExamBtn) addExamBtn.addEventListener('click', addExamSection);

  window.validateWassceExamSections = function() {
    const cont = document.getElementById('examSectionsContainer');
    if (!cont) return false;
    const sections = cont.querySelectorAll('.exam-section');
    if (sections.length === 0) {
      alert('Please complete at least one Examination Details section.');
      return false;
    }
    for (let s = 0; s < sections.length; s++) {
      const sectionEl = sections[s];
      const typeInput = sectionEl.querySelector('.exam_type');
      const sittingInput = sectionEl.querySelector('.sitting_exam');
      const yearInput = sectionEl.querySelector('.exam_year');
      const indexInput = sectionEl.querySelector('.index_number');
      if (!typeInput?.value?.trim() || !sittingInput?.value?.trim() || !String(yearInput?.value ?? '').trim() || !indexInput?.value?.trim()) {
        alert('Please complete Exam Type, Sitting Exam, Year, and Index Number for every examination section.');
        return false;
      }
      const rows = sectionEl.querySelectorAll('.subjectsBody tr');
      for (const tr of rows) {
        const subj = tr.querySelector('.subject_input');
        const letter = tr.querySelector('.grade_letter_input');
        const num = tr.querySelector('.grade_number_input');
        const chk = tr.querySelector('.best_six_chk');
        if (!subj || !letter || !num || !chk) continue;
        const isEmpty = !subj.value.trim() && !letter.value.trim() && (num.value === '' || num.value == null) && !chk.checked;
        if (isEmpty) {
          alert('You have one or more empty subject rows. Please fill Subject, Grade (Letter), and Grade (Number) for each row you keep, or remove empty rows using Remove.');
          return false;
        }
        const rowUsed = !!(subj.value.trim()) || !!(letter.value.trim()) || (num.value !== '' && num.value != null) || !!chk.checked;
        if (rowUsed) {
          if (!subj.value.trim() || !letter.value.trim() || num.value === '' || num.value == null) {
            alert('Please complete Subject, Grade (Letter), and Grade (Number) for every subject row you have entered.');
            return false;
          }
        }
      }
    }
    let totalBestSix = 0;
    for (let s = 0; s < sections.length; s++) {
      const sectionEl = sections[s];
      sectionEl.querySelectorAll('.subjectsBody tr').forEach(tr => {
        const chk = tr.querySelector('.best_six_chk');
        if (chk && chk.checked) totalBestSix++;
      });
    }
    if (totalBestSix !== 6) {
      if (totalBestSix > 6) {
        alert('You have selected more than 6 subjects as your Best 6 across all examinations. Please uncheck until exactly 6 remain in total.');
      } else {
        alert('Please tick exactly 6 subjects as your Best 6 in total across all your examinations.');
      }
      return false;
    }
    return true;
  };

  if (isWassceOrSssce() && Array.isArray(window.prefillExamSections) && window.prefillExamSections.length && !window.isSubmittedView) {
    window.prefillExamSections.forEach((sec, idx) => {
      addExamSection();
      const sectionEl = container.querySelectorAll('.exam-section')[idx];
      if (!sectionEl) return;
      sectionEl.querySelector('.exam_type').value = sec.exam_type || '';
      sectionEl.querySelector('.sitting_exam').value = sec.sitting_exam || '';
      sectionEl.querySelector('.exam_year').value = sec.year || '';
      sectionEl.querySelector('.index_number').value = sec.index_number || '';
      const subjects = Array.isArray(sec.subjects) ? sec.subjects : [];
      const tbody = sectionEl.querySelector('.subjectsBody');
      if (tbody) tbody.innerHTML = '';
      if (subjects.length) {
        subjects.forEach((row) => {
          addSubjectRow(sectionEl);
          const lastRow = sectionEl.querySelector('.subjectsBody tr:last-child');
          if (!lastRow) return;
          const subjectInput = lastRow.querySelector('.subject_input');
          const letterInput = lastRow.querySelector('.grade_letter_input');
          const numberInput = lastRow.querySelector('.grade_number_input');
          const bestChk = lastRow.querySelector('.best_six_chk');
          if (subjectInput) subjectInput.value = row.subject || '';
          if (letterInput) letterInput.value = row.grade_letter || '';
          if (numberInput) numberInput.value = row.grade_number || '';
          if (bestChk) bestChk.checked = !!row.is_best_six;
        });
      } else {
        for (let i = 0; i < 6; i++) addSubjectRow(sectionEl);
      }
      reindexSubjectRows(sectionEl);
      computeBest6Total(sectionEl);
    });
    clampGlobalBestSixToMax();
  }

  if (container) {
    container.addEventListener('change', (e) => {
      const t = e.target;
      if (!t || !t.classList || !t.classList.contains('best_six_chk')) return;
      const allChecked = Array.from(container.querySelectorAll('.best_six_chk')).filter(c => c.checked);
      if (allChecked.length > 6) {
        t.checked = false;
        alert('You can select at most 6 subjects as your Best 6 in total across all examinations.');
      }
      container.querySelectorAll('.exam-section').forEach(computeBest6Total);
      autosaveDraft();
    });
  }

  ensureDefaultExamSection();
})();

// Existing functions
function addInstitutionRow() {
  const tbody = document.getElementById('institutionsBody');
  const idx = tbody.querySelectorAll('tr').length;
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="institutions[${idx}][name]" placeholder="e.g., Accra High School" required></td>
    <td><input type="text" name="institutions[${idx}][qualification]" placeholder="e.g., WASSCE " required></td>
    <td><input type="date" name="institutions[${idx}][date]" required></td>
    <td class="inst-row-actions">
      <button type="button" class="btn-link" onclick="removeInstitutionRow(this)" aria-label="Remove row">Remove</button>
    </td>
  `;
  tbody.appendChild(tr);
}

function removeInstitutionRow(btn) {
  const tbody = document.getElementById('institutionsBody');
  tbody.removeChild(btn.closest('tr'));
  Array.from(tbody.querySelectorAll('tr')).forEach((tr, i) => {
    tr.querySelectorAll('input').forEach(input => {
      input.name = input.name.replace(/\[\d+\]/, '[' + i + ']');
    });
  });
}

function previewFiles(inputIds, previewContainerId) {
  const container = document.getElementById(previewContainerId);
  container.innerHTML = '';
  let files = [];
  inputIds.forEach(id => {
    const input = document.getElementById(id);
    if (!input) return;
    if (input.multiple) {
      files = files.concat(Array.from(input.files || []));
    } else if (input.files && input.files[0]) {
      files.push(input.files[0]);
    }
  });

  if (!files.length) {
    container.classList.add('active');
    container.innerHTML = '<div class="hint">No file selected.</div>';
    return;
  }

  const first = files[0];
  renderSinglePreview(first, container);

  if (files.length > 1) {
    const names = files.map(f => f.name).join(', ');
    const list = document.createElement('div');
    list.className = 'file-names';
    list.textContent = 'Files: ' + names;
    container.appendChild(list);
  }

  container.classList.add('active');
}

function renderSinglePreview(file, container) {
  const url = URL.createObjectURL(file);
  const mime = file.type || '';
  container.innerHTML = '';
  if (mime.startsWith('image/')) {
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Preview';
    container.appendChild(img);
  } else if (mime === 'application/pdf') {
    const embed = document.createElement('embed');
    embed.src = url;
    embed.type = 'application/pdf';
    embed.style.width = '100%';
    embed.style.height = '480px';
    embed.setAttribute('aria-label', 'PDF Preview');
    container.appendChild(embed);
  } else {
    const p = document.createElement('p');
    p.textContent = 'Selected file: ' + (file.name || 'unnamed') + ' (' + (mime || 'unknown type') + ')';
    container.appendChild(p);
  }
}

function clearAllPreviews() {
  document.querySelectorAll('.preview-box').forEach(box => {
    box.classList.remove('active');
    box.innerHTML = '';
  });
  const otherList = document.getElementById('other_files_list');
  if (otherList) otherList.textContent = '';
}

function addEmploymentRow() {
  const tbody = document.getElementById('employmentBody');
  const idx = tbody.querySelectorAll('tr').length;
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="employment[${idx}][company]" placeholder="e.g., ABC Ltd"></td>
    <td><input type="text" name="employment[${idx}][duration]" placeholder="e.g., Jan 2020 – Dec 2022"></td>
    <td>
      <input type="file" id="employment_file_${idx}" name="employment[${idx}][file]" accept="application/pdf,image/*" class="file-upload" data-max-size="1048576">
      <small class="hint" style="display:block;margin-top:4px;">Max: 1MB</small>
      <button type="button" class="btn-link" onclick="previewEmploymentFile(${idx})">Preview</button>
      <div id="employment_preview_${idx}" class="preview-box"></div>
    </td>
    <td class="inst-row-actions">
      <button type="button" class="btn-link" onclick="removeEmploymentRow(this)">Remove</button>
    </td>
  `;
  tbody.appendChild(tr);
}

function removeEmploymentRow(btn) {
  const tbody = document.getElementById('employmentBody');
  tbody.removeChild(btn.closest('tr'));
}

function previewEmploymentFile(idx) {
  const input = document.getElementById(`employment_file_${idx}`);
  const container = document.getElementById(`employment_preview_${idx}`);
  container.innerHTML = '';
  if (!input.files || !input.files[0]) {
    container.classList.add('active');
    container.innerHTML = '<div class="hint">No file selected.</div>';
    return;
  }
  const file = input.files[0];
  const url = URL.createObjectURL(file);
  if (file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.src = url;
    container.innerHTML = '';
    container.appendChild(img);
  } else if (file.type === 'application/pdf') {
    const embed = document.createElement('embed');
    embed.src = url;
    embed.type = 'application/pdf';
    embed.style.width = '100%';
    embed.style.height = '400px';
    container.innerHTML = '';
    container.appendChild(embed);
  } else {
    container.textContent = 'Selected file: ' + file.name;
  }
  container.classList.add('active');
}

// Form validation on submit
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('applicationForm');
  
  if (!form) {
    console.log('WARNING: Form not found!');
    return;
  }
  
  console.log('Form validation listener attached successfully');
  
  form.addEventListener('submit', function (e) {
    console.log('Form submit validation triggered');
    
    // If application is submitted, only validate when an update button was clicked
    if (isSubmitted) {
      const clickedButton = e.submitter || document.activeElement;
      const isPersonalUpdate = clickedButton && clickedButton.id === 'updatePersonalDataBtn';
      const isDocumentsUpdate = clickedButton && clickedButton.id === 'updateDocumentsBtn';

      if (isPersonalUpdate) {
        const personalTab = document.getElementById('personal');
        if (!personalTab) return;
        
        const requiredFields = personalTab.querySelectorAll('input[required], select[required], textarea[required]');
        for (let field of requiredFields) {
          if (!field.value.trim()) {
            e.preventDefault();
            e.stopPropagation();
            field.focus();
            field.style.outline = '2px solid #e53935';
            alert('Please fill in all required fields in the Personal Data section.');
            return false;
          }
        }
        return true;
      }

      if (isDocumentsUpdate) {
        const documentsTab = document.getElementById('documents');
        const fileInputs = documentsTab ? documentsTab.querySelectorAll('input[type="file"].file-upload') : [];
        let hasNewUpload = false;

        for (let fileInput of fileInputs) {
          if (fileInput.files && fileInput.files.length > 0) {
            hasNewUpload = true;
            const maxSize = parseInt(fileInput.getAttribute('data-max-size')) || 1048576;
            for (let file of fileInput.files) {
              if (file.size > maxSize) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.style.outline = '2px solid #e53935';
                setTimeout(() => fileInput.focus(), 100);
                const sizeMB = (file.size / 1048576).toFixed(2);
                alert(`File "${file.name}" is too large (${sizeMB}MB). Maximum allowed size is 1MB.`);
                return false;
              }
            }
          }
        }

        if (!hasNewUpload) {
          e.preventDefault();
          e.stopPropagation();
          alert('Please select at least one document to upload.');
          return false;
        }

        return true;
      }

      e.preventDefault();
      return false;
    }
    
    // FIRST: Validate all file uploads for size (1MB = 1048576 bytes)
    const allFileInputs = form.querySelectorAll('input[type="file"].file-upload');
    for (let fileInput of allFileInputs) {
      if (fileInput.files && fileInput.files.length > 0) {
        const maxSize = parseInt(fileInput.getAttribute('data-max-size')) || 1048576;
        for (let file of fileInput.files) {
          if (file.size > maxSize) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.style.outline = '2px solid #e53935';
            setTimeout(() => fileInput.focus(), 100);
            const sizeMB = (file.size / 1048576).toFixed(2);
            alert(`File "${file.name}" is too large (${sizeMB}MB). Maximum allowed size is 1MB. Please choose a smaller file or compress it.`);
            return false;
          }
        }
      }
    }
    
    // SECOND: Specifically validate Documents tab required files (Priority check)
    const ghanaCardFront = document.getElementById('ghana_card_front');
    const ghanaCardBack = document.getElementById('ghana_card_back');
    const passportPicture = document.getElementById('passport_picture');
    
    // Only validate if these elements exist (not in submitted/view mode)
    if (ghanaCardFront) {
      if (!ghanaCardFront.files || ghanaCardFront.files.length === 0) {
        e.preventDefault();
        e.stopPropagation();
        currentTab = 4; // Documents tab
        showTab(currentTab);
        ghanaCardFront.style.outline = '2px solid #e53935';
        setTimeout(() => ghanaCardFront.focus(), 100);
        alert('Please upload Ghana Card (Front). This is required.');
        return false;
      }
    }
    
    if (ghanaCardBack) {
      if (!ghanaCardBack.files || ghanaCardBack.files.length === 0) {
        e.preventDefault();
        e.stopPropagation();
        currentTab = 4; // Documents tab
        showTab(currentTab);
        ghanaCardBack.style.outline = '2px solid #e53935';
        setTimeout(() => ghanaCardBack.focus(), 100);
        alert('Please upload Ghana Card (Back). This is required.');
        return false;
      }
    }
    
    if (passportPicture) {
      if (!passportPicture.files || passportPicture.files.length === 0) {
        e.preventDefault();
        e.stopPropagation();
        currentTab = 4; // Documents tab
        showTab(currentTab);
        passportPicture.style.outline = '2px solid #e53935';
        setTimeout(() => passportPicture.focus(), 100);
        alert('Please upload Passport Picture. This is required.');
        return false;
      }
    }
    
    // THIRD: Validate all tabs before submission
    for (let i = 0; i < totalTabs; i++) {
      const tabContent = document.getElementById(['personal', 'education', 'programs', 'employment', 'documents'][i]);
      if (!tabContent) continue;
      
      // Special validation for Education tab - Applicant Type must be selected
      if (i === 1) { // Education tab
        const applicantTypeRadios = document.querySelectorAll('.applicant-type-radio');
        const selectedRadio = Array.from(applicantTypeRadios).find(r => r.checked);
        
        if (!selectedRadio) {
          e.preventDefault();
          e.stopPropagation();
          currentTab = 1; // Education tab
          showTab(currentTab);
          const applicantTypeGroup = document.getElementById('applicantTypeGroup');
          if (applicantTypeGroup) {
            applicantTypeGroup.style.outline = '2px solid #e53935';
            applicantTypeGroup.style.borderRadius = '4px';
            applicantTypeGroup.style.padding = '4px';
          }
          setTimeout(() => {
            const firstRadio = applicantTypeRadios[0];
            if (firstRadio) firstRadio.focus();
          }, 100);
          const errorEl = document.getElementById('entry_type_error');
          if (errorEl) {
            errorEl.textContent = 'Please select your Applicant Type (WASSCE, SSSCE, International Baccalaureate, Transfer, or Other).';
            errorEl.style.display = 'block';
          } else {
            alert('Please select your Applicant Type (WASSCE, SSSCE, International Baccalaureate, Transfer, or Other). This field is required.');
          }
          return false;
        }

        const entryValue = selectedRadio.value;
        if (entryValue === 'entry_wassce' || entryValue === 'entry_sssce') {
          if (typeof window.validateWassceExamSections === 'function' && !window.validateWassceExamSections()) {
            e.preventDefault();
            e.stopPropagation();
            currentTab = 1;
            showTab(currentTab);
            return false;
          }
        }
      }
      
      const requiredFields = tabContent.querySelectorAll('input[required], select[required], textarea[required]');
      
      for (let field of requiredFields) {
        // Handle file inputs separately
        if (field.type === 'file') {
          if (!field.files || field.files.length === 0) {
            e.preventDefault();
            e.stopPropagation();
            // Switch to the tab with missing fields
            currentTab = i;
            showTab(currentTab);
            setTimeout(() => field.focus(), 100);
            field.style.outline = '2px solid #e53935';
            alert(`Please upload the required file: ${field.previousElementSibling?.textContent || 'Required file'}`);
            return false;
          }
        } else if (!field.value.trim()) {
          e.preventDefault();
          e.stopPropagation();
          // Switch to the tab with missing fields
          currentTab = i;
          showTab(currentTab);
          setTimeout(() => field.focus(), 100);
          alert(`Please fill in all required fields in the ${getTabName(i)} section.`);
          return false;
        }
      }
    }
    
    // Employment validation
    const tbody = document.getElementById('employmentBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      const company = row.querySelector('input[name^="employment"][name$="[company]"]');
      const duration = row.querySelector('input[name^="employment"][name$="[duration]"]');
      const fileInput = row.querySelector('input[type="file"]');
      const hasText = (company && company.value.trim()) || (duration && duration.value.trim());
      const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
      [company, duration, fileInput].forEach(el => { if (el) el.style.outline = ''; });
      if (hasText && !hasFile) {
        e.preventDefault();
        if (fileInput) fileInput.style.outline = '2px solid #e53935';
        alert('Please upload the Appointment Letter for each employment row that has Company/Duration filled.');
        return false;
      }
    }
  });
});

// Print Application Function
function printApplication() {
  // Hide the print button and chat widget before printing
  const printBtn = event.target.closest('button');
  const chatWidget = document.getElementById('tawkchat-container') || document.querySelector('.tawk-chat-widget') || document.querySelector('[id^="tawk"]');
  
  if (printBtn) printBtn.style.display = 'none';
  if (chatWidget) chatWidget.style.display = 'none';
  
  // Print the page
  window.print();
  
  // Restore after print
  setTimeout(() => {
    if (printBtn) printBtn.style.display = 'flex';
    if (chatWidget) chatWidget.style.display = 'block';
  }, 100);
}

// Add print-specific styles
const style = document.createElement('style');
style.textContent = `
  @media print {
    /* Hide unnecessary elements */
    .alert, button, .btn-link, .inline-options, .tawk-chat-widget, [id^="tawk"] {
      display: none !important;
    }
    
    /* Remove margins and padding for print */
    body {
      margin: 0;
      padding: 20px;
      font-size: 12pt;
    }
    
    /* Ensure proper page breaks */
    fieldset {
      page-break-inside: avoid;
    }
    
    /* Make tables fit nicely */
    table {
      page-break-inside: auto;
      width: 100%;
    }
    
    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }
    
    /* Hide file input fields */
    input[type="file"] {
      display: none !important;
    }
    
    /* Show uploaded file previews */
    .mb-2 {
      display: block !important;
      margin: 10px 0;
    }
    
    .mb-2 a {
      display: none !important;
    }
    
    /* Show images in print */
    .mb-2 img {
      display: block !important;
      max-width: 300px;
      max-height: 400px;
      border: 1px solid #ddd;
      margin: 10px 0;
    }
    
    /* Make readonly inputs look cleaner */
    input[readonly], textarea[readonly] {
      border: 1px solid #ddd !important;
      background: white !important;
    }
    
    /* Better heading styles */
    h1 {
      font-size: 18pt;
      text-align: center;
      margin-bottom: 20px;
    }
    
    legend {
      font-size: 14pt;
      font-weight: bold;
      margin-bottom: 10px;
    }
    
    /* Adjust container for print */
    .container {
      max-width: 100% !important;
      width: 100% !important;
      padding: 0 !important;
    }
  }
`;
document.head.appendChild(style);
</script>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68e59523a72e351952185ab1/1j70ct4nl';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

@endsection

