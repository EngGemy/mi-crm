<x-filament-panels::page>
<div dir="rtl" x-data="kanban()" class="space-y-4">

    {{-- فلاتر --}}
    <div class="flex flex-wrap gap-3 items-center bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
        <x-heroicon-o-funnel class="w-5 h-5 text-gray-400" />

        <select wire:model.live="filterUser" class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5">
            <option value="">كل المناديب</option>
            @foreach ($this->getSalesReps() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterSource" class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5">
            <option value="">كل المصادر</option>
            @foreach (\App\Models\Lead::SOURCES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterPriority" class="text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5">
            <option value="">كل الأولويات</option>
            @foreach (\App\Models\Lead::PRIORITIES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- لوحة Kanban --}}
    <div class="flex gap-3 overflow-x-auto pb-4 min-h-[70vh]">
        @foreach ($columns as $status => $col)
        <div
            class="flex-shrink-0 w-72 flex flex-col"
            @dragover.prevent="onDragOver($event)"
            @drop="onDrop($event, '{{ $status }}')"
            wire:key="col-{{ $status }}"
        >
            {{-- رأس العمود --}}
            <div class="rounded-t-xl px-3 py-2.5 flex items-center justify-between
                @if($col['color'] === 'gray') bg-gray-100 dark:bg-gray-800
                @elseif($col['color'] === 'info') bg-blue-50 dark:bg-blue-950/30
                @elseif($col['color'] === 'warning') bg-yellow-50 dark:bg-yellow-950/30
                @elseif($col['color'] === 'primary') bg-primary-50 dark:bg-primary-950/30
                @elseif($col['color'] === 'success') bg-green-50 dark:bg-green-950/30
                @elseif($col['color'] === 'danger') bg-red-50 dark:bg-red-950/30
                @endif
            ">
                <div>
                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $col['label'] }}</span>
                    <span class="mr-2 text-xs text-gray-400">({{ $col['count'] }})</span>
                </div>
                @if($col['total_budget'] > 0)
                <span class="text-xs font-mono text-gray-500">{{ number_format($col['total_budget'] / 1000, 0) }}K</span>
                @endif
            </div>

            {{-- البطاقات --}}
            <div
                class="flex-1 rounded-b-xl bg-gray-50 dark:bg-gray-800/50 p-2 space-y-2 min-h-32 border border-t-0 border-gray-200 dark:border-gray-700"
                @dragover.prevent
                @drop.prevent="onDrop($event, '{{ $status }}')"
            >
                @foreach ($col['leads'] as $lead)
                <div
                    draggable="true"
                    @dragstart="onDragStart($event, {{ $lead['id'] }})"
                    @dragend="onDragEnd($event)"
                    wire:key="card-{{ $lead['id'] }}"
                    class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-grab active:cursor-grabbing shadow-sm hover:shadow-md transition-shadow select-none"
                    :class="dragging === {{ $lead['id'] }} ? 'opacity-50' : ''"
                >
                    {{-- اسم العميل --}}
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ $lead['edit_url'] }}" class="font-semibold text-sm text-gray-800 dark:text-gray-100 hover:text-primary-600 truncate">
                            {{ $lead['name'] }}
                        </a>
                        @if($lead['priority'] === 'urgent')
                        <span class="text-red-500 shrink-0" title="عاجل">🔴</span>
                        @elseif($lead['priority'] === 'high')
                        <span class="text-orange-500 shrink-0" title="عالية">🟠</span>
                        @endif
                    </div>

                    @if($lead['company'])
                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $lead['company'] }}</p>
                    @endif

                    {{-- تفاصيل --}}
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                        @if($lead['estimated_budget'])
                        <span class="font-mono">{{ number_format($lead['estimated_budget'] / 1000, 0) }}K ج.م</span>
                        @else
                        <span></span>
                        @endif

                        <span class="flex items-center gap-1
                            @if($lead['days_since_contact'] >= 14) text-red-500
                            @elseif($lead['days_since_contact'] >= 7) text-orange-500
                            @else text-gray-400 @endif
                        " title="أيام منذ آخر تواصل">
                            @if($lead['days_since_contact'] < 999)
                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                            {{ $lead['days_since_contact'] }}ي
                            @endif
                        </span>
                    </div>

                    {{-- المندوب + واتساب --}}
                    <div class="mt-2 flex items-center justify-between">
                        @if($lead['assignee'])
                        <span class="text-xs text-gray-400 truncate max-w-[100px]">{{ $lead['assignee'] }}</span>
                        @endif

                        @if($lead['whatsapp'])
                        <a
                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead['whatsapp']) }}"
                            target="_blank"
                            class="text-green-500 hover:text-green-600"
                            onclick="event.stopPropagation()"
                            title="واتساب"
                        >
                            <x-heroicon-s-chat-bubble-left-ellipsis class="w-4 h-4" />
                        </a>
                        @endif
                    </div>

                    {{-- Score bar --}}
                    @if($lead['score'])
                    <div class="mt-2">
                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div
                                class="h-full rounded-full @if($lead['score'] >= 70) bg-green-400 @elseif($lead['score'] >= 40) bg-yellow-400 @else bg-red-400 @endif"
                                style="width: {{ $lead['score'] }}%"
                            ></div>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach

                @if(empty($col['leads']))
                <div class="text-center text-gray-300 dark:text-gray-600 text-xs py-8">
                    لا يوجد
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

</div>

<script>
function kanban() {
    return {
        dragging: null,
        onDragStart(event, leadId) {
            this.dragging = leadId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', leadId);
        },
        onDragOver(event) {
            event.dataTransfer.dropEffect = 'move';
        },
        onDrop(event, status) {
            const leadId = parseInt(event.dataTransfer.getData('text/plain'));
            if (leadId && this.dragging === leadId) {
                @this.moveCard(leadId, status);
            }
            this.dragging = null;
        },
        onDragEnd(event) {
            this.dragging = null;
        },
    }
}
</script>
</x-filament-panels::page>
