<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Your assessment booking is confirmed</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#0097b2;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Brainova School &mdash; assessment booking confirmed</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                Hi {{ $data['guardian_name'] ?? 'there' }}, thanks for booking an assessment for {{ $data['student_name'] ?? 'your child' }}.
                            </p>
                            <table role="presentation" width="100%" style="border-collapse:collapse;margin-bottom:18px;">
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;width:110px;vertical-align:top;">Scheduled for</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['slot_label'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;vertical-align:top;">Subject</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['subject'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;vertical-align:top;">Grade</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['grade'] ?? '—' }}</td>
                                </tr>
                            </table>

                            @if (!empty($data['assessment_link']))
                                <p style="margin:0 0 16px;color:#384049;font-size:14px;line-height:1.6;">
                                    When you're ready, open this link on the device {{ $data['student_name'] ?? 'your child' }} will use for the assessment:
                                </p>
                                <table role="presentation" width="100%" style="margin-bottom:16px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $data['assessment_link'] }}" style="display:inline-block;background:#0097b2;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">Start the assessment</a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 0;color:#8a97a0;font-size:12px;">This link stays valid for 7 days. If it's expired when you come back to it, just reply to this email and we'll send a fresh one.</p>
                            @else
                                <p style="margin:0 0 0;color:#384049;font-size:14px;line-height:1.6;">
                                    Our team is finishing setting up {{ $data['student_name'] ?? 'your child' }}'s assessment and will send the link shortly.
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
