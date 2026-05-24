<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عروض الأسعار</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #C00000; color: white; }
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
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .pagination { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-title">
                <span>عروض أسعار عنابر الدواجن</span>
                <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary">عرض سعر جديد</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العرض</th>
                        <th>العميل</th>
                        <th>الأبعاد</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                    <tr>
                        <td style="font-weight:700;">{{ $q->quote_number }}</td>
                        <td>{{ $q->client_name }}</td>
                        <td>{{ $q->length }}×{{ $q->width }}×{{ $q->height }}</td>
                        <td style="direction:ltr;text-align:right;font-weight:700;">{{ number_format($q->total, 2) }} EGP</td>
                        <td><span class="status status-{{ $q->status }}">{{ $q->status_label }}</span></td>
                        <td>{{ $q->created_at->format('Y-m-d') }}</td>
                        <td><a href="{{ route('sales.quotations.show', $q) }}" class="btn btn-primary" style="padding:6px 14px;font-size:12px;">عرض</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#94a3b8;padding:40px;">لا توجد عروض أسعار بعد</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                {{ $quotations->links() }}
            </div>
        </div>
    </div>
</body>
</html>
