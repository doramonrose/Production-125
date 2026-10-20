@php
    $displayCode = (string) ($payload['display_code'] ?? '-');
    $subjectText = (string) ($payload['subject_text'] ?? '-');
    $recipientName = (string) ($payload['recipient_name'] ?? '-');
    $applicantText = (string) ($payload['applicant_text'] ?? '-');
    $amountText = (string) ($payload['amount_text'] ?? '0.00');
    $actionUrl = (string) ($payload['action_url'] ?? '');
    $headerTitle = (string) ($payload['header_title'] ?? 'แจ้งเตือนรายการเบิกจ่ายทั่วไป');
    $headerDescription = (string) ($payload['header_description'] ?? 'มีรายการใหม่รอการตรวจสอบจากท่าน');
    $statusBadge = (string) ($payload['status_badge'] ?? 'รอตรวจสอบ');
    $introMessage = (string) ($payload['intro_message'] ?? 'มีรายการเบิกจ่ายทั่วไป รอการตรวจสอบจากท่าน');
    $buttonLabel = (string) ($payload['button_label'] ?? 'เปิดเพื่ออนุมัติ');
    $extraNote = (string) ($payload['extra_note'] ?? '');
@endphp
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แจ้งเตือนรายการเบิกจ่าย</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Tahoma,'Sarabun',Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.06);">
                    <tr>
                        <td style="padding:18px 22px;background:#0f172a;color:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="font-size:18px;font-weight:800;letter-spacing:0.2px;">{{ $headerTitle }}</div>
                                        <div style="font-size:13px;opacity:0.9;margin-top:6px;line-height:1.5;">
                                            {{ $headerDescription }}
                                        </div>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;background:#f59e0b;color:#111827;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800;">
                                            {{ $statusBadge }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px;">
                            <p style="margin:0 0 14px 0;font-size:15px;line-height:1.7;">
                                <strong>เรียน {{ $recipientName }}</strong><br>
                                {{ $introMessage }}
                            </p>

                            @if ($extraNote !== '')
                                <div style="margin:0 0 14px 0;padding:10px 12px;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:13px;line-height:1.6;border:1px solid #fecaca;">
                                    {{ $extraNote }}
                                </div>
                            @endif

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0;width:100%;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 14px 12px 14px;border-bottom:1px solid #e2e8f0;">
                                        <div style="font-size:12px;color:#64748b;font-weight:700;letter-spacing:0.2px;">เลขที่รายการ</div>
                                        <div style="margin-top:4px;font-size:15px;font-weight:800;color:#0f172a;">{{ $displayCode }}</div>
                                    </td>
                                    <td style="padding:14px 14px 12px 14px;border-bottom:1px solid #e2e8f0;" align="right">
                                        <div style="font-size:12px;color:#64748b;font-weight:700;letter-spacing:0.2px;">จำนวนเงิน</div>
                                        <div style="margin-top:4px;font-size:16px;font-weight:900;color:#0f766e;">{{ $amountText }} <span style="font-size:13px;font-weight:800;color:#0f766e;">บาท</span></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding:14px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                                            <tr>
                                                <td style="padding:6px 0;color:#64748b;width:140px;vertical-align:top;">เรื่อง</td>
                                                <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $subjectText }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#64748b;vertical-align:top;">ผู้ขอเบิก</td>
                                                <td style="padding:6px 0;color:#0f172a;">{{ $applicantText }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if ($actionUrl !== '')
                                <div style="margin-top:18px;">
                                    <a href="{{ $actionUrl }}"
                                       style="display:inline-block;background:#0284c7;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:10px;font-size:14px;font-weight:800;">
                                        {{ $buttonLabel }}
                                    </a>
                                    <div style="margin-top:10px;font-size:12px;line-height:1.6;color:#64748b;">
                                        หากปุ่มกดไม่ได้ ให้คัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์:<br>
                                        <span style="word-break:break-all;color:#0f172a;">{{ $actionUrl }}</span>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 22px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;">
                            <div style="font-weight:700;color:#475569;">ระบบเบิกจ่ายงบประมาณ (PBM Withdraw)</div>
                            <div>อีเมลนี้ถูกส่งอัตโนมัติ กรุณาอย่าตอบกลับ</div>
                        </td>
                    </tr>
                </table>
                <div style="max-width:640px;color:#94a3b8;font-size:11px;line-height:1.6;margin-top:10px;">
                    หากคุณไม่ได้เป็นผู้เกี่ยวข้องกับรายการนี้ กรุณาติดต่อผู้ดูแลระบบ
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
