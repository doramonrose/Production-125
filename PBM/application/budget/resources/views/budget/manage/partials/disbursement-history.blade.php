@php
    $fmtMoney = $fmtMoney ?? static function ($n): string {
        $n = (float) $n;
        $decimals = abs($n - round($n)) < 0.0000001 ? 0 : 2;

        return number_format($n, $decimals, '.', ',');
    };
    $groups = $disbursementHistory['groups'] ?? [];
    $total = (float) ($disbursementHistory['total'] ?? 0);
    $isPendingStatus = static function (string $status): bool {
        $status = strtolower(trim($status));

        return $status === 'processing' || str_starts_with($status, 'pending');
    };
@endphp

@if ($groups === [])
    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-10 text-center">
        <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
            <i class="fas fa-inbox"></i>
        </div>
        <p class="text-sm font-semibold text-slate-500">ยังไม่มีรายการเบิกจ่าย</p>
    </div>
@else
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm [&_td]:align-top">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-700 text-[11px] font-semibold uppercase tracking-wider backdrop-blur">
                    <th class="px-3 py-3 text-center whitespace-nowrap">เลขที่หนังสือ</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">ประเภท</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">วันที่ขอเบิกจ่าย</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">วันที่อนุมัติเบิกจ่าย</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">ผู้ขอเบิกจ่าย</th>
                    <th class="px-3 py-3 text-center">รายละเอียด</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">สถานะ</th>
                    <th class="px-3 py-3 text-center whitespace-nowrap">จำนวน</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($groups as $group)
                    @php
                        $isPending = $isPendingStatus((string) ($group['status'] ?? ''));
                        $statusText = $isPending ? 'รอเบิกจ่าย' : 'อนุมัติ';
                        $groupPayload = json_encode($group, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                    @endphp
                    <tr class="odd:bg-white even:bg-slate-50/40 hover:bg-slate-100/60 transition-colors">
                        <td class="px-3 py-3 text-slate-700 break-all">{{ ($group['document_number'] ?? '') !== '' ? $group['document_number'] : '-' }}</td>
                        <td class="px-3 py-3 text-slate-700 whitespace-nowrap">{{ ($group['source_label'] ?? '') !== '' ? $group['source_label'] : '-' }}</td>
                        <td class="px-3 py-3 text-slate-700 whitespace-nowrap">{{ ($group['request_date'] ?? '') !== '' ? $group['request_date'] : '-' }}</td>
                        <td class="px-3 py-3 text-slate-700 whitespace-nowrap">{{ ($group['approved_date'] ?? '') !== '' ? $group['approved_date'] : '-' }}</td>
                        <td class="px-3 py-3 text-slate-700 whitespace-nowrap">{{ ($group['applicant_name'] ?? '') !== '' ? $group['applicant_name'] : '-' }}</td>
                        <td class="px-3 py-3 text-xs text-slate-700">{{ ($group['subject'] ?? '') !== '' ? $group['subject'] : '-' }}</td>
                        <td class="px-3 py-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-extrabold ring-1 {{ $isPending ? 'bg-orange-50 text-orange-700 ring-orange-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right tabular-nums {{ $isPending ? 'text-orange-700' : 'text-slate-800' }}">
                            @if ((float) ($group['amount'] ?? 0) > 0)
                                <button type="button"
                                    class="js-disbursement-detail tabular-nums underline decoration-dotted underline-offset-4 hover:text-blue-700"
                                    data-ui-tooltip="ดูรายละเอียด"
                                    data-group="{{ $groupPayload }}">{{ $fmtMoney($group['amount'] ?? 0) }}</button>
                            @else
                                {{ $fmtMoney($group['amount'] ?? 0) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 bg-amber-50/70 font-semibold">
                    <td class="px-3 py-3 text-slate-700" colspan="7">รวมทั้งหมด</td>
                    <td class="px-3 py-3 text-right tabular-nums text-amber-800">{{ $fmtMoney($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
