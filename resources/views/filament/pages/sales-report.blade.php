<x-filament-panels::page>
@include('filament.pages.partials.report-styles')

@php
    $fromLabel = \Illuminate\Support\Carbon::parse($dateFrom ?: now())->translatedFormat('j F Y');
    $toLabel = \Illuminate\Support\Carbon::parse($dateTo ?: now())->translatedFormat('j F Y');
    $sum = fn (string $key) => array_sum(array_column($reps, $key));
    $presetOn = match (true) {
        $dateFrom === now()->toDateString() && $dateTo === now()->toDateString() => 'daily',
        $dateFrom === now()->copy()->subDay()->toDateString() && $dateTo === now()->copy()->subDay()->toDateString() => 'yesterday',
        $dateFrom === now()->copy()->startOfWeek()->toDateString() && $dateTo === now()->copy()->endOfWeek()->toDateString() => 'weekly',
        $dateFrom === now()->copy()->startOfMonth()->toDateString() && $dateTo === now()->copy()->endOfMonth()->toDateString() => 'monthly',
        default => '',
    };
    $pipeline = $sum('pipeline_value');
@endphp

<div dir="rtl" class="mi-rpt">
    <div class="mi-rpt-toolbar">
        <div class="mi-rpt-seg">
            <button type="button" wire:click="applyPreset('daily')" @class(['is-on' => $presetOn === 'daily'])>اليوم</button>
            <button type="button" wire:click="applyPreset('yesterday')" @class(['is-on' => $presetOn === 'yesterday'])>أمس</button>
            <button type="button" wire:click="applyPreset('weekly')" @class(['is-on' => $presetOn === 'weekly'])>هذا الأسبوع</button>
            <button type="button" wire:click="applyPreset('monthly')" @class(['is-on' => $presetOn === 'monthly'])>هذا الشهر</button>
        </div>
        <label class="mi-rpt-field">
            <span>من تاريخ</span>
            <input wire:model="dateFrom" type="date">
        </label>
        <label class="mi-rpt-field">
            <span>إلى تاريخ</span>
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
            <button wire:click="exportExcel" type="button" class="mi-rpt-btn mi-rpt-btn-solid">تصدير Excel</button>
        </div>
    </div>

    @if(empty($reps))
        <p class="mi-rpt-empty">لا يوجد مناديب مبيعات مسجلون في النظام</p>
    @else
        <div class="mi-rpt-kpis">
            <div class="mi-rpt-kpi"><b>{{ number_format($sum('total_leads')) }}</b><span>عملاء محتملون</span></div>
            <div class="mi-rpt-kpi"><b>{{ number_format($sum('won')) }}</b><span>صفقات مغلقة</span></div>
            <div class="mi-rpt-kpi"><b>{{ number_format($sum('calls')) }}</b><span>مكالمات</span></div>
            <div class="mi-rpt-kpi"><b>{{ $pipeline > 0 ? number_format($pipeline, 0) : '0' }}</b><span>قيمة الفرص بالجنيه</span></div>
        </div>

        <div class="mi-rpt-period" style="margin:0">
            <span class="mi-rpt-note">{{ $fromLabel }} — {{ $toLabel }}</span>
        </div>

        <div class="mi-rpt-ranks">
            @foreach(array_slice($reps, 0, 3) as $i => $rep)
                <article class="mi-rpt-rank">
                    <header>
                        <div class="mi-rpt-who">
                            <div class="mi-rpt-avatar">{{ $i + 1 }}</div>
                            <div>
                                <strong>{{ $rep['name'] }}</strong>
                                <em>المركز {{ $i + 1 }}</em>
                            </div>
                        </div>
                        <div class="score">
                            <b>{{ $rep['won'] }}</b>
                            <span>صفقة مغلقة</span>
                        </div>
                    </header>
                    <div class="mi-rpt-mini">
                        <div><b>{{ $rep['total_leads'] }}</b><span>عملاء</span></div>
                        <div><b>{{ $rep['conversion_rate'] }}%</b><span>تحويل</span></div>
                        <div><b>{{ $rep['activities'] }}</b><span>نشاط</span></div>
                    </div>
                    @if($rep['won_value'] > 0)
                        <div class="mi-rpt-money" style="text-align:center">{{ number_format($rep['won_value'], 0) }} <span>ج.م مغلقة</span></div>
                    @endif
                </article>
            @endforeach
        </div>

        <section class="mi-rpt-card">
            <header>
                <div>
                    <h2>أداء المناديب</h2>
                    <p>{{ count($reps) }} مندوب</p>
                </div>
            </header>
            <div class="mi-rpt-scroll">
                <table class="mi-rpt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المندوب</th>
                            <th>العملاء</th>
                            <th>جديد</th>
                            <th>نشط</th>
                            <th>مغلق</th>
                            <th>مفقود</th>
                            <th>التحويل</th>
                            <th>الفرص</th>
                            <th>نشاط</th>
                            <th>مكالمات</th>
                            <th>دقائق</th>
                            <th>معلق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reps as $i => $rep)
                            <tr>
                                <td class="num">{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $rep['name'] }}</strong>
                                    <div class="mi-rpt-sub">{{ $rep['email'] }}</div>
                                </td>
                                <td class="num">{{ $rep['total_leads'] }}</td>
                                <td class="num"><span class="mi-rpt-pill info">{{ $rep['new_period'] }}</span></td>
                                <td class="num">{{ $rep['active'] }}</td>
                                <td class="num"><span class="mi-rpt-pill ok">{{ $rep['won'] }}</span></td>
                                <td class="num"><span class="mi-rpt-pill bad">{{ $rep['lost'] }}</span></td>
                                <td>
                                    <div class="mi-rpt-bar">
                                        <i><b style="width:{{ min($rep['conversion_rate'], 100) }}%"></b></i>
                                        <span>{{ $rep['conversion_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="num">{{ $rep['pipeline_value'] > 0 ? number_format($rep['pipeline_value'] / 1000, 0).'K' : '—' }}</td>
                                <td class="num">{{ $rep['activities'] }}</td>
                                <td class="num">{{ $rep['calls'] ?? 0 }}</td>
                                <td class="num">{{ $rep['duration_minutes'] ?? 0 }}</td>
                                <td class="num">
                                    @if($rep['tasks_pending'] > 0)
                                        <span class="mi-rpt-pill warn">{{ $rep['tasks_pending'] }}</span>
                                    @else
                                        <span class="mi-rpt-pill ok">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @php $shown = collect($reps)->filter(fn ($rep) => array_sum($rep['activities_breakdown']) > 0); @endphp
        @if($shown->isNotEmpty())
            <div class="mi-rpt-ranks">
                @foreach($shown as $rep)
                    @php
                        $typeLabels = \App\Models\LeadActivity::TYPES;
                        $maxAct = max(array_values($rep['activities_breakdown']) ?: [1]);
                    @endphp
                    <article class="mi-rpt-rank">
                        <header>
                            <div class="mi-rpt-who">
                                <div class="mi-rpt-avatar">{{ mb_substr($rep['name'], 0, 1) }}</div>
                                <div>
                                    <strong>{{ $rep['name'] }}</strong>
                                    <em>{{ $rep['activities'] }} نشاط</em>
                                </div>
                            </div>
                        </header>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach($rep['activities_breakdown'] as $type => $count)
                                @if($count > 0)
                                    <div class="mi-rpt-bar" style="justify-content:stretch">
                                        <span style="width:72px;font-size:12px;font-weight:700;color:var(--muted)">{{ $typeLabels[$type] ?? $type }}</span>
                                        <i style="flex:1"><b style="width:{{ round(($count / $maxAct) * 100) }}%"></b></i>
                                        <strong style="width:24px;text-align:end">{{ $count }}</strong>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</div>
</x-filament-panels::page>
