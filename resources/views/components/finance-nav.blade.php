@php
    $pendingDisbursements = \App\Models\DisbursementOrder::where('status', \App\Models\DisbursementOrder::STATUS_PENDING)->count();
    $pendingReceipts = \App\Models\ReceiptOrder::where('status', \App\Models\ReceiptOrder::STATUS_PENDING)->count();
    $pendingClosing = \App\Models\DailyCashClosing::where('status', \App\Models\DailyCashClosing::STATUS_PENDING_APPROVAL)->count();

    $financeTabs = [
        [
            'label' => 'أذونات الصرف اللحظية',
            'url' => route('finance.disbursements.index'),
            'icon' => 'truck',
            'active' => request()->routeIs('finance.disbursements*'),
            'badge' => $pendingDisbursements > 0 ? $pendingDisbursements : null,
            'badge_class' => 'erp-badge-danger',
        ],
        [
            'label' => 'الوارد واعتماد التحصيلات',
            'url' => route('finance.receipts.index'),
            'icon' => 'arrow-down',
            'active' => request()->routeIs('finance.receipts*'),
            'badge' => $pendingReceipts > 0 ? $pendingReceipts : null,
            'badge_class' => 'erp-badge-warning',
        ],
        [
            'label' => 'الخزينة وعهد النثريات',
            'url' => route('finance.treasury.index'),
            'icon' => 'wallet',
            'active' => request()->routeIs('finance.treasury*'),
        ],
        [
            'label' => 'التقفيل اليومي ومحضر العجز',
            'url' => route('finance.closing.index'),
            'icon' => 'pie-chart',
            'active' => request()->routeIs('finance.closing*'),
            'badge' => $pendingClosing > 0 ? 'معلق' : null,
            'badge_class' => 'erp-badge-danger',
        ],
        [
            'label' => 'دفتر اليومية العامة التلقائي',
            'url' => route('finance.journal.index'),
            'icon' => 'file-text',
            'active' => request()->routeIs('finance.journal*'),
        ],
        [
            'label' => 'دفتر الأستاذ العام',
            'url' => route('finance.ledger.index'),
            'icon' => 'book-open',
            'active' => request()->routeIs('finance.ledger*'),
        ],
        [
            'label' => 'ترصيد الموردين والعهد',
            'url' => route('finance.settlements.index'),
            'icon' => 'clipboard-check',
            'active' => request()->routeIs('finance.settlements*'),
        ],
        [
            'label' => 'التفريدة الشهرية للجهات',
            'url' => route('finance.breakdown.index'),
            'icon' => 'calendar',
            'active' => request()->routeIs('finance.breakdown*'),
        ],
    ];
@endphp

<x-module-nav :tabs="$financeTabs" />
