{{-- เบิกจ่ายในการเดินทางไปปฏิบัติงาน: เนื้อหาตามเทมเพลตบันทึกข้อความชนิด travel --}}
<table class="meta-table">
    <tr>
        <td>เรียน {{ $to !== '' ? \App\Support\ThaiDigits::inString($to) : ' ' }}</td>
    </tr>
</table>

@php
    $memoBlockOrder = is_array($memoBlockOrder ?? null) && ($memoBlockOrder ?? []) !== []
        ? $memoBlockOrder
        : ['paragraph1', 'items', 'paragraph2', 'paragraph3', 'paragraph4'];
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
            $multiBudgetLines = is_array($memoParagraph2BudgetLines ?? null) ? $memoParagraph2BudgetLines : [];
            $useMultiBudgetParagraph2 = $memoBlock === 'paragraph2' && count($multiBudgetLines) >= 2;
        @endphp
        @if ($useMultiBudgetParagraph2)
            <p class="p-travel" lang="th">โดยเบิกจ่ายจาก</p>
            @foreach ($multiBudgetLines as $budgetLine)
                @php
                    $budgetLabelHtml = \App\Support\ThaiLineBreak::insertBreakOpportunities(e((string) ($budgetLine['label'] ?? '')));
                    $budgetAmountFormatted = e((string) ($budgetLine['amount_formatted'] ?? ''));
                    $budgetAmountText = e((string) ($budgetLine['amount_text'] ?? ''));
                @endphp
                <p class="p-travel" lang="th">(<span class="memo-budget-check-mark">&#10003;</span>) {!! $budgetLabelHtml !!} จำนวนเงิน {!! $budgetAmountFormatted !!} บาท ({!! $budgetAmountText !!})</p>
            @endforeach
        @else
            @foreach ($formatMemoParagraphBlocks($paragraphText) as $paragraphBlock)
                <p class="p-travel" lang="th">{!! $paragraphBlock !!}</p>
            @endforeach
        @endif
    @elseif ($memoBlock === 'items')
        @php
            $travelItems = is_array($detail['items'] ?? null) ? $detail['items'] : [];
            $travelMatrixColumns = ['ค่าเบี้ยเลี้ยง', 'ค่าที่พัก', 'ค่าพาหนะ', 'ค่าลงทะเบียน'];
            $travelKnownLabels = array_merge($travelMatrixColumns, ['ค่าใช้จ่ายอื่น', 'ค่าใช้จ่ายเดินทาง', 'อื่นๆ', 'ค่าตอบแทนคนขับรถ']);
            $travelMatchLabel = static function (string $name) use ($travelKnownLabels): string {
                $text = trim($name);
                foreach ($travelKnownLabels as $label) {
                    if ($text === $label || str_ends_with($text, ' '.$label)) {
                        return $label;
                    }
                }

                return $text !== '' ? $text : '-';
            };
            $travelMatrixMoney = static function (float $amount): string {
                if (! is_finite($amount) || abs($amount) < 0.005) {
                    return '-';
                }
                if (abs($amount - round($amount)) < 0.005) {
                    return \App\Support\ThaiDigits::inString(number_format((float) round($amount), 0, '.', ',').'.-');
                }

                return \App\Support\ThaiDigits::inString(number_format($amount, 2, '.', ','));
            };
            $travelAmounts = [];
            $travelTotal = 0.0;
            foreach ($travelItems as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $amount = (float) ($item['amount'] ?? 0);
                if (! is_finite($amount) || $amount <= 0) {
                    continue;
                }
                $label = $travelMatchLabel((string) ($item['name'] ?? ''));
                $travelAmounts[$label] = ($travelAmounts[$label] ?? 0) + $amount;
                $travelTotal += $amount;
            }
            $travelColumns = array_merge(
                $travelMatrixColumns,
                array_values(array_filter(array_keys($travelAmounts), static fn (string $label): bool => ! in_array($label, $travelMatrixColumns, true)))
            );
            $travelPersonNames = array_values(array_filter(
                array_map(
                    static fn ($name): string => trim((string) $name),
                    is_array($detail['traveler_names'] ?? null) ? $detail['traveler_names'] : []
                ),
                static fn (string $name): bool => $name !== ''
            ));
            $travelPersonName = $travelPersonNames !== [] ? implode(', ', $travelPersonNames) : '-';
        @endphp
        <div class="items-section">
            <table class="travel-matrix">
                <thead>
                    <tr>
                        <th class="name-cell">ชื่อ - สกุล</th>
                        @foreach ($travelColumns as $column)
                            <th>{{ \App\Support\ThaiDigits::inString($column) }}</th>
                        @endforeach
                        <th>รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="name-cell">{{ \App\Support\ThaiDigits::inString($travelPersonName) }}</td>
                        @foreach ($travelColumns as $column)
                            <td>{{ $travelMatrixMoney((float) ($travelAmounts[$column] ?? 0)) }}</td>
                        @endforeach
                        <td>{{ $travelMatrixMoney($travelTotal) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>รวม</td>
                        @foreach ($travelColumns as $column)
                            <td>{{ $travelMatrixMoney((float) ($travelAmounts[$column] ?? 0)) }}</td>
                        @endforeach
                        <td>{{ $travelMatrixMoney($travelTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endforeach

@php
    $travelMakerName = trim((string) ($detail['applicant_name'] ?? ''));
    $travelMakerPosition = trim((string) ($detail['applicant_position'] ?? ''));
    $travelMakerSigUri = trim((string) ($detail['applicant_signature_base64'] ?? ($detail['applicant_signature_url'] ?? '')));
    $showTravelMakerSigImg = $travelMakerSigUri !== '' && ! str_starts_with($travelMakerSigUri, 'data:image/svg+xml');
    $travelApproverName = $deanNameMemo !== '' ? \App\Support\ThaiDigits::inString($deanNameMemo) : '-';
    $travelApproverNameLabel = '('.$travelApproverName.')';
    $travelApproverNameWidth = max(36, (int) ceil(mb_strlen($travelApproverNameLabel) * 2.8));
    $travelApproverStepName = trim((string) ($detail['dean_step_name'] ?? ''));
    $travelApproverRole = trim((string) $deanRoleMemo);
    $travelApproverIsViceDean = str_contains($travelApproverStepName, 'รองคณบดี')
        || str_contains($travelApproverRole, 'รองคณบดี');
    if ($travelApproverIsViceDean) {
        if (str_contains($travelApproverStepName, 'รองคณบดี')) {
            $travelApproverRole = $travelApproverStepName;
        } elseif ($travelApproverRole === '' || $travelApproverRole === '-') {
            $travelApproverRole = 'รองคณบดี';
        }
        $travelActingSuffix = 'ปฏิบัติรายการแทน';
        if (! str_contains($travelApproverRole, $travelActingSuffix)) {
            $travelApproverRole = rtrim($travelApproverRole).' '.$travelActingSuffix;
        }
        $travelApproverRole = trim(str_replace('คณบดีคณะเภสัชศาสตร์', '', $travelApproverRole));
    } elseif (! str_contains($travelApproverRole, 'คณะเภสัชศาสตร์')) {
        $travelApproverRole .= 'คณะเภสัชศาสตร์';
    }
    $travelFacultyTitle = 'คณบดีคณะเภสัชศาสตร์';
    if ($travelApproverIsViceDean) {
        $travelApproverRoleLines = array_values(array_filter([
            trim($travelApproverRole),
            $travelFacultyTitle,
        ], static fn (string $line): bool => $line !== ''));
    } elseif (str_contains($travelApproverRole, $travelFacultyTitle) && trim($travelApproverRole) !== $travelFacultyTitle) {
        $travelApproverRoleHead = trim(str_replace($travelFacultyTitle, '', $travelApproverRole));
        $travelApproverRoleLines = array_values(array_filter([
            $travelApproverRoleHead,
            $travelFacultyTitle,
        ], static fn (string $line): bool => $line !== ''));
    } else {
        $travelApproverRoleLines = [$travelApproverRole];
    }
@endphp
<div class="signatures-wrap">
    <table class="signatures-grid">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;text-align:center;vertical-align:top;">
                <div class="sign-block">
                    <div class="sign-img-wrap">
                        @if ($showTravelMakerSigImg)
                            <img class="sign-img" src="{{ $travelMakerSigUri }}" alt="">
                        @else
                            <div class="sign-dots">(..........................................................)</div>
                        @endif
                    </div>
                    <div>({{ $travelMakerName !== '' ? \App\Support\ThaiDigits::inString($travelMakerName) : '-' }})</div>
                    @if ($travelMakerPosition !== '')
                        <div>{{ \App\Support\ThaiDigits::inString($travelMakerPosition) }}</div>
                    @endif
                    @php
                        $travelHangingSigners = array_values(array_filter(
                            is_array($detail['travel_hanging_signers'] ?? null) ? $detail['travel_hanging_signers'] : [],
                            static fn ($item): bool => is_array($item) && trim((string) ($item['sig_uri'] ?? '')) !== ''
                        ));
                    @endphp
                    @if ($travelHangingSigners !== [])
                        <div class="sign-hanging-wrap">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width:22%;"></td>
                                    <td style="width:78%;text-align:right;">
                                        <table class="sign-hanging-table" align="right">
                                            <tr>
                                                @foreach ($travelHangingSigners as $hangIndex => $hang)
                                                    @if ($hangIndex > 0)
                                                        <td class="sign-hanging-gap">&nbsp;</td>
                                                    @endif
                                                    <td>
                                                        <div class="sign-hanging-img-wrap">
                                                            <img class="sign-hanging-img" src="{{ $hang['sig_uri'] }}" alt="">
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:50%;text-align:left;vertical-align:top;padding-top:8mm;">
                @php
                    $travelApproverRoleLabels = array_map(
                        static fn (string $line): string => \App\Support\ThaiDigits::inString($line),
                        $travelApproverRoleLines
                    );
                    $travelApproverBlockWidth = $travelApproverNameWidth;
                    foreach ($travelApproverRoleLabels as $travelApproverRoleLabel) {
                        $travelApproverBlockWidth = max($travelApproverBlockWidth, (int) ceil(mb_strlen($travelApproverRoleLabel) * 2.8));
                    }
                @endphp
                <table cellpadding="0" cellspacing="0" width="{{ $travelApproverBlockWidth }}mm" style="width:{{ $travelApproverBlockWidth }}mm;border-collapse:collapse;margin:0;">
                    <tr>
                        <td style="text-align:center;white-space:nowrap;padding:0;font-weight:bold;">อนุมัติ</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding:1mm 0 0;">
                            <div class="sign-img-wrap" style="text-align:center;">
                                @if ($showDeanSigImg)
                                    <img class="sign-img" src="{{ $deanSigUri }}" alt="">
                                @else
                                    <div class="sign-dots">(..........................................................)</div>
                                @endif
                            </div>
                            @if ($deanStatusMemo !== '')
                                <div class="sign-status" style="text-align:center;">{{ $deanStatusMemo }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;white-space:nowrap;padding:0;">{{ $travelApproverNameLabel }}</td>
                    </tr>
                    @foreach ($travelApproverRoleLabels as $travelApproverRoleLabel)
                        <tr>
                            <td style="text-align:center;white-space:nowrap;padding:0;">{{ $travelApproverRoleLabel }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td style="width:50%;"></td>
        </tr>
    </table>
</div>
@if (!empty($additionalSignerCards ?? []))
    <div class="signatures-extra-wrap">
        <table class="signatures-grid signatures-grid-extra">
            @foreach (array_chunk($additionalSignerCards, 2) as $pair)
                <tr>
                    @foreach ($pair as $card)
                        <td>
                            <div class="sign-block">
                                <div class="sign-img-wrap">
                                    @if (!empty($card['show_sig']))
                                        <img class="sign-img" src="{{ $card['sig_uri'] }}" alt="">
                                    @else
                                        <div class="sign-dots">(..........................................................)</div>
                                    @endif
                                </div>
                                @if (!empty($card['status_line']))
                                    <div class="sign-status">{{ $card['status_line'] }}</div>
                                @endif
                                <div>{{ $card['name'] ?? '-' }}</div>
                                <div>{{ $card['position'] ?? '-' }}</div>
                                <div>{{ $card['role'] ?? '-' }}</div>
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

@if ($paragraph1 === '' && $paragraph2 === '' && $paragraph3 === '' && ($paragraph4 ?? '') === '')
    <div class="p"> </div>
@endif
