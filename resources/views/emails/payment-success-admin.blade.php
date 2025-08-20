<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Payment Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #28a745; color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
        <h1 style="margin-bottom: 10px;">New Payment Received</h1>
        <p>A new payment has been successfully processed on the Digital Ads Platform.</p>
    </div>

    <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #495057; margin-bottom: 15px;">Customer Information</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Name:</td>
                <td style="padding: 8px 0;">{{ $user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Email:</td>
                <td style="padding: 8px 0;">{{ $user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">User ID:</td>
                <td style="padding: 8px 0;">{{ $user->id ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #495057; margin-bottom: 15px;">Payment Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Plan Name:</td>
                <td style="padding: 8px 0;">{{ $plan->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Amount:</td>
                <td style="padding: 8px 0;">₹{{ number_format($payment->amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Payment ID:</td>
                <td style="padding: 8px 0;">{{ $payment->payment_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Transaction ID:</td>
                <td style="padding: 8px 0;">{{ $payment->transaction_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Payment Date:</td>
                <td style="padding: 8px 0;">{{ \Carbon\Carbon::parse($payment->transaction_date ?? now())->format('d M Y, h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Payment Method:</td>
                <td style="padding: 8px 0;">{{ ucfirst($payment->payment_mode ?? 'razorpay') }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px;">
        <h3 style="color: #495057; margin-bottom: 10px;">Action Required</h3>
        <p>Please review this payment in the admin dashboard and ensure all details are correct.</p>
    </div>

    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <p style="color: #6c757d; font-size: 14px;">Digital Ads Platform - Admin Notification</p>
    </div>
</body>
</html>
