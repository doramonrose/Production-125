<!doctype html>
<html lang="th">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>บันทึกข้อความ</title>
    <style>
        @page {
            margin: 18mm 20mm 14mm 20mm;
            margin-footer: 2mm;
            footer: html_esignature;
        }

        * {
            box-sizing: border-box;
        }

        body, table, td, th, div, p, span {
            font-family: thsarabunnew, sans-serif;
        }

        body {
            color: #000;
            font-size: 16pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        strong, b, .title {
            font-family: thsarabunnew, sans-serif;
            font-weight: bold;
        }

        .page {
            width: 100%;
        }

        .topbar {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 0;
        }

        .topbar td {
            padding: 0;
            height: 19mm;
        }

        .topbar-left,
        .topbar-right {
            width: 25mm;
        }

        .topbar-left {
            text-align: left;
            vertical-align: top;
        }

        .krut-img {
            width: auto;
            height: 1.5cm;
            max-height: 1.5cm;
            max-width: 25mm;
            display: block;
            vertical-align: bottom;
        }

        .topbar-center {
            text-align: center;
            vertical-align: bottom !important;
            height: 15mm;
            padding-top: 0;
            padding-bottom: 0;
            line-height: 0;
            position: relative;
        }

        .title {
            font-weight: bold;
            font-size: 20pt;
            margin: 0;
            padding: 0;
            line-height: 1;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16pt;
            margin-top: -2mm;
        }

        .meta-table td {
            padding: 0.35mm 0;
            vertical-align: bottom;
        }

        .meta-table tr:first-child td {
            padding-top: 0;
        }


        .rule {
            margin: 4mm 0 4mm;
            border-top: 0.6pt solid #000;
        }

        .body {
            font-size: 16pt;
        }

        .p-wrap {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 3mm;
            table-layout: fixed;
        }

        .p-wrap td {
            padding: 0;
            vertical-align: top;
        }

        .p {
            margin: 0;
            text-align: justify;
            line-height: 1.45;
        }

        .p-project {
            margin: 0 0 1.8mm;
            text-align: justify;
            line-height: 1.25;
            text-indent: 20mm;
            font-size: 16pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .p-project-flush {
            margin: 0 0 0.6mm;
            text-align: left;
            line-height: 1.25;
            text-indent: 0;
            font-size: 16pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .project-label-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 0.4mm;
            table-layout: fixed;
        }

        .project-label-table td {
            padding: 0.1mm 0;
            vertical-align: top;
            font-size: 16pt;
            line-height: 1.25;
            border: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .project-label-table .indent-col {
            width: 20mm;
            padding: 0;
            white-space: nowrap;
            line-height: 1.25;
            font-size: 16pt;
        }

        .project-label-table .label-col {
            width: 55mm;
            padding: 0 1mm 0 0;
            white-space: nowrap;
            font-weight: bold;
            vertical-align: top;
        }

        .project-label-table .value-col {
            width: auto;
            text-align: left;
            padding: 0;
            vertical-align: top;
        }

        .project-expense-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 0.3mm;
            table-layout: fixed;
        }

        .project-expense-table td {
            padding: 0.1mm 0;
            vertical-align: top;
            font-size: 16pt;
            line-height: 1.25;
            border: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .project-expense-table .indent-col {
            width: 26mm;
            padding: 0;
            white-space: nowrap;
            font-size: 16pt;
            line-height: 1.25;
        }

        .project-expense-table .name-col {
            width: auto;
            padding-left: 0;
            text-align: left;
        }

        .project-expense-table .amt-col {
            width: 36mm;
            text-align: right;
            white-space: nowrap;
            padding-left: 2mm;
        }

        .page.project-memo .items-section {
            margin: 2.5mm 0 2.5mm;
            font-size: 16pt;
            line-height: 1.25;
        }

        .page.project-memo .items-table {
            font-size: 16pt;
        }

        .page.project-memo .items-table th,
        .page.project-memo .items-table td {
            padding: 1.1mm 1.5mm;
            border: 0.6pt solid #000;
            line-height: 1.25;
            word-wrap: break-word;
        }

        .page.project-memo .p-wrap {
            margin: 0 0 0.2mm;
            width: 100%;
        }

        .page.project-memo .p-wrap td {
            font-size: 16pt;
            line-height: 1.25;
        }

        .page.project-memo .body {
            font-size: 16pt;
            line-height: 1.25;
        }

        .muted {
            color: #000;
        }

        .items-section {
            margin: 2mm 0 2mm;
            font-size: 14pt;
            line-height: 1.2;
        }

        .items-heading {
            font-weight: bold;
            font-size: 13pt;
            margin: 0 0 1mm;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 14pt;
        }

        .items-table th,
        .items-table td {
            border: 0.5pt solid #000;
            padding: 0.5mm 1.2mm;
            vertical-align: top;
            word-wrap: break-word;
            background: #fff;
        }

        .items-table thead th {
            text-align: center;
            font-weight: bold;
            padding-top: 0.6mm;
            padding-bottom: 0.6mm;
        }

        .items-table .col-no {
            width: 12mm;
            text-align: center;
        }

        .items-table .col-amt {
            text-align: right;
            white-space: nowrap;
        }

        .project-budget-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 14pt;
            margin: 2mm 0 3mm;
        }

        .project-budget-table th,
        .project-budget-table td {
            border: 0.5pt solid #000;
            padding: 0.6mm 1.2mm;
            vertical-align: middle;
            background: #fff;
        }

        .project-budget-table th {
            text-align: center;
            font-weight: bold;
            white-space: normal;
        }

        .project-budget-table td {
            text-align: right;
            white-space: nowrap;
        }

        .items-table .category-row td {
            font-weight: bold;
        }

        .items-table tfoot td {
            font-weight: bold;
            padding-top: 0.55mm;
            padding-bottom: 0.55mm;
            vertical-align: middle;
        }

        .items-table tfoot .items-total-text {
            font-weight: bold;
            font-size: 0.92em;
            white-space: normal;
            line-height: 1.2;
        }

        .items-empty {
            text-align: center;
            padding: 0.55mm 1.2mm !important;
        }

        .signatures-wrap {
            margin-top: 2mm;
            font-size: 14pt;
            line-height: 1.35;
        }

        .signatures-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .signatures-grid td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0.4mm 4mm;
        }

        .signatures-grid tr.sign-img-row td {
            padding-bottom: 0;
            vertical-align: bottom;
        }

        .signatures-grid tr.sign-meta-row td {
            vertical-align: top;
            padding-top: 0;
            padding-bottom: 0;
        }

        .signatures-grid tr.sign-name-row td {
            padding-top: 0;
        }

        .sign-meta-stack {
            margin: 0;
            padding: 0;
            line-height: 1.05;
            text-align: center;
        }

        .sign-block {
            text-align: center;
        }

        .sign-img-wrap {
            height: 14mm;
            min-height: 14mm;
            margin: 0;
            overflow: hidden;
            text-align: center;
            line-height: 14mm;
        }

        .sign-img {
            max-height: 14mm;
            max-width: 48mm;
            height: auto;
            width: auto;
            vertical-align: bottom;
        }

        .sign-dots {
            font-size: 11pt;
            letter-spacing: 0.02em;
            line-height: 14mm;
            color: #000;
            vertical-align: bottom;
        }

        .sign-status {
            font-size: 10pt;
            min-height: 0;
            line-height: 1.05;
            margin: 0;
            color: #000;
        }

        .sign-status.is-empty {
            display: none;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        .sign-name,
        .sign-position,
        .sign-role {
            display: block;
            line-height: 1.05;
            margin: 0;
            padding: 0;
        }

        .sign-hanging-wrap {
            margin-top: 1.2mm;
            width: 100%;
            text-align: right;
        }

        .sign-hanging-table {
            width: auto;
            border-collapse: collapse;
            margin-left: auto;
            margin-right: 0;
        }

        .sign-hanging-table td {
            width: auto;
            vertical-align: bottom;
            text-align: right;
            padding: 0;
            font-size: 9pt;
            line-height: 1;
        }

        .sign-hanging-table td.sign-hanging-gap {
            width: 0.35em;
            padding: 0;
            font-size: 9pt;
            line-height: 1;
        }

        .sign-hanging-img-wrap {
            height: 7mm;
            min-height: 7mm;
            line-height: 7mm;
            overflow: hidden;
            margin: 0;
            display: inline-block;
            text-align: right;
        }

        .sign-hanging-img {
            max-height: 7mm;
            max-width: 24mm;
            height: auto;
            width: auto;
            vertical-align: bottom;
        }

        .sign-hanging-dots {
            font-size: 7pt;
            letter-spacing: 0;
            line-height: 7mm;
            color: #000;
        }

        .signatures-extra-wrap {
            margin-top: 2mm;
            font-size: 14pt;
            line-height: 1.35;
        }

        .signatures-extra-heading {
            font-weight: bold;
            font-size: 13pt;
            margin: 0 0 2mm;
        }
    </style>
</head>

<body>
    @php
        $isProjectMemo = (bool) ($isProjectMemo ?? false);
        $memoDigits = static function (string $text) use ($isProjectMemo): string {
            return $isProjectMemo
                ? \App\Support\ThaiDigits::toArabic($text)
                : \App\Support\ThaiDigits::inString($text);
        };
        $memoFormatAmount = static function (float $amount) use ($isProjectMemo): string {
            if ($isProjectMemo) {
                if (! is_finite($amount)) {
                    return '-';
                }

                return number_format($amount, 2, '.', ',');
            }

            return \App\Support\ThaiDigits::formatAmount($amount);
        };
        $displayCode = trim((string) ($detail['document_number_display'] ?? ($detail['document_number'] ?? '')));
        $departmentDisplay = trim((string) ($detail['department_display'] ?? ''));
        $subject = trim((string) ($detail['subject'] ?? ''));
        $to = trim((string) ($detail['to'] ?? ''));
        $paragraph1 = trim((string) ($detail['paragraph1'] ?? ''));
        $paragraph2 = trim((string) ($detail['paragraph2'] ?? ''));
        $paragraph3 = trim((string) ($detail['paragraph3'] ?? ''));
        $paragraph4 = trim((string) ($detail['paragraph4'] ?? ''));
        $splitMemoParagraphBlocks = static function (string $text): array {
            $text = trim($text);
            if ($text === '') {
                return [];
            }
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $text = preg_replace('/[ \t\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/(?<!\n)\n(?!\n)/u', ' ', $text) ?? $text;
            $parts = preg_split('/\n{2,}/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            if (! is_array($parts) || $parts === []) {
                return [$text];
            }
            return array_values(array_filter(array_map('trim', $parts), static fn ($part) => $part !== ''));
        };
        $formatMemoParagraphBlocks = static function (string $text) use ($splitMemoParagraphBlocks, $memoDigits): array {
            return array_map(
                static function (string $block) use ($memoDigits): string {
                    $escaped = e($memoDigits($block));
                    return \App\Support\ThaiLineBreak::insertBreakOpportunities($escaped);
                },
                $splitMemoParagraphBlocks($text)
            );
        };
        $displayCodeThai = $memoDigits($displayCode);
        $thaiDateDisplay = $memoDigits((string) ($thaiDate ?? ''));
        $applicantName = trim((string) ($detail['applicant_name'] ?? ''));
        $applicantPosition = trim((string) ($detail['applicant_position'] ?? ''));
        $krutPath = public_path('images/krut.jpg');
        $krutUrl =
            file_exists($krutPath) && is_readable($krutPath)
                ? 'file://' . str_replace('\\', '/', realpath($krutPath))
                : null;
        $krutHeightMm = 15.0;
        $krutWidthMm = 15.0;
        if ($krutPath && file_exists($krutPath)) {
            $imgSize = @getimagesize($krutPath);
            if (is_array($imgSize) && (int) ($imgSize[0] ?? 0) > 0 && (int) ($imgSize[1] ?? 0) > 0) {
                $krutWidthMm = round($krutHeightMm * ((float) $imgSize[0] / (float) $imgSize[1]), 2);
            }
        }

        // Group items like withdraw/general/partials/detail-modal.blade.php (category rows + sub-lines).
        $rawWithdrawItems = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        $withdrawItemGroupBuckets = [];
        $withdrawItemGroupOrder = [];
        foreach ($rawWithdrawItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $category = trim((string) ($item['category'] ?? '-'));
            if ($category === '') {
                $category = '-';
            }
            $categoryCode = trim((string) ($item['category_code'] ?? ''));
            $groupKey = $categoryCode . '::' . $category;
            if (!array_key_exists($groupKey, $withdrawItemGroupBuckets)) {
                $withdrawItemGroupBuckets[$groupKey] = [
                    'category' => $category,
                    'category_code' => $categoryCode,
                    'lines' => [],
                    'total' => 0.0,
                ];
                $withdrawItemGroupOrder[] = $groupKey;
            }
            $safeAmount = (float) ($item['amount'] ?? 0);
            if (!is_finite($safeAmount)) {
                $safeAmount = 0.0;
            }
            $lineName = trim((string) ($item['name'] ?? '-'));
            if ($lineName === '') {
                $lineName = '-';
            }
            $withdrawItemGroupBuckets[$groupKey]['lines'][] = [
                'name' => $lineName,
                'amount' => $safeAmount,
            ];
            $withdrawItemGroupBuckets[$groupKey]['total'] += $safeAmount;
        }
        $withdrawItemGroups = [];
        foreach ($withdrawItemGroupOrder as $groupKey) {
            $withdrawItemGroups[] = $withdrawItemGroupBuckets[$groupKey];
        }
        $formatBahtCell = static function (float $amount) use ($memoFormatAmount): string {
            return $memoFormatAmount($amount);
        };

        $rawStatusMemo = strtolower(trim((string) ($detail['raw_status'] ?? '')));
        $isReviewerApprovedMemo = in_array(
            $rawStatusMemo,
            ['pending_additional_review', 'pending_finance_review', 'pending_approval', 'approved'],
            true,
        );

        $applicantSigUri = trim(
            (string) ($detail['applicant_signature_base64'] ?? ($detail['applicant_signature_url'] ?? '')),
        );
        $showApplicantSigImg = $applicantSigUri !== '' && !str_starts_with($applicantSigUri, 'data:image/svg+xml');

        $reviewerSigUri = trim(
            (string) ($detail['reviewer_signature_base64'] ?? ($detail['reviewer_signature_url'] ?? '')),
        );
        $showReviewerSigImg =
            $isReviewerApprovedMemo &&
            $reviewerSigUri !== '' &&
            !str_starts_with($reviewerSigUri, 'data:image/svg+xml');

        $reviewerNameMemo = trim((string) ($detail['reviewer_name'] ?? ''));
        $reviewerPositionMemo = trim((string) ($detail['reviewer_position'] ?? ''));
        $applicantStatusMemo = trim((string) ($detail['applicant_signature_status'] ?? ''));
        $reviewerStatusMemo = trim((string) ($detail['reviewer_signature_status'] ?? ''));
    @endphp

    <htmlpagefooter name="esignature">
        <div
            style="font-family: thsarabunnew, sans-serif; font-size: 12pt; line-height: 1.2; text-align: center; color: #000; margin: 0; padding: 0;">
            {{ $memoDigits('เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติ ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544') }}
            <br>{{ $memoDigits('และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564') }}
        </div>
    </htmlpagefooter>

    <div class="page{{ !empty($isProjectMemo) ? ' project-memo' : '' }}">
        <table class="topbar">
            <tr>
                <td class="topbar-left">
                    @if ($krutUrl)
                        <img class="krut-img" src="{{ $krutUrl }}" alt="ครุฑ"
                            style="height:{{ $krutHeightMm }}mm; width:{{ $krutWidthMm }}mm;">
                    @endif
                </td>
                <td class="topbar-center" valign="bottom">
                    <div class="title" style="font-weight: bold;"><strong>บันทึกข้อความ</strong></div>
                </td>
                <td class="topbar-right"></td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td colspan="2"><strong>ส่วนงาน</strong> {{ $departmentDisplay !== '' ? $departmentDisplay : ' ' }}
                </td>
            </tr>
            <tr>
                <td><strong>ที่</strong> {{ $displayCodeThai !== '' ? $displayCodeThai : '-' }}</td>
                <td><strong>วันที่</strong> {{ $thaiDateDisplay !== '' ? $thaiDateDisplay : '-' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>เรื่อง</strong> {{ $subject !== '' ? $memoDigits($subject) : ' ' }}</td>
            </tr>
        </table>

        <div class="rule"></div>

        <div class="body">
            <table class="meta-table">
                <tr>
                    <td>เรียน {{ $to !== '' ? $memoDigits($to) : ' ' }}</td>
                </tr>
            </table>
            @include('withdraw.partials.memo-pdf-body-general')
            @if (false)
            @foreach ($formatMemoParagraphBlocks($paragraph1) as $paragraphBlock)
                <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td lang="th">
                            <div class="p" style="text-align: justify;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!! $paragraphBlock !!}
                            </div>
                        </td>
                    </tr>
                </table>
            @endforeach
            @foreach ($formatMemoParagraphBlocks($paragraph2) as $paragraphBlock)
                <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td lang="th">
                            <div class="p" style="text-align: justify;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!! $paragraphBlock !!}
                            </div>
                        </td>
                    </tr>
                </table>
            @endforeach

            <div class="items-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="col-no">ที่</th>
                            <th class="col-name">รายการ</th>
                            <th class="col-amt">จำนวนเงิน (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($withdrawItemGroups as $idx => $group)
                            <tr class="category-row">
                                <td class="col-no">{{ $idx + 1 }}</td>
                                <td class="col-name">{{ $group['category'] }}</td>
                                <td class="col-amt">{{ $formatBahtCell((float) ($group['total'] ?? 0)) }}</td>
                            </tr>
                            @foreach ($group['lines'] ?? [] as $line)
                                <tr>
                                    <td class="col-no"></td>
                                    <td class="col-name">- {{ $line['name'] }}</td>
                                    <td class="col-amt">{{ $formatBahtCell((float) ($line['amount'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td class="items-empty" colspan="3">- ไม่มีรายการ -</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="col-no" colspan="2" style="text-align:center;">รวม</td>
                            <td class="col-amt">{{ $formatBahtCell((float) ($itemsTotalAmount ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align:right;font-weight:normal;">
                                <strong>จำนวนรวม (ตัวอักษร):</strong>
                                {{ $itemsTotalText ?? ($totalText ?? '-') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @foreach ($formatMemoParagraphBlocks($paragraph3) as $paragraphBlock)
                <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td lang="th">
                            <div class="p" style="text-align: justify;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!! $paragraphBlock !!}
                            </div>
                        </td>
                    </tr>
                </table>
            @endforeach
            @endif

            <div class="signatures-wrap">
                @php
                    $isProjectMemoLayout = !empty($isProjectMemo);
                    $extraSignerCards = is_array($additionalSignerCards ?? null) ? array_values($additionalSignerCards) : [];
                    $headSignerCard = null;
                    if ($isProjectMemoLayout) {
                        foreach ($extraSignerCards as $idx => $card) {
                            if (! is_array($card)) {
                                continue;
                            }
                            if (trim((string) ($card['role'] ?? '')) === 'หัวหน้างาน') {
                                $headSignerCard = $card;
                                unset($extraSignerCards[$idx]);
                                $extraSignerCards = array_values($extraSignerCards);
                                break;
                            }
                        }
                        $hiddenProjectMemoRoles = ['ผู้ตรวจสอบ', 'หัวหน้างานการเงิน', 'เลขาคณะ', 'เลขานุการคณะ', 'ผู้อนุมัติ'];
                        $extraSignerCards = array_values(array_filter(
                            $extraSignerCards,
                            static function ($card) use ($hiddenProjectMemoRoles): bool {
                                if (! is_array($card)) {
                                    return false;
                                }

                                return ! in_array(trim((string) ($card['role'] ?? '')), $hiddenProjectMemoRoles, true);
                            }
                        ));
                    }

                    $wrapSignerName = static function (string $name): string {
                        $name = trim($name);
                        if ($name === '') {
                            $name = '-';
                        }
                        if (preg_match('/^\(.*\)$/u', $name) === 1) {
                            return $name;
                        }

                        return '('.$name.')';
                    };
                    $leftSigner = [
                        'show_sig' => $showApplicantSigImg,
                        'sig_uri' => $applicantSigUri,
                        'status' => $applicantStatusMemo,
                        'name' => $wrapSignerName($applicantName !== '' ? $memoDigits($applicantName) : '-'),
                        'position' => $applicantPosition !== '' ? $memoDigits($applicantPosition) : '-',
                        'role' => 'ผู้ขอเบิก',
                    ];
                    if ($isProjectMemoLayout && is_array($headSignerCard)) {
                        $rightSigner = [
                            'show_sig' => !empty($headSignerCard['show_sig']),
                            'sig_uri' => (string) ($headSignerCard['sig_uri'] ?? ''),
                            'status' => (string) ($headSignerCard['status_line'] ?? ''),
                            'name' => $wrapSignerName($memoDigits((string) ($headSignerCard['name'] ?? '-'))),
                            'position' => $memoDigits((string) ($headSignerCard['position'] ?? '-')),
                            'role' => 'หัวหน้างาน',
                        ];
                    } elseif ($isProjectMemoLayout) {
                        $rightSigner = null;
                    } else {
                        $rightSigner = [
                            'show_sig' => $showReviewerSigImg,
                            'sig_uri' => $reviewerSigUri,
                            'status' => $reviewerStatusMemo,
                            'name' => $wrapSignerName($reviewerNameMemo !== '' ? $memoDigits($reviewerNameMemo) : '-'),
                            'position' => $reviewerPositionMemo !== '' ? $memoDigits($reviewerPositionMemo) : '-',
                            'role' => 'ผู้ตรวจสอบ',
                        ];
                    }
                    $primarySignerPair = $rightSigner === null
                        ? [null, $leftSigner]
                        : [$leftSigner, $rightSigner];
                @endphp
                @php
                    $primaryHasStatus = collect($primarySignerPair)->contains(static function ($signer) {
                        return is_array($signer) && trim((string) ($signer['status'] ?? '')) !== '';
                    });
                @endphp
                <table class="signatures-grid" autosize="1" style="page-break-inside: avoid;">
                    <tr>
                        @foreach ($primarySignerPair as $signer)
                            <td>
                                @if ($signer !== null)
                                    <div class="sign-block">
                                        <div class="sign-img-wrap">
                                            @if (!empty($signer['show_sig']))
                                                <img class="sign-img" src="{{ $signer['sig_uri'] }}" alt="">
                                            @else
                                                <div class="sign-dots">(..........................................................)</div>
                                            @endif
                                        </div>
                                        @if ($primaryHasStatus)
                                            <div class="sign-status {{ trim((string) ($signer['status'] ?? '')) === '' ? 'is-empty' : '' }}">
                                                {!! trim((string) ($signer['status'] ?? '')) !== '' ? e($signer['status']) : '&nbsp;' !!}
                                            </div>
                                        @endif
                                        <div class="sign-meta-stack">
                                            <div class="sign-name">{{ $signer['name'] }}</div>
                                            <div class="sign-position">{{ $signer['position'] }}</div>
                                            <div class="sign-role">{{ $signer['role'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>

            @if (!empty($extraSignerCards))
                <div class="signatures-extra-wrap">
                    @foreach (array_chunk($extraSignerCards, 2) as $pair)
                        @php
                            $pairCards = array_values($pair);
                            while (count($pairCards) < 2) {
                                $pairCards[] = null;
                            }
                        @endphp
                        @php
                            $pairHasStatus = collect($pairCards)->contains(static function ($card) {
                                return is_array($card) && trim((string) ($card['status_line'] ?? '')) !== '';
                            });
                        @endphp
                        <table class="signatures-grid signatures-grid-extra" autosize="1" style="margin-top:1.5mm; page-break-inside: avoid;">
                            <tr>
                                @foreach ($pairCards as $card)
                                    <td>
                                        @if ($card !== null)
                                            <div class="sign-block">
                                                <div class="sign-img-wrap">
                                                    @if (!empty($card['show_sig']))
                                                        <img class="sign-img" src="{{ $card['sig_uri'] }}" alt="">
                                                    @else
                                                        <div class="sign-dots">(..........................................................)</div>
                                                    @endif
                                                </div>
                                                @if ($pairHasStatus)
                                                    <div class="sign-status {{ trim((string) ($card['status_line'] ?? '')) === '' ? 'is-empty' : '' }}">
                                                        {!! trim((string) ($card['status_line'] ?? '')) !== '' ? e($card['status_line']) : '&nbsp;' !!}
                                                    </div>
                                                @endif
                                                <div class="sign-meta-stack">
                                                    <div class="sign-name">{{ $wrapSignerName($memoDigits((string) ($card['name'] ?? '-'))) }}</div>
                                                    <div class="sign-position">{{ $memoDigits((string) ($card['position'] ?? '-')) }}</div>
                                                    <div class="sign-role">{{ $memoDigits((string) ($card['role'] ?? '-')) }}</div>
                                                </div>
                                                @php
                                                    $hangingSigners = is_array($card['hanging_signers'] ?? null)
                                                        ? array_values(array_filter($card['hanging_signers'], static fn ($item) => is_array($item)))
                                                        : [];
                                                @endphp
                                                @if ($hangingSigners !== [])
                                                    <div class="sign-hanging-wrap">
                                                        <table class="sign-hanging-table">
                                                            <tr>
                                                                @foreach ($hangingSigners as $hangIndex => $hang)
                                                                    @if ($hangIndex > 0)
                                                                        <td class="sign-hanging-gap">&nbsp;</td>
                                                                    @endif
                                                                    <td>
                                                                        <div class="sign-hanging-img-wrap">
                                                                            @if (!empty($hang['show_sig']))
                                                                                <img class="sign-hanging-img" src="{{ $hang['sig_uri'] }}" alt="">
                                                                            @else
                                                                                <div class="sign-hanging-dots">(...)</div>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                    @endforeach
                </div>
            @endif

            @if ($paragraph1 === '' && $paragraph2 === '' && $paragraph3 === '' && $paragraph4 === '')
                <div class="p"> </div>
            @endif
        </div>

    </div>
</body>

</html>
