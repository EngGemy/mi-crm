<x-filament-widgets::widget>
<x-filament::section>
    <x-slot name="heading" class="flex items-center gap-2">
        <x-heroicon-o-bell class="w-5 h-5 text-orange-500" />
        <span>تذكيراتي المستحقة</span>
    </x-slot>

    @php $reminders = $this->getReminders(); @endphp

    @if($reminders->isEmpty())
        <p class="text-sm text-gray-400 py-2">لا توجد تذكيرات مستحقة</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-800 -mx-4">
            @foreach($reminders as $r)
            <div class="flex items-center justify-between px-4 py-2.5">
                <div>
                    <p class="text-sm font-medium">{{ $r->title }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $r->lead?->name }} | {{ $r->remind_at?->format('Y-m-d H:i') }}
                        @if($r->remind_at?->isPast())
                            <span class="text-red-400"> — متأخر</span>
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="snooze({{ $r->id }})" class="text-xs text-orange-600 border border-orange-200 rounded px-2 py-0.5">تأجيل</button>
                    <button wire:click="complete({{ $r->id }})" class="text-xs text-green-600 border border-green-200 rounded px-2 py-0.5">تم</button>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</x-filament::section>
</x-filament-widgets::widget>
