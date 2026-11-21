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
                                Hi {{ $data['guardian_name'] ?? 'there' }}, {{ $data['student_name'] ?? 'your child' }} has finished the assessment. Here's a quick summary:
                            </p>
                            @php
                                $tiles = [
                                    ['Questions', $data['total'] ?? 0],
                                    ['Attempted', $data['attempted'] ?? 0],
                                    ['Correct', $data['correct'] ?? 0],
                                    ['Incorrect', $data['incorrect'] ?? 0],
                                    ['Skipped', $data['skipped'] ?? 0],
                                    ['Not reached', $data['unanswered'] ?? 0],
                                    ['Completion', $data['completion'] ?? '0%'],
                                    ['Total time', $data['total_time'] ?? '—'],
                                    ['Avg per question', $data['avg_time'] ?? '—'],
                                ];
                            @endphp
                            <table role="presentation" width="100%" style="border-collapse:collapse;margin-bottom:18px;">
                                @foreach (array_chunk($tiles, 3) as $row)
                                    <tr>
                                        @foreach ($row as $tile)
                                            <td width="33%" style="padding:6px;">
                                                <table role="presentation" width="100%" style="background:#eef3f7;border-radius:10px;">
                                                    <tr><td style="padding:12px;text-align:center;">
                                                        <div style="font-size:20px;font-weight:800;color:#0097b2;">{{ $tile[1] }}</div>
                                                        <div style="font-size:11px;color:#384049;">{{ $tile[0] }}</div>
                                                    </td></tr>
                                                </table>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                            <p style="margin:0 0 16px;color:#384049;font-size:14px;line-height:1.6;">
                                To see exactly which areas {{ $data['student_name'] ?? 'your child' }} needs support in, and get the teacher's recommended next steps, book a short meeting with the concerned teacher:
                            </p>
                            <table role="presentation" width="100%" style="margin-bottom:8px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $data['booking_link'] ?? '#' }}" style="display:inline-block;background:#0097b2;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">Book a meeting with the teacher</a>
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
