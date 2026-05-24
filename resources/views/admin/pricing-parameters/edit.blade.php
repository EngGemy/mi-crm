<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل معامل التسعير</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            line-height: 1.7;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 28px;
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #0f172a;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #C00000;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #C00000; color: white; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .actions { display: flex; gap: 12px; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-title">تعديل: {{ $pricingParameter->label_ar }}</div>
            <form method="POST" action="{{ route('admin.pricing-parameters.update', $pricingParameter) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>المعرف الفني</label>
                    <input type="text" value="{{ $pricingParameter->key }}" disabled>
                </div>

                <div class="form-group">
                    <label>الاسم بالعربية *</label>
                    <input type="text" name="label_ar" value="{{ old('label_ar', $pricingParameter->label_ar) }}" required>
                </div>

                <div class="form-group">
                    <label>الاسم بالإنجليزية *</label>
                    <input type="text" name="label_en" value="{{ old('label_en', $pricingParameter->label_en) }}" required>
                </div>

                <div class="form-group">
                    <label>القيمة *</label>
                    <input type="number" step="0.01" name="value" value="{{ old('value', $pricingParameter->value) }}" required>
                </div>

                <div class="form-group">
                    <label>الوحدة</label>
                    <input type="text" name="unit" value="{{ old('unit', $pricingParameter->unit) }}">
                </div>

                <div class="form-group">
                    <label>الحالة</label>
                    <select name="is_active">
                        <option value="1" {{ old('is_active', $pricingParameter->is_active) ? 'selected' : '' }}>مفعل</option>
                        <option value="0" {{ !old('is_active', $pricingParameter->is_active) ? 'selected' : '' }}>معطل</option>
                    </select>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    <a href="{{ route('admin.pricing-parameters.index') }}" class="btn btn-secondary">رجوع</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
