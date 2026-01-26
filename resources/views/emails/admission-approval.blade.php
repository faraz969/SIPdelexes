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
        <li><strong>Student Email:</strong> {{ $login_email ?? ($student->student_id . '@delexesuniversity.edu.gh') }}</li>
        <li><strong>Temporary Password/PIN:</strong> {{ $password }}</li>
    </ul>
    
    <p><strong>Important:</strong> You <strong>MUST</strong> change your password when you log in for the first time. This is required for security purposes.</p>
    
    <h3>Login Instructions:</h3>
    <p><strong>You must login using your Student Email:</strong> {{ $login_email ?? ($student->student_id . '@delexesuniversity.edu.gh') }}</p>
    <ol>
        <li>Visit: <a href="{{ url('/login') }}">{{ url('/login') }}</a></li>
        <li>Enter your Student Email: <strong>{{ $login_email ?? ($student->student_id . '@delexesuniversity.edu.gh') }}</strong></li>
        <li>Enter your temporary password/PIN: <strong>{{ $password }}</strong></li>
        <li>After logging in, you will be <strong>required</strong> to change your password before accessing your dashboard</li>
        <li>Once you change your password, you can access all SIP features</li>
    </ol>
    
    <p><strong>Note:</strong> Your Student Email ({{ $login_email ?? ($student->student_id . '@delexesuniversity.edu.gh') }}) is your official university email address. The administration will create your webmail account manually.</p>
    
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

