<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proof of Registration - {{ $student->student_id }}</title>
    <style>
        @page {
            margin: 28px 36px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .university-name {
            text-align: center;
            color: #1a4f9c;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0 0 14px 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-cell {
            width: 90px;
        }
        .logo-cell img {
            width: 78px;
            height: auto;
        }
        .title-cell {
            text-align: center;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .doc-semester {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .photo-cell {
            width: 90px;
            text-align: right;
        }
        .photo-box {
            width: 78px;
            height: 92px;
            border: 1px solid #999;
            display: inline-block;
            overflow: hidden;
            background: #f3f3f3;
            text-align: center;
        }
        .photo-box img {
            width: 78px;
            height: 92px;
            object-fit: cover;
        }
        .photo-placeholder {
            font-size: 9px;
            color: #777;
            padding-top: 36px;
        }
        .info-block {
            margin-bottom: 8px;
            line-height: 1.55;
        }
        .info-block .label {
            font-weight: bold;
        }
        .printed-on {
            text-align: right;
            font-style: italic;
            margin: 10px 0 6px 0;
        }
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 16px;
        }
        .courses-table th,
        .courses-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .courses-table th {
            background: #e6e6e6;
            font-weight: bold;
            text-align: left;
        }
        .courses-table .col-num {
            width: 36px;
            text-align: center;
        }
        .courses-table .col-code {
            width: 110px;
        }
        .courses-table .col-credits {
            width: 70px;
            text-align: center;
        }
        .courses-table .total-label {
            text-align: right;
            font-weight: bold;
        }
        .courses-table .total-credits {
            text-align: center;
            font-weight: bold;
        }
        .notes {
            color: #c00000;
            font-style: italic;
            font-weight: bold;
            margin: 8px 0 28px 0;
            line-height: 1.45;
        }
        .notes ol {
            margin: 4px 0 0 18px;
            padding: 0;
        }
        .notes li {
            margin-bottom: 3px;
        }
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
        }
        .signatures td {
            width: 50%;
            border: none;
            padding: 0;
            font-size: 12px;
        }
        .signatures .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="university-name">DELEXES UNIVERSITY COLLEGE, GHANA</div>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="DUC Logo">
                @endif
            </td>
            <td class="title-cell">
                <div class="doc-title">Proof of Registration</div>
                <div class="doc-semester">{{ $semesterLabel }}</div>
            </td>
            <td class="photo-cell">
                <div class="photo-box">
                    @if(!empty($photoSrc))
                        <img src="{{ $photoSrc }}" alt="Student Photo">
                    @else
                        <div class="photo-placeholder">Photo</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="info-block">
        <div><span class="label">Index Number:</span> {{ $student->student_id }}</div>
        <div><span class="label">Name:</span> {{ strtoupper($studentName) }}</div>
        <div><span class="label">Programme:</span> {{ strtoupper($programmeName) }}</div>
        <div>
            <span class="label">Level:</span> {{ $levelLabel }}
            &nbsp;&nbsp;•&nbsp;&nbsp;
            <span class="label">DOA:</span> {{ $doa }}
            &nbsp;&nbsp;•&nbsp;&nbsp;
            <span class="label">DOC:</span> {{ $doc }}
        </div>
        <div><span class="label">Campus:</span> {{ $campus }}</div>
        <div><span class="label">Session:</span> {{ $session }}</div>
    </div>

    <div class="printed-on">Printed on: <strong>{{ $printedOn }}</strong></div>

    <table class="courses-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-code">Course Code</th>
                <th>Course Title</th>
                <th class="col-credits">Credits</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courses as $index => $course)
                <tr>
                    <td class="col-num">{{ $index + 1 }}</td>
                    <td class="col-code">{{ $course['code'] }}</td>
                    <td>{{ $course['title'] }}</td>
                    <td class="col-credits">{{ $course['credits'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;">No courses registered</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="3" class="total-label">Total Credits</td>
                <td class="total-credits">{{ $totalCredits }}</td>
            </tr>
        </tbody>
    </table>

    <div class="notes">
        <div>Note:</div>
        <ol>
            <li>You are required to submit a printed copy of this proof of registration to your Faculty/Departmental Officer for endorsement.</li>
            <li>This proof of registration must be presented before a student is allowed to add or drop course(s) or register for re-sit(s).</li>
            <li>You are required to keep a copy of the endorsed proof of registration.</li>
        </ol>
    </div>

    <table class="signatures">
        <tr>
            <td>Student's Signature........................................</td>
            <td class="right">Faculty's Officer's Signature........................................</td>
        </tr>
    </table>
</body>
</html>
