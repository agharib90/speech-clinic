<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        /* إعدادات عامة للـ PDF */
        body {
            font-family: 'DejaVu Sans', sans-serif; /* خط يدعم العربية في DomPDF */
            direction: rtl;
            text-align: right;
            font-size: 14px;
            line-height: 1.6;
        }

        /* تنسيقات الجداول */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: right;
        }

        th {
            background-color: #f3f4f6;
        }

        /* تنسيق العناوين */
        h1 { font-size: 24px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 18px; margin-top: 20px; border-bottom: 2px solid #000; padding-bottom: 5px; }

        /* ترويسة التقرير */
        .header-info {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            background: #fafafa;
        }
    </style>
</head>
<body>

    <!-- ترويسة التقرير -->
    <h1>تقرير التطور اللغوي والمهاري</h1>

    <div class="header-info">
        <p><strong>اسم المريض:</strong> {{ $program->patient->name }}</p>
        <p><strong>الأخصائي:</strong> {{ $program->therapist->name ?? 'غير محدد' }}</p>
        <p><strong>تاريخ التقرير:</strong> {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- قسم الجلسات المنفذة -->
    <h2>الجلسات المنفذة</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>تاريخ الجلسة</th>
                <th>ملاحظات</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($program->sessions as $index => $session)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $session->created_at->format('Y-m-d') }}</td>
                <td>{{ $session->notes ?? 'لا توجد ملاحظات' }}</td>
                <td>{{ $session->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- قسم قياسات التطور (Milestones) -->
    <h2>قياسات التطور</h2>
    <table>
        <thead>
            <tr>
                <th>المهارة</th>
                <th>النسبة %</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($program->milestones as $milestone)
            <tr>
                <td>{{ $milestone->skill_area }}</td>
                <td>{{ $milestone->current_score }}%</td>
                <td>{{ $milestone->recorded_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
