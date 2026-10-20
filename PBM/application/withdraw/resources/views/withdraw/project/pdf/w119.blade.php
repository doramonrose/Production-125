@include('withdraw.project.pdf._head')
@php
    $blank = static function (string $value, string $fallback = '................................') {
        $text = trim($value);
        return $text !== '' ? $text : $fallback;
    };
    $department = $blank((string) ($d['department'] ?? ''));
    $departmentUnit = trim((string) ($d['department_unit'] ?? ''));
    $departmentJob = trim((string) ($d['department_job'] ?? ''));
    $departmentActor = trim(implode(' ', array_filter([$departmentUnit, $departmentJob], static fn ($part) => $part !== '')));
    if ($departmentActor === '') {
        $departmentActor = $department;
    }
    $phone = trim((string) ($d['phone'] ?? ''));
    $docNo = $blank((string) ($d['document_number'] ?? ''), '-');
    $docDate = $blank((string) ($d['document_date'] ?? ''), '-');
    // หน้า 2 อ้างอิงเลขที่/วันที่เดียวกับหัวบันทึกข้อความหน้าแรก
    $memoDocNo = $docNo;
    $memoDocDate = $docDate;
    $subject = $blank((string) ($d['subject'] ?? ''));
    $to = $blank((string) ($d['to'] ?? ''), 'คณบดีคณะเภสัชศาสตร์');
    $procurement = $blank((string) ($d['procurement'] ?? ''));
    $purpose = trim((string) ($d['purpose'] ?? ''));
    $purpose = trim((string) preg_replace('/^เพื่อ\s*/u', '', $purpose));
    $purpose = $purpose !== '' ? $purpose : $blank('');
    $budget = $blank((string) ($d['budget_source'] ?? ''));
    $amountDisplayArabic = (string) ($d['amount_display'] ?? '0.-');
    $amountDisplay = $amountDisplayArabic;
    $amountText = (string) ($d['amount_text'] ?? '-');
    $itemCount = (string) max((int) ($d['item_count'] ?? 1), 1);
    $receiptDocuments = $blank((string) ($d['receipt_documents'] ?? ''), 'ใบสำคัญรับเงิน');
    $attach = $blank((string) ($d['attachment_count'] ?? '1'), '1');
    $rows = is_array($d['detail_rows'] ?? null) ? $d['detail_rows'] : [];
    $winners = is_array($d['winners'] ?? null) ? $d['winners'] : [];
    $krutUrl = $krut['url'] ?? null;
    $krutHeightMm = (float) ($krut['height'] ?? 15);
    $krutWidthMm = (float) ($krut['width'] ?? 15);
    $indent = '';
    $requesterSignature = trim((string) ($d['requester_signature'] ?? ''));
    $showRequesterSignature = (str_starts_with($requesterSignature, 'data:image/')
        && ! str_starts_with($requesterSignature, 'data:image/svg'))
        || str_starts_with($requesterSignature, 'file://');
    $reviewerHeadSignature = trim((string) ($d['reviewer_head_signature'] ?? ''));
    $showReviewerHeadSignature = (str_starts_with($reviewerHeadSignature, 'data:image/')
        && ! str_starts_with($reviewerHeadSignature, 'data:image/svg'))
        || str_starts_with($reviewerHeadSignature, 'file://');
    $financeSignature = trim((string) ($d['finance_signature'] ?? ''));
    $showFinanceSignature = (str_starts_with($financeSignature, 'data:image/')
        && ! str_starts_with($financeSignature, 'data:image/svg'))
        || str_starts_with($financeSignature, 'file://');
    $secretarySignature = trim((string) ($d['secretary_signature'] ?? ''));
    $showSecretarySignature = (str_starts_with($secretarySignature, 'data:image/')
        && ! str_starts_with($secretarySignature, 'data:image/svg'))
        || str_starts_with($secretarySignature, 'file://');
    $approverSignature = trim((string) ($d['approver_signature'] ?? ''));
    $showApproverSignature = (str_starts_with($approverSignature, 'data:image/')
        && ! str_starts_with($approverSignature, 'data:image/svg'))
        || str_starts_with($approverSignature, 'file://');
    $approvalDecision = trim((string) ($d['approval_decision'] ?? ''));
    $approvalReason = trim((string) ($d['approval_reason'] ?? ''));
    $decisionCheckHtml = static function (bool $checked): string {
        $mark = $checked ? '&#10003;' : '&nbsp;';
        $class = $checked ? 'decision-check-cell' : 'decision-check-cell empty';

        return '<td class="decision-check-wrap" width="6mm" valign="middle">'
            .'<table cellpadding="0" cellspacing="0"><tr>'
            .'<td class="'.$class.'" align="center" valign="middle">'.$mark.'</td>'
            .'</tr></table>'
            .'</td>';
    };
    $agreeMark = $decisionCheckHtml($approvalDecision === 'approved');
    $disagreeMark = $decisionCheckHtml($approvalDecision === 'rejected');
    $rejectReasonDisplay = $approvalDecision === 'rejected' && $approvalReason !== ''
        ? $approvalReason
        : '................................................';
    $approverPositionLines = is_array($d['approver_position_lines'] ?? null)
        ? array_values(array_filter(array_map('trim', $d['approver_position_lines'])))
        : [];
    if ($approverPositionLines === []) {
        $approverPositionLines = \App\Support\EsignAttachedPdf::normalizeRequesterPositionLines(
            (string) ($d['approver_position'] ?? ''),
            'vice_dean'
        );
    }
    $approverPositionText = trim((string) ($d['approver_position'] ?? ''));
    if ($approverPositionText === '' && $approverPositionLines !== []) {
        $approverPositionText = implode(' ', $approverPositionLines);
    }
    if ($approverPositionText === '') {
        $approverPositionText = $blank('');
    }
    $approverName = trim(preg_replace('/\s+/u', ' ', (string) ($d['approver_name'] ?? '')) ?? '');
    $tb = static function (string $text): string {
        $normalized = trim(preg_replace('/[ \t\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $text) ?? $text);

        return \App\Support\ThaiLineBreak::insertBreakOpportunities(e($normalized));
    };
    $cellTb = static function (string $text): string {
        return \App\Support\ThaiLineBreak::insertBreakOpportunities(e($text));
    };
    $cellTbMultiline = static function (string $text) use ($cellTb): string {
        $parts = preg_split("/\r\n|\n|\r/u", $text) ?: [$text];

        return implode('<br>', array_map(static fn (string $line): string => $cellTb($line), $parts));
    };
    $paragraph1 = $tb(
        'ด้วย '.$departmentActor.' คณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ได้ดำเนินการจัดซื้อจัดจ้าง '
        .$procurement.' จำนวน '.$itemCount.' รายการ ในวงเงินจำนวน '.$amountDisplay
        .' ('.$amountText.') เพื่อ '.$purpose.' โดยมีรายละเอียดตาม '.$receiptDocuments
        .' ที่แนบมาพร้อมนี้ จำนวน '.$attach.' ฉบับ'
    );
    $paragraph2 = $tb(
        'ทั้งนี้ การดำเนินการจัดซื้อจัดจ้างดังกล่าว เป็นการดำเนินการตามหนังสือด่วนที่สุด ที่ กค(กวจ)0405.2/ว119 '
        .'ลงวันที่ 7 มีนาคม 2561 เรื่อง แนวทางการปฏิบัติในการดำเนินการจัดหาพัสดุที่เกี่ยวกับค่าใช้จ่ายในการบริหารงาน '
        .'ค่าใช้จ่ายในการฝึกอบรม การจัดงาน และการประชุมของหน่วยงาน โดยเบิกจ่ายจากงบประมาณ '.$budget
    );
    $paragraph3 = $tb($blank((string) ($d['closing'] ?? '')));
    $toConsider = $tb('เพื่อโปรดพิจารณาดังนี้.-');
    $item1 = $tb('1. ให้ความเห็นชอบการจัดซื้อจัดจ้างดังกล่าวข้างต้น');
    $item2 = $tb(
        '2. อนุมัติให้เบิกจ่ายเงิน จำนวน '.$amountDisplay.' บาท ('.$amountText.') เพื่อ '.$purpose
    );
    $announceBody1 = $tb(
        'ตามที่คณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ได้ดำเนินการจัดซื้อจัดจ้าง '
        .$procurement.' เพื่อ '.$purpose
    );
    $announceBody2 = $tb(
        'คณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ได้ดำเนินการพิจารณาคัดเลือก โดยมีผู้ชนะการคัดเลือกทั้งหมด คือ'
    );
    $announceClosing = $tb('จึงประกาศให้ทราบโดยทั่วกัน');
    $announceDotLine = str_repeat('.', 50);
    $winnerTotalDisplay = (string) ($d['winner_total_display'] ?? $amountDisplayArabic);
    $winnerTotalText = (string) ($d['winner_total_text'] ?? $amountText);
    $announceTotal = $tb(
        'รวมเป็นจำนวนเงิน '.$winnerTotalDisplay.' ('.$winnerTotalText.')'
    );
    $winnerLines = [];
    if ($winners === []) {
        $winners = [[
            'name' => '',
            'amount_display' => $amountDisplayArabic,
            'amount_text' => $amountText,
        ]];
    }
    foreach ($winners as $idx => $winner) {
        $winnerName = $blank((string) ($winner['name'] ?? ''), '................................');
        $winnerAmount = (string) ($winner['amount_display'] ?? $amountDisplayArabic);
        $winnerAmountText = (string) ($winner['amount_text'] ?? $amountText);
        $winnerLines[] = [
            'name' => e(($idx + 1).'. '.$winnerName),
            'amount' => e('จำนวนเงิน '.$winnerAmount.' บาท ('.$winnerAmountText.')'),
        ];
    }
@endphp

<table class="topbar">
    <tr>
        <td class="topbar-left">
            @if (!empty($krutUrl))
                <img class="krut-img" src="{{ $krutUrl }}" alt="ครุฑ" style="height:{{ $krutHeightMm }}mm;width:{{ $krutWidthMm }}mm;">
            @endif
        </td>
        <td class="center" valign="bottom"><div class="title">บันทึกข้อความ</div></td>
        <td class="topbar-right"></td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td colspan="2"><strong>ส่วนงาน</strong> {{ $department }} คณะเภสัชศาสตร์@if ($phone !== '') โทร. {{ $phone }}@endif</td>
    </tr>
    <tr>
        <td style="width:58%;"><strong>ที่</strong> {{ $docNo }}</td>
        <td><strong>วันที่</strong> {{ $docDate }}</td>
    </tr>
    <tr>
        <td colspan="2"><strong>เรื่อง</strong> {{ $subject }}</td>
    </tr>
    <tr>
        <td colspan="2"><strong>เรียน</strong> {{ $to }}</td>
    </tr>
</table>

<p class="p">{!! $paragraph1 !!}</p>
<p class="p">{!! $paragraph2 !!}</p>
<p class="p">{!! $paragraph3 !!}</p>

<table class="sign">
    <tr>
        <td style="width:50%;"></td>
        <td class="sign-box">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showRequesterSignature)
                            <img class="sign-img" src="{!! $requesterSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center">({{ $blank((string) ($d['requester_name'] ?? '')) }})</td>
                </tr>
                <tr>
                    <td align="center">{{ $blank((string) ($d['requester_position'] ?? '')) }}</td>
                </tr>
                @if ($showReviewerHeadSignature)
                    <tr>
                        <td align="right" class="sign-hang-cell">
                            <img class="sign-img-hang" src="{!! $reviewerHeadSignature !!}" alt="">
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<p class="p-flush" style="margin-top:6mm;">เรียน {{ $to }}</p>
<p class="p-item">{!! $toConsider !!}</p>
<p class="p-item">{!! $item1 !!}</p>
<p class="p-item">{!! $item2 !!}</p>

<table class="sign">
    <tr>
        <td style="width:50%;"></td>
        <td class="sign-box">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showFinanceSignature)
                            <img class="sign-img" src="{!! $financeSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center">({{ $blank((string) ($d['finance_name'] ?? '')) }})</td>
                </tr>
                <tr>
                    <td align="center">{{ $blank((string) ($d['finance_position'] ?? '')) }}</td>
                </tr>
                @if ($showSecretarySignature)
                    <tr>
                        <td align="right" class="sign-hang-cell">
                            <img class="sign-img-hang" src="{!! $secretarySignature !!}" alt="">
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<div class="approval-block">
<table class="decision-list" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        {!! $agreeMark !!}
        <td class="decision-option" valign="middle">เห็นชอบ, อนุมัติ, ดำเนินการตามเสนอ</td>
    </tr>
    <tr>
        {!! $disagreeMark !!}
        <td class="decision-option" valign="middle">ไม่เห็นชอบ เหตุผล {{ $rejectReasonDisplay }}</td>
    </tr>
</table>
<table class="sign">
    <tr>
        <td class="sign-box sign-approver-left">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showApproverSignature)
                            <img class="sign-img" src="{!! $approverSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center" class="sign-text">({{ $blank($approverName) }})</td>
                </tr>
                @foreach ($approverPositionLines as $positionLine)
                    <tr>
                        <td align="center" class="sign-text">{{ $blank($positionLine) }}</td>
                    </tr>
                @endforeach
                @if ($approverPositionLines === [])
                    <tr>
                        <td align="center" class="sign-text">{{ $approverPositionText }}</td>
                    </tr>
                @endif
            </table>
        </td>
        <td></td>
    </tr>
</table>
</div>

<pagebreak />

<div class="section-title">รายละเอียดประกอบรายงานขอความเห็นชอบการจัดซื้อจัดจ้าง</div>
<p class="p-flush">ตามหนังสือที่ {{ $memoDocNo }}&nbsp;&nbsp;&nbsp;ลงวันที่ {{ $memoDocDate }}</p>
<table class="items-table" style="margin-top:1.8mm;">
    <thead>
        <tr>
            <th style="width:10mm;">ลำดับ</th>
            <th style="width:42mm;">{!! $cellTb('บริษัท/ห้างร้าน') !!}</th>
            <th style="width:28mm;">{!! $cellTb('เลขที่เอกสาร') !!}</th>
            <th style="width:28mm;">{!! $cellTb('วันที่เอกสาร') !!}</th>
            <th>{!! $cellTb('ค่าใช้จ่าย') !!}</th>
            <th style="width:28mm;">{!! $cellTb('จำนวนเงิน') !!}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $idx => $row)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{!! $cellTb($blank((string) ($row['company'] ?? ''), '-')) !!}</td>
                <td>{!! $cellTb($blank((string) ($row['document_type'] ?? ''), '-')) !!}</td>
                <td class="center">{!! $cellTb($blank((string) ($row['document_date'] ?? ''), '-')) !!}</td>
                <td>{!! $cellTbMultiline($blank((string) ($row['description'] ?? ''), '-')) !!}</td>
                <td class="right">{!! $cellTb((string) ($row['amount_display'] ?? '0.-')) !!}</td>
            </tr>
        @empty
            <tr>
                <td class="center">1</td>
                <td>-</td>
                <td>-</td>
                <td class="center">-</td>
                <td>-</td>
                <td class="right">0.-</td>
            </tr>
        @endforelse
        <tr>
            <td colspan="5" class="center"><strong>รวม ({!! $cellTb($amountText) !!})</strong></td>
            <td class="right"><strong>{{ $amountDisplayArabic }}</strong></td>
        </tr>
    </tbody>
</table>
<table class="sign">
    <tr>
        <td style="width:50%;"></td>
        <td class="sign-box">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showRequesterSignature)
                            <img class="sign-img" src="{!! $requesterSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center">({{ $blank((string) ($d['requester_name'] ?? '')) }})</td>
                </tr>
                <tr>
                    <td align="center">{{ $blank((string) ($d['requester_position'] ?? '')) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<pagebreak />

<div class="announce-krut">
    @if (!empty($krutUrl))
        <img src="{{ $krutUrl }}" alt="ครุฑ" style="height:22mm;">
    @endif
</div>
<div class="announce-title"><strong>ประกาศคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่</strong></div>
<div class="center announce-subject"><strong>เรื่อง</strong> <strong>{{ $blank((string) ($d['announce_subject'] ?? '')) }}</strong></div>
<div class="center announce-dots">{{ $announceDotLine }}</div>
<p class="p">{!! $announceBody1 !!}</p>
<p class="p">{!! $announceBody2 !!}</p>
@foreach ($winnerLines as $winnerLine)
    <p class="announce-winner"><span class="announce-winner-name">{!! $winnerLine['name'] !!}</span> <span class="announce-winner-amount">{!! $winnerLine['amount'] !!}</span></p>
@endforeach
<p class="announce-winner">{!! $announceTotal !!}</p>
<p class="p">{!! $announceClosing !!}</p>
<table class="announce-date">
    <tr>
        <td style="width:50%;"></td>
        <td class="announce-date-text">ประกาศ ณ วันที่ {{ $d['announce_date']['day'] ?? '' }} เดือน {{ $d['announce_date']['month'] ?? '' }} พ.ศ. {{ $d['announce_date']['year'] ?? '' }}</td>
    </tr>
</table>
<table class="sign">
    <tr>
        <td style="width:50%;"></td>
        <td class="sign-box sign-approver-right">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showApproverSignature)
                            <img class="sign-img" src="{!! $approverSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center" class="sign-text">({{ $blank($approverName) }})</td>
                </tr>
                @foreach ($approverPositionLines as $positionLine)
                    <tr>
                        <td align="center" class="sign-text">{{ $blank($positionLine) }}</td>
                    </tr>
                @endforeach
                @if ($approverPositionLines === [])
                    <tr>
                        <td align="center" class="sign-text">{{ $approverPositionText }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
