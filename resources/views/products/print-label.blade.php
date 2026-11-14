<!DOCTYPE html>
<html lang="ar" dir="rtl" data-size="{{ $size ?? '38x25' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'طباعة ملصقات الباركود - كيان كيدرينج' }}</title>

    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    @vite(['resources/css/print/thermal-label.css', 'resources/js/pages/print-label.js'])
</head>
<body data-size="{{ $size ?? '38x25' }}" data-width-mm="{{ $widthMm }}" data-height-mm="{{ $heightMm }}" data-auto-print="{{ !empty($autoPrint) ? 'true' : 'false' }}">

    <!-- Screen Preview Toolbar -->
    <header class="preview-toolbar no-print">
        <div class="preview-info">
            <span class="preview-title preview-title-wrap">
                <x-icon name="printer" size="16" />
                <span>طباعة ملصقات الباركود الحرارية</span>
            </span>
            <span class="preview-badge preview-badge-primary">
                <span>المقاس: {{ $widthMm }}×{{ $heightMm }} مم ({{ $barcodeSettings->printer_name ?? 'Xprinter XP-233B' }})</span>
            </span>
            <div class="preview-btn-group">
                <a href="{{ request()->fullUrlWithQuery(['size' => '38x25']) }}" class="btn {{ $size === '38x25' ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    38×25 مم (قياسي)
                </a>
                <a href="{{ request()->fullUrlWithQuery(['size' => '50x30']) }}" class="btn {{ $size === '50x30' ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    50×30 مم (تشغيلات ومستودع)
                </a>
                <a href="{{ request()->fullUrlWithQuery(['size' => '38x15']) }}" class="btn {{ $size === '38x15' ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    38×15 مم (صغير)
                </a>
            </div>
            <span class="preview-badge preview-badge-qty">
                <span id="previewTotalLabelsText">الملصقات: {{ $totalQuantity }} ملصق</span>
            </span>
        </div>

        <div class="preview-actions">
            <button type="button" data-action="print-now" class="btn btn-primary btn-inline-icon" id="printButton" title="طباعة فورية للطابعة الحرارية">
                <x-icon name="printer" size="16" />
                <span>طباعة الملصق الآن ({{ $totalQuantity }})</span>
            </button>
            <button type="button" data-action="close-window" class="btn btn-secondary btn-inline-icon">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- Instructions Tip -->
    <div class="preview-tips no-print">
        <div class="preview-tips-content">
            <span class="preview-tips-title">
                <x-icon name="info" size="14" />
                <span>توجيهات ضبط الطابعة:</span>
            </span>
            <span>في نافذة الطباعة، تأكد من اختيار طابعة الملصقات الحرارية (مثل <strong>{{ $barcodeSettings->printer_name ?? 'Xprinter XP-233B' }}</strong>) بمقاس <strong>{{ $widthMm }}x{{ $heightMm }}mm</strong> وضبط الهوامش على <strong>None</strong>.</span>
        </div>
        <button type="button" data-action="dismiss-tip" class="preview-tips-close" title="إغلاق التنبيه">
            <x-icon name="x" size="14" />
        </button>
    </div>

    <!-- Main Labels Grid -->
    <main class="roll-stage">
        <div class="roll-grid">
            @php $labelIndex = 1; @endphp
            @foreach($items as $item)
                @for($copy = 1; $copy <= $item['quantity']; $copy++)
                    <div class="label-card-preview">
                        <span class="card-index font-numeric">{{ $labelIndex }}</span>
                        <div class="thermal-label">
                            {!! $item['label_svg'] !!}
                        </div>
                    </div>
                    @php $labelIndex++; @endphp
                @endfor
            @endforeach
        </div>
    </main>

    <!-- Notification Toast -->
    <div id="printNotificationToast" class="erp-toast hidden">
        <span class="erp-toast-icon"><x-icon name="printer" size="18" /></span>
        <span id="printToastMessage" class="erp-toast-message">جاري إرسال الملصقات للطباعة...</span>
    </div>

</body>
</html>
