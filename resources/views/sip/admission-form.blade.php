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
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        @media print {
            .no-print,
            .footer {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
                max-width: none;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .highlight-yellow {
                background-color: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                box-decoration-break: clone;
                -webkit-box-decoration-break: clone;
            }
        }
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.35;
            color: #000;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm 12mm;
            background: #fff;
            font-size: 10pt;
        }
        body.is-pdf {
            padding: 4mm 6mm;
        }
        .header-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .logo-container {
            width: 58px;
            margin-right: 12px;
        }
        .logo-container img {
            width: 100%;
            height: auto;
            max-width: 58px;
        }
        .header-content {
            flex: 1;
            margin-left: 12px;
        }
        .university-name {
            font-size: 15pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 3px;
            font-family: Arial, sans-serif;
        }
        .address-line {
            font-size: 9pt;
            margin-bottom: 2px;
            color: #333;
        }
        .contact-line {
            font-size: 9pt;
            color: #333;
            text-align: center;
        }
        .divider {
            border-bottom: 1px solid #000;
            margin: 6px 0 8px 0;
        }
        .student-name-date {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 6px 0;
        }
        .student-name {
            font-size: 11pt;
            font-weight: bold;
        }
        .document-date {
            font-size: 10pt;
            text-align: right;
        }
        .offer-title {
            text-align: center;
            font-size: 11.5pt;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .body-text {
            text-align: justify;
            margin: 6px 0;
            font-size: 10pt;
            line-height: 1.4;
        }
        .body-text p {
            margin: 0;
        }
        .body-text strong {
            font-weight: bold;
        }
        .student-details-table {
            margin: 6px 0;
            font-size: 10pt;
            border-collapse: collapse;
            line-height: 1.35;
        }
        .student-details-table td {
            vertical-align: top;
            padding: 1px 0;
        }
        .student-details-table .detail-label {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 8px;
        }
        .student-details-table .detail-value {
            min-width: 150px;
            padding: 0 5px;
            font-weight: bold;
        }
        .student-details-table .detail-spacer {
            width: 30px;
        }
        .fees-section {
            margin: 8px 0;
        }
        .fees-section .title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10pt;
        }
        .fees-item {
            margin: 3px 0;
            font-size: 10pt;
            line-height: 1.4;
        }
        .highlight-yellow {
            background-color: none;
            padding: 0 2px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
            box-decoration-break: clone;
            -webkit-box-decoration-break: clone;
        }
        .fees-item strong {
            font-weight: bold;
        }
        .anniversary-cloth {
            margin: 4px 0;
            font-size: 10pt;
        }
        .anniversary-cloth strong {
            font-weight: bold;
        }
        .hostel-info {
            margin: 6px 0;
            font-size: 10pt;
        }
        .hostel-info strong {
            font-weight: bold;
        }
        .dates-section {
            margin: 8px 0;
        }
        .dates-section .title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10pt;
        }
        .dates-list {
            margin: 4px 0;
            font-size: 10pt;
            line-height: 1.45;
        }
        .dates-list .date-item {
            margin: 1px 0;
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
            margin: 8px 0 4px 0;
        }
        .terms-section p {
            margin: 5px 0;
            text-align: justify;
            font-size: 10pt;
            line-height: 1.4;
        }
        .terms-section strong {
            font-weight: bold;
        }
        .signature-section {
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .signature-image {
            max-height: 42px;
            max-width: 160px;
            height: auto;
            display: block;
            margin-bottom: 2px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 220px;
            margin: 14px 0 4px 0;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 2px;
            font-size: 10pt;
        }
        .signature-title {
            font-size: 10pt;
            margin-top: 0;
        }
        .pin-info {
            margin-top: 6px;
            font-size: 10pt;
        }
        .footer {
            margin-top: 10px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        body.is-pdf .footer {
            display: none;
        }
        .bank-details-page {
            page-break-before: always;
            break-before: page;
            padding-top: 20px;
        }
        .bank-details-page .page-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 20px;
        }
        .bank-details-page .intro {
            text-align: justify;
            margin-bottom: 18px;
            font-size: 10.5pt;
            line-height: 1.5;
        }
        .bank-details-table {
            width: 100%;
            max-width: 480px;
            border-collapse: collapse;
            margin: 20px auto;
            font-size: 11pt;
        }
        .bank-details-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ccc;
            vertical-align: top;
        }
        .bank-details-table .bank-label {
            font-weight: bold;
            width: 40%;
            white-space: nowrap;
        }
        .bank-details-table .bank-value {
            font-weight: bold;
        }
        .bank-details-page .note {
            margin-top: 24px;
            font-size: 10pt;
            text-align: justify;
            line-height: 1.5;
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
<body class="{{ !empty($isPdf) ? 'is-pdf' : '' }}">
    <!-- Header with Logo -->
    <div class="header-container">
        <div class="logo-container">
            @if(file_exists(public_path('images/logo_blue.png')))
                <img src="{{ asset('images/logo_blue.png') }}" alt="DUC Logo" style="max-width: 58px;">
            @else
                <div style="width: 58px; height: 58px; border: 2px solid #1e3a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #1e3a8a;">
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
            The Admissions Committee has considered your application and is delighted to offer you admission to <strong>level {{ $data['level'] }}</strong> of the <strong class="highlight-yellow">BACHELOR OF SCIENCE</strong> {{ strtoupper($data['course_title']) }} degree programme commencing {{ $data['admission_date'] }}. Other details of your admission are as follows:
        </p>
    </div>

    <!-- Student Details (each field on its own line) -->
    <table class="student-details-table">
        <tr>
            <td class="detail-label">Student ID:</td>
            <td class="detail-value" colspan="7">{{ $data['student_id'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Level:</td>
            <td class="detail-value" colspan="7"><strong>{{ $data['level'] }}</strong></td>
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
       
        <div class="hostel-info">
            <strong>Hostel:</strong> The University assists students to locate decent and affordable hostels.
        </div>
        <div class="body-text">
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
        @if(!empty($data['registrar_signature']))
            <img src="{{ $data['registrar_signature'] }}" alt="Registrar signature" class="signature-image">
        @else
            <div class="signature-line"></div>
        @endif
        <div class="signature-name">{{ $data['registrar_name'] }}</div>
        <div class="signature-title">{{ $data['registrar_title'] }}</div>
        <div class="pin-info">
            PIN: {{ $data['application_pin'] }}
        </div>
    </div>

    <!-- Action Buttons (Hidden in Print/PDF) -->
    @if(!isset($isPdf) || !$isPdf)
    <div class="action-buttons no-print">
        @if(!empty($download))
        <a href="{{ route('sip.downloads.pdf', $download) }}" class="btn btn-info">
            <i class="fas fa-download"></i> Download PDF
        </a>
        @endif
       
        <a href="{{ route('sip.downloads') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Downloads
        </a>
    </div>
    @endif

    <div class="footer">
        <p>This is an official document from Delexes University College.</p>
        <p>Generated on: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @php
        $hasBankDetails = !empty($data['bank_name'])
            || !empty($data['bank_branch'])
            || !empty($data['bank_account_no'])
            || !empty($data['payment_reference']);
    @endphp

    @if($hasBankDetails)
    <!-- Page 2: Bank Payment Details -->
    <div class="bank-details-page">
        <div class="page-title">Bank Payment Details</div>
        <p class="intro">
            Please use the bank account details below when making fee payments related to this offer of admission.
            Ensure the payment reference is clearly stated on the deposit slip or transfer narration.
        </p>

        <table class="bank-details-table">
            <tr>
                <td class="bank-label">Bank Name:</td>
                <td class="bank-value">{{ $data['bank_name'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="bank-label">Branch:</td>
                <td class="bank-value">{{ $data['bank_branch'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="bank-label">Account No:</td>
                <td class="bank-value">{{ $data['bank_account_no'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="bank-label">Payment Reference:</td>
                <td class="bank-value">{{ $data['payment_reference'] ?: '—' }}</td>
            </tr>
        </table>

        <p class="note">
            After payment, keep your receipt/slip safely. You may be required to present it during registration
            or upload it through the Student Information Portal (SIP) for verification by the accounts department.
        </p>
    </div>
    @endif
</body>
</html>
