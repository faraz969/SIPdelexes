<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form - {{ $student->student_id }}</title>
    @if(!isset($isPdf) || !$isPdf)
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @endif
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #000;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: #fff;
            font-size: 11pt;
        }
        .header-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .logo-container {
            width: 80px;
            margin-right: 15px;
        }
        .logo-container img {
            width: 100%;
            height: auto;
        }
        .header-content {
            flex: 1;
            margin-left:58px;
        }
        .university-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 8px;
            font-family: Arial, sans-serif;
        }
        .address-line {
            font-size: 10pt;
            margin-bottom: 3px;
            color: #333;
        }
        .contact-line {
            font-size: 10pt;
            color: #333;
            text-align:center;
        }
        .divider {
            border-bottom: 1px solid #000;
            margin: 15px 0 20px 0;
        }
        .student-name-date {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 20px 0;
        }
        .student-name {
            font-size: 12pt;
            font-weight: bold;
        }
        .document-date {
            font-size: 11pt;
            text-align: right;
        }
        .offer-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .body-text {
            text-align: justify;
            margin: 15px 0;
            font-size: 11pt;
            line-height: 1.8;
        }
        .body-text strong {
            font-weight: bold;
        }
        .student-details-table {
           
            margin: 20px 0;
            font-size: 11pt;
            border-collapse: collapse;
        }
        .student-details-table td {
            
            vertical-align: top;
        }
        .student-details-table .detail-label {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 8px;
        }
        .student-details-table .detail-value {
            
            min-width: 150px;
            padding: 0 5px;
            font-weight:bold;
        }
        .student-details-table .detail-spacer {
            width: 30px;
        }
        .fees-section {
            margin: 20px 0;
        }
        .fees-section .title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 11pt;
        }
        .fees-item {
            margin: 8px 0;
            font-size: 11pt;
            line-height: 1.8;
        }
        .highlight-yellow {
            background-color: #ffff00;
            padding: 2px 4px;
        }
        .fees-item strong {
            font-weight: bold;
        }
        .anniversary-cloth {
            margin: 10px 0;
            font-size: 11pt;
        }
        .anniversary-cloth strong {
            font-weight: bold;
        }
        .hostel-info {
            margin: 15px 0;
            font-size: 11pt;
        }
        .hostel-info strong {
            font-weight: bold;
        }
        .dates-section {
            margin: 20px 0;
        }
        .dates-section .title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 11pt;
        }
        .dates-list {
            margin: 10px 0;
            font-size: 11pt;
            line-height: 2;
        }
        .dates-list .date-item {
            margin: 5px 0;
        }
        .dates-list .date-label {
            font-weight: bold;
            display: inline-block;
            min-width: 220px;
        }
        .dates-list .date-value {
            display: inline-block;
            
            min-width: 150px;
            padding: 0 5px;
        }
        .terms-section {
            margin: 20px 0;
        }
        .terms-section p {
            margin: 12px 0;
            text-align: justify;
            font-size: 11pt;
            line-height: 1.8;
        }
        .terms-section strong {
            font-weight: bold;
        }
        .signature-section {
            margin-top: 50px;
            margin-bottom: 20px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin: 50px 0 8px 0;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 5px;
            font-size: 11pt;
        }
        .signature-title {
            font-size: 11pt;
            margin-top: 5px;
        }
        .pin-info {
            margin-top: 15px;
            font-size: 11pt;
        }
        .footer {
            margin-top: 30px;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1e40af;
        }
        .btn-info {
            background-color: #0dcaf0;
            color: white;
        }
        .btn-info:hover {
            background-color: #0aa2c0;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <!-- Header with Logo -->
    <div class="header-container">
        <div class="logo-container">
            @if(file_exists(public_path('images/logo_blue.png')))
                <img src="{{ asset('images/logo.png') }}" alt="DUC Logo" style="max-width: 80px;">
            @else
                <div style="width: 80px; height: 80px; border: 2px solid #1e3a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #1e3a8a;">
                    DUC
                </div>
            @endif
        </div>
        <div class="header-content">
            <div class="university-name">DELEXES UNIVERSITY COLLEGE, GHANA</div>
            <div class="address-line">P.O.Box Co 3538,Tema | Peace Bee Junction C25 Tema-Aflao Road, Ningo-Prampram, Greater Accra, Ghana</div>
            <div class="contact-line">Tel: +233 (0) 55 1126 448 +233 (0) 55 1198 100 GPS: GN-0603-8481</div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Student Name and Date -->
    <div class="student-name-date">
        <div class="student-name">
            <span class="highlight-yellow"><strong>{{ $data['student_name'] }}</strong></span>
        </div>
        <div class="document-date highlight-yellow">{{ $data['date'] }}</div>
    </div>

    <!-- Offer Title -->
    <div class="offer-title">
        OFFER OF ADMISSION FOR <span class="highlight-yellow">{{ strtoupper($data['course_title']) }} DEGREE</span>
    </div>

    <!-- Body Text -->
    <div class="body-text">
        <p>
            The Admissions Committee has considered your application and is delighted to offer you admission to level {{ $data['level'] }} of the <strong class="highlight-yellow">BACHELOR OF SCIENCE</strong> {{ strtoupper($data['course_title']) }} degree programme commencing {{ $data['admission_date'] }}. Other details of your admission are as follows:
        </p>
    </div>

    <!-- Student Details (each field on its own line) -->
    <table class="student-details-table">
        <tr>
            <td class="detail-label">Student ID:</td>
            <td class="detail-value" colspan="7">{{ $data['student_id'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Nationality:</td>
            <td class="detail-value" colspan="7">{{ $data['nationality'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Gender:</td>
            <td class="detail-value" colspan="7">{{ $data['gender'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Date of Birth:</td>
            <td class="detail-value" colspan="7">{{ $data['date_of_birth'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Campus:</td>
            <td class="detail-value" colspan="7">{{ $data['preferred_campus'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Session:</td>
            <td class="detail-value" colspan="7">{{ $data['preferred_session'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Student Email Address:</td>
            <td class="detail-value" colspan="7">{{ $data['email'] }}</td>
        </tr>
    </table>

    <!-- Fees Section -->
    <div class="fees-section">
        <div class="title">The full fees for your enrollment are stated below (subject to review):</div>
        <div class="fees-item">
            Tuition and other fees <span class="highlight-yellow"><strong>GHS {{ $data['total_fees'] }}</strong></span> per semester. Minimum fees to be paid <span class="highlight-yellow">before Registration</span> <strong>{{ $data['minimum_fee_percentage'] }} (GHS {{ $data['minimum_fee_amount'] }})</strong>.
        </div>
        <div class="fees-item">
            Balance to be paid before end-of-semester exams: <strong>{{ $data['balance_percentage'] }} (GHS {{ $data['balance_amount'] }})</strong>.
        </div>
        <div class="anniversary-cloth">
            A one-off cost of <strong>GHS 150</strong> will be billed for each student for the purchase of the anniversary cloth.
        </div>
        <div class="hostel-info">
            <strong>Hostel:</strong> The University assists students to locate decent and affordable hostels.
        </div>
        <div class="body-text" style="margin-top: 15px;">
            <p>
                If you accept this offer of admission, then you are required to pay not less than {{ $data['minimum_fee_percentage'] ? str_replace('%', '', $data['minimum_fee_percentage']) : '60' }}% of the total fees by <strong>{{ $data['paid_fees_by_date'] }}</strong> and the outstanding balance within the semester. Full payment of fees for the semester is expected two weeks before the End-of-Semester Examinations begin.
            </p>
        </div>
    </div>

    <!-- Important Dates -->
    <div class="dates-section">
        <div class="title">Arrangements for the semester are as follows:</div>
        <div class="dates-list">
            <div class="date-item">
                <span class="date-label">Registration begins:</span>
                <span class="date-value">{{ $data['registration_begins'] }}</span>
            </div>
            <div class="date-item">
                <span class="date-label">Orientation for new students:</span>
                <span class="date-value">{{ $data['orientation_new_students'] }}</span>
            </div>
            <div class="date-item">
                <span class="date-label">Faculty Orientation:</span>
                <span class="date-value">{{ $data['faculty_orientation'] }}</span>
            </div>
            <div class="date-item">
                <span class="date-label">Lectures begin:</span>
                <span class="date-value">{{ $data['lectures_begin'] }}</span>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="terms-section">
        <p>
            You are obliged to undergo medical examination at a facility of your choice. Submit the report to your faculty during registration.
        </p>
        <p>
            You will be required to adhere to ALL University rules and regulations as contained in the <strong>Student Handbook</strong>, a copy of which will be made available to you during orientation.
        </p>
        <p>
            If at any time the University discovers that you do not, in fact, possess the qualifications by virtue of which you have been offered admission into your programme of study, you will be withdrawn.
        </p>
        <p>
            Should you decide to withdraw within four (4) weeks of registration, the University shall refund your fees to you less 40% to be retained as administrative charges. <strong>NO REFUND</strong> will be made after the fourth week.
        </p>
        <p>
            Once again, please accept our congratulations. We look forward to seeing you.
        </p>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-line"></div>
        <div class="signature-name">{{ $data['registrar_name'] }}</div>
        <div class="signature-title">{{ $data['registrar_title'] }}</div>
        <div class="pin-info">
            PIN: {{ $data['application_pin'] }}
        </div>
    </div>

    <!-- Action Buttons (Hidden in Print/PDF) -->
    @if(!isset($isPdf) || !$isPdf)
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-info">
            <i class="fas fa-print"></i> Print
        </button>
       
        <a href="{{ route('sip.downloads') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Downloads
        </a>
    </div>
    @endif

    <div class="footer">
        <p>This is an official document from Delexes University College.</p>
        <p>Generated on: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(!isset($isPdf) || !$isPdf)
    <script>
        // Auto-print if requested
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
    @endif
</body>
</html>
