<x-filament-panels::page>
    @include('filament.pages.partials.report-styles')

    @php
        $from = \Illuminate\Support\Carbon::parse($report['from'] ?? now());
        $to = \Illuminate\Support\Carbon::parse($report['to'] ?? now());
        $range = $from->equalTo($to)
            ? $from->translatedFormat('l j F Y')
            : $from->translatedFormat('j F').' — '.$to->translatedFormat('j F Y');
        $tone = fn (string $status) => match ($status) {
            'مقبول', 'معتمد' => 'ok',
            'مرفوض' => 'bad',
            'مرسل' => 'info',
            default => 'mute',
        };
    @endphp

    <div dir="rtl" class="mi-rpt">
        <div class="mi-rpt-toolbar">
            <div class="mi-rpt-seg">
                @if ($this->canUseDaily())
                    <button type="button" wire:click="applyPreset('daily')" @class(['is-on' => $period === 'daily'])>اليوم</button>
                    <button type="button" wire:click="applyPreset('yesterday')" @class(['is-on' => $period === 'yesterday'])>أمس</button>
                @endif
                @if ($this->canUseWeekly())
                    <button type="button" wire:click="applyPreset('weekly')" @class(['is-on' => $period === 'weekly'])>هذا الأسبوع</button>
                @endif
                <button type="button" wire:click="applyPreset('monthly')" @class(['is-on' => $period === 'monthly'])>هذا الشهر</button>
            </div>
            <label class="mi-rpt-field">
                <span>من</span>
                <input wire:model="dateFrom" type="date">
            </label>
            <label class="mi-rpt-field">
                <span>إلى</span>
                <input wire:model="dateTo" type="date">
            </label>
            @if (count($userOptions) > 1)
                <label class="mi-rpt-field" style="min-width:200px">
                    <span>المندوب</span>
                    <select wire:model="userId">
                        @foreach ($userOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <div class="mi-rpt-actions">
                <button type="button" wire:click="refresh" class="mi-rpt-btn mi-rpt-btn-ghost">تحديث</button>
                <button type="button" wire:click="exportExcel" class="mi-rpt-btn mi-rpt-btn-solid">تصدير Excel</button>
            </div>
            <div class="mi-rpt-period">
                <strong>{{ $range }}</strong>
                <span class="mi-rpt-note">اختر المندوب والفترة ثم اضغط تحديث</span>
            </div>
        </div>

        <div class="mi-rpt-kpis cols-3">
            <div class="mi-rpt-kpi">
                <b>{{ number_format($report['quotes_count'] ?? 0) }}</b>
                <span>حساب</span>
            </div>
            <div class="mi-rpt-kpi">
                <b>{{ number_format($report['clients_count'] ?? 0) }}</b>
                <span>عميل</span>
            </div>
            <div class="mi-rpt-kpi">
                <b>{{ number_format($report['total'] ?? 0, 0) }}</b>
                <span>الإجمالي بالجنيه</span>
            </div>
        </div>

        @forelse ($report['reps'] ?? [] as $rep)
            <section class="mi-rpt-card">
                <header>
                    <div class="mi-rpt-who">
                        <div class="mi-rpt-avatar">{{ mb_substr($rep['name'], 0, 1) }}</div>
                        <div>
                            <strong>{{ $rep['name'] }}</strong>
                            <em>{{ $rep['quotes_count'] }} حساب · {{ $rep['clients_count'] }} عميل</em>
                        </div>
                    </div>
                    <div class="mi-rpt-money">{{ number_format($rep['total'], 0) }} <span>ج.م</span></div>
                </header>

                @if ($rep['clients'] === [])
                    <p class="mi-rpt-empty">لا توجد حسابات في هذه الفترة.</p>
                @else
                    <div class="mi-rpt-scroll">
                        <table class="mi-rpt-table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوقت</th>
                                    <th>العميل</th>
                                    <th>الهاتف</th>
                                    <th>العرض</th>
                                    <th>النوع</th>
                                    <th>الحالة</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rep['clients'] as $client)
                                    <tr>
                                        <td class="num">{{ $client['on'] }}</td>
                                        <td class="num">{{ $client['at'] }}</td>
                                        <td><strong>{{ $client['name'] }}</strong></td>
                                        <td dir="ltr" style="text-align:right">{{ $client['phone'] }}</td>
                                        <td>{{ $client['quote_number'] }}</td>
                                        <td>{{ $client['project'] }}</td>
                                        <td><span class="mi-rpt-pill {{ $tone($client['status']) }}">{{ $client['status'] }}</span></td>
                                        <td class="end">{{ number_format($client['total'], 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @empty
            <p class="mi-rpt-empty">لا يوجد مندوبو مبيعات نشطون.</p>
        @endforelse
    </div>
</x-filament-panels::page>
