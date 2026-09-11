<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>New website enquiry</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#0097b2;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Brainova School &mdash; website enquiry</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 18px;color:#384049;font-size:14px;line-height:1.6;">
                                Someone just submitted the Contact form on brainovaschool.com.
                            </p>
                            <table role="presentation" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;width:90px;vertical-align:top;">Name</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['name'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;vertical-align:top;">Email</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['email'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;vertical-align:top;">Phone</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['phone'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#0f2937;font-size:13px;font-weight:bold;vertical-align:top;">Subject</td>
                                    <td style="padding:8px 0;color:#384049;font-size:14px;">{{ $data['subject'] ?? '—' }}</td>
                                </tr>
                            </table>
                            <div style="margin-top:14px;padding:14px 16px;background:#eef3f7;border-radius:10px;color:#384049;font-size:14px;line-height:1.7;white-space:pre-line;">{{ $data['message'] ?? '' }}</div>
                            <p style="margin:20px 0 0;color:#8a97a0;font-size:12px;">
                                This message is also saved in the school&rsquo;s database. Reply directly to
                                {{ $data['email'] ?? 'the sender' }} to respond.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
