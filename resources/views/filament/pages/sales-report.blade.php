<x-filament-panels::page>
<div dir="rtl" class="space-y-6">

    {{-- Date filter --}}
    <div class="flex flex-wrap items-end gap-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm">
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">من تاريخ</label>
            <input wire:model.live="dateFrom" type="date"
                class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">إلى تاريخ</label>
            <input wire:model.live="dateTo" type="date"
                class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="flex gap-2 mr-auto">
            <button wire:click="$set('dateFrom', '{{ now()->startOfMonth()->format('Y-m-d') }}')"
                wire:then="$set('dateTo', '{{ now()->endOfMonth()->format('Y-m-d') }}')"
                onclick="Livewire.dispatch('refresh')"
                class="text-xs px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">هذا الشهر</button>
            <button wire:click="$set('dateFrom', '{{ now()->startOfQuarter()->format('Y-m-d') }}')"
                class="text-xs px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">هذا الربع</button>
        </div>
    </div>

    @if(empty($reps))
    <div class="flex items-center justify-center py-20 text-gray-400">
        <p>لا يوجد مناديب مبيعات مسجلون في النظام</p>
    </div>
    @else

    {{-- Leaderboard cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach(array_slice($reps, 0, 3) as $i => $rep)
        @php
            $medals = ['🥇','🥈','🥉'];
            $colors = [
                0 => ['border'=>'border-yellow-300','bg'=>'bg-yellow-50 dark:bg-yellow-950/20','text'=>'text-yellow-700 dark:text-yellow-300'],
                1 => ['border'=>'border-gray-300','bg'=>'bg-gray-50 dark:bg-gray-800','text'=>'text-gray-600 dark:text-gray-300'],
                2 => ['border'=>'border-orange-300','bg'=>'bg-orange-50 dark:bg-orange-950/20','text'=>'text-orange-700 dark:text-orange-300'],
            ];
            $c = $colors[$i];
        @endphp
        <div class="rounded-xl border-2 {{ $c['border'] }} {{ $c['bg'] }} p-5 flex flex-col gap-3 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">{{ $medals[$i] }}</span>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white">{{ $rep['name'] }}</p>
                        <p class="text-xs text-gray-400">المركز {{ $i + 1 }}</p>
                    </div>
                </div>
                <div class="text-left">
                    <p class="text-2xl font-bold {{ $c['text'] }}">{{ $rep['won'] }}</p>
                    <p class="text-xs text-gray-400">صفقات مغلقة</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs border-t border-gray-200 dark:border-gray-700 pt-3">
                <div>
                    <p class="font-bold text-gray-800 dark:text-white">{{ $rep['total_leads'] }}</p>
                    <p class="text-gray-400">إجمالي Leads</p>
                </div>
                <div>
                    <p class="font-bold text-green-600">{{ $rep['conversion_rate'] }}%</p>
                    <p class="text-gray-400">معدل التحويل</p>
                </div>
                <div>
                    <p class="font-bold text-blue-600">{{ $rep['activities'] }}</p>
                    <p class="text-gray-400">نشاط</p>
                </div>
            </div>
            @if($rep['won_value'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 text-center">
                <p class="text-xs text-gray-400">قيمة الصفقات المغلقة</p>
                <p class="font-bold text-primary-600 font-mono">{{ number_format($rep['won_value']/1000, 0) }}K ج.م</p>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Full table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">أداء جميع المناديب</h3>
            <span class="text-xs text-gray-400 mr-auto">{{ $dateFrom }} — {{ $dateTo }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" dir="rtl">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">المندوب</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Leads الكلي</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">جديد (الفترة)</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">نشط</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase text-green-600">مغلق ✓</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase text-red-500">مفقود ✗</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">التحويل</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Pipeline</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">نشاطات</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">مهام معلقة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($reps as $i => $rep)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-xs shrink-0">
                                    {{ mb_substr($rep['name'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $rep['name'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $rep['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-medium">{{ $rep['total_leads'] }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                {{ $rep['new_period'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-amber-600 font-medium">{{ $rep['active'] }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                {{ $rep['won'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ $rep['lost'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $rate = $rep['conversion_rate']; @endphp
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-16 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $rate >= 60 ? 'bg-green-500' : ($rate >= 30 ? 'bg-amber-500' : 'bg-red-400') }}"
                                         style="width:{{ min($rate, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold {{ $rate >= 60 ? 'text-green-600' : ($rate >= 30 ? 'text-amber-600' : 'text-red-500') }}">{{ $rate }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                            @if($rep['pipeline_value'] > 0)
                            {{ number_format($rep['pipeline_value']/1000, 0) }}K
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-blue-600 font-medium">{{ $rep['activities'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rep['tasks_pending'] > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                {{ $rep['tasks_pending'] }}
                            </span>
                            @else
                            <span class="text-green-500 text-xs">✓</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Activity breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($reps as $rep)
        @if(array_sum($rep['activities_breakdown']) > 0)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 font-bold text-xs">
                    {{ mb_substr($rep['name'], 0, 1) }}
                </div>
                <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $rep['name'] }}</h4>
                <span class="mr-auto text-xs text-gray-400">{{ $rep['activities'] }} نشاط</span>
            </div>
            <div class="space-y-1.5">
                @php
                    $typeColors = [
                        'call'=>'#22c55e','whatsapp'=>'#25D366','email'=>'#f97316',
                        'meeting'=>'#3b82f6','visit'=>'#8b5cf6','note'=>'#6b7280',
                        'sms'=>'#eab308','reminder'=>'#ef4444','status_change'=>'#64748b',
                    ];
                    $typeLabels = \App\Models\LeadActivity::TYPES;
                    $maxAct = max(array_values($rep['activities_breakdown']) ?: [1]);
                @endphp
                @foreach($rep['activities_breakdown'] as $type => $count)
                @if($count > 0)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-16 shrink-0">{{ $typeLabels[$type] ?? $type }}</span>
                    <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ round(($count/$maxAct)*100) }}%; background:{{ $typeColors[$type] ?? '#6b7280' }}"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 w-5 text-left">{{ $count }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
    </div>

    @endif
</div>
</x-filament-panels::page>
