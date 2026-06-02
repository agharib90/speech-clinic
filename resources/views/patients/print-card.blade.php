<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة المريض - {{ $patient->name }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; margin: 0; padding: 20px; }
        .card { border: 2px dashed #ccc; padding: 30px; width: 350px; margin: auto; border-radius: 15px; }
        .card h2 { margin: 0 0 10px; color: #1f2937; }
        .card p { margin: 5px 0; color: #4b5563; font-size: 14px; }
        .barcode-section { margin-top: 20px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom: 20px; padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">طباعة البطاقة</button>

    <div class="card">
        <h2>عيادة التخاطب</h2>
        <hr style="border-top: 1px solid #ddd; margin: 10px 0;">
        <h3>{{ $patient->name }}</h3>
        <p>ولي الأمر: {{ $patient->guardian->name }}</p>
        <p>التشخيص: {{ $patient->diagnosis }}</p>

        <div class="barcode-section">
            @php
                $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
            @endphp
            {!! $generator->getBarcode($patient->barcode, $generator::TYPE_CODE_128, 1.5, 40) !!}
            <p style="font-family: monospace; letter-spacing: 2px;">{{ $patient->barcode }}</p>
        </div>
    </div>
</body>
</html>
