{{-- เงินเดือนพนักงานมหาวิทยาลัย: แก้ไขเนื้อหาตั้งแต่ "เรียน" ลงมาในไฟล์นี้ --}}
<table class="meta-table">
    <tr>
        <td>เรียน {{ $to !== '' ? \App\Support\ThaiDigits::inString($to) : ' ' }}</td>
    </tr>
</table>

@php
    $memoBlockOrder = is_array($memoBlockOrder ?? null) && ($memoBlockOrder ?? []) !== []
        ? $memoBlockOrder
        : ['paragraph1', 'items', 'paragraph3'];
@endphp
@foreach ($memoBlockOrder as $memoBlock)
    @if (in_array($memoBlock, ['paragraph1', 'paragraph3', 'paragraph4'], true))
        @php
            $paragraphText = match ($memoBlock) {
                'paragraph1' => $paragraph1,
                'paragraph3' => $paragraph3,
                default => $paragraph4 ?? '',
            };
            $multiBudgetLines = is_array($memoParagraph2BudgetLines ?? null) ? $memoParagraph2BudgetLines : [];
            $useMultiBudgetParagraph2 = $memoBlock === 'paragraph2' && count($multiBudgetLines) >= 2;
        @endphp
        @if ($useMultiBudgetParagraph2)
            <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td lang="th">
                        <div class="p" style="text-align: justify;">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;โดยเบิกจ่ายจาก
                        </div>
                    </td>
                </tr>
            </table>
            @foreach ($multiBudgetLines as $budgetLine)
                @php
                    $budgetLabelHtml = \App\Support\ThaiLineBreak::insertBreakOpportunities(e((string) ($budgetLine['label'] ?? '')));
                    $budgetAmountFormatted = e((string) ($budgetLine['amount_formatted'] ?? ''));
                    $budgetAmountText = e((string) ($budgetLine['amount_text'] ?? ''));
                @endphp
                <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td lang="th">
                            <div class="p" style="text-align: justify;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(<span class="memo-budget-check-mark">&#10003;</span>) {!! $budgetLabelHtml !!} จำนวนเงิน {!! $budgetAmountFormatted !!} บาท ({!! $budgetAmountText !!})
                            </div>
                        </td>
                    </tr>
                </table>
            @endforeach
        @else
            @if ($memoBlock === 'paragraph1')
                @php
                    $paragraph1Raw = str_replace(["\r\n", "\r"], "\n", (string) $paragraphText);
                    $paragraph1Lines = $paragraph1Raw === '' ? [] : explode("\n", $paragraph1Raw);
                @endphp
                @foreach ($paragraph1Lines as $paragraph1Line)
                    @php
                        $paragraph1LineNormalized = preg_replace(
                            '/[ \t\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u',
                            ' ',
                            (string) $paragraph1Line,
                        ) ?? (string) $paragraph1Line;
                        $paragraph1LineNormalized = trim($paragraph1LineNormalized);
                        $paragraph1LineHtml = $paragraph1LineNormalized === ''
                            ? '&nbsp;'
                            : \App\Support\ThaiLineBreak::insertBreakOpportunities(
                                e(\App\Support\ThaiDigits::inString($paragraph1LineNormalized)),
                            );
                    @endphp
                    <table class="p-wrap" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td lang="th">
                                <div class="p" style="text-align: justify;">
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!! $paragraph1LineHtml !!}
                                </div>
                            </td>
                        </tr>
                    </table>
                @endforeach
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
        @endif
    @elseif ($memoBlock === 'items')
        {{-- <div class="items-section">
            <table class="items-table" border="0">
                <tbody>
                    @forelse ($withdrawItemLines as $idx => $line)
                        <tr>
                            <td class="col-name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ \App\Support\ThaiDigits::inString((string) ($line['name'] ?? '')) }}</td>
                            <td class="col-amt">จำนวนเงิน {{ $formatBahtCell((float) ($line['amount'] ?? 0)) }} บาท</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="items-empty" colspan="2">- ไม่มีรายการ -</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-no" style="text-align:center;">รวมทั้งสิ้น</td>
                        <td class="col-amt">จำนวนเงิน <u>{{ $formatBahtCell((float) ($itemsTotalAmount ?? 0)) }}</u> บาท</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:right;font-weight:normal;">
                            ({{ $itemsTotalText ?? ($totalText ?? '-') }})
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div> --}}
    @endif
@endforeach

<div class="signatures-wrap">
    <table class="signatures-grid">
        <tr>
            <td colspan="2">
                <div class="sign-block">
                    <div class="sign-img-wrap">
                        @if ($showDeanSigImg)
                            <img class="sign-img" src="{{ $deanSigUri }}" alt="">
                        @else
                            <div class="sign-dots">(..........................................................)</div>
                        @endif
                    </div>
                    @if ($deanStatusMemo !== '')
                        <div class="sign-status">{{ $deanStatusMemo }}</div>
                    @endif
                    <div>({{ $deanNameMemo !== '' ? \App\Support\ThaiDigits::inString($deanNameMemo) : '-' }})</div>
                    <div>{{ \App\Support\ThaiDigits::inString($deanRoleMemo) }}คณะเภสัชศาสตร์</div>
                </div>
            </td>
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

@if ($paragraph1 === '' && $paragraph3 === '' && ($paragraph4 ?? '') === '')
    <div class="p"> </div>
@endif
