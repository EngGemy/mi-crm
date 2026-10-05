<x-filament-panels::page>
    <div dir="rtl" class="space-y-6">
        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex gap-2">
                @if ($this->canUseDaily())
                    <button type="button" wire:click="setPeriod('daily')"
                        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $period === 'daily' ? 'bg-primary-600 text-white' : 'border border-gray-200 text-gray-600' }}">
                        يومي
                    </button>
                @endif
                @if ($this->canUseWeekly())
                    <button type="button" wire:click="setPeriod('weekly')"
                        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $period === 'weekly' ? 'bg-primary-600 text-white' : 'border border-gray-200 text-gray-600' }}">
                        أسبوعي
                    </button>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">{{ $period === 'weekly' ? 'تاريخ داخل الأسبوع' : 'اليوم' }}</label>
                <input wire:model.live="date" type="date"
                    class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800">
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
                @if (($report['from'] ?? '') !== ($report['to'] ?? ''))
                    من {{ $report['from'] }} إلى {{ $report['to'] }} ·
                @endif
                {{ number_format($report['quotes_count'] ?? 0) }} حساب
                · {{ number_format($report['clients_count'] ?? 0) }} عميل
                · {{ number_format($report['total'] ?? 0, 0) }} ج.م
            </div>
        </div>

        @forelse ($report['reps'] ?? [] as $rep)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $rep['name'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $rep['quotes_count'] }} حساب · {{ $rep['clients_count'] }} عميل</p>
                    </div>
                    <div class="text-sm font-bold text-primary-700">{{ number_format($rep['total'], 0) }} ج.م</div>
                </header>

                @if ($rep['clients'] === [])
                    <p class="px-5 py-6 text-sm text-gray-400">لا توجد حسابات في هذا اليوم.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-right font-semibold">الوقت</th>
                                    <th class="px-4 py-2 text-right font-semibold">العميل</th>
                                    <th class="px-4 py-2 text-right font-semibold">الهاتف</th>
                                    <th class="px-4 py-2 text-right font-semibold">العرض</th>
                                    <th class="px-4 py-2 text-right font-semibold">النوع</th>
                                    <th class="px-4 py-2 text-right font-semibold">الحالة</th>
                                    <th class="px-4 py-2 text-left font-semibold">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rep['clients'] as $client)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-4 py-2">{{ $client['at'] }}</td>
                                        <td class="px-4 py-2 font-semibold">{{ $client['name'] }}</td>
                                        <td class="px-4 py-2" dir="ltr">{{ $client['phone'] }}</td>
                                        <td class="px-4 py-2">{{ $client['quote_number'] }}</td>
                                        <td class="px-4 py-2">{{ $client['project'] }}</td>
                                        <td class="px-4 py-2">{{ $client['status'] }}</td>
                                        <td class="px-4 py-2 text-left">{{ number_format($client['total'], 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @empty
            <p class="py-16 text-center text-gray-400">لا يوجد مندوبو مبيعات نشطون.</p>
        @endforelse
    </div>
</x-filament-panels::page>
