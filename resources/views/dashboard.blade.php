@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" dir="rtl">
    <!-- Header with AI Copilot Spotlight Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-700/50 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 text-right">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs rounded-full font-semibold">
                <span>🤖 Kayan AI Copilot</span>
                <span>·</span>
                <span>متصل بقاعدة البيانات لحظياً</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">منظومة كيان لإعاشة وتغذية المستشفيات (Catering ERP)</h1>
            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                إدارة متكاملة تفصل بين المبيعات والمناقصات، وحسابات التكاليف بالمقنن (BOM)، والمخازن متعددة الباتشات والـ FIFO، مع مساعد ذكاء اصطناعي فوري يجيبك عن الأرصدة، الأرباح، والهدر.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="button" data-action="open-ai-copilot" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
                <span>💬 اسأل المساعد الذكي</span>
            </button>
        </div>
    </div>

    <!-- Quick KPIs Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Hospitals Active -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">المستشفيات والجهات المتعاقدة</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $hospitals_count ?? 0 }}</h3>
                <span class="text-xs text-emerald-600 font-medium">عقود سارية ونشطة</span>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl">
                🏥
            </div>
        </div>

        <!-- 2. Daily Meals Capacity -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">طاقة الوجبات اليومية المجهزة</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($total_patient_capacity ?? 0) }}</h3>
                <span class="text-xs text-blue-600 font-medium">وجبة ومريض يومياً</span>
            </div>
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-xl">
                🍲
            </div>
        </div>

        <!-- 3. Active FIFO Batches -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">تشغيلات الشراء النشطة (FIFO)</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $active_batches_count ?? 0 }}</h3>
                <span class="text-xs text-amber-600 font-medium">مراقبة تواريخ الصلاحية</span>
            </div>
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400 text-xl">
                📦
            </div>
        </div>

        <!-- 4. Active Dispatches / Van Transit -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">أذون الصرف قيد التوريد</p>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $pending_dispatches_count ?? 0 }}</h3>
                <span class="text-xs text-indigo-600 font-medium">بعهدة سيارات المناديب</span>
            </div>
            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xl">
                🚚
            </div>
        </div>
    </div>

    <!-- Two Columns Layout: Recent Dispatches & Expiring Soon Batches -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Dispatches -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-700 pb-3">
                <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <span>🚚</span>
                    <span>أحدث أذون التحميل والتوريد للمستشفيات</span>
                </h3>
            </div>
            <div class="space-y-3">
                @forelse($recent_dispatches ?? [] as $dsp)
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $dsp->dispatch_number }}</span>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $dsp->hospital->name ?? 'مستشفى' }}</p>
                            <span class="text-xs text-slate-400">السائق: {{ $dsp->driver->name ?? 'غير محدد' }}</span>
                        </div>
                        <span class="px-2.5 py-1 text-xs rounded-full font-medium {{ $dsp->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $dsp->status === 'delivered' ? 'تم التسليم' : 'في الطريق' }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">لا توجد أذون تحميل مسجلة حالياً.</p>
                @endforelse
            </div>
        </div>

        <!-- Expiring Soon Batches (FIFO Alert) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-700 pb-3">
                <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2 text-rose-600 dark:text-rose-400">
                    <span>⚠️</span>
                    <span>تنبيه الباتشات الأقرب انتهاءً (الأولوية للصرف الفوري)</span>
                </h3>
            </div>
            <div class="space-y-3">
                @forelse($expiring_soon_batches ?? [] as $b)
                    <div class="p-3 rounded-lg bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/40 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-mono text-slate-500">تشغيلة: {{ $b->batch_number }}</span>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $b->product->name ?? 'صنف' }}</p>
                            <span class="text-xs text-slate-500">متبقي: {{ $b->remaining_quantity }} {{ $b->product->unit ?? 'كجم' }} بسعر {{ $b->cost_price }} ج.م</span>
                        </div>
                        <div class="text-left">
                            <span class="text-xs font-bold text-rose-600 block">صلاحية: {{ $b->expiry_date }}</span>
                            <span class="text-[11px] text-slate-400">{{ $b->warehouse->name ?? 'المخزن' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-emerald-600 py-4 text-center">✅ جميع الباتشات بحالة ممتازة ولا توجد أصناف قريبة الانتهاء.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
