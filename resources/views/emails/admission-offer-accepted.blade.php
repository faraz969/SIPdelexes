<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Offer Accepted</title>
</head>
<body>
    <h2>Admission Offer Accepted</h2>

    <p>A student has accepted their offer of admission.</p>

    <h3>Student details</h3>
    <ul>
        <li><strong>Name:</strong> {{ $studentName }}</li>
        <li><strong>Student ID:</strong> {{ $student->student_id }}</li>
        <li><strong>Email:</strong> {{ $studentEmail }}</li>
        @if(!empty($programName))
            <li><strong>Program:</strong> {{ $programName }}</li>
        @endif
        @if(!empty($academicYear))
            <li><strong>Academic year:</strong> {{ $academicYear }}</li>
        @endif
        @if(!empty($offerType))
            <li><strong>Offer type:</strong> {{ ucfirst($offerType) }}</li>
        @endif
        <li><strong>Accepted at:</strong> {{ $acceptedAt }}</li>
    </ul>

    <p>Best regards,<br>
    SIP System</p>
</body>
</html>
