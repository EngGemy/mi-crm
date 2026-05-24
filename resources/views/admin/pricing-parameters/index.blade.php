<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاملات التسعير</title>
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
            max-width: 1100px;
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
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #f8fafc;
            color: #475569;
            padding: 12px;
            text-align: right;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        tr:hover td { background: #f8fafc; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #C00000; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-title">معاملات التسعير</div>
            <table>
                <thead>
                    <tr>
                        <th>المعامل</th>
                        <th>القيمة</th>
                        <th>الوحدة</th>
                        <th>التصنيف</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parameters as $param)
                    <tr>
                        <td>
                            <div style="font-weight:700;">{{ $param->label_ar }}</div>
                            <div style="font-size:12px;color:#94a3b8;">{{ $param->key }}</div>
                        </td>
                        <td style="font-weight:700;direction:ltr;text-align:right;">{{ number_format($param->value, 2) }}</td>
                        <td>{{ $param->unit }}</td>
                        <td>{{ $param->category }}</td>
                        <td>
                            <span class="badge {{ $param->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $param->is_active ? 'مفعل' : 'معطل' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.pricing-parameters.edit', $param) }}" class="btn btn-primary">تعديل</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
