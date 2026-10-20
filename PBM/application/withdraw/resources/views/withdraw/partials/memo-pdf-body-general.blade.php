@php
    $memoBlockOrder = is_array($memoBlockOrder ?? null) && ($memoBlockOrder ?? []) !== []
        ? $memoBlockOrder
        : ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];
    $isProjectMemo = (bool) ($isProjectMemo ?? false);
    $memoDigits = $memoDigits ?? static function (string $text) use ($isProjectMemo): string {
        return $isProjectMemo
            ? \App\Support\ThaiDigits::toArabic($text)
            : \App\Support\ThaiDigits::inString($text);
    };
    $tb = static function (string $text) use ($memoDigits): string {
        $normalized = trim(preg_replace('/[ \t\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $text) ?? $text);
        if ($normalized === '') {
            return '';
        }

        return \App\Support\ThaiLineBreak::insertBreakOpportunities(
            e($memoDigits($normalized))
        );
    };
    $indentPrefix = '';
    $expenseIndentPrefix = '';
    $renderProjectParagraph = static function (string $paragraphText, string $memoBlock) use ($tb, $indentPrefix, $expenseIndentPrefix, $memoDigits): string {
        $paragraphText = str_replace(["\r\n", "\r"], "\n", trim($paragraphText));
        if ($paragraphText === '') {
            return '';
        }

        $labels = [
            'รายละเอียดค่าใช้จ่ายทั้งสิ้น',
            'รายละเอียดการดำเนินการ',
            'กิจกรรม',
        ];
        $html = '';
        $lines = explode("\n", $paragraphText);
        $narrativeBuffer = [];

        $flushNarrative = static function () use (&$html, &$narrativeBuffer, $tb, $indentPrefix): void {
            if ($narrativeBuffer === []) {
                return;
            }
            $text = trim(implode(' ', $narrativeBuffer));
            $narrativeBuffer = [];
            if ($text === '') {
                return;
            }
            $html .= '<p class="p-project" lang="th">'
                .$indentPrefix.$tb($text)
                .'</p>';
        };

        foreach ($lines as $rawLine) {
            $trimmed = trim((string) $rawLine);
            if ($trimmed === '') {
                continue;
            }

            $matchedLabel = null;
            foreach ($labels as $label) {
                if (str_starts_with($trimmed, $label)) {
                    $matchedLabel = $label;
                    break;
                }
            }

            if ($matchedLabel !== null) {
                $flushNarrative();
                $value = trim(mb_substr($trimmed, mb_strlen($matchedLabel)));
                // เยื้อง | ป้ายหนา (ความกว้างคงที่ตามป้ายยาวสุด) | ค่า เริ่มจุดเดียวกันและชิดป้าย
                $html .= '<table class="project-label-table" width="100%" cellpadding="0" cellspacing="0"><tr>'
                    .'<td class="indent-col" style="width:20mm;">&nbsp;</td>'
                    .'<td class="label-col" lang="th"><strong>'.$tb($matchedLabel).'</strong></td>'
                    .'<td class="value-col" lang="th">'.$tb($value).'</td>'
                    .'</tr></table>';
                continue;
            }

            if (preg_match('/^-\s*(.+?)(?:\s{2,}|\t+)(.+?\s*บาท)\s*$/u', $trimmed, $matches) === 1) {
                $flushNarrative();
                $html .= '<table class="project-expense-table" width="100%" cellpadding="0" cellspacing="0"><tr>'
                    .'<td class="indent-col" style="width:26mm;">&nbsp;</td>'
                    .'<td class="name-col" lang="th">- '.$tb(trim((string) $matches[1])).'</td>'
                    .'<td class="amt-col" lang="th">'.e($memoDigits(trim((string) $matches[2]))).'</td>'
                    .'</tr></table>';
                continue;
            }

            if (str_starts_with($trimmed, '- ')) {
                $flushNarrative();
                $html .= '<table class="project-expense-table" width="100%" cellpadding="0" cellspacing="0"><tr>'
                    .'<td class="indent-col" style="width:26mm;">&nbsp;</td>'
                    .'<td class="name-col" lang="th" colspan="2">- '.$tb(ltrim(mb_substr($trimmed, 1))).'</td>'
                    .'</tr></table>';
                continue;
            }

            if ($memoBlock === 'paragraph2' && $narrativeBuffer !== [] && (
                str_contains($trimmed, 'รายละเอียด')
                || str_starts_with($trimmed, 'กิจกรรม')
            )) {
                $flushNarrative();
            }
            $narrativeBuffer[] = $trimmed;
        }

        $flushNarrative();

        return $html;
    };
@endphp
@foreach ($memoBlockOrder as $memoBlock)
    @if (in_array($memoBlock, ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'], true))
        @php
            $paragraphText = match ($memoBlock) {
                'paragraph1' => $paragraph1,
                'paragraph2' => $paragraph2,
                'paragraph3' => $paragraph3,
                default => $paragraph4 ?? '',
            };
        @endphp
        @if ($isProjectMemo)
            {!! $renderProjectParagraph((string) $paragraphText, (string) $memoBlock) !!}
        @else
            @foreach ($formatMemoParagraphBlocks($paragraphText) as $paragraphBlock)
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
    @elseif ($memoBlock === 'items')
        @php
            $amountWordsLabel = '('.$memoDigits((string) ($itemsTotalText ?? ($totalText ?? '-'))).')';
            $measureMemoTextMm = static function (string $text, float $fontPt): float {
                $text = trim($text);
                if ($text === '') {
                    return 0.0;
                }
                $font = storage_path('fonts/THSarabunNew-Bold.ttf');
                if (is_file($font) && function_exists('imagettfbbox')) {
                    $box = @imagettfbbox($fontPt, 0, $font, $text);
                    if (is_array($box)) {
                        $px = abs((float) $box[2] - (float) $box[0]);
                        if ($px > 0) {
                            return $px * 25.4 / 96;
                        }
                    }
                }

                return mb_strlen($text) * $fontPt * 0.16;
            };
            $amountColWidthMm = max(
                $measureMemoTextMm($amountWordsLabel, 12.88),
                $measureMemoTextMm('จำนวนเงิน (บาท)', 14),
                $measureMemoTextMm($formatBahtCell((float) ($itemsTotalAmount ?? 0)), 14)
            );
            foreach ($withdrawItemGroups as $amountGroup) {
                $amountColWidthMm = max(
                    $amountColWidthMm,
                    $measureMemoTextMm($formatBahtCell((float) ($amountGroup['total'] ?? 0)), 14)
                );
                foreach ($amountGroup['lines'] ?? [] as $amountLine) {
                    $amountColWidthMm = max(
                        $amountColWidthMm,
                        $measureMemoTextMm($formatBahtCell((float) ($amountLine['amount'] ?? 0)), 14)
                    );
                }
            }
            $amountColWidthMm = round(min(110, max(24, $amountColWidthMm + 3.6)), 1);
        @endphp
        <div class="items-section">
            <table class="items-table" autosize="1">
                <colgroup>
                    <col style="width:12mm">
                    <col>
                    <col style="width:{{ $amountColWidthMm }}mm">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-no">ที่</th>
                        <th class="col-name">รายการ</th>
                        <th class="col-amt" style="width:{{ $amountColWidthMm }}mm">จำนวนเงิน (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawItemGroups as $idx => $group)
                        <tr class="category-row">
                            <td class="col-no">{{ $memoDigits((string) ($idx + 1)) }}</td>
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
                        <td class="col-no" colspan="2" style="text-align:center;vertical-align:middle;">
                            รวม
                            <span class="items-total-text">({{ $memoDigits((string) ($itemsTotalText ?? ($totalText ?? '-'))) }})</span>
                        </td>
                        <td class="col-amt" style="text-align:right;vertical-align:middle;">{{ $formatBahtCell((float) ($itemsTotalAmount ?? 0)) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
    @if ($memoBlock === 'paragraph3' && ! empty($isProjectMemo) && is_array($projectBudgetSummary ?? null))
        <table class="project-budget-table" autosize="1">
            <thead>
                <tr>
                    <th>งบประมาณโครงการ</th>
                    <th>อนุมัติแล้ว</th>
                    <th>อนุมัติครั้งนี้</th>
                    <th>รวมอนุมัติ</th>
                    <th>คงเหลือ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $formatBahtCell((float) ($projectBudgetSummary['amount'] ?? 0)) }}</td>
                    <td>{{ $formatBahtCell((float) ($projectBudgetSummary['approved_already'] ?? 0)) }}</td>
                    <td>{{ $formatBahtCell((float) ($projectBudgetSummary['approved_this_time'] ?? 0)) }}</td>
                    <td>{{ $formatBahtCell((float) ($projectBudgetSummary['total_approved'] ?? 0)) }}</td>
                    <td>{{ $formatBahtCell((float) ($projectBudgetSummary['remaining'] ?? 0)) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
@endforeach
