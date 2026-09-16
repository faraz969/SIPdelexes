<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Transcript - {{ $student->student_id }}</title>
    <style>
        @page {
            margin: 22px 28px 28px 28px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .logo-cell { width: 72px; }
        .logo-cell img { width: 64px; height: auto; }
        .header-center { text-align: center; padding: 0 8px; }
        .uni-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0 0 3px 0;
            line-height: 1.2;
        }
        .uni-meta {
            font-size: 8.5px;
            line-height: 1.35;
            color: #222;
            margin: 0;
        }
        .photo-cell { width: 78px; text-align: right; }
        .photo-box {
            width: 70px;
            height: 84px;
            border: 1px solid #666;
            overflow: hidden;
            background: #f5f5f5;
            text-align: center;
        }
        .photo-box img {
            width: 70px;
            height: 84px;
        }
        .photo-placeholder {
            font-size: 8px;
            color: #888;
            padding-top: 32px;
        }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0 2px 0;
            letter-spacing: 0.4px;
        }
        .accreditation {
            text-align: center;
            font-size: 7.5px;
            color: #333;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        .notice-bar {
            background: #c9a227;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 0.6px;
            padding: 5px 8px;
            text-transform: uppercase;
        }
        .bio-wrap {
            border: 1px solid #222;
            border-top: none;
            margin-bottom: 12px;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bio-table td {
            width: 50%;
            vertical-align: top;
            padding: 7px 10px;
            font-size: 10px;
            line-height: 1.55;
            border: none;
        }
        .bio-label { font-weight: bold; }
        .semester-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .semester-heading {
            font-size: 10.5px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .results-table th,
        .results-table td {
            border: none;
            border-bottom: 0.5px solid #bbb;
            padding: 2px 4px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .results-table th {
            font-weight: bold;
            border-bottom: 1px solid #222;
            text-transform: uppercase;
            font-size: 8.5px;
        }
        .col-code { width: 12%; }
        .col-name { width: 48%; }
        .col-cr { width: 10%; text-align: center; }
        .col-grade { width: 12%; text-align: center; }
        .col-gp { width: 18%; text-align: right; }
        .summary-line {
            font-size: 9px;
            font-weight: bold;
            margin: 2px 0 0 0;
            letter-spacing: 0.2px;
        }
        .summary-line span { margin-right: 10px; }
        .classification {
            margin-top: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            border: none;
            font-size: 8.5px;
            color: #333;
            padding: 0;
            vertical-align: middle;
        }
        .footer-hash {
            text-align: center;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        .watermark {
            position: fixed;
            top: 38%;
            left: 28%;
            width: 44%;
            opacity: 0.06;
            z-index: -1;
        }
        .watermark img { width: 100%; }
    </style>
</head>
<body>
    @if(!empty($logoSrc))
        <div class="watermark"><img src="{{ $logoSrc }}" alt=""></div>
    @endif

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="DUC Logo">
                @endif
            </td>
            <td class="header-center">
                <div class="uni-name">Delexes University College, Ghana</div>
                <p class="uni-meta">
                    P.O.Box Co 3538, Tema | Peace Bee Junction C25 Tema-Aflao Road,<br>
                    Ningo-Prampram, Greater Accra, Ghana<br>
                    Tel: +233 (0) 55 1126 448 / +233 (0) 55 1198 100 &nbsp;|&nbsp; GPS: GN-0603-8481<br>
                    www.delexesuniversity.edu.gh &nbsp;|&nbsp; info@delexesuniversity.edu.gh
                </p>
            </td>
            <td class="photo-cell">
                <div class="photo-box">
                    @if(!empty($photoSrc))
                        <img src="{{ $photoSrc }}" alt="Student Photo">
                    @else
                        <div class="photo-placeholder">PHOTO</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Official Transcript of Academic Record</div>
    <div class="accreditation">
        Accredited by the Ghana Tertiary Education Commission (GTEC).<br>
        Affiliated programmes follow the grading scheme of the awarding / affiliating university (UCC).
    </div>

    <div class="notice-bar">A BLACK AND WHITE DOCUMENT IS NOT OFFICIAL</div>
    <div class="bio-wrap">
        <table class="bio-table">
            <tr>
                <td>
                    <span class="bio-label">Name:</span> {{ strtoupper($meta['name'] ?? '') }}<br>
                    <span class="bio-label">Date of Birth:</span> {{ $meta['dob'] ?? '—' }}<br>
                    <span class="bio-label">Programme:</span> {{ strtoupper($meta['programme'] ?? '—') }}
                </td>
                <td>
                    <span class="bio-label">Student Number:</span> {{ $meta['student_number'] ?? '—' }}<br>
                    <span class="bio-label">Sex:</span> {{ strtoupper($meta['sex'] ?? '—') }}<br>
                    <span class="bio-label">Period:</span> {{ $meta['period'] ?? '—' }}
                </td>
            </tr>
        </table>
    </div>

    @forelse($results['semesters'] as $semester)
        <div class="semester-block">
            <div class="semester-heading">{{ $semester['heading'] }}</div>
            <table class="results-table">
                <thead>
                    <tr>
                        <th class="col-code">CODE</th>
                        <th class="col-name">COURSE NAME</th>
                        <th class="col-cr">credits</th>
                        <th class="col-grade">GRADE</th>
                        <th class="col-gp">GRADE POINT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semester['courses'] as $course)
                        <tr>
                            <td class="col-code">{{ $course['course_code'] }}</td>
                            <td class="col-name">{{ strtoupper($course['course_title'] ?? '') }}</td>
                            <td class="col-cr">{{ rtrim(rtrim(number_format((float) $course['credits'], 2, '.', ''), '0'), '.') }}</td>
                            <td class="col-grade">{{ $course['grade'] ?? '—' }}</td>
                            <td class="col-gp">{{ $course['quality_points'] !== null ? number_format((float) $course['quality_points'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="summary-line">
                <span>TCR: {{ number_format((float) $semester['tcr'], 1) }}</span>
                <span>TGP: {{ number_format((float) $semester['tgp'], 2) }}</span>
                <span>GPA: {{ $semester['gpa'] !== null ? number_format((float) $semester['gpa'], 2) : '—' }}</span>
                <span>CGP: {{ number_format((float) $semester['cgp'], 2) }}</span>
                <span>CCR: {{ number_format((float) $semester['ccr'], 1) }}</span>
                <span>FGPA: {{ $semester['fgpa'] !== null ? number_format((float) $semester['fgpa'], 2) : '—' }}</span>
            </div>
        </div>
    @empty
        <p>No published results available for this transcript.</p>
    @endforelse

    @if(!empty($results['cumulative']['classification']))
        <div class="classification">
            Classification: {{ $results['cumulative']['classification'] }}
            @if($results['cumulative']['fgpa'] !== null)
                &nbsp;|&nbsp; Final CGPA: {{ number_format((float) $results['cumulative']['fgpa'], 2) }}
            @endif
        </div>
    @endif

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width: 28%;">Printed on {{ $printedOn }}</td>
                <td class="footer-hash">{{ $verificationCode }}</td>
                <td style="width: 28%; text-align: right;">Official Student Transcript</td>
            </tr>
        </table>
    </div>
</body>
</html>
