<x-filament-panels::page>
@include('filament.pages.partials.report-styles')

@php
    $fromLabel = \Illuminate\Support\Carbon::parse($dateFrom ?: now())->translatedFormat('j F Y');
    $toLabel = \Illuminate\Support\Carbon::parse($dateTo ?: now())->translatedFormat('j F Y');
    $cards = [
        ['مكالمات', $summary['calls'] ?? 0],
        ['واتساب', $summary['whatsapp'] ?? 0],
        ['زيارات', $summary['visits'] ?? 0],
        ['اجتماعات', $summary['meetings'] ?? 0],
        ['إجمالي النشاط', $summary['total_activities'] ?? 0],
        ['دقائق', $summary['duration_minutes'] ?? 0],
        ['عملاء جدد', $summary['new_leads'] ?? 0],
        ['صفقات', $summary['won'] ?? 0],
    ];
    $typeTone = [
        'call' => 'ok',
        'whatsapp' => 'ok',
        'visit' => 'info',
        'meeting' => 'info',
        'note' => 'mute',
    ];
@endphp

<div dir="rtl" class="mi-rpt">
    <div class="mi-rpt-toolbar">
        <div class="mi-rpt-seg">
            @foreach([
                'daily' => 'اليوم',
                'yesterday' => 'أمس',
                'weekly' => 'هذا الأسبوع',
                'monthly' => 'هذا الشهر',
            ] as $key => $label)
                <button type="button" wire:click="applyMode('{{ $key }}')" @class(['is-on' => $mode === $key])>{{ $label }}</button>
            @endforeach
        </div>
        <label class="mi-rpt-field">
            <span>من</span>
                <input wire:model="dateFrom" type="date">
            </label>
            <label class="mi-rpt-field">
                <span>إلى</span>
                <input wire:model="dateTo" type="date">
            </label>
            @if(count($userOptions) > 1)
                <label class="mi-rpt-field" style="min-width:200px">
                    <span>المندوب</span>
                    <select wire:model="userId">
                    @foreach($userOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif
            <div class="mi-rpt-actions">
                <button wire:click="refresh" type="button" class="mi-rpt-btn mi-rpt-btn-ghost">تحديث</button>
                <button wire:click="exportSummaryExcel" type="button" class="mi-rpt-btn mi-rpt-btn-ghost">Excel ملخص</button>
                <button wire:click="exportExcel" type="button" class="mi-rpt-btn mi-rpt-btn-solid">Excel تفصيلي</button>
            </div>
    </div>

    <div class="mi-rpt-period" style="margin:0">
        <strong>{{ $fromLabel }} — {{ $toLabel }}</strong>
        <span class="mi-rpt-note">المندوب يرى تقريره فقط، والمدير يرى الفريق حسب الصلاحية</span>
    </div>

    <div class="mi-rpt-kpis cols-8">
        @foreach($cards as [$label, $value])
            <div class="mi-rpt-kpi">
                <b>{{ number_format((int) $value) }}</b>
                <span>{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <section class="mi-rpt-card">
        <header>
            <div>
                <h2>أداء المناديب</h2>
                <p>{{ count($reps) }} مندوب في الفترة</p>
            </div>
        </header>
        <div class="mi-rpt-scroll">
            <table class="mi-rpt-table">
                <thead>
                    <tr>
                        <th>المندوب</th>
                        <th>مكالمات</th>
                        <th>واتساب</th>
                        <th>زيارات</th>
                        <th>اجتماعات</th>
                        <th>النشاط</th>
                        <th>دقائق</th>
                        <th>مكتمل</th>
                        <th>معلق</th>
                        <th>عملاء جدد</th>
                        <th>صفقات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reps as $rep)
                        <tr>
                            <td>
                                <strong>{{ $rep['name'] }}</strong>
                                <div class="mi-rpt-sub">{{ $rep['email'] }}</div>
                            </td>
                            <td class="num">{{ $rep['calls'] }}</td>
                            <td class="num">{{ $rep['whatsapp'] }}</td>
                            <td class="num">{{ $rep['visits'] }}</td>
                            <td class="num">{{ $rep['meetings'] }}</td>
                            <td class="num">{{ $rep['total_activities'] }}</td>
                            <td class="num">{{ $rep['duration_minutes'] }}</td>
                            <td class="num">{{ $rep['completed'] }}</td>
                            <td class="num">
                                @if($rep['pending'] > 0)
                                    <span class="mi-rpt-pill warn">{{ $rep['pending'] }}</span>
                                @else
                                    <span class="mi-rpt-pill ok">0</span>
                                @endif
                            </td>
                            <td class="num">{{ $rep['new_leads'] }}</td>
                            <td class="num">{{ $rep['won'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="mi-rpt-empty">لا توجد بيانات في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mi-rpt-card">
        <header>
            <div>
                <h2>سجل العمل</h2>
                <p>مكالمات وواتساب وزيارات وملاحظات</p>
            </div>
            <span class="mi-rpt-pill">{{ count($activities) }} سجل</span>
        </header>
        <div class="mi-rpt-scroll mi-rpt-log">
            <table class="mi-rpt-table">
                <thead>
                    <tr>
                        <th>الوقت</th>
                        <th>المندوب</th>
                        <th>النوع</th>
                        <th>الموضوع / العميل</th>
                        <th>النتيجة</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $row)
                        <tr>
                            <td class="num" style="white-space:nowrap">{{ $row['created_at'] }}</td>
                            <td>{{ $row['user_name'] }}</td>
                            <td><span class="mi-rpt-pill {{ $typeTone[$row['type']] ?? 'mute' }}">{{ $row['type_label'] }}</span></td>
                            <td>
                                <strong>{{ $row['subject'] ?: '—' }}</strong>
                                <div class="mi-rpt-sub">
                                    {{ $row['lead_name'] ?: 'بدون عميل' }}
                                    @if($row['lead_phone'])
                                        · <span dir="ltr">{{ $row['lead_phone'] }}</span>
                                    @endif
                                </div>
                                @if($row['description'])
                                    <div class="mi-rpt-sub">{{ $row['description'] }}</div>
                                @endif
                            </td>
                            <td class="num">{{ $row['outcome_label'] }}</td>
                            <td class="num">{{ $row['duration_minutes'] > 0 ? $row['duration_minutes'].' د' : '—' }}</td>
                            <td class="num">
                                @if($row['is_completed'])
                                    <span class="mi-rpt-pill ok">مكتمل</span>
                                @else
                                    <span class="mi-rpt-pill warn">جاري</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="mi-rpt-empty">لا يوجد سجل عمل في هذه الفترة. سجّل مكالمة من مهامي اليوم أو من صفحة العميل المحتمل.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-filament-panels::page>
