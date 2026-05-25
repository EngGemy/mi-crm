<div class="space-y-4 text-sm" dir="rtl">

    {{-- معلومات عامة --}}
    <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div>
            <p class="text-xs text-gray-500 mb-1">المستخدم</p>
            <p class="font-medium">{{ $record->user_name ?? 'النظام' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">التاريخ</p>
            <p class="font-medium">{{ $record->created_at?->format('Y-m-d H:i:s') }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">عنوان IP</p>
            <p class="font-mono text-xs">{{ $record->ip_address ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">المتصفح</p>
            <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ Str::limit($record->user_agent, 60) ?? '—' }}</p>
        </div>
    </div>

    @if ($record->reason)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 p-3">
            <p class="text-xs text-yellow-700 dark:text-yellow-300 mb-1">السبب</p>
            <p>{{ $record->reason }}</p>
        </div>
    @endif

    {{-- القيم القديمة مقابل الجديدة --}}
    @if ($record->old_values || $record->new_values)
        <div class="grid grid-cols-1 gap-3 @if($record->old_values && $record->new_values) md:grid-cols-2 @endif">

            @if ($record->old_values)
                <div>
                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-2">◀ القيم السابقة</p>
                    <div class="rounded-lg border border-red-200 dark:border-red-800 overflow-hidden">
                        <table class="w-full text-xs">
                            @foreach ($record->old_values as $field => $value)
                                <tr class="border-b border-red-100 dark:border-red-900 last:border-0">
                                    <td class="px-3 py-1.5 font-mono text-gray-500 dark:text-gray-400 bg-red-50 dark:bg-red-950/30 w-1/3">{{ $field }}</td>
                                    <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 break-all">
                                        @if (is_array($value))
                                            <span class="text-gray-400 italic">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</span>
                                        @elseif ($value === null)
                                            <span class="text-gray-400 italic">null</span>
                                        @else
                                            {{ Str::limit((string) $value, 200) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif

            @if ($record->new_values)
                <div>
                    <p class="text-xs font-semibold text-green-600 dark:text-green-400 mb-2">▶ القيم الجديدة</p>
                    <div class="rounded-lg border border-green-200 dark:border-green-800 overflow-hidden">
                        <table class="w-full text-xs">
                            @foreach ($record->new_values as $field => $value)
                                <tr class="border-b border-green-100 dark:border-green-900 last:border-0">
                                    <td class="px-3 py-1.5 font-mono text-gray-500 dark:text-gray-400 bg-green-50 dark:bg-green-950/30 w-1/3">{{ $field }}</td>
                                    <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 break-all">
                                        @if (is_array($value))
                                            <span class="text-gray-400 italic">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</span>
                                        @elseif ($value === null)
                                            <span class="text-gray-400 italic">null</span>
                                        @else
                                            {{ Str::limit((string) $value, 200) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @else
        <p class="text-gray-400 italic text-center py-4">لا توجد بيانات قيم لهذا الحدث</p>
    @endif

</div>
