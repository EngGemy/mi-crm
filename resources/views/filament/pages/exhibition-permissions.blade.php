<x-filament-panels::page>
    <div dir="rtl" class="space-y-4">
        <section class="space-y-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-base font-bold">نص رسالة الواتساب</h2>
            <p class="text-xs text-gray-500">
                تُرسل للعميل أول ما يُحفظ حساب السعر، ومعها رابط عرض السعر.
                المتغيرات: {client_name} {quote_number} {project_type} {length} {width} {height} {total} {share_url} {pdf_url}
                ضع {share_url} أول رابط في النص حتى تظهر صورة الكارت داخل واتساب، و{pdf_url} لملف العرض.
            </p>
            <textarea wire:model="welcomeMessage" rows="8"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"></textarea>
            <button type="button" wire:click="saveWelcome"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">
                حفظ النص
            </button>
        </section>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            المدير يحدد لكل مندوب: التقرير اليومي، التقرير الأسبوعي، وهل يرى عملاء زملائه.
            بدون «يشوف كل المندوبين» المندوب يرى عملاءه وتقاريره فقط.
        </p>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold">المندوب</th>
                        <th class="px-4 py-3 text-center font-semibold">التقرير اليومي</th>
                        <th class="px-4 py-3 text-center font-semibold">التقرير الأسبوعي</th>
                        <th class="px-4 py-3 text-center font-semibold">يشوف كل المندوبين</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reps as $rep)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-3 font-semibold">{{ $rep['name'] }}</td>
                            @foreach (['report_daily', 'report_weekly', 'view_team'] as $field)
                                <td class="px-4 py-3 text-center">
                                    <button type="button" wire:click="toggle({{ $rep['id'] }}, '{{ $field }}')"
                                        class="rounded-full px-3 py-1 text-xs font-bold {{ $rep[$field] ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $rep[$field] ? 'مفعّل' : 'موقوف' }}
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">لا يوجد مندوبو مبيعات نشطون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
