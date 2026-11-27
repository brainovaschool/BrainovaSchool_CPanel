<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Your meeting slot was released</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#64748b;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Your meeting slot was released</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                Hi {{ $data['guardian_name'] ?? 'there' }}, we hadn't heard a confirmation for your meeting with the teacher about {{ $data['student_name'] ?? 'your child' }}'s assessment ({{ $data['slot_label'] ?? 'the scheduled time' }}), so that time has been given to another family.
                            </p>
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                If you'd still like a meeting, you're welcome to book a new time below.
                            </p>
                            <table role="presentation" width="100%" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $data['booking_link'] }}" style="display:inline-block;background:#0097b2;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">Book a new time</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;color:#8a97a0;font-size:12px;">If you confirmed but still received this, just reply to this email and we'll sort it out.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
