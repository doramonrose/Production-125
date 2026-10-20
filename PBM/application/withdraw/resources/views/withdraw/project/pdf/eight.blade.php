@php
    $hideFirstPageEsignFooter = true;
@endphp
@include('withdraw.project.pdf._head')
@php
    $blank = static function (string $value, string $fallback = '................................') {
        $text = trim($value);
        return $text !== '' ? $text : $fallback;
    };
    $department = $blank((string) ($d['department'] ?? ''));
    $phone = trim((string) ($d['phone'] ?? ''));
    $docNo = $blank((string) ($d['document_number'] ?? ''), '-');
    $docDate = $blank((string) ($d['document_date'] ?? ''), '-');
    $subject = $blank((string) ($d['subject'] ?? ''));
    $to = $blank((string) ($d['to'] ?? ''), 'คณบดีคณะเภสัชศาสตร์');
    $procurement = $blank((string) ($d['procurement'] ?? ''));
    $budget = $blank((string) ($d['budget_source'] ?? ''));
    $amountDisplay = (string) ($d['amount_display'] ?? '0.-');
    $amountText = (string) ($d['amount_text'] ?? '-');
    $purchaseItems = is_array($d['purchase_items'] ?? null) ? $d['purchase_items'] : [];
    $specItems = is_array($d['spec_items'] ?? null) ? $d['spec_items'] : [];
    $priceItems = is_array($d['price_items'] ?? null) ? $d['price_items'] : [];
    $considerRows = is_array($d['consider_rows'] ?? null) ? $d['consider_rows'] : [];
    $source = (string) ($d['price_source'] ?? 'market');
    $krutUrl = $krut['url'] ?? null;
    $krutHeightMm = (float) ($krut['height'] ?? 15);
    $krutWidthMm = (float) ($krut['width'] ?? 15);
    $requesterSignature = trim((string) ($d['requester_signature'] ?? ''));
    $showRequesterSignature = (str_starts_with($requesterSignature, 'data:image/')
        && ! str_starts_with($requesterSignature, 'data:image/svg'))
        || str_starts_with($requesterSignature, 'file://');
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
    $specSignerSignature = trim((string) ($d['spec_signer_signature'] ?? ''));
    $showSpecSignerSignature = (str_starts_with($specSignerSignature, 'data:image/')
        && ! str_starts_with($specSignerSignature, 'data:image/svg'))
        || str_starts_with($specSignerSignature, 'file://');
    $specSignerName = trim((string) ($d['spec_signer_name'] ?? ''));
    if (preg_match('/^(.*?)\s+-\s+/u', $specSignerName, $specSignerMatch) === 1) {
        $specSignerName = trim($specSignerMatch[1]);
    }
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
    $certBreak = static function (string $marked): string {
        $parts = explode('|', $marked);
        $html = '';
        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $html .= '<span style="font-size:0.01pt"> </span>';
            }
            $html .= e($part);
        }

        return $html;
    };
    $cellTb = static function (string $text): string {
        return \App\Support\ThaiLineBreak::insertBreakOpportunities(e($text));
    };
    $cellTbMultiline = static function (string $text) use ($cellTb): string {
        $parts = preg_split("/\r\n|\n|\r/u", $text) ?: [$text];

        return implode('<br>', array_map(static fn (string $line): string => $cellTb($line), $parts));
    };
    $preserveUserLines = static function (string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $rendered = [];
        foreach ($lines as $line) {
            if ($line === '') {
                $rendered[] = '&nbsp;';

                continue;
            }
            $line = str_replace("\t", '    ', $line);
            $escaped = str_replace(' ', '&nbsp;', e($line));
            $rendered[] = \App\Support\ThaiLineBreak::insertBreakOpportunities($escaped);
        }

        return implode('<br>', $rendered);
    };
    $opening = $tb(
        'ด้วย '.$department.' มีความประสงค์จะจัดซื้อจัดจ้าง '.$procurement
        .' ในวงเงิน '.$amountDisplay.' บาท ('.$amountText.') โดยเบิกจ่ายจาก '.$budget
        .' จึงขอรายงานขอความเห็นชอบการจัดซื้อหรือจัดจ้างโดยวิธีเฉพาะเจาะจง ดังนี้'
    );
    $scopeBody = trim((string) ($d['scope'] ?? ''));
    if ($scopeBody === '' || $scopeBody === 'เอกสารแนบ') {
        $scopeBody = '(เอกสารแนบ)';
    }
    $inspectorName = trim((string) ($d['inspector'] ?? ''));
    if (preg_match('/^(.*?)\s+-\s+/u', $inspectorName, $inspectorMatch) === 1) {
        $inspectorName = trim($inspectorMatch[1]);
    }
    $points = [
        ['เหตุผลและความจำเป็นที่ต้องซื้อหรือจ้าง', trim((string) ($d['reason'] ?? ''))],
        ['ขอบเขตของงานหรือรายละเอียดคุณลักษณะเฉพาะของพัสดุหรือแบบรูปรายการงานก่อสร้างที่จะซื้อหรือจ้างแล้วแต่กรณี', $scopeBody],
        ['ราคากลางของพัสดุที่จะซื้อหรือจ้าง', (string) ($d['median_price_display'] ?? $amountDisplay).' บาท ('.($d['median_price_text'] ?? $amountText).')'],
        ['วงเงินที่จะซื้อหรือจ้าง โดยให้ระบุวงเงินงบประมาณ ถ้าไม่มีเงินดังกล่าวให้ระบุวงเงินที่ประมาณว่าจะจ้างในครั้งนั้น', (string) ($d['budget_amount_display'] ?? $amountDisplay).' บาท ('.($d['budget_amount_text'] ?? $amountText).')'],
        ['กำหนดเวลาที่ต้องการใช้พัสดุนั้นหรือให้งานนั้นแล้วเสร็จ', $blank((string) ($d['timeline'] ?? ''), 'ภายใน 15 วัน')],
        ['วิธีที่จะซื้อหรือจ้าง และเหตุผลที่ต้องซื้อหรือจ้างโดยวิธีนั้น', 'วิธีเฉพาะเจาะจง เนื่องจากเป็นไปตามเงื่อนไขในมาตรา 56 (2)(ข) มาตรา 96 วรรคสอง และมาตรา 100 วรรคสาม แห่งพระราชบัญญัติการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560, และข้อ 79 วรรคหนึ่งแห่งระเบียบการคลังว่าด้วยการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560 และตามกฎหมายที่เกี่ยวข้อง'],
        ['หลักเกณฑ์การพิจารณาคัดเลือกข้อเสนอ', $blank((string) ($d['criteria'] ?? ''), 'ใช้เกณฑ์ราคา')],
        ['ขอแต่งตั้งผู้กำหนดลักษณะเฉพาะของพัสดุที่จะซื้อหรือจ้างหรือขอบเขตของงาน (TOR) และผู้ตรวจรับพัสดุ', $inspectorName, true],
    ];
    $followUp = $tb('หากให้ความเห็นชอบตามข้อ 1 - 8 ขอได้โปรดพิจารณาอนุมัติแต่งตั้งผู้กำหนดคุณลักษณะเฉพาะของพัสดุที่จะจัดซื้อหรือจัดจ้าง หรือขอบเขตของงาน (TOR) และผู้ตรวจรับพัสดุดังกล่าวและอนุมัติให้จัดซื้อหรือจัดจ้างดังนี้.-');
    $considerOpening = $tb(
        'ตาม รายงานขอความเห็นชอบการจัดซื้อหรือจัดจ้างโดยวิธีเฉพาะเจาะจง ที่ '
        .$blank((string) ($d['consider_ref_number'] ?? ''), '-')
        .' ลงวันที่ '.$blank((string) ($d['consider_ref_date'] ?? ''), '-')
        .' ได้รายงานผลการพิจารณาการจัดซื้อหรือจัดจ้าง ดังนี้'
    );
    $considerClosing = $tb('จึงเรียนมาเพื่อโปรดพิจารณา หากเห็นชอบขอได้โปรดอนุมัติให้สั่งซื้อสั่งจ้างจากผู้เสนอราคาดังกล่าวที่เสนอมาพร้อมนี้');
    $announceBody1 = $tb('ด้วยคณะเภสัชศาสตร์ ได้ดำเนินการจัดซื้อจัดจ้าง '.$procurement.' โดยวิธีเฉพาะเจาะจง และได้ดำเนินการคัดเลือกผู้ชนะการเสนอราคาแล้ว');
    $announceBody2 = $tb(
        'ผลปรากฏว่า '.$blank((string) ($d['winner_name'] ?? ''))
        .' เป็นผู้ชนะการคัดเลือก จำนวนเงิน '.$amountDisplay.' บาท ('.$amountText.')'
    );
    $announceClosing = $tb('จึงประกาศให้ทราบโดยทั่วกัน');
    $announceDotLine = str_repeat('.', 50);
    $tor = str_replace(["\r\n", "\r"], "\n", (string) ($d['tor'] ?? ''));
    if (trim($tor) === '') {
        $tor = '';
    }
    $marketLabel = 'ราคาที่ได้มาจากการสืบราคาจากท้องตลาด';
    $marketDetail = trim((string) ($d['price_source_detail'] ?? ''));
    if ($marketDetail !== '') {
        $marketLabel .= ' '.$marketDetail;
    }
    if ($source === 'market') {
        $winnerNames = [];
        foreach ($considerRows as $considerRow) {
            $winnerName = trim((string) ($considerRow['winner'] ?? ''));
            if ($winnerName !== '' && ! in_array($winnerName, $winnerNames, true)) {
                $winnerNames[] = $winnerName;
            }
        }
        if ($winnerNames === []) {
            $winnerName = trim((string) ($d['winner_name'] ?? ''));
            if ($winnerName !== '') {
                $winnerNames[] = $winnerName;
            }
        }
        if ($winnerNames !== []) {
            $marketLabel .= ' ( '.implode(', ', $winnerNames).' )';
        }
    }
    $priceMarks = [
        'committee' => 'ใช้ราคาที่ได้มาจากการคำนวณตามหลักเกณฑ์ที่คณะกรรมการราคากลางกำหนด',
        'cgd' => 'ใช้ราคาที่ได้มาจากฐานข้อมูลราคาอ้างอิงของพัสดุที่กรมบัญชีกลางจัดทำ',
        'budget_office' => 'ใช้ราคาที่สำนักงบประมาณหรือหน่วยงานกลางอื่นกำหนด',
        'market' => $marketLabel,
        'previous' => 'ราคาที่เคยซื้อหรือจ้างครั้งล่าสุดภายในระยะเวลาสองปีงบประมาณ',
        'other' => 'ราคาอื่นตามหลักเกณฑ์ วิธีการ หรือแนวทางปฏิบัติของหน่วยงานของรัฐนั้น ๆ',
    ];
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
    <tr><td colspan="2"><strong>เรื่อง</strong> {{ $subject }}</td></tr>
    <tr><td colspan="2"><strong>เรียน</strong> {{ $to }}</td></tr>
</table>

<p class="p">{!! $opening !!}</p>
<style>
    .eight-point-title {
        width: 100%;
        border-collapse: collapse;
        margin: 1.2mm 0 0 0;
    }
    .eight-point-gap {
        width: 25mm;
        padding: 0;
        border: 0;
    }
    .eight-point-num,
    .eight-point-label {
        padding: 0;
        border: 0;
        font-weight: bold;
        font-size: 16pt;
        line-height: 1.15;
        vertical-align: top;
    }
    .eight-point-num { width: 5mm; white-space: nowrap; }
    .eight-point-label { text-align: justify; }
    .eight-point-body {
        margin: 0 0 0.6mm 0;
        text-indent: 30mm;
        font-weight: normal;
        text-align: justify;
        line-height: 1.15;
        font-size: 16pt;
    }
    .eight-point-user {
        margin: 0 0 0.6mm 0;
        text-indent: 0;
        font-weight: normal;
        text-align: left;
        line-height: 1.15;
        font-size: 16pt;
    }
    .eight-point-inline {
        margin: 1.2mm 0 0.6mm 0;
        text-indent: 25mm;
        font-weight: normal;
        text-align: justify;
        line-height: 1.15;
        font-size: 16pt;
    }
    .eight-cert {
        margin: 6.5mm 0 1.8mm;
        padding-left: 5mm;
        text-indent: 0;
        text-align: justify;
        font-style: italic;
        line-height: 1.15;
        font-size: 16pt;
    }
</style>
@foreach ($points as $index => $point)
    @if (! empty($point[2]))
        <p class="eight-point-inline"><strong>{{ $index + 1 }}. {!! $tb($point[0]) !!}</strong> {!! $tb('คือ '.($blank((string) $point[1]))) !!}</p>
    @else
        <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="eight-point-gap"></td>
                <td class="eight-point-num" valign="top"><strong>{{ $index + 1 }}.</strong></td>
                <td class="eight-point-label" valign="top"><strong>{!! $tb($point[0]) !!}</strong></td>
            </tr>
        </table>
        <p class="eight-point-body">{!! $cellTbMultiline(trim((string) $point[1]) !== '' ? (string) $point[1] : '-') !!}</p>
    @endif
@endforeach

<pagebreak page-selector="memo" />

<p class="p"><strong>{!! $followUp !!}</strong></p>
<table class="items-table">
    <thead>
        <tr>
            <th style="width:18mm;">ลำดับที่</th>
            <th>รายการที่ขอซื้อหรือขอจ้าง</th>
            <th style="width:32mm;">จำนวน</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($purchaseItems as $idx => $item)
            <tr>
                <td class="center">{{ $idx + 1 }}.</td>
                <td>{!! $cellTb($blank((string) ($item['name'] ?? ''), '-')) !!}</td>
                <td class="center">{!! $cellTb($blank((string) ($item['quantity'] ?? ''), '-')) !!}</td>
            </tr>
        @empty
            <tr>
                <td class="center">1.</td>
                <td>{!! $cellTb($procurement) !!}</td>
                <td class="center">1</td>
            </tr>
        @endforelse
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
                <tr><td align="center">({{ $blank((string) ($d['requester_name'] ?? '')) }})</td></tr>
                <tr><td align="center">{{ $blank((string) ($d['requester_position'] ?? '')) }}</td></tr>
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

<table class="sign">
    <tr>
        <td class="sign-text"><strong>ความเห็นของหัวหน้าเจ้าหน้าที่</strong></td>
    </tr>
    <tr>
        <td style="padding-left:25mm;">
            <table cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:auto;">
                <tr>
                    <td class="sign-text">ได้ตรวจสอบแล้วเป็นไปตามระเบียบที่เกี่ยวข้อง</td>
                </tr>
                <tr>
                    <td align="center">
                        <table class="sign-caption" cellpadding="0" cellspacing="0" align="center">
                            <tr>
                                <td align="center" class="sign-img-cell">
                                    @if ($showFinanceSignature)
                                        <img class="sign-img" src="{!! $financeSignature !!}" alt="">
                                    @else
                                        <div style="height:12mm;"></div>
                                    @endif
                                </td>
                            </tr>
                            <tr><td align="center">({{ $blank((string) ($d['officer_name'] ?? '')) }})</td></tr>
                            <tr><td align="center">{{ $blank((string) ($d['officer_position'] ?? ''), 'หัวหน้าเจ้าหน้าที่') }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="approval-block">
    <table class="decision-list" cellpadding="0" cellspacing="0" width="100%" style="margin-left:12.5mm;">
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
                    <tr><td align="center" class="sign-text">({{ $blank($approverName) }})</td></tr>
                    @foreach ($approverPositionLines as $positionLine)
                        <tr><td align="center" class="sign-text">{{ $blank($positionLine) }}</td></tr>
                    @endforeach
                    @if ($approverPositionLines === [])
                        <tr><td align="center" class="sign-text">{{ $approverPositionText }}</td></tr>
                    @endif
                </table>
            </td>
            <td></td>
        </tr>
    </table>
</div>

<pagebreak page-selector="doc" />

<div class="section-title center" style="font-size:18pt; font-weight:bold; text-align:center;"><u>{{ $blank((string) ($d['spec_title'] ?? ''), 'คุณลักษณะเฉพาะ/TOR จ้างซื้อวัสดุ') }}</u></div>
@php
    $specBody1 = $tor;
    if ($specBody1 === '') {
        $specLines = [];
        foreach ($specItems as $idx => $item) {
            $specLines[] = '1.'.($idx + 1).' '.$blank((string) ($item['name'] ?? ''), '-');
            $specDetail = trim((string) ($item['detail'] ?? ''));
            if ($specDetail !== '') {
                $specLines[] = $specDetail;
            }
        }
        $specBody1 = implode("\n", $specLines);
    }
    $specSections = [
        ['1.', 'รายละเอียดคุณลักษณะเฉพาะของพัสดุ/TOR ที่จะจัดจ้างต้องไม่ต่ำกว่ารายละเอียดดังนี้.-', $specBody1, '5mm', true],
        ['2.', 'หลักเกณฑ์การพิจารณาคัดเลือกข้อเสนอ', 'เกณฑ์ราคา', '5mm'],
        ['3.', 'ระยะเวลาที่ต้องการใช้พัสดุ', $blank((string) ($d['timeline'] ?? ''), 'ภายใน 15 วัน'), '5mm'],
        ['4.', 'วงเงินที่ประมาณที่จะจ้าง', (string) ($d['budget_amount_display'] ?? '0.-').' บาท ('.($d['budget_amount_text'] ?? $amountText).')', '5mm'],
        ['5.', 'รายละเอียดอื่น', '', '5mm'],
    ];
    $specSubItems = [
        ['5.1', 'ผู้เสนอราคาจะต้องเสนอพัสดุสดที่ผลิตภายในประเทศเป็นลำดับแรก หากไม่มีหน่วยงานจะพิจารณาพัสดุที่ผลิตหรือนำเข้าจากต่างประเทศตามความจำเป็น'],
        ['5.2', 'ผู้เสนอราคาจะต้องเสนอยี่ห้อ และประเทศผู้ผลิตของผลิตภัณฑ์ที่เสนอให้ครบถ้วนทุกรายการ'],
    ];
@endphp
@foreach ($specSections as $section)
    <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="eight-point-num" valign="top" style="width:{{ $section[3] }};"><strong>{{ $section[0] }}</strong></td>
            <td class="eight-point-label" valign="top"><strong>{!! $tb($section[1]) !!}</strong></td>
        </tr>
    </table>
    @if (trim((string) $section[2]) !== '')
        @if (! empty($section[4]))
            <p class="eight-point-user" style="padding-left:{{ $section[3] }};">{!! $preserveUserLines((string) $section[2]) !!}</p>
        @else
            <p class="eight-point-body" style="text-indent:0; padding-left:{{ $section[3] }};">{!! $cellTbMultiline((string) $section[2]) !!}</p>
        @endif
    @endif
@endforeach
@foreach ($specSubItems as $sub)
    <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top" style="width:5mm; padding:0; border:0;"></td>
            <td valign="top" style="width:8mm; padding:0; border:0; font-weight:normal; font-size:16pt; white-space:nowrap;">{{ $sub[0] }}</td>
            <td valign="top" style="padding:0; border:0; font-weight:normal; font-size:16pt; text-align:justify;">{!! $tb($sub[1]) !!}</td>
        </tr>
    </table>
@endforeach
<p class="eight-cert">{!! $certBreak('ขอรับ|รอง|ว่า คุณลักษณะ|เฉพาะ|ของ|พัสดุ|ที่|จะ|จัด|ซื้อ|จัด|จ้าง|ใน|ครั้ง|นี้|เป็น|ไป|ตาม') !!} <strong style="font-style:italic;">{!! $certBreak('มาตรา 9 แห่ง|พระ|ราช|บัญญัติ|การ|จัด|ซื้อ|จัด|จ้าง|และ|การ|บริหาร|พัสดุ|ภาค|รัฐ พ.ศ. 2560') !!}</strong> {!! $certBreak('ซึ่ง|กำหนด|ไว้|ว่า การ|กำหนด|คุณลักษณะ|เฉพาะ|ของ|พัสดุ|ที่|จะ|ทำการ|จัด|ซื้อ|จัด|จ้าง ให้|หน่วย|งาน|ของ|รัฐ|คำนึง|ถึง|คุณภาพ เทคนิค และ|วัตถุประสงค์|ของ|การ|จัด|ซื้อ|จัด|จ้าง|พัสดุ|นั้น และ|ห้าม|มิ|ให้|กำหนด|คุณลักษณะ|เฉพาะ|ของ|พัสดุ|ให้|ใกล้|เคียง|กับ|ยี่ห้อ|ใด|ยี่ห้อ|หนึ่ง หรือ|ของ|ผู้|ขาย|ราย|ใด|ราย|หนึ่ง|โดย|เฉพาะ เว้น|แต่|พัสดุ|ที่|จะ|ทำการ|จัด|ซื้อ|จัด|จ้าง ตาม|วัตถุประสงค์|นั้น|มี|ยี่ห้อ|เดียว|หรือ|จะ|ต้อง|ใช้|อะไหล่|ของ|ยี่ห้อ|ใด ก็|ให้|ระบุ|ยี่ห้อ|นั้น') !!}</p>
<table class="sign">
    <tr>
        <td style="width:50%;"></td>
        <td class="sign-box">
            <table class="sign-caption" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" class="sign-img-cell">
                        @if ($showSpecSignerSignature)
                            <img class="sign-img" src="{!! $specSignerSignature !!}" alt="">
                        @else
                            <div style="height:12mm;"></div>
                        @endif
                    </td>
                </tr>
                <tr><td align="center">({{ $blank($specSignerName) }})</td></tr>
                <tr><td align="center">{{ $blank((string) ($d['spec_signer_position'] ?? ''), 'ผู้กำหนดคุณลักษณะเฉพาะ/TOR') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<pagebreak page-selector="bk06" />

<div class="section-title" style="text-align:right;"><strong>แบบ บก.06</strong></div>
<div class="center" style="font-size:17pt; font-weight:bold; margin-bottom:3mm; line-height:1.15;">ตารางแสดงวงเงินงบประมาณที่ได้รับจัดสรรและรายละเอียดค่าใช้จ่าย<br>การจัดซื้อจัดจ้างที่มิใช่งานก่อสร้าง</div>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:1mm;">
    <tr>
        <td style="border:0.7pt solid #000; padding:3mm;">
@php
    $bkSections = [
        ['1.', 'ชื่อโครงการ', $blank((string) ($d['bk_project_name'] ?? ''))],
        ['2.', 'หน่วยงานเจ้าของโครงการ', $blank((string) ($d['bk_owner'] ?? ''))],
        ['3.', 'วงเงินงบประมาณที่ได้รับจัดสรร', ($d['allocated_budget_display'] ?? '0.-').' บาท ('.($d['allocated_budget_text'] ?? '-').')'],
        ['4.', 'วันที่กำหนดราคากลาง (ราคาอ้างอิง) ณ วันที่', $blank((string) ($d['price_date'] ?? ''), '-')],
    ];
@endphp
@foreach ($bkSections as $section)
    @php $bkInline = in_array($section[0], ['1.', '2.', '3.', '4.'], true); @endphp
    <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="eight-point-num" valign="top" style="width:5mm;"><strong>{{ $section[0] }}</strong></td>
            <td class="eight-point-label" valign="top"><strong>{!! $tb($section[1]) !!}</strong>@if ($bkInline) <span style="font-weight:normal;">{!! $cellTb((string) $section[2]) !!}</span>@endif</td>
        </tr>
    </table>
    @if (! $bkInline)
        <p class="eight-point-body" style="text-indent:0; padding-left:5mm;">{!! $cellTb((string) $section[2]) !!}</p>
    @endif
@endforeach
<table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td valign="top" style="width:5mm; padding:0; border:0;"></td>
        <td valign="top" style="padding:0; border:0; font-weight:normal; font-size:16pt;">เป็นเงิน {{ $d['median_price_display'] ?? '0.-' }} บาท ({{ $d['median_price_text'] ?? '-' }})</td>
    </tr>
</table>
@foreach ($priceItems as $idx => $item)
    <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top" style="width:5mm; padding:0; border:0;"></td>
            <td valign="top" style="width:8mm; padding:0; border:0; font-weight:normal; font-size:16pt; white-space:nowrap;">4.{{ $idx + 1 }}</td>
            <td valign="top" style="padding:0; border:0; font-weight:normal; font-size:16pt; text-align:justify;">{!! $cellTb($blank((string) ($item['name'] ?? ''), '-')) !!}</td>
        </tr>
    </table>
@endforeach
<table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="eight-point-num" valign="top" style="width:5mm;"><strong>5.</strong></td>
        <td class="eight-point-label" valign="top"><strong>{!! $tb('แหล่งที่มาของราคากลาง (ราคาอ้างอิง)') !!}</strong></td>
    </tr>
</table>
@foreach ($priceMarks as $code => $label)
    <table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top" style="width:5mm; padding:0; border:0;"></td>
            <td valign="top" style="width:10mm; padding:0; border:0; font-weight:normal; font-size:16pt; white-space:nowrap;">(@if ($source === $code)<span style="font-family:dejavusans; font-weight:bold; font-size:12pt;">&#10003;</span>@else&nbsp;@endif)</td>
            <td valign="top" style="padding:0; border:0; font-weight:normal; font-size:16pt; text-align:justify;">{!! $tb($label) !!}</td>
        </tr>
    </table>
@endforeach
<table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="eight-point-num" valign="top" style="width:5mm;"><strong>6.</strong></td>
        <td class="eight-point-label" valign="top"><strong>{!! $tb('รายชื่อเจ้าหน้าที่ผู้กำหนดราคากลาง (ราคาอ้างอิง) ทุกคน') !!}</strong></td>
    </tr>
</table>
@php
    $priceOfficerName = trim((string) ($d['price_officer'] ?? ''));
    if (preg_match('/^(.*?)\s+-\s+/u', $priceOfficerName, $priceOfficerMatch) === 1) {
        $priceOfficerName = trim($priceOfficerMatch[1]);
    }
@endphp
<table class="eight-point-title" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td valign="top" style="width:5mm; padding:0; border:0;"></td>
        <td valign="top" style="padding:0; border:0; font-weight:normal; font-size:16pt;">{!! $cellTb($blank($priceOfficerName)) !!}</td>
    </tr>
</table>
        </td>
    </tr>
</table>

<pagebreak page-selector="memo2" resetpagenum="1" />

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
        <td style="width:58%;"><strong>ที่</strong> {{ $blank((string) ($d['consider_number'] ?? $d['document_number'] ?? ''), '-') }}</td>
        <td><strong>วันที่</strong> {{ $blank((string) ($d['consider_date'] ?? $d['document_date'] ?? ''), '-') }}</td>
    </tr>
    <tr><td colspan="2"><strong>เรื่อง</strong> รายงานผลการพิจารณาและขออนุมัติสั่งซื้อสั่งจ้าง</td></tr>
    <tr><td colspan="2"><strong>เรียน</strong> {{ $to }}</td></tr>
</table>
<p class="p">{!! $considerOpening !!}</p>
<table class="items-table">
    <thead>
        <tr>
            <th>รายการพิจารณา</th>
            <th>ผู้ชนะการเสนอราคา</th>
            <th style="width:28mm;">{!! $cellTb('ราคาที่เสนอ') !!}</th>
            <th style="width:32mm;">{!! $cellTb('ราคาที่ตกลงซื้อหรือจ้าง') !!}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($considerRows as $row)
            <tr>
                <td>{!! $cellTb($blank((string) ($row['item'] ?? ''), '-')) !!}</td>
                <td>{!! $cellTb($blank((string) ($row['winner'] ?? ''), '-')) !!}</td>
                <td class="right">{!! $cellTb((string) ($row['proposed_display'] ?? '0.-')) !!}</td>
                <td class="right">{!! $cellTb((string) ($row['agreed_display'] ?? '0.-')) !!}</td>
            </tr>
        @empty
            <tr>
                <td>{!! $cellTb($procurement) !!}</td>
                <td>{!! $cellTb($blank((string) ($d['winner_name'] ?? ''), '-')) !!}</td>
                <td class="right">{{ $amountDisplay }}</td>
                <td class="right">{{ $amountDisplay }}</td>
            </tr>
        @endforelse
        <tr>
            <td colspan="2" class="center"><strong>รวม (ตัวอักษร)</strong> <span style="font-weight:normal;">{!! $cellTb((string) ($d['agreed_total_text'] ?? $amountText)) !!}</span></td>
            <td class="right"><strong>{{ $d['proposed_total_display'] ?? $amountDisplay }}</strong></td>
            <td class="right"><strong>{{ $d['agreed_total_display'] ?? $amountDisplay }}</strong></td>
        </tr>
    </tbody>
</table>
<p class="p">{!! $considerClosing !!}</p>
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
                <tr><td align="center">({{ $blank((string) ($d['requester_name'] ?? '')) }})</td></tr>
                <tr><td align="center">{{ $blank((string) ($d['requester_position'] ?? '')) }}</td></tr>
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
<table class="sign">
    <tr>
        <td class="sign-text"><strong>ความเห็นของหัวหน้าเจ้าหน้าที่</strong></td>
    </tr>
    <tr>
        <td style="padding-left:25mm;">
            <table cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:auto;">
                <tr>
                    <td class="sign-text">เห็นควรอนุมัติให้สั่งซื้อสั่งจ้าง</td>
                </tr>
                <tr>
                    <td align="center">
                        <table class="sign-caption" cellpadding="0" cellspacing="0" align="center">
                            <tr>
                                <td align="center" class="sign-img-cell">
                                    @if ($showFinanceSignature)
                                        <img class="sign-img" src="{!! $financeSignature !!}" alt="">
                                    @else
                                        <div style="height:12mm;"></div>
                                    @endif
                                </td>
                            </tr>
                            <tr><td align="center">({{ $blank((string) ($d['officer_name'] ?? '')) }})</td></tr>
                            <tr><td align="center">{{ $blank((string) ($d['officer_position'] ?? ''), 'หัวหน้าเจ้าหน้าที่') }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<div class="approval-block">
    <table class="decision-list" cellpadding="0" cellspacing="0" width="100%" style="margin-left:12.5mm;">
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
                    <tr><td align="center" class="sign-text">({{ $blank($approverName) }})</td></tr>
                    @foreach ($approverPositionLines as $positionLine)
                        <tr><td align="center" class="sign-text">{{ $blank($positionLine) }}</td></tr>
                    @endforeach
                    @if ($approverPositionLines === [])
                        <tr><td align="center" class="sign-text">{{ $approverPositionText }}</td></tr>
                    @endif
                </table>
            </td>
            <td></td>
        </tr>
    </table>
</div>

<pagebreak page-selector="doc" />

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
                <tr><td align="center" class="sign-text">({{ $blank($approverName) }})</td></tr>
                @foreach ($approverPositionLines as $positionLine)
                    <tr><td align="center" class="sign-text">{{ $blank($positionLine) }}</td></tr>
                @endforeach
                @if ($approverPositionLines === [])
                    <tr><td align="center" class="sign-text">{{ $approverPositionText }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
