<x-filament-panels::page>
<div dir="rtl" class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- مهام اليوم --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- التذكيرات المستحقة --}}
            @if(count($dueReminders))
            <div class="rounded-xl border border-red-200 dark:border-red-800 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="bg-red-50 dark:bg-red-950/30 px-4 py-3 flex items-center gap-2">
                    <x-heroicon-s-bell class="w-5 h-5 text-red-500" />
                    <h3 class="font-semibold text-red-700 dark:text-red-300">تذكيرات مستحقة ({{ count($dueReminders) }})</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($dueReminders as $r)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="font-medium text-sm">{{ $r['title'] }}</p>
                            @if($r['lead_name'])
                            <p class="text-xs text-gray-400">{{ $r['lead_name'] }} | {{ $r['type_label'] }}</p>
                            @endif
                            <p class="text-xs text-red-400 mt-0.5">{{ $r['remind_at'] }}</p>
                        </div>
                        <button
                            wire:click="snoozeReminder({{ $r['id'] }})"
                            class="text-xs text-orange-600 hover:text-orange-800 border border-orange-200 rounded px-2 py-1"
                        >تأجيل ساعة</button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- مهام اليوم --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="bg-blue-50 dark:bg-blue-950/30 px-4 py-3 flex items-center gap-2">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-blue-500" />
                    <h3 class="font-semibold text-blue-700 dark:text-blue-300">
                        مهامي المعلّقة ({{ count($todayTasks) }})
                    </h3>
                </div>

                @if(count($todayTasks))
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($todayTasks as $task)
                    <div class="flex items-start justify-between px-4 py-3 {{ $task['is_overdue'] ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-lg">
                                @if($task['type'] === 'call') 📞
                                @elseif($task['type'] === 'meeting') 🤝
                                @elseif($task['type'] === 'visit') 🚗
                                @elseif($task['type'] === 'whatsapp') 💬
                                @else 📋
                                @endif
                            </span>
                            <div>
                                <p class="font-medium text-sm">{{ $task['subject'] ?? $task['type_label'] }}</p>
                                @if($task['lead_name'])
                                <a href="{{ $task['edit_url'] }}" class="text-xs text-primary-600 hover:underline">
                                    {{ $task['lead_name'] }}
                                </a>
                                @endif
                                @if($task['scheduled_at'])
                                <p class="text-xs {{ $task['is_overdue'] ? 'text-red-400' : 'text-gray-400' }}">
                                    {{ $task['is_overdue'] ? '⚠ متأخر — ' : '' }}{{ $task['scheduled_at'] }}
                                </p>
                                @endif
                            </div>
                        </div>
                        <button
                            wire:click="completeTask({{ $task['id'] }})"
                            class="shrink-0 text-xs text-green-600 hover:text-green-800 border border-green-200 rounded px-2 py-1"
                        >✓ تم</button>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-gray-400 py-10">
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2 opacity-30" />
                    <p>لا توجد مهام معلّقة — يوم منتج! 🎉</p>
                </div>
                @endif
            </div>

            {{-- مواعيد الأسبوع --}}
            @if(count($upcomingMeetings))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="bg-green-50 dark:bg-green-950/30 px-4 py-3 flex items-center gap-2">
                    <x-heroicon-o-calendar class="w-5 h-5 text-green-500" />
                    <h3 class="font-semibold text-green-700 dark:text-green-300">مواعيد هذا الأسبوع</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($upcomingMeetings as $meeting)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="w-14 text-center text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5">
                                {{ $meeting['day_label'] }}
                            </span>
                            <div>
                                <p class="text-sm font-medium">{{ $meeting['subject'] ?? $meeting['type_label'] }}</p>
                                @if($meeting['lead_name'])
                                <a href="{{ $meeting['edit_url'] }}" class="text-xs text-primary-600 hover:underline">{{ $meeting['lead_name'] }}</a>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">{{ $meeting['scheduled_at'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- التقويم --}}
        <div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
                <h3 class="font-semibold mb-3 text-center">{{ now()->translatedFormat('F Y') }}</h3>

                <div class="grid grid-cols-7 gap-0.5 text-xs text-center mb-1">
                    @foreach(['أح', 'إث', 'ثل', 'أر', 'خم', 'جم', 'سب'] as $d)
                    <div class="text-gray-400 font-medium py-1">{{ $d }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-0.5 text-xs text-center">
                    @foreach($calendarDays as $day)
                    @if($day === null)
                    <div></div>
                    @else
                    <div class="rounded py-1.5 relative
                        {{ $day['is_today'] ? 'bg-primary-600 text-white font-bold' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}
                    ">
                        {{ $day['day'] }}
                        @if($day['count'] > 0)
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-{{ $day['is_today'] ? 'white' : 'green-400' }}"></span>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>

                <p class="text-xs text-gray-400 text-center mt-3">النقطة الخضراء = لديك موعد</p>
            </div>
        </div>

    </div>

</div>
</x-filament-panels::page>
