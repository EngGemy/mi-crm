<x-filament-panels::page>
<div dir="rtl" class="space-y-6">

    {{-- Filters --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'daily' => 'اليوم',
                    'yesterday' => 'أمس',
                    'weekly' => 'هذا الأسبوع',
                    'monthly' => 'هذا الشهر',
                ] as $key => $label)
                    <button
                        wire:click="applyMode('{{ $key }}')"
                        type="button"
                        @class([
                            'px-4 py-2 rounded-xl text-sm font-semibold transition',
                            'bg-primary-600 text-white shadow' => $mode === $key,
                            'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' => $mode !== $key,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">من</label>
                <input wire:model.live="dateFrom" type="date"
                    class="rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">إلى</label>
                <input wire:model.live="dateTo" type="date"
                    class="rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            @if(count($userOptions) > 1)
            <div class="min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 mb-1">المندوب</label>
                <select wire:model.live="userId"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach($userOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex gap-2 mr-auto">
                <button wire:click="exportSummaryExcel" type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold shadow">
                    Excel ملخص
                </button>
                <button wire:click="exportExcel" type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 text-sm font-semibold shadow">
                    Excel تفصيلي
                </button>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">
            الفترة: <strong>{{ $dateFrom }}</strong> — <strong>{{ $dateTo }}</strong>
            · يظهر للمندوب تقريره فقط · المدير يرى الكل حسب الصلاحيات
        </p>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
        @php
            $cards = [
                ['مكالمات', $summary['calls'] ?? 0, 'bg-green-50 text-green-700 border-green-200'],
                ['واتساب', $summary['whatsapp'] ?? 0, 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                ['زيارات', $summary['visits'] ?? 0, 'bg-violet-50 text-violet-700 border-violet-200'],
                ['اجتماعات', $summary['meetings'] ?? 0, 'bg-blue-50 text-blue-700 border-blue-200'],
                ['إجمالي النشاط', $summary['total_activities'] ?? 0, 'bg-slate-50 text-slate-700 border-slate-200'],
                ['دقائق', $summary['duration_minutes'] ?? 0, 'bg-amber-50 text-amber-700 border-amber-200'],
                ['عملاء جدد', $summary['new_leads'] ?? 0, 'bg-sky-50 text-sky-700 border-sky-200'],
                ['صفقات', $summary['won'] ?? 0, 'bg-rose-50 text-rose-700 border-rose-200'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $cls])
            <div class="rounded-2xl border {{ $cls }} p-4 text-center shadow-sm">
                <div class="text-2xl font-black">{{ number_format((int) $value) }}</div>
                <div class="text-xs font-semibold mt-1 opacity-80">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    {{-- Per-rep table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">أداء المناديب في الفترة</h3>
            <span class="text-xs text-gray-400">{{ count($reps) }} مندوب</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-500">
                        <th class="px-4 py-3 text-right">المندوب</th>
                        <th class="px-3 py-3 text-center">مكالمات</th>
                        <th class="px-3 py-3 text-center">واتساب</th>
                        <th class="px-3 py-3 text-center">زيارات</th>
                        <th class="px-3 py-3 text-center">اجتماعات</th>
                        <th class="px-3 py-3 text-center">النشاط</th>
                        <th class="px-3 py-3 text-center">دقائق</th>
                        <th class="px-3 py-3 text-center">مكتمل</th>
                        <th class="px-3 py-3 text-center">معلق</th>
                        <th class="px-3 py-3 text-center">عملاء جدد</th>
                        <th class="px-3 py-3 text-center">صفقات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($reps as $rep)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $rep['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $rep['email'] }}</div>
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-green-600">{{ $rep['calls'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-emerald-600">{{ $rep['whatsapp'] }}</td>
                            <td class="px-3 py-3 text-center">{{ $rep['visits'] }}</td>
                            <td class="px-3 py-3 text-center">{{ $rep['meetings'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-primary-600">{{ $rep['total_activities'] }}</td>
                            <td class="px-3 py-3 text-center font-mono text-xs">{{ $rep['duration_minutes'] }}</td>
                            <td class="px-3 py-3 text-center text-green-600">{{ $rep['completed'] }}</td>
                            <td class="px-3 py-3 text-center">
                                @if($rep['pending'] > 0)
                                    <span class="inline-flex rounded-full bg-red-100 text-red-700 px-2 py-0.5 text-xs font-bold">{{ $rep['pending'] }}</span>
                                @else
                                    <span class="text-green-500">✓</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">{{ $rep['new_leads'] }}</td>
                            <td class="px-3 py-3 text-center font-bold text-rose-600">{{ $rep['won'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-gray-400">لا توجد بيانات في هذه الفترة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detailed work log --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-gray-800 dark:text-gray-100">سجل العمل والمكالمات</h3>
                <p class="text-xs text-gray-400 mt-0.5">كل ما سجّله المندوب من مكالمات وواتساب وزيارات وملاحظات</p>
            </div>
            <span class="text-xs rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-gray-500">{{ count($activities) }} سجل</span>
        </div>
        <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800 z-10">
                    <tr class="text-xs text-gray-500">
                        <th class="px-4 py-3 text-right">الوقت</th>
                        <th class="px-3 py-3 text-right">المندوب</th>
                        <th class="px-3 py-3 text-center">النوع</th>
                        <th class="px-3 py-3 text-right">الموضوع / العميل</th>
                        <th class="px-3 py-3 text-center">النتيجة</th>
                        <th class="px-3 py-3 text-center">المدة</th>
                        <th class="px-3 py-3 text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($activities as $row)
                        @php
                            $typeColors = [
                                'call' => 'bg-green-100 text-green-700',
                                'whatsapp' => 'bg-emerald-100 text-emerald-700',
                                'visit' => 'bg-violet-100 text-violet-700',
                                'meeting' => 'bg-blue-100 text-blue-700',
                                'note' => 'bg-gray-100 text-gray-600',
                            ];
                            $cls = $typeColors[$row['type']] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30 align-top">
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-500">{{ $row['created_at'] }}</td>
                            <td class="px-3 py-3 font-medium">{{ $row['user_name'] }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $cls }}">{{ $row['type_label'] }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $row['subject'] ?: '—' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $row['lead_name'] ?: 'بدون عميل' }}
                                    @if($row['lead_phone'])
                                        · <span dir="ltr">{{ $row['lead_phone'] }}</span>
                                    @endif
                                </div>
                                @if($row['description'])
                                    <div class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $row['description'] }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center text-xs">{{ $row['outcome_label'] }}</td>
                            <td class="px-3 py-3 text-center font-mono text-xs">
                                {{ $row['duration_minutes'] > 0 ? $row['duration_minutes'].' د' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($row['is_completed'])
                                    <span class="text-green-600 text-xs font-bold">مكتمل</span>
                                @else
                                    <span class="text-amber-600 text-xs font-bold">جاري</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                                لا يوجد سجل عمل في هذه الفترة — سجّل مكالمة أو نشاط من «مهامي اليوم» أو صفحة الـ Lead
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-filament-panels::page>
