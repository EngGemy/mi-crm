<x-filament-panels::page>
<div dir="rtl" class="space-y-6">

    {{-- بيانات المعرض --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 text-center">
            <p class="text-3xl font-bold text-primary-600">{{ $leadsCount }}</p>
            <p class="text-sm text-gray-500 mt-1">إجمالي العملاء المحتملين</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-blue-200 dark:border-blue-700 p-4 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $quotationsCount }}</p>
            <p class="text-sm text-gray-500 mt-1">تحوّلوا لعروض أسعار</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-green-200 dark:border-green-700 p-4 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $contractsCount }}</p>
            <p class="text-sm text-gray-500 mt-1">تحوّلوا لعقود</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-emerald-200 dark:border-emerald-700 p-4 text-center">
            <p class="text-3xl font-bold text-emerald-600">{{ number_format($contractsValue) }}</p>
            <p class="text-sm text-gray-500 mt-1">قيمة العقود (ج.م)</p>
        </div>
    </div>

    {{-- ROI --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl p-5 text-center {{ $roiPercentage > 0 ? 'bg-green-50 dark:bg-green-950/20 border border-green-200' : 'bg-red-50 dark:bg-red-950/20 border border-red-200' }}">
            <p class="text-xs text-gray-500 mb-1">تكلفة المعرض</p>
            <p class="text-2xl font-bold">{{ number_format($record->cost) }} ج.م</p>
        </div>
        <div class="rounded-xl p-5 text-center {{ $roiPercentage >= 0 ? 'bg-green-50 dark:bg-green-950/20 border border-green-200' : 'bg-red-50 dark:bg-red-950/20 border border-red-200' }}">
            <p class="text-xs text-gray-500 mb-1">العائد على الاستثمار (ROI)</p>
            <p class="text-3xl font-bold {{ $roiPercentage >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ number_format($roiPercentage, 1) }}٪
            </p>
            <p class="text-xs text-gray-400 mt-1">(قيمة العقود − التكلفة) ÷ التكلفة</p>
        </div>
        <div class="rounded-xl p-5 text-center bg-blue-50 dark:bg-blue-950/20 border border-blue-200">
            <p class="text-xs text-gray-500 mb-1">معدّل التحويل</p>
            <p class="text-3xl font-bold text-blue-700">{{ number_format($conversionRate, 1) }}٪</p>
            <p class="text-xs text-gray-400 mt-1">عقود ÷ إجمالي Leads</p>
        </div>
    </div>

    {{-- قائمة العملاء المحتملين --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <h3 class="font-semibold">العملاء المحتملين من هذا المعرض ({{ $leadsCount }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-500">
                        <th class="px-4 py-2 text-right">الاسم</th>
                        <th class="px-4 py-2 text-right">الشركة</th>
                        <th class="px-4 py-2 text-right">الحالة</th>
                        <th class="px-4 py-2 text-right">المسؤول</th>
                        <th class="px-4 py-2 text-right">تاريخ الإضافة</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($leads as $lead)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-2 font-medium">{{ $lead->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $lead->company ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700">
                                {{ \App\Models\Lead::STATUSES[$lead->status] ?? $lead->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $lead->assignedUser?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-400">{{ $lead->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('filament.admin.resources.leads.edit', $lead) }}" class="text-primary-600 hover:underline text-xs">عرض</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">لا يوجد عملاء محتملون لهذا المعرض بعد</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-filament-panels::page>
