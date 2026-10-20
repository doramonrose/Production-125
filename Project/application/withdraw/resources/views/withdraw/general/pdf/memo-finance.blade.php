<!doctype html>
<html lang="th">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>บันทึกข้อความ (การเงิน)</title>
    <style>
        @page { margin: 18mm 20mm 14mm 20mm; margin-footer: 2mm; footer: html_esignature; }
        body, table, td, th, div, p, span { font-family: thsarabunnew, sans-serif; }
        body { color: #000; font-size: 16pt; line-height: 1.25; margin: 0; padding: 0; }
        strong, b, .title { font-family: thsarabunnew, sans-serif; font-weight: bold; }
        .topbar, .items-table, .budget-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .topbar { margin-bottom: 0; }
        .topbar td { padding: 0; height: 17mm; }
        .topbar-left, .topbar-right { width: 25mm; }
        .topbar-left { text-align: left; vertical-align: top; }
        .krut-img { width: auto; height: 1.5cm; max-height: 1.5cm; max-width: 25mm; display: block; vertical-align: bottom; }
        .topbar-center { text-align: center; vertical-align: bottom !important; height: 15mm; padding-top: 0; padding-bottom: 0; line-height: 1; }
        .title { font-weight: bold; font-size: 20pt; margin: 0; padding: 0; line-height: 1; display: inline-block; }
        .meta-table td { padding: 0.35mm 0; vertical-align: bottom; }
        .rule { margin: 4mm 0 4mm; border-top: 0.6pt solid #000; }
        .p-wrap { width: 100%; border-collapse: collapse; margin: 0 0 3mm; table-layout: fixed; }
        .p-wrap td { padding: 0; vertical-align: top; }
        .p { margin: 0 0 1.8mm; text-align: justify; line-height: 1.25; text-indent: 20mm; font-size: 16pt; word-wrap: break-word; overflow-wrap: break-word; }
        .items-table th, .items-table td, .budget-table th, .budget-table td { border: 0.5pt solid #000; padding: 0.8mm 1.2mm; vertical-align: top; background: #fff; }
        .items-table th, .budget-table th { text-align: center; font-weight: bold; }
        .items-table .col-no { width: 11%; text-align: center; }
        .items-table .col-amt { width: 28%; text-align: right; white-space: nowrap; }
        .items-table .items-total-text { white-space: normal; font-weight: bold; line-height: 1.2; }
        .items-table .category-row td { font-weight: bold; }
        .items-table tfoot td { font-weight: bold; }
        .text-right { text-align: right; }
        .mt-3 { margin-top: 3mm; }
        .mt-2 { margin-top: 2mm; }
        .after-items-paragraph { margin-top: 6mm; }
        .empty { text-align: center; }
        .signatures-wrap { margin-top: 2mm; font-size: 14pt; line-height: 1.35; }
        .signatures-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .signatures-grid td { width: 50%; vertical-align: top; text-align: center; padding: 0 4mm 2mm; }
        .sign-block { text-align: center; }
        .sign-img-wrap { min-height: 12mm; margin: 0 0 0.5mm; padding: 0; }
        .sign-img { max-height: 14mm; max-width: 48mm; height: auto; width: auto; }
        .sign-dots { font-size: 11pt; letter-spacing: 0.02em; line-height: 1.2; color: #000; }
        .sign-status { font-size: 10pt; margin-top: 0.5mm; color: #000; }
    </style>
</head>

<body>
    @php
        $useArabicDigits = (bool) ($useArabicDigits ?? false);
        $memoHeaderDigits = static function (string $text) use ($useArabicDigits): string {
            return $useArabicDigits
                ? \App\Support\ThaiDigits::toArabic($text)
                : \App\Support\ThaiDigits::inString($text);
        };
        $displayCode = trim((string) ($detail['document_number_display'] ?? $detail['document_number'] ?? ''));
        $subject = trim((string) ($subjectFinance ?? $detail['subject'] ?? ''));
        $to = trim((string) ($detail['to'] ?? ''));
        $paragraph1 = trim((string) ($paragraph1Finance ?? ''));
        $paragraph2 = trim((string) ($paragraph3Finance ?? ''));
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
        $displayCodeThai = $memoHeaderDigits($displayCode);
        $thaiDateDisplay = $memoHeaderDigits((string) ($thaiDate ?? ''));
        $formatBahtCell = static function (float $amount): string {
            return \App\Support\ThaiDigits::formatAmount($amount);
        };
        $krutPath = public_path('images/krut.jpg');
        $krutUrl = file_exists($krutPath) && is_readable($krutPath) ? 'file://' . str_replace('\\', '/', realpath($krutPath)) : null;
        $rawWithdrawItems = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        $withdrawItemGroupBuckets = [];
        $withdrawItemGroupOrder = [];
        foreach ($rawWithdrawItems as $item) {
            if (! is_array($item)) continue;
            $category = trim((string) ($item['category'] ?? '-'));
            if ($category === '') $category = '-';
            $categoryCode = trim((string) ($item['category_code'] ?? ''));
            $groupKey = $categoryCode . '::' . $category;
            if (! array_key_exists($groupKey, $withdrawItemGroupBuckets)) {
                $withdrawItemGroupBuckets[$groupKey] = ['category' => $category, 'lines' => [], 'total' => 0.0];
                $withdrawItemGroupOrder[] = $groupKey;
            }
            $safeAmount = (float) ($item['amount'] ?? 0);
            $lineName = trim((string) ($item['expense_chart_name'] ?? ''));
            if ($lineName === '') $lineName = trim((string) ($item['name'] ?? '-'));
            if ($lineName === '') $lineName = '-';
            $withdrawItemGroupBuckets[$groupKey]['lines'][] = ['name' => $lineName, 'amount' => $safeAmount];
            $withdrawItemGroupBuckets[$groupKey]['total'] += $safeAmount;
        }
        $withdrawItemGroups = [];
        foreach ($withdrawItemGroupOrder as $groupKey) $withdrawItemGroups[] = $withdrawItemGroupBuckets[$groupKey];
    @endphp

    <htmlpagefooter name="esignature">
        <div style="font-family: thsarabunnew, sans-serif; font-size: 12pt; line-height: 1.2; text-align: center; color: #000; margin: 0; padding: 0;">
            {{ $memoHeaderDigits('เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติ ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544') }}
            <br>{{ $memoHeaderDigits('และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564') }}
        </div>
    </htmlpagefooter>

    <table class="topbar">
        <tr>
            <td class="topbar-left">
                @if ($krutUrl)
                    <img class="krut-img" src="{{ $krutUrl }}" alt="ครุฑ" style="height:15mm; width:15mm;">
                @endif
            </td>
            <td class="topbar-center"><div class="title"><strong>บันทึกข้อความ</strong></div></td>
            <td class="topbar-right"></td>
        </tr>
    </table>

    <table class="meta-table">
        <tr><td colspan="2"><strong>ส่วนงาน</strong> {{ trim((string) ($departmentDisplay ?? '')) !== '' ? $departmentDisplay : ' ' }} คณะเภสัชศาสตร์</td></tr>
        <tr>
            <td><strong>ที่</strong> {{ $displayCodeThai !== '' ? $displayCodeThai : '-' }}</td>
            <td><strong>วันที่</strong> {{ $thaiDateDisplay !== '' ? $thaiDateDisplay : '-' }}</td>
        </tr>
        <tr><td colspan="2"><strong>เรื่อง</strong> {{ $subject !== '' ? $memoHeaderDigits($subject) : ' ' }}</td></tr>
    </table>

    <div class="rule"></div>

    <table class="meta-table">
        <tr><td>เรียน {{ $to !== '' ? \App\Support\ThaiDigits::inString($to) : ' ' }}</td></tr>
    </table>
    @foreach ($formatMemoParagraphBlocks($paragraph1) as $paragraphBlock)
        <p class="p" lang="th">{!! $paragraphBlock !!}</p>
    @endforeach

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">ที่</th>
                <th>รายการ</th>
                <th class="col-amt">จำนวนเงิน (บาท)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($withdrawItemGroups as $idx => $group)
                <tr class="category-row">
                    <td class="col-no">{{ \App\Support\ThaiDigits::inString((string) ($idx + 1)) }}</td>
                    <td>{{ $group['category'] }}</td>
                    <td class="col-amt">{{ $formatBahtCell((float) ($group['total'] ?? 0)) }}</td>
                </tr>
                @foreach ($group['lines'] ?? [] as $line)
                    <tr>
                        <td class="col-no"></td>
                        <td>- {{ $line['name'] }}</td>
                        <td class="col-amt">{{ $formatBahtCell((float) ($line['amount'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td class="empty" colspan="3">- ไม่มีรายการ -</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="empty">
                    รวม
                    <span class="items-total-text">({{ $useArabicDigits
                        ? \App\Support\ThaiDigits::toArabic((string) ($itemsTotalText ?? '-'))
                        : \App\Support\ThaiDigits::inString((string) ($itemsTotalText ?? '-')) }})</span>
                </td>
                <td class="col-amt">{{ $formatBahtCell((float) ($itemsTotalAmount ?? 0)) }}</td>
            </tr>
        </tfoot>
    </table>

    @foreach ($formatMemoParagraphBlocks($paragraph2) as $paragraphBlock)
        <p class="p" lang="th" style="margin-top: 1mm;">{!! $paragraphBlock !!}</p>
    @endforeach

    @if (! empty($memoSignerCards ?? []))
        <div class="signatures-wrap">
            <table class="signatures-grid">
                @foreach (array_chunk(($memoSignerCards ?? []), 2) as $pair)
                    <tr>
                        @foreach ($pair as $card)
                            <td>
                                <div class="sign-block">
                                    <div class="sign-img-wrap">
                                        @if (! empty($card['show_sig']) && ! empty($card['sig_uri']))
                                            <img class="sign-img" src="{!! $card['sig_uri'] !!}" alt="">
                                        @else
                                            <div class="sign-dots">(..........................................................)</div>
                                        @endif
                                    </div>
                                    @if (! empty($card['status_line']))
                                        <div class="sign-status">{{ $card['status_line'] }}</div>
                                    @endif
                                    <div>{{ \App\Support\ThaiDigits::inString((string) ($card['name'] ?? '-')) }}</div>
                                    <div>{{ \App\Support\ThaiDigits::inString((string) ($card['position'] ?? '-')) }}</div>
                                    <div>{{ \App\Support\ThaiDigits::inString((string) ($card['role'] ?? '-')) }}</div>
                                </div>
                            </td>
                        @endforeach
                        @if (count($pair) === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</body>

</html>
