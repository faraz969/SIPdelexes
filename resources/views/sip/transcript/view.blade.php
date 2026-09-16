<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Transcript - {{ $student->student_id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 0; background: #eef1f5; }
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            background: #1e3a8a; color: #fff; padding: 10px 16px;
            display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .toolbar a { color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.5); padding: 6px 12px; border-radius: 6px; }
        .toolbar .note { font-size: 12px; opacity: .9; }
        .sheet {
            max-width: 900px; margin: 20px auto; background: #fff; padding: 28px;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: top; border: none; padding: 0; }
        .logo-cell { width: 72px; }
        .logo-cell img { width: 64px; height: auto; }
        .header-center { text-align: center; padding: 0 8px; }
        .uni-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0 0 4px; }
        .uni-meta { font-size: 11px; line-height: 1.4; margin: 0; color: #222; }
        .photo-cell { width: 78px; text-align: right; }
        .photo-box { width: 70px; height: 84px; border: 1px solid #666; overflow: hidden; background: #f5f5f5; text-align: center; display: inline-block; }
        .photo-box img { width: 70px; height: 84px; object-fit: cover; }
        .photo-placeholder { font-size: 9px; color: #888; padding-top: 32px; }
        .doc-title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 10px 0 4px; }
        .accreditation { text-align: center; font-size: 10px; color: #333; margin: 0 0 10px; }
        .notice-bar { background: #c9a227; color: #fff; text-align: center; font-weight: bold; font-size: 12px; letter-spacing: .6px; padding: 7px 8px; text-transform: uppercase; }
        .bio-wrap { border: 1px solid #222; border-top: none; margin-bottom: 14px; }
        .bio-table { width: 100%; border-collapse: collapse; }
        .bio-table td { width: 50%; vertical-align: top; padding: 8px 10px; font-size: 12px; line-height: 1.55; }
        .bio-label { font-weight: bold; }
        .semester-block { margin-bottom: 14px; }
        .semester-heading { font-size: 13px; font-weight: bold; margin: 0 0 4px; }
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .results-table th, .results-table td { border-bottom: 1px solid #ccc; padding: 4px; font-size: 11px; }
        .results-table th { border-bottom: 1px solid #222; text-transform: uppercase; font-size: 10px; }
        .col-code { width: 12%; }
        .col-name { width: 48%; }
        .col-cr { width: 10%; text-align: center; }
        .col-grade { width: 12%; text-align: center; }
        .col-gp { width: 18%; text-align: right; }
        .summary-line { font-size: 11px; font-weight: bold; }
        .summary-line span { margin-right: 12px; }
        .classification { margin-top: 12px; font-weight: bold; }
        .footer { margin-top: 18px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 11px; color: #333; display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .footer-hash { font-family: monospace; font-size: 10px; }
        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>Official Transcript</strong>
            <div class="note">View only — PDF download is handled by the Registrar.</div>
        </div>
        <div>
            <a href="{{ route('sip.transcript.index') }}">Back to Transcript Requests</a>
        </div>
    </div>

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(!empty($logoSrc))
                        <img src="{{ asset('images/logo_blue.png') }}" alt="DUC Logo"
                             onerror="this.src='{{ asset('images/logo.png') }}'">
                    @endif
                </td>
                <td class="header-center">
                    <div class="uni-name">Delexes University College, Ghana</div>
                    <p class="uni-meta">
                        P.O.Box Co 3538, Tema | Peace Bee Junction C25 Tema-Aflao Road,<br>
                        Ningo-Prampram, Greater Accra, Ghana<br>
                        Tel: +233 (0) 55 1126 448 / +233 (0) 55 1198 100 | GPS: GN-0603-8481<br>
                        www.delexesuniversity.edu.gh | info@delexesuniversity.edu.gh
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

        @foreach($results['semesters'] as $semester)
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
        @endforeach

        @if(!empty($results['cumulative']['classification']))
            <div class="classification">
                Classification: {{ $results['cumulative']['classification'] }}
                @if($results['cumulative']['fgpa'] !== null)
                    | Final CGPA: {{ number_format((float) $results['cumulative']['fgpa'], 2) }}
                @endif
            </div>
        @endif

        <div class="footer">
            <div>Printed on {{ $printedOn }}</div>
            <div class="footer-hash">{{ $verificationCode }}</div>
            <div>Official Student Transcript (View Only)</div>
        </div>
    </div>
</body>
</html>
