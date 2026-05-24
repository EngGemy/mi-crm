<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسعير عنبر دواجن — MI Metal</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f1f5f9; color: #1e293b; line-height: 1.7; }
        .container { max-width: 1280px; margin: 0 auto; padding: 24px 16px; display: grid; grid-template-columns: 1fr 400px; gap: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 24px; margin-bottom: 16px; }
        .card-title { font-size: 17px; font-weight: 700; margin-bottom: 16px; color: #0f172a; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: 'Cairo', sans-serif; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #C00000; }
        .form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-row-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; font-family: 'Cairo', sans-serif; cursor: pointer; }
        .btn-primary { background: #C00000; color: #fff; width: 100%; }
        .sticky-summary { position: sticky; top: 20px; align-self: start; }
        .summary-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .summary-item .value { font-weight: 700; direction: ltr; }
        .summary-total { background: linear-gradient(135deg, #C00000, #800000); color: #fff; padding: 18px; border-radius: 10px; margin-top: 12px; text-align: center; }
        .summary-total .amount { font-size: 26px; font-weight: 800; direction: ltr; }
        .badge { display: inline-block; background: #f0fdf4; color: #166534; font-size: 11px; padding: 2px 8px; border-radius: 4px; margin-right: 6px; }
        .loading { opacity: .5; pointer-events: none; }
        .tech-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; margin-bottom: 12px; }
        .tech-grid div { background: #f8fafc; padding: 8px; border-radius: 6px; }
        .tech-grid strong { display: block; color: #64748b; font-weight: 600; }
        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } .form-row, .form-row-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <main>
        <form id="quoteForm" method="POST" action="{{ route('sales.quotations.store') }}">
            @csrf

            <div class="card">
                <div class="card-title">بيانات العميل</div>
                <div class="form-row">
                    <div class="form-group" style="grid-column: span 3;">
                        <label>اسم العميل *</label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>الهاتف</label>
                        <input type="text" name="client_phone" value="{{ old('client_phone') }}">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>العنوان</label>
                        <input type="text" name="client_address" value="{{ old('client_address') }}">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">نوع المشروع <span class="badge">حساب مباشر</span></div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>نوع العنبر *</label>
                        <select name="project_type" id="project_type">
                            @foreach($projectTypes as $val => $label)
                                <option value="{{ $val }}" @selected(old('project_type', 'broiler') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>نطاق التسعير *</label>
                        <select name="pricing_scope" id="pricing_scope">
                            @foreach($pricingScopes as $val => $label)
                                <option value="{{ $val }}" @selected(old('pricing_scope', 'full_project') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">أبعاد العنبر</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>الطول (م) *</label>
                        <input type="number" step="0.01" name="length" id="length" value="{{ old('length', 81) }}" required class="calc-input">
                    </div>
                    <div class="form-group">
                        <label>العرض (م) *</label>
                        <input type="number" step="0.01" name="width" id="width" value="{{ old('width', 15) }}" required class="calc-input">
                    </div>
                    <div class="form-group">
                        <label>الارتفاع (م) *</label>
                        <input type="number" step="0.01" name="height" id="height" value="{{ old('height', 3.5) }}" required class="calc-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>منطقة الخدمات (م)</label>
                        <input type="number" step="0.01" name="service_length" id="service_length" value="{{ old('service_length', 10) }}" class="calc-input">
                    </div>
                    <div class="form-group">
                        <label>عدد الأدوار *</label>
                        <input type="number" name="tiers" id="tiers" value="{{ old('tiers', 4) }}" required class="calc-input">
                    </div>
                    <div class="form-group">
                        <label>عدد الخطوط *</label>
                        <input type="number" name="lines" id="lines" value="{{ old('lines', 5) }}" required class="calc-input">
                    </div>
                </div>
            </div>

            <div class="card" id="broiler-options">
                <div class="card-title">خيارات التسمين</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>وزن الطائر (كجم)</label>
                        <select name="bird_weight_kg" id="bird_weight_kg" class="calc-input">
                            @foreach($weightOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('bird_weight_kg', '2.100') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الشفاطات الجانبية</label>
                        <input type="number" name="side_fans_count" id="side_fans_count" placeholder="تلقائي" class="calc-input">
                    </div>
                    <div class="form-group">
                        <label>الدفايات (اختياري)</label>
                        <select name="heaters_count" id="heaters_count" class="calc-input">
                            @foreach($heaterOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('heaters_count', '0') == (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>نوع الحوائط</label>
                    <select name="wall_type" id="wall_type" class="calc-input">
                        <option value="sandwich">ساندوتش بانل</option>
                        <option value="cement">خرسانة</option>
                    </select>
                </div>
                <div id="weight-table-wrap"></div>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>الضريبة</label>
                    <select name="vat_region" id="vat_region" class="calc-input">
                        <option value="none">بدون</option>
                        <option value="egypt">مصر 14%</option>
                        <option value="ksa">السعودية 15%</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button type="button" class="btn btn-primary" id="btn-preview-pdf" style="flex:1;background:#0f172a">تحميل PDF (معاينة)</button>
                <button type="submit" class="btn btn-primary" style="flex:1">حفظ عرض السعر</button>
            </div>
        </form>
    </main>

    <aside class="sticky-summary">
        <div class="card" id="summary-card">
            <div class="card-title">ملخص مباشر</div>
            <div class="tech-grid">
                <div><strong>الطول الفعال</strong><span id="t-effective">—</span> م</div>
                <div><strong>أعشاش/خط</strong><span id="t-nests-line">—</span></div>
                <div><strong>إجمالي الأعشاش</strong><span id="t-total-nests">—</span></div>
                <div><strong>الشفاطات</strong><span id="t-fans">—</span><small id="t-fans-formula" style="display:block;color:#64748b;font-size:11px"></small></div>
            </div>
            <div class="summary-item"><span>طيور / عش</span><span class="value" id="sum-bpn">—</span></div>
            <div class="summary-item"><span>عدد الطيور</span><span class="value" id="sum-birds">—</span></div>
            <div class="summary-item"><span>التبريد (م)</span><span class="value" id="sum-cooling">—</span><small id="sum-cooling-formula" style="display:block;font-size:11px;color:#64748b;font-weight:400"></small></div>
            <div class="summary-item"><span>بالدولار (تقريبي)</span><span class="value" id="sum-usd" style="direction:ltr">—</span></div>
            <div class="summary-item"><span>الشبابيك</span><span class="value" id="sum-windows">—</span></div>
            <div class="summary-item"><span>المجموع الفرعي</span><span class="value" id="sum-subtotal">—</span></div>
            <div class="summary-item"><span>الضريبة</span><span class="value" id="sum-vat">—</span></div>
            <div class="summary-total">
                <div style="font-size:12px;opacity:.9">الإجمالي النهائي</div>
                <div class="amount" id="sum-total">—</div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">تفاصيل البنود</div>
            <div id="breakdown-table" style="font-size:12px;color:#64748b">جاري الحساب…</div>
        </div>
    </aside>
</div>

<script>
const calcUrl = '{{ route('api.poultry.calculate') }}';
const pdfUrl = '{{ route('sales.quotations.preview-pdf') }}';
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const sectionLabels = @json(\App\Support\PoultrySectionLabels::labelsAr());
let debounceTimer;
let lastCalcPayload = null;

function updateWeightTable(data) {
    const wrap = document.getElementById('weight-table-wrap');
    if (!wrap || !data.weight_table) return;
    const selected = document.getElementById('bird_weight_kg')?.value;
    let html = '<p style="font-size:12px;color:#64748b;margin-bottom:8px">جدول السعة المعتمدة لكل عش حسب الوزن (يتحدث مع الأعشاش)</p>';
    html += '<table style="width:100%;border-collapse:collapse;font-size:13px;text-align:center">';
    html += '<thead><tr style="background:#C00000;color:#fff"><th style="padding:8px">وزن (كجم)</th><th style="padding:8px">طيور/عش</th><th style="padding:8px">إجمالي الطيور</th></tr></thead><tbody>';
    data.weight_table.forEach(row => {
        const sel = selected && parseFloat(selected) === parseFloat(row.weight_kg);
        html += '<tr style="' + (sel ? 'background:#fef2f2;font-weight:700' : '') + '">';
        html += '<td style="padding:8px;border:1px solid #eee;direction:ltr">' + row.weight_kg + '</td>';
        html += '<td style="padding:8px;border:1px solid #eee">' + row.birds_per_nest + ' طائر</td>';
        html += '<td style="padding:8px;border:1px solid #eee;direction:ltr">' + (row.total_birds ? Number(row.total_birds).toLocaleString('en') : '—') + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    wrap.innerHTML = html;
}

function toggleBroilerOptions() {
    const isBroiler = document.getElementById('project_type').value === 'broiler';
    document.getElementById('broiler-options').style.display = isBroiler ? 'block' : 'none';
    if (!isBroiler) {
        document.getElementById('service_length').value = '9';
    }
}

function buildPayload() {
    return {
        project_type: document.getElementById('project_type').value,
        pricing_scope: document.getElementById('pricing_scope').value,
        length: document.getElementById('length').value,
        width: document.getElementById('width').value,
        height: document.getElementById('height').value,
        service_length: document.getElementById('service_length').value,
        tiers: document.getElementById('tiers').value,
        lines: document.getElementById('lines').value,
        bird_weight_kg: document.getElementById('bird_weight_kg')?.value,
        side_fans_count: document.getElementById('side_fans_count')?.value || null,
        heaters_count: document.getElementById('heaters_count')?.value || null,
        wall_type: document.getElementById('wall_type')?.value,
        vat_region: document.getElementById('vat_region').value,
        client_name: document.querySelector('[name="client_name"]')?.value || 'عميل',
        client_phone: document.querySelector('[name="client_phone"]')?.value || '',
    };
}

function renderBreakdown(data) {
    const labels = data.section_labels || sectionLabels;
    const bySection = {};
    (data.breakdown || []).forEach(item => {
        const sec = item.section || 'other';
        if (!bySection[sec]) bySection[sec] = [];
        bySection[sec].push(item);
    });

    let html = '';
    Object.keys(bySection).forEach(sec => {
        html += '<div style="margin-bottom:12px"><div style="font-weight:700;color:#C00000;font-size:13px;margin-bottom:6px">' + (labels[sec] || sec) + '</div>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px"><tbody>';
        bySection[sec].forEach(item => {
            html += '<tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px">' + item.label_ar + '</td><td style="padding:5px;direction:ltr;text-align:center;white-space:nowrap">' + Number(item.total).toLocaleString('en',{minimumFractionDigits:2}) + '</td></tr>';
        });
        const sub = (data.section_subtotals && data.section_subtotals[sec]) ? data.section_subtotals[sec] : 0;
        html += '<tr style="background:#f8fafc;font-weight:700"><td style="padding:5px">مجموع القسم</td><td style="padding:5px;direction:ltr;text-align:center">' + Number(sub).toLocaleString('en',{minimumFractionDigits:2}) + '</td></tr>';
        html += '</tbody></table></div>';
    });
    document.getElementById('breakdown-table').innerHTML = html || '<p>لا توجد بنود</p>';
}

function calculate() {
    const payload = buildPayload();
    lastCalcPayload = payload;

    if (!payload.length || !payload.width || !payload.height) return;

    document.getElementById('summary-card').classList.add('loading');

    fetch(calcUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('t-effective').textContent = Number(data.effective_length || 0).toFixed(1);
        document.getElementById('t-nests-line').textContent = Number(data.nests_per_line || 0).toLocaleString('en');
        document.getElementById('t-total-nests').textContent = Number(data.total_nests || 0).toLocaleString('en');
        document.getElementById('t-fans').textContent = data.back_fans_count;
        document.getElementById('t-fans-formula').textContent = data.fan_formula || '';
        document.getElementById('sum-bpn').textContent = data.birds_per_nest ?? '—';
        document.getElementById('sum-birds').textContent = Number(data.bird_count).toLocaleString('en');
        updateWeightTable(data);
        document.getElementById('sum-cooling').textContent = data.cooling_units;
        document.getElementById('sum-cooling-formula').textContent = data.cooling_formula || '';
        document.getElementById('sum-windows').textContent = data.windows_count;
        document.getElementById('sum-subtotal').textContent = Number(data.subtotal).toLocaleString('en', {minimumFractionDigits: 2}) + ' ج.م';
        document.getElementById('sum-vat').textContent = Number(data.vat_amount).toLocaleString('en', {minimumFractionDigits: 2}) + ' ج.م';
        document.getElementById('sum-total').textContent = Number(data.total).toLocaleString('en', {minimumFractionDigits: 2}) + ' ج.م';
        if (data.currency) {
            document.getElementById('sum-usd').textContent = Number(data.currency.total_usd).toLocaleString('en', {minimumFractionDigits: 2}) + ' $';
        }
        renderBreakdown(data);
    })
    .catch(() => {
        document.getElementById('breakdown-table').innerHTML = '<p style="color:#dc2626">خطأ في الحساب</p>';
    })
    .finally(() => document.getElementById('summary-card').classList.remove('loading'));
}

document.querySelectorAll('.calc-input, #project_type, #pricing_scope').forEach(el => {
    el.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(calculate, 350); });
    el.addEventListener('change', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(calculate, 350); });
});

document.getElementById('project_type').addEventListener('change', toggleBroilerOptions);

document.getElementById('btn-preview-pdf').addEventListener('click', function() {
    const payload = lastCalcPayload || buildPayload();
    if (!payload.client_name) {
        alert('أدخل اسم العميل أولاً');
        return;
    }
    fetch(pdfUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/pdf', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
    }).then(async r => {
        if (!r.ok) throw new Error('PDF failed');
        const blob = await r.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'quotation-preview.pdf';
        a.click();
        URL.revokeObjectURL(url);
    }).catch(() => alert('تعذر إنشاء PDF — تأكد من الحساب والبيانات'));
});

toggleBroilerOptions();
calculate();
</script>
</body>
</html>

