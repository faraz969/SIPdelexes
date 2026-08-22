<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Payment Slip Submitted - SIP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <h2>Bank Payment Slip Submitted</h2>

    <p>A student has uploaded a bank payment slip for an invoice in SIP. Please review the attached slip and update ERP accordingly.</p>

    <h3>Payment Details</h3>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
        <tr>
            <th align="left">Payment Reference</th>
            <td>{{ $payment->payment_reference }}</td>
        </tr>
        <tr>
            <th align="left">Amount</th>
            <td>GHS {{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <th align="left">Submitted At</th>
            <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <th align="left">Status</th>
            <td>{{ ucfirst($payment->status) }} (awaiting accounts verification)</td>
        </tr>
    </table>

    <h3>Student</h3>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
        <tr>
            <th align="left">Student ID</th>
            <td>{{ $student->student_id }}</td>
        </tr>
        <tr>
            <th align="left">Name</th>
            <td>{{ optional($student->user)->name }}</td>
        </tr>
        <tr>
            <th align="left">Email</th>
            <td>{{ optional($student->user)->email }}</td>
        </tr>
        <tr>
            <th align="left">Phone</th>
            <td>{{ optional($student->user)->phone }}</td>
        </tr>
    </table>

    <h3>Invoice</h3>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
        <tr>
            <th align="left">Invoice Number</th>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <th align="left">Type</th>
            <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
        </tr>
        <tr>
            <th align="left">Academic Year</th>
            <td>{{ $invoice->academic_year }}</td>
        </tr>
        <tr>
            <th align="left">Invoice Total</th>
            <td>GHS {{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <th align="left">Current Balance</th>
            <td>GHS {{ number_format($invoice->balance, 2) }}</td>
        </tr>
        @if(!empty($invoice->erp_invoice_id))
        <tr>
            <th align="left">ERP Invoice ID</th>
            <td>{{ $invoice->erp_invoice_id }}</td>
        </tr>
        @endif
    </table>

    <p style="margin-top: 20px;">
        The payment slip is attached to this email. After verification, update the payment in ERPNext
        and mark the SIP payment as completed if needed.
    </p>
</body>
</html>
