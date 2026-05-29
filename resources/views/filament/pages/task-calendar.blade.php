<x-filament-panels::page>
<div dir="rtl" class="space-y-4"
     x-data="taskCalendar(@js($events), @js($currentMonth))"
     wire:ignore.self>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3 shadow-sm">

        {{-- Navigation --}}
        <div class="flex items-center gap-1">
            <button wire:click="prevMonth" type="button"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </button>
            <button wire:click="goToday" type="button"
                class="px-3 h-8 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-gray-200 dark:border-gray-700">
                اليوم
            </button>
            <button wire:click="nextMonth" type="button"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>

        {{-- Month title --}}
        <h2 class="text-base font-bold text-gray-800 dark:text-white" x-text="monthLabel"></h2>

        {{-- User filter (admin only) --}}
        @if(auth()->user()?->hasAnyRole(['super_admin','admin','sales_manager']))
        <select wire:model.live="filterUser"
            class="mr-auto text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">كل المناديب</option>
            @foreach($this->getSalesReps() as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        @endif

        {{-- Add task button --}}
        <button wire:click="openTaskModal()" type="button"
            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-sm transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            إضافة مهمة
        </button>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-3 text-xs">
        @foreach(\App\Models\LeadActivity::TYPE_COLORS as $type => $color)
        @if(isset(\App\Models\LeadActivity::TYPES[$type]))
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full" style="background:{{ $color }}"></span>
            {{ \App\Models\LeadActivity::TYPES[$type] }}
        </span>
        @endif
        @endforeach
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
            مكتملة
        </span>
    </div>

    {{-- Calendar grid --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        {{-- Days header --}}
        <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-800">
            @foreach(['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'] as $d)
            <div class="py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 border-l first:border-l-0 border-gray-100 dark:border-gray-800">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Weeks --}}
        <div class="grid grid-cols-7" style="min-height:520px;" x-ref="calGrid">
            <template x-for="(day, idx) in calDays" :key="idx">
                <div class="border-l border-b border-gray-100 dark:border-gray-800 p-1.5 min-h-[110px] relative transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-800/20"
                     :class="{'col-start-1': idx === 0 && day === null}"
                     @click="day && openDayModal(day.date)"
                     :style="day === null ? 'pointer-events:none' : 'cursor:pointer'"
                >
                    <template x-if="day !== null">
                        <div class="flex flex-col h-full">
                            {{-- Day number --}}
                            <span class="text-xs font-semibold mb-1 w-6 h-6 flex items-center justify-center rounded-full self-end"
                                  :class="{
                                    'bg-primary-600 text-white': day.isToday,
                                    'text-gray-700 dark:text-gray-300': !day.isToday
                                  }"
                                  x-text="day.num">
                            </span>

                            {{-- Events --}}
                            <div class="space-y-0.5 flex-1 overflow-hidden">
                                <template x-for="ev in day.events" :key="ev.id">
                                    <div class="text-white text-[10px] leading-tight px-1.5 py-0.5 rounded truncate cursor-pointer"
                                         :style="`background:${ev.backgroundColor}`"
                                         :class="{'opacity-50 line-through': ev.extendedProps.is_completed}"
                                         @click.stop="selectEvent(ev)"
                                         :title="ev.title">
                                        <span x-text="ev.title"></span>
                                    </div>
                                </template>
                                <template x-if="day.overflow > 0">
                                    <div class="text-xs text-gray-400 pr-1">+<span x-text="day.overflow"></span> أخرى</div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Event detail panel --}}
    <div x-show="selectedEvent !== null" x-cloak
         class="fixed bottom-6 left-6 z-50 w-72 bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
         @click.outside="selectedEvent = null">
        <template x-if="selectedEvent !== null">
            <div>
                <div class="h-1.5 w-full" :style="`background:${selectedEvent.backgroundColor}`"></div>
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-100 leading-snug" x-text="selectedEvent.title"></h4>
                        <button @click="selectedEvent = null" class="text-gray-400 hover:text-gray-600 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-1.5 text-xs text-gray-500">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">النوع:</span> <span x-text="selectedEvent.extendedProps.type_label"></span></p>
                        <p x-show="selectedEvent.extendedProps.assignee"><span class="font-medium text-gray-700 dark:text-gray-300">المندوب:</span> <span x-text="selectedEvent.extendedProps.assignee"></span></p>
                        <p x-show="selectedEvent.extendedProps.description"><span x-text="selectedEvent.extendedProps.description"></span></p>
                        <p>
                            <span class="font-medium">الحالة:</span>
                            <span x-show="selectedEvent.extendedProps.is_completed" class="text-green-600">✓ مكتملة</span>
                            <span x-show="!selectedEvent.extendedProps.is_completed" class="text-amber-500">معلقة</span>
                        </p>
                    </div>
                    <div x-show="!selectedEvent.extendedProps.is_completed" class="mt-3">
                        <button @click="$wire.completeTask(selectedEvent.id).then(() => { selectedEvent = null })"
                            class="w-full text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg px-3 py-2 transition-colors flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            تحديد كمكتملة
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

</div>

{{-- Add Task Modal --}}
@if($showTaskModal)
<template x-teleport="body">
<div x-data class="fixed inset-0 overflow-y-auto" style="z-index:99999;font-family:inherit;" dir="rtl"
     x-on:keydown.escape.window="$wire.closeTaskModal()">
    <div class="fixed inset-0 bg-black/50" wire:click="closeTaskModal"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700" style="max-width:560px;">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary-50 dark:bg-primary-950/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">إضافة مهمة / موعد</h2>
                </div>
                <button wire:click="closeTaskModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4">

                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">نوع المهمة</label>
                    <div class="grid grid-cols-3 gap-2">
                        @php
                            $taskTypes = ['call'=>['label'=>'مكالمة','color'=>'#22c55e'],'whatsapp'=>['label'=>'واتساب','color'=>'#25D366'],'meeting'=>['label'=>'اجتماع','color'=>'#3b82f6'],'visit'=>['label'=>'زيارة','color'=>'#8b5cf6'],'email'=>['label'=>'إيميل','color'=>'#f97316'],'note'=>['label'=>'ملاحظة','color'=>'#6b7280']];
                        @endphp
                        @foreach($taskTypes as $k => $meta)
                        <button type="button"
                            wire:click="$set('taskType','{{ $k }}')"
                            class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-medium border transition-all"
                            style="{{ $taskType === $k ? 'background:'.$meta['color'].';color:#fff;border-color:transparent;' : 'background:transparent;border-color:#e5e7eb;color:#6b7280;' }}">
                            <span class="w-2 h-2 rounded-full" style="background:{{ $taskType === $k ? 'rgba(255,255,255,.5)' : $meta['color'] }}"></span>
                            {{ $meta['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">الموضوع <span class="text-red-500">*</span></label>
                    <input wire:model="taskSubject" type="text" placeholder="وصف المهمة..."
                        class="block w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white shadow-sm placeholder-gray-400 outline-none focus:ring-2 focus:ring-inset focus:ring-primary-600 transition">
                    @error('taskSubject')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Lead + Assigned To --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">العميل المحتمل <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select wire:model="taskLeadId"
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 pr-3 pl-8 py-2 text-sm text-gray-950 dark:text-white shadow-sm outline-none focus:ring-2 focus:ring-primary-600 transition cursor-pointer">
                                <option value="">-- اختر --</option>
                                @foreach($this->getLeadsForSelect() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                        @error('taskLeadId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">تعيين إلى <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select wire:model="taskAssignedTo"
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 pr-3 pl-8 py-2 text-sm text-gray-950 dark:text-white shadow-sm outline-none focus:ring-2 focus:ring-primary-600 transition cursor-pointer">
                                <option value="">-- اختر --</option>
                                @foreach($this->getSalesReps() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                        @error('taskAssignedTo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- DateTime + Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">الوقت المجدول <span class="text-red-500">*</span></label>
                    <input wire:model="taskScheduledAt" type="datetime-local"
                        class="block w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white shadow-sm outline-none focus:ring-2 focus:ring-primary-600 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">ملاحظات</label>
                    <textarea wire:model="taskDescription" rows="2" placeholder="تفاصيل إضافية..."
                        class="block w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white shadow-sm placeholder-gray-400 outline-none focus:ring-2 focus:ring-primary-600 transition resize-none"></textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 rounded-b-xl">
                <button wire:click="saveTask" wire:loading.attr="disabled" wire:loading.class="opacity-70"
                    class="inline-flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition-colors">
                    <svg wire:loading.remove wire:target="saveTask" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg wire:loading wire:target="saveTask" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    حفظ المهمة
                </button>
                <button wire:click="closeTaskModal" type="button"
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 shadow-sm hover:bg-gray-50 dark:hover:bg-white/10 transition-colors">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>
</template>
@endif

<script>
function taskCalendar(events, currentMonth) {
    return {
        events,
        currentMonth,
        calDays: [],
        selectedEvent: null,

        get monthLabel() {
            const d = new Date(this.currentMonth + '-01');
            return d.toLocaleDateString('ar-EG', { month: 'long', year: 'numeric' });
        },

        init() {
            this.buildCal();
            this.$watch('$wire.events', v => { this.events = v; this.buildCal(); });
            this.$watch('$wire.currentMonth', v => { this.currentMonth = v; this.buildCal(); });
        },

        buildCal() {
            const [y, m] = this.currentMonth.split('-').map(Number);
            const firstDay = new Date(y, m - 1, 1).getDay(); // 0=Sun
            const daysInMonth = new Date(y, m, 0).getDate();
            const today = new Date().toISOString().slice(0, 10);

            const evByDate = {};
            this.events.forEach(ev => {
                const d = ev.start.slice(0, 10);
                (evByDate[d] = evByDate[d] || []).push(ev);
            });

            const days = [];
            for (let i = 0; i < firstDay; i++) days.push(null);
            for (let d = 1; d <= daysInMonth; d++) {
                const date = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const allEvs = evByDate[date] || [];
                days.push({
                    num: d,
                    date,
                    isToday: date === today,
                    events: allEvs.slice(0, 3),
                    overflow: Math.max(0, allEvs.length - 3),
                });
            }
            // pad to full weeks
            while (days.length % 7 !== 0) days.push(null);
            this.calDays = days;
        },

        openDayModal(date) {
            this.$wire.openTaskModal(date);
        },

        selectEvent(ev) {
            this.selectedEvent = ev;
        },
    }
}
</script>
</x-filament-panels::page>
