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
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .header-table .logo-cell {
            width: 70px;
            padding-right: 12px;
        }
        .header-table .logo-cell img {
            width: 58px;
            height: auto;
            max-width: 58px;
            display: block;
        }
        .header-content {
            vertical-align: top;
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
        body.is-pdf .student-name-date {
            display: table;
            width: 100%;
        }
        body.is-pdf .student-name,
        body.is-pdf .document-date {
            display: table-cell;
            vertical-align: top;
        }
        body.is-pdf .document-date {
            text-align: right;
            white-space: nowrap;
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
            text-align: justify;
        }
        .fees-item strong {
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
    @php
        $logoPath = public_path('images/logo_blue.png');
        $logoExists = file_exists($logoPath);
        $logoSrc = ($logoExists && !empty($isPdf)) ? $logoPath : asset('images/logo_blue.png');
    @endphp
    <table class="header-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="logo-cell">
                @if($logoExists)
                    <img src="{{ $logoSrc }}" alt="DUC Logo" width="58">
                @else
                    <div style="width: 58px; height: 58px; border: 2px solid #1e3a8a; border-radius: 50%; text-align: center; line-height: 54px; font-weight: bold; color: #1e3a8a;">
                        DUC
                    </div>
                @endif
            </td>
            <td class="header-content">
                <div class="university-name">DELEXES UNIVERSITY COLLEGE, GHANA</div>
                <div class="address-line">P.O.Box Co 3538,Tema | Peace Bee Junction C25 Tema-Aflao Road, Ningo-Prampram, Greater Accra, Ghana</div>
                <div class="contact-line">Tel: +233 (0) 55 1126 448 +233 (0) 55 1198 100 GPS: GN-0603-8481</div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Student Name and Date -->
    <div class="student-name-date">
        <div class="student-name">
            <span><strong>{{ $data['student_name'] }}</strong></span>
        </div>
        <div class="document-date">{{ $data['date'] }}</div>
    </div>

    <!-- Offer Title -->
    @php
        $offerType = $data['offer_type'] ?? 'regular';
        $courseTitle = strtoupper($data['course_title'] ?? '');
        if ($offerType === 'conditional') {
            $offerTitle = 'CONDITIONAL OFFER OF ADMISSION FOR ' . $courseTitle . ' DEGREE';
        } elseif ($offerType === 'mature') {
            $offerTitle = 'OFFER OF ADMISSION FOR ' . $courseTitle . ' DEGREE AS A MATURE STUDENT';
        } else {
            $offerTitle = 'OFFER OF ADMISSION FOR ' . $courseTitle . ' DEGREE';
        }
    @endphp
    <div class="offer-title">
        {{ $offerTitle }}
    </div>

    <!-- Body Text -->
    <div class="body-text">
        <p>
            @if($offerType === 'conditional')
                The Admissions Committee has considered your application and is delighted to offer you
                <strong>Conditional Admission</strong> to <strong>level {{ $data['level'] }}</strong>
                of a 4-Year {{ $courseTitle }} degree programme, commencing
                <strong>{{ $data['programme_start_date'] }}</strong>.
                You are required to re-sit the WASSCE in <strong>{{ $data['conditional_subject'] }}</strong>
                within a year of being admitted to the programme. Failure to meet this requirement would result in withdrawal.
                Other details of your admission are as follows:
            @elseif($offerType === 'mature')
                The Admissions Committee has considered your application and is delighted to offer you admission to
                <strong>level {{ $data['level'] }}</strong> of a 4-Year {{ $courseTitle }} degree programme
                <strong>as a mature student subject to passing a written exam</strong>.
                Kindly note that {{ $data['academic_year'] }} academic year starts in
                <strong>{{ $data['programme_start_date'] }}</strong>.
                Other details of your admission are as follows:
            @else
                The Admissions Committee has considered your application and is delighted to offer you admission to
                <strong>level {{ $data['level'] }}</strong> of the <strong>BACHELOR OF SCIENCE</strong>
                {{ $courseTitle }} degree programme commencing {{ $data['admission_date'] }}.
                Other details of your admission are as follows:
            @endif
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
    @php
        $bankPaymentLines = $data['bank_payment_lines'] ?? [];
        $bankPaymentHtml = '';
        if (!empty($bankPaymentLines)) {
            $parts = [];
            foreach ($bankPaymentLines as $bankLine) {
                $parts[] = '<strong>' . e($bankLine) . '</strong>';
            }
            $bankPaymentHtml = ', through any Branch of ' . implode(' or ', $parts);
        }
    @endphp
    <div class="fees-section">
        <div class="title">The fees per semester are stated here-below (subject to review):</div>
        <div class="fees-item">
            Kindly note that your fees per semester is <strong>GHS {{ $data['total_fees'] }}</strong>.
            However, you can pay a minimum of <strong>{{ $data['minimum_fee_percentage'] }}</strong> of the fees
            (<strong>GHS {{ $data['minimum_fee_amount'] }}</strong>) before Registration, and the remaining
            <strong>{{ $data['balance_percentage'] }} (GHS {{ $data['balance_amount'] }})</strong> before end-of-semester exams{!! $bankPaymentHtml !!}.
            Note that Fees paid are <strong>NOT REFUNDABLE</strong>.
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
</body>
</html>
