{{-- เงิน พ.ต.ส.: แก้ไขเนื้อหาตั้งแต่ "เรียน" ลงมาในไฟล์นี้ --}}
@php
    $memoCopyIndex = (int) ($memoCopyIndex ?? 1);
    $ptsMemoIsExportCopy = $memoCopyIndex === 2;

    $ptsMemoTo = trim((string) ($detail['to'] ?? ''));
    $ptsMemoParagraph1 = trim((string) ($detail['paragraph1'] ?? ''));
    $ptsMemoParagraph2 = trim((string) ($detail['paragraph2'] ?? ''));
    $ptsMemoParagraph3 = trim((string) ($detail['paragraph3'] ?? ''));
    $ptsMemoParagraph4 = trim((string) ($detail['paragraph4'] ?? ''));
    $ptsMemoBlockOrder = is_array($memoBlockOrder ?? null) && ($memoBlockOrder ?? []) !== []
        ? $memoBlockOrder
        : ['paragraph1', 'items', 'paragraph2', 'paragraph3', 'paragraph4'];

    if ($ptsMemoIsExportCopy) {
        $ptsMemoTo = 'อธิการบดี มหาวิทยาลัยเชียงใหม่';

        $thaiMonthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
        ];
        $monthPeriodLabel = 'ประจำเดือน...';
        $documentDateRaw = trim((string) ($detail['document_date'] ?? ''));
        if ($documentDateRaw !== '') {
            try {
                $documentDate = \Carbon\Carbon::parse($documentDateRaw);
                $monthIndex = (int) $documentDate->month - 1;
                if ($monthIndex >= 0 && $monthIndex < count($thaiMonthNames)) {
                    $monthPeriodLabel = 'ประจำเดือน '.$thaiMonthNames[$monthIndex].' '.((int) $documentDate->year + 543);
                }
            } catch (\Throwable) {
                // keep placeholder
            }
        }

        $exportTotalAmount = (float) ($itemsTotalAmount ?? 0);
        if (! is_finite($exportTotalAmount)) {
            $exportTotalAmount = 0.0;
        }
        $exportTotalFormatted = $formatBahtCell($exportTotalAmount);
        $exportTotalText = trim((string) ($itemsTotalText ?? ($totalText ?? '')));
        if ($exportTotalText === '') {
            $exportTotalText = '-';
        }

        $ptsMemoParagraph1 = 'ด้วยงานการเงินการคลังและพัสดุ คณะเภสัชศาสตร์ขอส่งหลักฐานการเบิกจ่ายเงินเพิ่มสำหรับตำแหน่งที่มีเหตุพิเศษของผู้ปฏิบัติงานด้านสาธารณสุข '
            .$monthPeriodLabel
            .' จำนวนเงิน '.$exportTotalFormatted.' บาท ('.$exportTotalText.') ตามเอกสารที่แนบมาพร้อมนี้';
        $ptsMemoParagraph2 = '';
        $ptsMemoParagraph3 = 'จึงเรียนมาเพื่อโปรดทราบ และดำเนินการในส่วนที่เกี่ยวข้องต่อไป จักเป็นพระคุณยิ่ง';
        $ptsMemoBlockOrder = ['paragraph1', 'paragraph3'];
    }
@endphp
<table class="meta-table">
    <tr>
        <td>เรียน {{ $ptsMemoTo !== '' ? \App\Support\ThaiDigits::inString($ptsMemoTo) : ' ' }}</td>
    </tr>
</table>

@foreach ($ptsMemoBlockOrder as $memoBlock)
    @if (in_array($memoBlock, ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'], true))
        @php
            $paragraphText = match ($memoBlock) {
                'paragraph1' => $ptsMemoParagraph1,
                'paragraph2' => $ptsMemoParagraph2,
                'paragraph3' => $ptsMemoParagraph3,
                default => $ptsMemoParagraph4,
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
            $ptsWithdrawItems = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        @endphp
        <div class="items-section">
            <table class="items-table" border="0">
                <tbody>
                    @forelse ($ptsWithdrawItems as $item)
                        @if (! is_array($item))
                            @continue
                        @endif
                        @php
                            $lineAmount = (float) ($item['amount'] ?? 0);
                            $displayLabel = trim((string) ($item['display_name'] ?? ''));
                            if ($displayLabel === '' || $displayLabel === '-') {
                                $displayLabel = trim((string) ($item['name'] ?? ''));
                            }
                            if ($displayLabel === '' || $displayLabel === '-') {
                                $displayLabel = '-';
                            }
                        @endphp
                        <tr>
                            <td class="col-name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ \App\Support\ThaiDigits::inString($displayLabel) }}</td>
                            <td class="col-amt">จำนวนเงิน {{ $formatBahtCell($lineAmount) }} บาท</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="items-empty" colspan="2">- ไม่มีรายการ -</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-no" style="text-align:center;font-weight:normal;">รวมทั้งสิ้น</td>
                        <td class="col-amt" style="font-weight:normal;">จำนวนเงิน <u>{{ $formatBahtCell((float) ($itemsTotalAmount ?? 0)) }}</u> บาท</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:right;font-weight:normal;">
                            ({{ $itemsTotalText ?? ($totalText ?? '-') }})
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
@endforeach

@php
    $applicantNameMemo = trim((string) ($detail['applicant_name'] ?? ''));
    $applicantPositionMemo = trim((string) ($detail['applicant_position'] ?? ''));
    $applicantSigUri = trim(
        (string) ($detail['applicant_signature_base64'] ?? ($detail['applicant_signature_url'] ?? '')),
    );
    $showApplicantSigImg =
        $applicantSigUri !== '' &&
        ! str_starts_with($applicantSigUri, 'data:image/svg+xml');
    $applicantStatusMemo = trim((string) ($detail['applicant_signature_status'] ?? ''));
    $ptsDeanRoleDisplay = $ptsMemoIsExportCopy ? 'คณบดีคณะเภสัชศาสตร์' : $deanRoleMemo.'คณะเภสัชศาสตร์';
@endphp
<div class="signatures-wrap">
    <table class="signatures-grid">
        @if ($ptsMemoIsExportCopy)
            <tr>
                <td colspan="2" style="text-align:center;">
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
                        <div>{{ \App\Support\ThaiDigits::inString($ptsDeanRoleDisplay) }}</div>
                    </div>
                </td>
            </tr>
        @else
            <tr>
                <td colspan="2">
                    <div class="sign-block">
                        <div class="sign-img-wrap">
                            @if ($showApplicantSigImg)
                                <img class="sign-img" src="{{ $applicantSigUri }}" alt="">
                            @else
                                <div class="sign-dots">(..........................................................)</div>
                            @endif
                        </div>
                        @if ($applicantStatusMemo !== '')
                            <div class="sign-status">{{ $applicantStatusMemo }}</div>
                        @endif
                        <div>({{ $applicantNameMemo !== '' ? \App\Support\ThaiDigits::inString($applicantNameMemo) : '-' }})</div>
                        <div>{{ $applicantPositionMemo !== '' ? \App\Support\ThaiDigits::inString($applicantPositionMemo) : '-' }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width:50%;text-align:center;">
                    <div class="sign-block">
                        <div><strong>ผู้อนุมัติ</strong></div>
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
                <td style="width:50%;"></td>
            </tr>
        @endif
    </table>
</div>

@if ($ptsMemoParagraph1 === '' && $ptsMemoParagraph2 === '' && $ptsMemoParagraph3 === '' && $ptsMemoParagraph4 === '')
    <div class="p"> </div>
@endif
