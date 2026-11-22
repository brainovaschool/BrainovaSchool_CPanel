<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Your child's assessment result</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#0097b2;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Brainova School &mdash; assessment result</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                Hi {{ $data['guardian_name'] ?? 'there' }}, {{ $data['student_name'] ?? 'your child' }} has finished the assessment.
                            </p>
                            @if (!empty($data['portal_link']))
                                <table role="presentation" width="100%" style="margin-bottom:18px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $data['portal_link'] }}" style="display:inline-block;background:#0097b2;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">View {{ $data['student_name'] ?? 'your child' }}'s results</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <p style="margin:0 0 16px;color:#384049;font-size:14px;line-height:1.6;">
                                To see exactly which areas {{ $data['student_name'] ?? 'your child' }} needs support in, and get the teacher's recommended next steps, book a short meeting with the concerned teacher:
                            </p>
                            <table role="presentation" width="100%" style="margin-bottom:8px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $data['booking_link'] ?? '#' }}" style="display:inline-block;background:#ffffff;color:#0097b2;text-decoration:none;font-size:14px;font-weight:bold;padding:11px 23px;border-radius:8px;border:1px solid #0097b2;">Book a meeting with the teacher</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
