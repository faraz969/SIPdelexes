<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Approved - SIP Login Credentials</title>
</head>
<body>
    <h2>Congratulations! Your Admission Has Been Approved</h2>
    
    <p>Dear {{ $user->name }},</p>
    
    <p>We are pleased to inform you that your admission application has been approved. Your Student Information Portal (SIP) account has been created.</p>
    
    <h3>Your Login Credentials:</h3>
    <ul>
        <li><strong>Student ID:</strong> {{ $student->student_id }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Temporary Password:</strong> {{ $password }}</li>
    </ul>
    
    <p><strong>Important:</strong> Please change your password immediately after logging in for the first time.</p>
    
    <h3>Login Instructions:</h3>
    <p>You can login using any of the following:</p>
    <ul>
        <li><strong>Student ID:</strong> {{ $student->student_id }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Serial Number:</strong> {{ $user->serial_number ?? 'N/A' }}</li>
    </ul>
    <ol>
        <li>Visit: <a href="{{ url('/login') }}">{{ url('/login') }}</a></li>
        <li>Enter your Student ID, Email, or Serial Number in the login field</li>
        <li>Enter your temporary password: <strong>{{ $password }}</strong></li>
        <li>After logging in, you will be redirected to your SIP dashboard</li>
        <li>Change your password in the profile section</li>
    </ol>
    
    <h3>Payment Instructions:</h3>
    <p>After logging in, you can view your school fees invoice and make payments through the SIP portal. You can pay using:</p>
    <ul>
        <li>Credit/Debit Card</li>
        <li>Mobile Money (MoMo)</li>
        <li>Bank Transfer</li>
    </ul>
    
    <p>If you have any questions, please contact the administration office.</p>
    
    <p>Best regards,<br>
    University Administration</p>
</body>
</html>

