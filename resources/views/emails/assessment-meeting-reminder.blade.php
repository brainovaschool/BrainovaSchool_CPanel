<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Your meeting is coming up</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#0097b2;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Your meeting is in about an hour</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                Hi {{ $data['guardian_name'] ?? 'there' }}, just a reminder — your meeting with the teacher about {{ $data['student_name'] ?? 'your child' }}'s assessment is coming up at {{ $data['slot_label'] ?? 'the scheduled time' }}.
                            </p>

                            <table role="presentation" width="100%" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $data['meeting_link'] }}" style="display:inline-block;background:#0097b2;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">Join the meeting</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px;color:#384049;font-size:14px;line-height:1.6;">
                                Please confirm you'll be there:
                            </p>
                            <table role="presentation" width="100%" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $data['confirm_link'] }}" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">I'll be there</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;color:#8a97a0;font-size:12px;">If you need to reschedule, just reply to this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
