@props([
    // Container id (optional)
    'id' => null,

    // Left info text element id (e.g. table-info, pagination-info)
    'infoId' => 'table-info',

    // Page badge element id
    'pageId' => 'pagination-page',
    'pageText' => 'หน้า 1/1',

    // Buttons ids (to be used by existing JS)
    'prevId' => 'btn-prev',
    'nextId' => 'btn-next',
    'viewAllId' => 'btn-view-all',

    // Button labels
    'prevText' => 'ก่อนหน้า',
    'nextText' => 'ถัดไป',
    'viewAllText' => 'แสดงทั้งหมด',

    // Tailwind classes overrides (optional)
    'class' => '',
])

<div
    @if ($id) id="{{ $id }}" @endif
    class="mt-5 flex items-center justify-between gap-3 overflow-x-auto whitespace-nowrap rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-sm text-slate-500 shadow-sm shadow-slate-200/30 {{ $class }}">
    <div class="flex h-9 items-center gap-2">
        <p id="{{ $infoId }}" class="inline-flex h-9 mt-3 items-center font-medium text-slate-600 leading-none"></p>
    </div>

    <div class="flex items-center gap-2">
        <span id="{{ $pageId }}"
            class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white/90 px-3 text-xs font-semibold text-slate-700 shadow-sm">
            {{ $pageText }}
        </span>

        <div
            class="inline-flex h-9 items-stretch overflow-hidden rounded-xl border border-slate-200 bg-white/80 p-0 shadow-sm transition-shadow hover:shadow-md">
            <button id="{{ $prevId }}" type="button"
                class="group inline-flex h-9 items-center gap-1.5 rounded-l-xl rounded-r-none px-3 text-xs font-semibold text-slate-700 transition-all duration-150 ease-out hover:bg-slate-200/50 hover:text-slate-900 active:bg-slate-200/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:cursor-not-allowed disabled:opacity-40"
                disabled>
                <i class="fas fa-chevron-left text-[10px] transition-transform group-hover:-translate-x-0.5"></i>
                <span>{{ $prevText }}</span>
            </button>
            <button id="{{ $nextId }}" type="button"
                class="group inline-flex h-9 items-center gap-1.5 rounded-none px-3 text-xs font-semibold text-slate-700 transition-all duration-150 ease-out hover:bg-slate-200/50 hover:text-slate-900 active:bg-slate-200/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:cursor-not-allowed disabled:opacity-40">
                <span>{{ $nextText }}</span>
                <i class="fas fa-chevron-right text-[10px] transition-transform group-hover:translate-x-0.5"></i>
            </button>
            <span class="mx-1 my-auto h-5 w-px bg-slate-200"></span>
            <button id="{{ $viewAllId }}" type="button"
                class="group inline-flex h-9 items-center gap-1.5 rounded-r-xl rounded-l-none bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 transition-all duration-150 ease-out hover:bg-emerald-200/60 hover:text-emerald-900 active:bg-emerald-200/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200">
                <i class="fas fa-list-ul text-[10px] text-emerald-700"></i>
                {{ $viewAllText }}
            </button>
        </div>
    </div>
</div>

