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

        body,
        table,
        td,
        th,
        div,
        p,
        span {
            font-family: thsarabunnew, sans-serif;
        }

        body {
            color: #000;
            font-size: 16pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        strong,
        b,
        .title {
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

        .p-travel {
            margin: 0 0 1.8mm;
            text-align: justify;
            line-height: 1.25;
            text-indent: 20mm;
            font-size: 16pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .items-section {
            margin: 2mm 0 2mm;
            line-height: 1.2;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            border: 0;
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
            width: 11%;
            text-align: center;
        }

        .items-table .col-amt {
            width: 22%;
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
        }

        .travel-matrix {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 14pt;
        }

        .travel-matrix th,
        .travel-matrix td {
            border: 0.6pt solid #000;
            padding: 1mm 1.2mm;
            text-align: center;
            vertical-align: middle;
            background: #fff;
        }

        .travel-matrix th,
        .travel-matrix th.name-cell {
            font-weight: bold;
            text-align: center;
        }

        .travel-matrix td.name-cell {
            width: 28%;
            text-align: left;
            font-weight: normal;
        }

        .travel-matrix .total-row td {
            font-weight: bold;
        }

        .items-empty {
            text-align: center;
            padding: 0.55mm 1.2mm !important;
        }

        .signatures-wrap {
            margin-top: 8mm;
            font-size: 14pt;
            line-height: 1.35;
        }

        .signatures-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signatures-grid td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 2mm 4mm;
        }

        .sign-block {
            text-align: center;
        }

        .sign-img-wrap {
            min-height: 14mm;
            margin-bottom: 1mm;
        }

        .sign-img {
            max-height: 14mm;
            max-width: 48mm;
            height: auto;
            width: auto;
        }

        .sign-dots {
            font-size: 11pt;
            letter-spacing: 0.02em;
            line-height: 1.2;
            color: #000;
        }

        .sign-status {
            font-size: 10pt;
            margin-top: 0.5mm;
            color: #000;
        }

        .sign-hanging-wrap {
            margin-top: 1.2mm;
            width: 100%;
            text-align: right;
            padding-left: 16mm;
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
            line-height: 1;
        }

        .sign-hanging-table td.sign-hanging-gap {
            width: 1.2mm;
            padding: 0;
        }

        .sign-hanging-img-wrap {
            height: 7mm;
            line-height: 7mm;
            overflow: hidden;
            text-align: right;
        }

        .sign-hanging-img {
            max-height: 7mm;
            max-width: 24mm;
            height: auto;
            width: auto;
            vertical-align: bottom;
        }

        .signatures-extra-wrap {
            margin-top: 5mm;
            font-size: 14pt;
            line-height: 1.35;
        }

        .memo-budget-check-mark {
            font-family: dejavusans, sans-serif;
            font-size: 16pt;
            line-height: 1;
        }
    </style>
</head>

<body>
    @php
        $memoCopies = is_array($memoCopies ?? null) && ($memoCopies ?? []) !== []
            ? $memoCopies
            : [[
                'document_number_display' => trim((string) ($detail['document_number_display'] ?? ($detail['document_number'] ?? ''))),
                'copy_index' => 1,
                'copy_total' => 1,
            ]];
        $departmentDisplay = trim((string) ($detail['department_display'] ?? ''));
        $departmentPhoneDisplay = \App\Support\ThaiDigits::inString(trim((string) ($detail['department_phone'] ?? '')));
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
        $formatMemoParagraphBlocks = static function (string $text) use ($splitMemoParagraphBlocks): array {
            return array_map(
                static function (string $block): string {
                    $escaped = e(\App\Support\ThaiDigits::inString($block));

                    return \App\Support\ThaiLineBreak::insertBreakOpportunities($escaped);
                },
                $splitMemoParagraphBlocks($text)
            );
        };
        // วันที่บันทึกข้อความ = วันที่คณบดีอนุมัติ (ส่งมาจาก controller ผ่าน $thaiDate)
        $thaiDateDisplay = \App\Support\ThaiDigits::inString((string) ($thaiDate ?? ''));
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

        $rawWithdrawItems = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        $withdrawItemGroupBuckets = [];
        $withdrawItemGroupOrder = [];
        foreach ($rawWithdrawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $category = trim((string) ($item['category'] ?? '-'));
            if ($category === '') {
                $category = '-';
            }
            $categoryCode = trim((string) ($item['category_code'] ?? ''));
            $groupKey = $categoryCode . '::' . $category;
            if (! array_key_exists($groupKey, $withdrawItemGroupBuckets)) {
                $withdrawItemGroupBuckets[$groupKey] = [
                    'category' => $category,
                    'category_code' => $categoryCode,
                    'lines' => [],
                    'total' => 0.0,
                ];
                $withdrawItemGroupOrder[] = $groupKey;
            }
            $safeAmount = (float) ($item['amount'] ?? 0);
            if (! is_finite($safeAmount)) {
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
        $withdrawItemLines = [];
        foreach ($rawWithdrawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $safeAmount = (float) ($item['amount'] ?? 0);
            if (! is_finite($safeAmount)) {
                $safeAmount = 0.0;
            }
            $lineName = trim((string) ($item['name'] ?? '-'));
            if ($lineName === '') {
                $lineName = '-';
            }
            $withdrawItemLines[] = [
                'name' => $lineName,
                'amount' => $safeAmount,
            ];
        }
        $formatBahtCell = static function (float $amount): string {
            return \App\Support\ThaiDigits::formatAmount($amount);
        };

        $deanSigUri = trim(
            (string) ($detail['dean_signature_base64'] ?? ($detail['dean_signature_url'] ?? '')),
        );
        $isDeanApprovedMemo = (bool) ($detail['dean_approved'] ?? false);
        $showDeanSigImg =
            $isDeanApprovedMemo &&
            $deanSigUri !== '' &&
            ! str_starts_with($deanSigUri, 'data:image/svg+xml');

        $deanNameMemo = trim((string) ($detail['dean_name'] ?? ''));
        $deanRoleMemo = trim((string) ($detail['dean_position'] ?? ''));
        if ($deanRoleMemo === '') {
            $deanRoleMemo = trim((string) ($detail['dean_step_name'] ?? ''));
        }
        if ($deanRoleMemo === '') {
            $deanRoleMemo = 'คณบดีคณะเภสัชศาสตร์';
        }
        $deanStatusMemo = trim((string) ($detail['dean_signature_status'] ?? ''));
    @endphp

    <htmlpagefooter name="esignature">
        <div style="font-family: thsarabunnew, sans-serif; font-size: 12pt; line-height: 1.2; text-align: center; color: #000; margin: 0; padding: 0;">
            เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติ ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544
            <br>และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564
        </div>
    </htmlpagefooter>

    @foreach ($memoCopies as $memoCopy)
        @php
            $displayCode = trim((string) ($memoCopy['document_number_display'] ?? ''));
            $displayCodeThai = \App\Support\ThaiDigits::inString($displayCode);
            $memoCopyIndex = (int) ($memoCopy['copy_index'] ?? 1);
            $memoSubjectDisplay = $subject;
            if (($salaryRequestType ?? '') === 'pts' && $memoCopyIndex === 2) {
                $memoSubjectDisplay = 'ขอส่งหลักฐานการเบิกจ่ายเงินเพิ่มสำหรับตำแหน่งที่มีเหตุพิเศษของผู้ปฏิบัติงานด้านสาธารณสุข';
            }
        @endphp
        @if (!$loop->first)
            <pagebreak />
        @endif
        <div class="page">
            <table class="topbar">
                <tr>
                    <td class="topbar-left">
                        @if (!empty($krutUrl ?? null))
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
                    <td colspan="2"><strong>ส่วนงาน</strong> {{ $departmentDisplay !== '' ? $departmentDisplay : ' ' }} คณะเภสัชศาสตร์@if ($departmentPhoneDisplay !== '') โทร. {{ $departmentPhoneDisplay }}@endif</td>
                </tr>
                <tr>
                    <td><strong>ที่</strong> {{ $displayCodeThai !== '' ? $displayCodeThai : '-' }}</td>
                    <td><strong>วันที่</strong> {{ $thaiDateDisplay !== '' ? $thaiDateDisplay : '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>เรื่อง</strong> {{ $memoSubjectDisplay !== '' ? \App\Support\ThaiDigits::inString($memoSubjectDisplay) : ' ' }}</td>
                </tr>
            </table>

            <div class="rule"></div>

            <div class="body">
                @include($memoBodyView ?? 'withdraw.salary.pdf.memo-body-salary-civil-servant')
            </div>
        </div>
    @endforeach
</body>

</html>
