<x-layouts.app :title="$title">
    <div class="py-4">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-slate-800">บันทึกการใช้งาน</h2>
            <p class="mt-1 text-sm text-slate-500">แสดงวันเวลา ผู้ใช้งาน การกระทำ เส้นทาง และรายละเอียดคำขอในระบบเบิกจ่าย</p>
        </div>

        @if (! $ready)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                ยังไม่มีตารางบันทึกการใช้งาน
            </div>
        @else
            <form method="GET" action="{{ route('withdraw.usage-log.index') }}" class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-12">
                <label class="md:col-span-4 text-xs font-semibold text-slate-600">
                    ค้นหา
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="การกระทำ, เส้นทาง, IP"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800" />
                </label>
                <div class="md:col-span-4 text-xs font-semibold text-slate-600">
                    ผู้ใช้งาน
                    <div class="mt-1 font-normal">
                        <x-searchable-dropdown
                            id="usage-log-staff"
                            name="staff_id"
                            :value="$filters['staff_id']"
                            placeholder="พิมพ์เพื่อค้นหาชื่อหรือรหัส"
                            :options="$users"
                            valueKey="id"
                            textKey="label"
                            :showClear="true"
                            clearTitle="ล้างผู้ใช้งาน"
                        />
                    </div>
                </div>
                <label class="md:col-span-2 text-xs font-semibold text-slate-600">
                    วิธี
                    <select name="method" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800">
                        <option value="">ทั้งหมด</option>
                        @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                            <option value="{{ $method }}" @selected($filters['method'] === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="md:col-span-2 text-xs font-semibold text-slate-600">
                    สิทธิ์
                    <select name="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800">
                        <option value="">ทั้งหมด</option>
                        @foreach (['admin' => 'แอดมิน', 'member' => 'สมาชิก', 'viewer' => 'ผู้ดู'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="md:col-span-3 text-xs font-semibold text-slate-600">
                    ตั้งแต่วันที่
                    <div class="mt-1">
                        <x-date-picker
                            id="usage-log-date-from"
                            name="date_from"
                            placeholder="เลือกวันที่เริ่มต้น"
                            :value="$filters['date_from']"
                            inputClass="rounded-lg py-2 shadow-none"
                        />
                    </div>
                </label>
                <label class="md:col-span-3 text-xs font-semibold text-slate-600">
                    ถึงวันที่
                    <div class="mt-1">
                        <x-date-picker
                            id="usage-log-date-to"
                            name="date_to"
                            placeholder="เลือกวันที่สิ้นสุด"
                            :value="$filters['date_to']"
                            inputClass="rounded-lg py-2 shadow-none"
                        />
                    </div>
                </label>
                <div class="md:col-span-6 flex items-end justify-end gap-2">
                    <a href="{{ route('withdraw.usage-log.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">ล้างตัวกรอง</a>
                    <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color:#1e293b;">ค้นหา</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-600">
                            <tr>
                                <th class="px-4 py-3">วันเวลา</th>
                                <th class="px-4 py-3">ผู้ใช้งาน</th>
                                <th class="px-4 py-3">การกระทำ</th>
                                <th class="px-4 py-3">เส้นทาง</th>
                                <th class="px-4 py-3">รายละเอียดคำขอ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                                        {{ \Illuminate\Support\Carbon::parse($log->occurred_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ $log->display_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $log->staff_id ?: '-' }} · {{ $log->system_role ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ $log->display_action }}</div>
                                        @if ($log->display_record !== '')
                                            <div class="mt-1 text-xs leading-relaxed text-slate-600">{{ $log->display_record }}</div>
                                        @endif
                                        @if (is_array($log->display_listed))
                                            @if (count($log->display_listed) === 0)
                                                <div class="mt-1 text-xs text-slate-500">ขณะนั้นไม่มีรายการในหน้านี้</div>
                                            @else
                                                <div class="mt-1 space-y-1 text-xs leading-relaxed text-slate-600">
                                                    @foreach (array_slice($log->display_listed, 0, 8) as $item)
                                                        <div>{{ $item }}</div>
                                                    @endforeach
                                                    @if (count($log->display_listed) > 8)
                                                        <div class="text-slate-500">และอีก {{ count($log->display_listed) - 8 }} รายการ ดูได้จากปุ่มรายละเอียด</div>
                                                    @endif
                                                </div>
                                            @endif
                                        @elseif ($log->display_list_note !== '')
                                            <div class="mt-1 text-xs leading-relaxed text-slate-500">{{ $log->display_list_note }}</div>
                                        @endif
                                        @if ((int) $log->http_status >= 400)
                                            <div class="mt-1 text-xs font-semibold text-rose-600">ไม่สำเร็จ ({{ $log->http_status }})</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="break-all font-medium text-slate-700">{{ $log->request_path }}</div>
                                        <div class="mt-1 text-slate-500">IP {{ $log->ip_address ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div>{{ $log->request_method }} · สถานะ {{ $log->http_status }} · {{ number_format((int) $log->duration_ms) }} ms</div>
                                        <div class="mt-1 break-all text-slate-500">{{ $log->route_name ?: $log->action_name ?: '-' }}</div>
                                        <button type="button"
                                            class="mt-2 inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold text-white"
                                            style="background-color:#1e293b;"
                                            onclick="window.openUsageLogDetail({{ (int) $log->id }})">
                                            รายละเอียด
                                        </button>
                                        <template id="usage-log-detail-{{ (int) $log->id }}">
                                            <div class="space-y-3 text-sm leading-relaxed text-slate-700">
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    <div><span class="font-semibold text-slate-500">วันเวลา</span><div>{{ \Illuminate\Support\Carbon::parse($log->occurred_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</div></div>
                                                    <div><span class="font-semibold text-slate-500">ผู้ใช้งาน</span><div>{{ $log->display_name }} · {{ $log->staff_id ?: '-' }} · {{ $log->system_role ?: '-' }}</div></div>
                                                    <div><span class="font-semibold text-slate-500">วิธีเรียก</span><div>{{ $log->request_method }} · สถานะ {{ $log->http_status }} · {{ number_format((int) $log->duration_ms) }} ms</div></div>
                                                    <div><span class="font-semibold text-slate-500">เส้นทาง</span><div class="break-all">{{ $log->request_path }}</div></div>
                                                </div>
                                                <div><span class="font-semibold text-slate-500">ชื่อเส้นทาง</span><div class="break-all">{{ $log->route_name ?: $log->action_name ?: '-' }}</div></div>
                                                <div><span class="font-semibold text-slate-500">ที่อยู่ไอพี</span><div>{{ $log->ip_address ?: '-' }}</div></div>
                                                <div><span class="font-semibold text-slate-500">เบราว์เซอร์</span><div class="break-all">{{ $log->user_agent ?: '-' }}</div></div>
                                                @if (is_array($log->display_listed) && count($log->display_listed) > 0)
                                                    <div>
                                                        <div class="font-semibold text-slate-500">รายการที่แสดงในขณะนั้น</div>
                                                        <ul class="mt-1 list-disc space-y-1 pl-5 text-xs text-slate-700">
                                                            @foreach ($log->display_listed as $item)
                                                                <li>{{ $item }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                                @if ($log->exception_message)
                                                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-rose-700">
                                                        <div class="font-semibold">ข้อผิดพลาด</div>
                                                        <div class="mt-1 whitespace-pre-wrap">{{ $log->exception_message }}</div>
                                                    </div>
                                                @endif
                                                @if ($log->query_params)
                                                    <div>
                                                        <div class="font-semibold text-slate-500">พารามิเตอร์</div>
                                                        <pre class="mt-1 max-h-64 overflow-auto whitespace-pre-wrap rounded-xl bg-slate-50 p-3 text-xs text-slate-700">{{ $log->query_params }}</pre>
                                                    </div>
                                                @endif
                                                @if ($log->request_payload)
                                                    <div>
                                                        <div class="font-semibold text-slate-500">ข้อมูลที่ส่ง</div>
                                                        <pre class="mt-1 max-h-80 overflow-auto whitespace-pre-wrap rounded-xl bg-slate-50 p-3 text-xs text-slate-700">{{ $log->request_payload }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">ยังไม่มีบันทึกตามเงื่อนไขนี้</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

            <x-modal name="usage-log-detail-modal" maxWidth="3xl" variant="modern" title="รายละเอียดคำขอ"
                iconClass="fas fa-clipboard-list" dialogClass="max-h-[85vh] overflow-y-auto">
                <div id="usage-log-detail-body"></div>
            </x-modal>
            <script>
                window.openUsageLogDetail = function (id) {
                    const template = document.getElementById('usage-log-detail-' + id);
                    const body = document.getElementById('usage-log-detail-body');
                    if (!template || !body) {
                        return;
                    }
                    body.replaceChildren(template.content.cloneNode(true));
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'usage-log-detail-modal' }));
                };
            </script>
        @endif
    </div>
</x-layouts.app>
