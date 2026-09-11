<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Result Slip - {{ $student->student_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .meta { text-align: center; margin-bottom: 12px; }
        .box { border: 1px solid #ccc; padding: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #eee; text-align: left; }
        .center { text-align: center; }
        .footer { margin-top: 20px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <h1>DELEXES UNIVERSITY COLLEGE</h1>
    <div class="meta">Student Result Slip (Provisional)</div>

    <div class="box">
        <strong>Name:</strong> {{ $student->user->name ?? '—' }} &nbsp;|&nbsp;
        <strong>Student ID:</strong> {{ $student->student_id }} &nbsp;|&nbsp;
        <strong>Programme:</strong> {{ $student->program->name ?? '—' }} &nbsp;|&nbsp;
        <strong>Level:</strong> {{ $student->level ?? '—' }}
    </div>

    <div class="box">
        <strong>CGPA:</strong> {{ $results['cumulative']['cgpa'] !== null ? number_format($results['cumulative']['cgpa'], 2) : '—' }}
        &nbsp;|&nbsp;
        <strong>Classification:</strong> {{ $results['cumulative']['classification'] ?? '—' }}
        &nbsp;|&nbsp;
        <strong>Total Credit:</strong> {{ $results['cumulative']['total_credit'] }}
        &nbsp;|&nbsp;
        <strong>Weighted Average:</strong> {{ $results['cumulative']['weighted_average'] ?? '—' }}
    </div>

    @foreach($results['semesters'] as $semester)
        <h2>{{ $semester['semester'] }} — {{ $semester['academic_year'] }} (GPA: {{ $semester['gpa'] !== null ? number_format($semester['gpa'], 2) : '—' }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th class="center">Cr</th>
                    <th class="center">Class</th>
                    <th class="center">Exam</th>
                    <th class="center">Mark</th>
                    <th class="center">Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semester['courses'] as $course)
                    <tr>
                        <td>{{ $course['course_code'] }}</td>
                        <td>{{ $course['course_title'] }}</td>
                        <td class="center">{{ $course['credits'] }}</td>
                        <td class="center">{{ $course['class_mark'] ?? '—' }}</td>
                        <td class="center">{{ $course['exam_mark'] ?? '—' }}</td>
                        <td class="center">{{ $course['final_mark'] ?? '—' }}</td>
                        <td class="center">{{ $course['grade'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div>
            Total Credit: {{ $semester['total_credit'] }} |
            Credit Obtained: {{ $semester['credit_obtained'] }} |
            Weighted Marks: {{ $semester['weighted_marks'] }} |
            Weighted Average: {{ $semester['weighted_average'] ?? '—' }}
        </div>
    @endforeach

    <div class="footer">
        Generated {{ now()->format('d M Y H:i') }} · Document for student use · Formal transcript template to follow
    </div>
</body>
</html>
