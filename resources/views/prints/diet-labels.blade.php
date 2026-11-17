<!DOCTYPE html>
<html lang="ar" dir="rtl" data-size="50x30">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    @vite(['resources/css/print/thermal-label.css', 'resources/js/pages/invoice-print.js'])
</head>
<body data-size="50x30" data-width-mm="50" data-height-mm="30">
    <header class="preview-toolbar no-print">
        <div class="preview-info">
            <span class="preview-title">
                <x-icon name="printer" size="16" />
                <span>طباعة ملصقات الوجبات الحرارية للنزلاء (50×30 مم)</span>
            </span>
            <span class="preview-badge preview-badge-primary">
                {{ $census->hospital->name }} • {{ $census->census_date?->format('Y-m-d') }}
            </span>
        </div>
        <div class="preview-actions">
            <button type="button" data-action="print-now" class="btn btn-primary">
                طباعة الملصقات الحرارية
            </button>
            <button type="button" data-action="close-window" class="btn btn-secondary">
                إغلاق
            </button>
        </div>
    </header>

    <main class="roll-stage">
        <div class="roll-grid">
            @foreach($census->items as $item)
                @for($i = 0; $i < min(10, (int)$item->patient_count); $i++)
                    <div class="label-card-preview">
                        <div class="thermal-label p-2 bg-white text-black flex flex-col justify-between box-border border border-slate-300">
                            <div class="flex items-center justify-between border-b border-black pb-0.5 text-[8px] font-bold">
                                <span>{{ $company->company_name_ar ?? 'كيان للخدمات الغذائية' }}</span>
                                <span class="font-numeric">{{ $census->census_date?->format('d/m') }}</span>
                            </div>
                            <div class="text-[9px] font-bold truncate">
                                {{ $census->hospital->name }}
                            </div>
                            <div class="text-[11px] font-black bg-black text-white text-center py-0.5 rounded truncate">
                                {{ $item->dietSchedule->name ?? 'وجبة عامة' }}
                            </div>
                            <div class="flex items-center justify-between text-[7px] text-slate-700">
                                <span>صحي علاجي</span>
                                <span>صالح يوم الإنتاج</span>
                            </div>
                        </div>
                    </div>
                @endfor
            @endforeach
        </div>
    </main>
</body>
</html>
