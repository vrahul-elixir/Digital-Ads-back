<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
        <h1 style="color: #28a745; margin-bottom: 20px;">Payment Successful!</h1>
        <p>Hello {{ $user->name ?? 'User' }},</p>
        <p>Thank you for your payment. Your subscription has been successfully activated.</p>
    </div>

    <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #495057; margin-bottom: 15px;">Payment Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Plan Name:</td>
                <td style="padding: 8px 0;">{{ $plan->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Amount Paid:</td>
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
                <td style="padding: 8px 0; font-weight: bold;">Date:</td>
                <td style="padding: 8px 0;">{{ \Carbon\Carbon::parse($payment->transaction_date ?? now())->format('d M Y, h:i A') }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 10px; padding: 20px;">
        <h3 style="color: #0056b3; margin-bottom: 10px;">What's Next?</h3>
        <p>You can now access all features of your selected plan. Log in to your dashboard to get started.</p>
        <p>If you have any questions, feel free to contact our support team.</p>
    </div>

    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <p style="color: #6c757d; font-size: 14px;">Thank you for choosing Digital Ads Platform!</p>
    </div>
</body>
</html>
