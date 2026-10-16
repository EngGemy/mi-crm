<x-filament-panels::page>
    @include('filament.pages.partials.report-styles')

    <div dir="rtl" class="mi-rpt">
        <div class="mi-rpt-toolbar">
            <label class="mi-rpt-field" style="min-width:240px">
                <span>العميل</span>
                <input wire:model.live.debounce.400ms="search" type="search" placeholder="الاسم أو جزء من الموبايل">
            </label>
            @if (count($userOptions) > 1)
                <label class="mi-rpt-field" style="min-width:200px">
                    <span>المندوب</span>
                    <select wire:model.live="userId">
                        @foreach ($userOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <div class="mi-rpt-actions">
                <button type="button" wire:click="exportExcel" class="mi-rpt-btn mi-rpt-btn-solid">تصدير Excel</button>
            </div>
            <div class="mi-rpt-period">
                <strong>{{ count($rows) }} عميل</strong>
                <span class="mi-rpt-note">كل مندوب فضّل ظاهر بالدور اللي عمله</span>
            </div>
        </div>

        @if ($rows === [])
            <p class="mi-rpt-empty">لا يوجد عملاء مطابقون.</p>
        @else
            <section class="mi-rpt-card">
                <div class="mi-rpt-scroll">
                    <table class="mi-rpt-table">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>الموبايل</th>
                                <th>عروض السعر</th>
                                <th>المندوبون اللي تعاملوا معاه</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['name'] }}</strong>
                                        @if ($row['company'] !== '')
                                            <div class="mi-rpt-note">{{ $row['company'] }}</div>
                                        @endif
                                    </td>
                                    <td dir="ltr" style="text-align:right">{{ $row['phone'] ?: '—' }}</td>
                                    <td class="num">{{ $row['quotes'] }}</td>
                                    <td>
                                        @foreach ($row['reps'] as $rep)
                                            <div style="margin-bottom:6px">
                                                <strong>{{ $rep['name'] }}</strong>
                                                <span class="mi-rpt-pill info">{{ $rep['roles'] }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
