@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل وشجرة تتبع دورة المستندات (Document Traceability Chain)" :breadcrumbs="['الرئيسية' => route('home'), 'تتبع المستندات' => '#']">
        <x-slot:actions>
            <a href="{{ url()->previous() }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمستند</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-primary font-mono text-xs px-2.5 py-1">
                {{ $trace['doc_number'] ?? '-' }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" dir="rtl">
    <!-- Current Document Spotlight Card -->
    <div class="erp-card p-6 bg-gradient-to-l from-slate-900 to-sky-900 text-white rounded-xl shadow-lg border border-sky-800">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs text-sky-300 font-mono tracking-wider">{{ $trace['type'] }}</span>
                <h2 class="text-2xl font-black text-white font-mono mt-1">{{ $trace['doc_number'] }}</h2>
                <span class="text-sm text-sky-100 mt-1 block">{{ $trace['title'] }}</span>
            </div>
            <div class="text-left">
                <span class="erp-badge erp-badge-neutral text-xs">الحالة: {{ $trace['status'] }}</span>
            </div>
        </div>
    </div>

    <!-- Upstream Dependencies (الأصول والمستندات المنشئة) -->
    <div class="erp-card p-5">
        <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2 pb-2 border-b border-slate-100">
            <x-icon name="corner-left-up" size="18" class="text-sky-600" />
            <span>المستندات والجهات المنشئة السابقة (Upstream Origins)</span>
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @forelse($trace['upstream'] as $up)
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                    <span class="text-xs text-slate-400 block">{{ $up['label'] }}</span>
                    @if(!empty($up['route']) && $up['route'] !== '#')
                        <a href="{{ $up['route'] }}" class="font-bold text-sky-700 hover:underline text-sm block mt-1">
                            {{ $up['value'] }}
                        </a>
                    @else
                        <strong class="font-bold text-slate-800 text-sm block mt-1">{{ $up['value'] }}</strong>
                    @endif
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-slate-400 text-xs">
                    هذا المستند يمثل نقطة البداية الأساسية في الدورة التشغيلية
                </div>
            @endforelse
        </div>
    </div>

    <!-- Downstream Impacts (المخرجات والعمليات اللاحقة) -->
    <div class="erp-card p-5">
        <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2 pb-2 border-b border-slate-100">
            <x-icon name="corner-down-right" size="18" class="text-emerald-600" />
            <span>المخرجات والعمليات التشغيلية والمالية اللاحقة (Downstream Impacts)</span>
        </h4>

        <div class="space-y-4">
            @foreach($trace['downstream'] as $key => $entities)
                @if($entities && (is_countable($entities) ? count($entities) > 0 : true))
                    <div class="border border-slate-100 rounded-lg p-4 bg-slate-50/50">
                        <div class="font-bold text-xs text-slate-700 mb-2 uppercase font-mono">
                            {{ str_replace('_', ' ', $key) }}
                        </div>

                        @if(is_iterable($entities))
                            <div class="divide-y divide-slate-100">
                                @foreach($entities as $entity)
                                    <div class="py-2 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="font-mono font-bold text-slate-800">
                                                {{ $entity->po_number ?? $entity->grn_number ?? $entity->dispatch_number ?? $entity->receipt_number ?? $entity->order_number ?? $entity->settlement_number ?? $entity->entry_number ?? '#' . $entity->id }}
                                            </span>
                                            <span class="text-slate-500">
                                                ({{ $entity->status ?? 'مسجل' }})
                                            </span>
                                        </div>
                                        <span class="text-slate-400 font-numeric">
                                            {{ $entity->created_at?->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-2 flex items-center justify-between text-xs">
                                <span class="font-mono font-bold text-slate-800">
                                    {{ $entities->receipt_number ?? $entities->settlement_number ?? $entities->entry_number ?? $entities->po_number ?? '#' . $entities->id }}
                                </span>
                                <span class="text-slate-400 font-numeric">
                                    {{ $entities->created_at?->format('Y-m-d H:i') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
