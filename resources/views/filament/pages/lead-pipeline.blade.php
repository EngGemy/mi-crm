<x-filament-panels::page>
<div dir="rtl" x-data="kanban()" class="flex flex-col gap-4 h-full">

    {{-- فلاتر --}}
    <div class="flex flex-wrap gap-2 items-center bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 shadow-sm">
        <x-heroicon-o-funnel class="w-4 h-4 text-gray-400 shrink-0" />
        <span class="text-xs text-gray-400 font-medium ml-1">تصفية:</span>

        <select wire:model.live="filterUser"
            class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">كل المناديب</option>
            @foreach ($this->getSalesReps() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterSource"
            class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">كل المصادر</option>
            @foreach (\App\Models\Lead::SOURCES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterPriority"
            class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">كل الأولويات</option>
            @foreach (\App\Models\Lead::PRIORITIES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <div class="mr-auto">
            <span class="text-xs text-gray-400">{{ collect($columns)->sum('count') }} عميل محتمل</span>
        </div>
    </div>

    {{-- Kanban board --}}
    @php
    $topBar = [
        'gray'    => 'bg-gray-400',
        'info'    => 'bg-blue-400',
        'warning' => 'bg-amber-400',
        'primary' => 'bg-primary-500',
        'success' => 'bg-green-500',
        'danger'  => 'bg-red-400',
    ];
    $colBg = [
        'gray'    => 'bg-gray-50/80 dark:bg-gray-800/40',
        'info'    => 'bg-blue-50/60 dark:bg-blue-950/20',
        'warning' => 'bg-amber-50/60 dark:bg-amber-950/20',
        'primary' => 'bg-primary-50/60 dark:bg-primary-950/20',
        'success' => 'bg-green-50/60 dark:bg-green-950/20',
        'danger'  => 'bg-red-50/60 dark:bg-red-950/20',
    ];
    $badge = [
        'gray'    => 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'info'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300',
        'success' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        'danger'  => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    ];
    @endphp

    <div class="flex gap-3 overflow-x-auto pb-4" style="min-height: calc(100vh - 220px);">
        @foreach ($columns as $status => $col)
        @php $c = $col['color']; @endphp
        <div
            class="flex-shrink-0 w-72 flex flex-col rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm"
            @dragover.prevent="onDragOver($event)"
            @drop="onDrop($event, '{{ $status }}')"
            wire:key="col-{{ $status }}"
        >
            {{-- Color top bar --}}
            <div class="h-1 {{ $topBar[$c] ?? 'bg-gray-300' }}"></div>

            {{-- Column header --}}
            <div class="px-3 py-2.5 flex items-center justify-between bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $col['label'] }}</span>
                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $badge[$c] ?? '' }}">{{ $col['count'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($col['total_budget'] > 0)
                    <span class="text-xs font-mono text-gray-400">{{ number_format($col['total_budget']/1000,0) }}K</span>
                    @endif
                    <button
                        wire:click="openCreateModal('{{ $status }}')"
                        class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 hover:bg-primary-100 dark:bg-gray-800 dark:hover:bg-primary-900/40 text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 transition-colors"
                        title="إضافة عميل في {{ $col['label'] }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Cards container --}}
            <div
                class="flex-1 {{ $colBg[$c] ?? 'bg-gray-50' }} p-2 space-y-2 overflow-y-auto"
                style="min-height:100px; max-height:calc(100vh - 280px);"
                @dragover.prevent
                @drop.prevent="onDrop($event, '{{ $status }}')"
            >
                @foreach ($col['leads'] as $lead)
                <div
                    draggable="true"
                    @dragstart="onDragStart($event, {{ $lead['id'] }})"
                    @dragend="onDragEnd($event)"
                    wire:key="card-{{ $lead['id'] }}"
                    class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-grab active:cursor-grabbing shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all select-none group"
                    :class="dragging === {{ $lead['id'] }} ? 'opacity-40 scale-95 rotate-1' : ''"
                >
                    {{-- Name + priority --}}
                    <div class="flex items-start justify-between gap-1.5 mb-1">
                        <a href="{{ $lead['edit_url'] }}"
                            class="font-semibold text-sm text-gray-800 dark:text-gray-100 hover:text-primary-600 leading-snug line-clamp-2 flex-1"
                            onclick="event.stopPropagation()">
                            {{ $lead['name'] }}
                        </a>
                        @if($lead['priority'] === 'urgent')
                        <span class="shrink-0 text-xs bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.5 rounded-full font-medium">عاجل</span>
                        @elseif($lead['priority'] === 'high')
                        <span class="shrink-0 text-xs bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400 px-1.5 py-0.5 rounded-full font-medium">عالية</span>
                        @endif
                    </div>

                    @if($lead['company'])
                    <p class="text-xs text-gray-400 mb-2 truncate flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ $lead['company'] }}
                    </p>
                    @endif

                    <div class="flex items-center justify-between text-xs mt-2">
                        @if($lead['estimated_budget'])
                        <span class="font-mono font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ number_format($lead['estimated_budget']/1000,0) }}K ج.م</span>
                        @else<span></span>@endif

                        @if($lead['days_since_contact'] < 999)
                        <span class="flex items-center gap-0.5 font-medium
                            @if($lead['days_since_contact'] >= 14) text-red-500
                            @elseif($lead['days_since_contact'] >= 7) text-amber-500
                            @else text-gray-400 @endif" title="أيام منذ آخر تواصل">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $lead['days_since_contact'] }}ي
                        </span>
                        @endif
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 mt-2 pt-2 flex items-center justify-between gap-2">
                        @if($lead['assignee'])
                        <span class="text-xs text-gray-400 truncate flex items-center gap-1 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="truncate">{{ $lead['assignee'] }}</span>
                        </span>
                        @else<span></span>@endif

                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($lead['whatsapp'])
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead['whatsapp']) }}"
                                target="_blank" class="text-green-400 hover:text-green-500 transition-colors"
                                onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            @endif
                            <a href="{{ $lead['edit_url'] }}"
                                class="text-gray-300 dark:text-gray-600 hover:text-primary-500 opacity-0 group-hover:opacity-100 transition-all"
                                onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>
                    </div>

                    @if($lead['score'])
                    <div class="mt-2">
                        <div class="h-1 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $lead['score'] >= 70 ? 'bg-green-400' : ($lead['score'] >= 40 ? 'bg-amber-400' : 'bg-red-400') }}" style="width:{{ $lead['score'] }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach

                @if(empty($col['leads']))
                <div class="flex flex-col items-center justify-center py-8 gap-2 text-gray-300 dark:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <span class="text-xs">لا يوجد</span>
                </div>
                @endif

                {{-- Bottom add button --}}
                <button
                    wire:click="openCreateModal('{{ $status }}')"
                    class="flex items-center gap-1.5 w-full px-2 py-1.5 text-xs text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-white dark:hover:bg-gray-900/50 rounded-lg transition-all group/add"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover/add:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    إضافة عميل
                </button>
            </div>
        </div>
        @endforeach
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════
     Create Lead Modal  — teleported to <body> to bypass Filament transforms
═══════════════════════════════════════════════════════════ --}}
@if($showModal)
@php
$sc = match($modalStatus) {
    'contacted'   => ['bar'=>'bg-blue-500',    'badge'=>'bg-blue-100 text-blue-700',    'dot'=>'bg-blue-500'],
    'qualified'   => ['bar'=>'bg-amber-500',   'badge'=>'bg-amber-100 text-amber-700',  'dot'=>'bg-amber-500'],
    'opportunity' => ['bar'=>'bg-violet-500',  'badge'=>'bg-violet-100 text-violet-700','dot'=>'bg-violet-500'],
    'won'         => ['bar'=>'bg-green-500',   'badge'=>'bg-green-100 text-green-700',  'dot'=>'bg-green-500'],
    'lost'        => ['bar'=>'bg-red-500',     'badge'=>'bg-red-100 text-red-700',      'dot'=>'bg-red-500'],
    default       => ['bar'=>'bg-gray-400',    'badge'=>'bg-gray-100 text-gray-600',    'dot'=>'bg-gray-400'],
};
// Inline styles avoid Tailwind purge of dynamic class names
$priorities = [
    'low'    => ['label'=>'منخفضة', 'bg'=>'#475569', 'dotColor'=>'#94a3b8'],
    'medium' => ['label'=>'متوسطة', 'bg'=>'#2563eb', 'dotColor'=>'#93c5fd'],
    'high'   => ['label'=>'عالية',  'bg'=>'#f97316', 'dotColor'=>'#fed7aa'],
    'urgent' => ['label'=>'عاجلة',  'bg'=>'#dc2626', 'dotColor'=>'#fca5a5'],
];
@endphp
<div
    x-data
    x-teleport="body"
>
<div
    class="fixed inset-0 flex items-center justify-center p-4"
    style="z-index:99999; font-family: inherit;"
    dir="rtl"
    x-on:keydown.escape.window="$wire.closeModal()"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-gray-950/80"
        style="backdrop-filter: blur(4px);"
        x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        wire:click="closeModal"
    ></div>

    {{-- Modal shell --}}
    <div
        class="relative z-10 w-full bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden"
        style="max-width:860px; max-height:90vh;"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Colour bar --}}
        <div class="h-1 w-full {{ $sc['bar'] }} shrink-0"></div>

        {{-- ── Header ─────────────────────────────── --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-bold text-gray-800 dark:text-gray-100 leading-tight">إضافة عميل محتمل جديد</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full {{ $sc['dot'] }} shrink-0"></span>
                    <span class="text-xs font-semibold {{ $sc['badge'] }} px-2 py-0.5 rounded-full">{{ \App\Models\Lead::STATUSES[$modalStatus] }}</span>
                </div>
            </div>
            <button type="button" wire:click="closeModal"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- ── Body: two panels ───────────────────── --}}
        <div class="flex flex-row overflow-hidden" style="flex:1; min-height:0;">

            {{-- LEFT: form (scrollable) --}}
            <div class="overflow-y-auto px-6 py-5" style="flex:1; min-width:0;">

                {{-- Name --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">اسم العميل <span class="text-red-500">*</span></label>
                    <input wire:model="newName" type="text" placeholder="الاسم الكامل..."
                        class="w-full h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100 outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all placeholder-gray-300">
                    @error('newName')
                    <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Phone + WhatsApp --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">الهاتف</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            </span>
                            <input wire:model="newPhone" type="tel" placeholder="01xxxxxxxxx"
                                class="w-full h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-9 pl-3 text-sm outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all placeholder-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">واتساب</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#25D366]">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </span>
                            <input wire:model="newWhatsapp" type="tel" placeholder="اتركه فارغاً إذا نفس الهاتف"
                                class="w-full h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-9 pl-3 text-sm outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all placeholder-gray-300">
                        </div>
                    </div>
                </div>

                {{-- Company + Budget --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">الشركة / المزرعة</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/></svg>
                            </span>
                            <input wire:model="newCompany" type="text" placeholder="اسم المنشأة..."
                                class="w-full h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-9 pl-3 text-sm outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all placeholder-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">الميزانية التقديرية</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs font-semibold text-gray-300">ج.م</span>
                            <input wire:model="newEstimatedBudget" type="number" min="0" placeholder="0"
                                class="w-full h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-10 pl-3 text-sm outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all placeholder-gray-300">
                        </div>
                    </div>
                </div>

                {{-- Source + Assigned --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">مصدر العميل</label>
                        <div class="relative">
                            <select wire:model="newSource"
                                class="w-full h-10 appearance-none rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-3 pl-8 text-sm text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all cursor-pointer">
                                @foreach(\App\Models\Lead::SOURCES as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">تعيين إلى</label>
                        <div class="relative">
                            <select wire:model="newAssignedTo"
                                class="w-full h-10 appearance-none rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pr-3 pl-8 text-sm text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all cursor-pointer">
                                <option value="">أنا ({{ Auth::user()->name }})</option>
                                @foreach($this->getSalesReps() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Priority --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">الأولوية</label>
                    <div class="flex gap-2">
                        @foreach($priorities as $k => $p)
                        @php $isActive = $newPriority === $k; @endphp
                        <button type="button" wire:click="$set('newPriority','{{ $k }}')"
                            class="flex-1 flex items-center justify-center gap-1.5 h-9 rounded-lg text-xs font-semibold border transition-all"
                            style="{{ $isActive ? 'background:'.$p['bg'].';color:#fff;border-color:transparent;box-shadow:0 1px 3px rgba(0,0,0,.2);' : 'background:#fff;border-color:#e5e7eb;color:#9ca3af;' }}">
                            <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $isActive ? 'rgba(255,255,255,0.6)' : $p['dotColor'] }}"></span>
                            {{ $p['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">ملاحظات</label>
                    <textarea wire:model="newNotes" rows="3" placeholder="أي تفاصيل إضافية عن العميل..."
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary-400/40 focus:border-primary-400 transition-all resize-none placeholder-gray-300"></textarea>
                </div>
            </div>

            {{-- RIGHT: task panel --}}
            <div class="flex flex-col bg-gray-50 dark:bg-gray-800/50 border-r border-gray-100 dark:border-gray-800" style="width:272px; min-width:272px;">

                {{-- Panel header --}}
                <div class="flex items-center gap-2 px-4 py-3.5 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <svg class="w-4 h-4 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">قائمة المهام</span>
                    @if(count($modalTasks) > 0)
                    <span class="mr-auto text-xs bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400 px-1.5 py-0.5 rounded-full font-bold tabular-nums">{{ count($modalTasks) }}</span>
                    @endif
                </div>

                {{-- Task input --}}
                <div class="px-3 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="flex items-center h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden focus-within:ring-2 focus-within:ring-primary-400/40 focus-within:border-primary-400 transition-all">
                        <input
                            wire:model="newTaskInput"
                            wire:keydown.enter.prevent="addModalTask"
                            type="text"
                            placeholder="اكتب مهمة واضغط ↵"
                            class="flex-1 h-full px-3 text-sm bg-transparent outline-none text-gray-700 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-600 min-w-0"
                        >
                        <button type="button" wire:click="addModalTask"
                            class="w-9 h-9 flex items-center justify-center text-gray-300 hover:text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors shrink-0 border-r border-gray-100 dark:border-gray-700">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Task list --}}
                <div class="overflow-y-auto" style="flex:1;">
                    @forelse($modalTasks as $i => $task)
                    <div class="flex items-start gap-2.5 px-3 py-2.5 group/t hover:bg-white dark:hover:bg-gray-800 transition-colors border-b border-gray-100/70 dark:border-gray-700/50">
                        <button type="button" wire:click="toggleModalTask({{ $i }})"
                            class="mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 transition-all
                                {{ $task['done'] ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400' }}">
                            @if($task['done'])
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </button>
                        <span class="flex-1 text-sm leading-relaxed {{ $task['done'] ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $task['text'] }}</span>
                        <button type="button" wire:click="removeModalTask({{ $i }})"
                            class="w-5 h-5 mt-0.5 flex items-center justify-center text-gray-300 hover:text-red-500 opacity-0 group-hover/t:opacity-100 transition-all shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center gap-3 py-10 px-4 text-center text-gray-300 dark:text-gray-600">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-xs leading-relaxed">لا توجد مهام بعد<br><span class="text-gray-200 dark:text-gray-700">اكتب مهمة أعلاه واضغط ↵</span></p>
                    </div>
                    @endforelse
                </div>

                @if(count($modalTasks) > 0)
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2 text-xs shrink-0">
                    <svg class="w-3 h-3 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-green-600 dark:text-green-400 font-medium">{{ count(array_filter($modalTasks, fn($t)=>$t['done'])) }} مكتملة</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-gray-400 font-medium">{{ count(array_filter($modalTasks, fn($t)=>!$t['done'])) }} معلّقة</span>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Footer ──────────────────────────────── --}}
        <div class="flex items-center gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 shrink-0">
            <button
                type="button"
                wire:click="saveLead"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-wait"
                class="inline-flex items-center gap-2 h-10 px-6 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                <span wire:loading.remove wire:target="saveLead">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span wire:loading wire:target="saveLead">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </span>
                <span wire:loading.remove wire:target="saveLead">حفظ العميل</span>
                <span wire:loading wire:target="saveLead">جارٍ الحفظ...</span>
            </button>
            <button type="button" wire:click="closeModal"
                class="h-10 px-5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                إلغاء
            </button>
            @if(count($modalTasks) > 0)
            <span class="mr-auto text-xs text-gray-400">{{ count($modalTasks) }} {{ count($modalTasks) === 1 ? 'مهمة ستُحفظ' : 'مهام ستُحفظ' }} مع العميل</span>
            @endif
        </div>
    </div>
</div>
</div>
@endif

<script>
function kanban() {
    return {
        dragging: null,
        onDragStart(event, leadId) {
            this.dragging = leadId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', leadId);
        },
        onDragOver(event) { event.dataTransfer.dropEffect = 'move'; },
        onDrop(event, status) {
            const leadId = parseInt(event.dataTransfer.getData('text/plain'));
            if (leadId && this.dragging === leadId) {
                @this.moveCard(leadId, status);
            }
            this.dragging = null;
        },
        onDragEnd() { this.dragging = null; },
    }
}
</script>
</x-filament-panels::page>
