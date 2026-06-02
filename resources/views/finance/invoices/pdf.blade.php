<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 14px; color: #333; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .invoice-box { padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { padding: 12px; border: 1px solid #eee; }
        table th { background-color: #f8f8f8; font-weight: bold; }
        .total-section { text-align: left; margin-top: 20px; font-size: 20px; font-weight: bold; color: #2563eb; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div>
                <h2 style="margin:0; color:#1f2937;">عيادة التخاطب</h2>
                <p style="color:#6b7280;">فاتورة ضريبية مبسطة</p>
            </div>
            <div style="text-align:left;">
                <h3 style="margin:0;">فاتورة #: {{ $invoice->invoice_number }}</h3>
                <p>التاريخ: {{ $invoice->issue_date }}</p>
                <p>الحالة: {{ $invoice->status }}</p>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <strong>اسم المريض:</strong> {{ $invoice->patient->name }}<br>
            @if($invoice->notes)
            <strong>ملاحظات:</strong> {{ $invoice->notes }}
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>الوصف</th>
                    <th>الكمية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }} ر.س</td>
                    <td>{{ number_format($item->total, 2) }} ر.س</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            الإجمالي المستحق: {{ number_format($invoice->total, 2) }} ر.س
        </div>
    </div>
</body>
</html>
