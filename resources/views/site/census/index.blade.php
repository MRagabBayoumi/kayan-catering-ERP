@extends('layouts.app')

@section('title', 'تشغيل وأعداد الموقع اليومية - مدير الموقع')

@section('control_panel')
    <x-control-panel title="تشغيل وأعداد الموقع اليومية" :breadcrumbs="['إدارة تشغيل المواقع' => route('site.census.index'), 'أعداد اليوم' => route('site.census.index')]">
        <x-slot:actions>
            <a href="{{ route('site.kitchen-issues.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="package" size="16" />
                <span>أذون صرف المخزن الفرعي</span>
            </a>
            <a href="{{ route('costing.reconciliation.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clipboard-check" size="16" />
                <span>مطابقة الهدر والعجز</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($hospital)
                <span class="erp-badge erp-badge-primary px-3 py-1 text-sm font-semibold">
                    <x-icon name="building-2" size="14" />
                    <span>{{ $hospital->name }}</span>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- 1. Header Information & Quota Counter -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Site & Hospital Info Card -->
        <div class="erp-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 font-medium">الموقع / المستشفى</span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1">
                        {{ $hospital?->name ?? 'غير محدد' }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        السعة التعاقدية: {{ $hospital?->daily_patient_capacity ?? 0 }} سرير/وجبة
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <x-icon name="building-2" size="24" />
                </div>
            </div>
            @if(auth()->user()->role !== 'site_manager' && count($allHospitals) > 1)
                <form action="{{ route('site.census.index') }}" method="GET" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex gap-2">
                    <div class="flex-1">
                        <label class="erp-form-label text-xs">تبديل المستشفى (للإدارة)</label>
                        <select name="hospital_id" class="erp-form-control text-xs">
                            @foreach($allHospitals as $hosp)
                                <option value="{{ $hosp->id }}" {{ $hospital?->id === $hosp->id ? 'selected' : '' }}>
                                    {{ $hosp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="erp-btn erp-btn-secondary text-xs">عرض</button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Date & Shift Info -->
        <div class="erp-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 font-medium">تاريخ اليوم المعتمد</span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1 font-mono">
                        {{ $carbonDate->format('Y-m-d') }}
                    </h3>
                    <span class="erp-badge erp-badge-info mt-2">
                        {{ $carbonDate->translatedFormat('l') }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <x-icon name="calendar" size="24" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500">
                <span>تاريخ السيرفر التلقائي المعتمد لإثبات الوجبات اليومية</span>
            </div>
        </div>

        <!-- Weekly Quota Tracker Card -->
        <div class="erp-card p-5">
            <span class="text-xs text-slate-500 font-medium">حصة الأسبوع الحالي (طبقاً لكراسة الشروط)</span>
            <div class="mt-3 space-y-3">
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-slate-700 dark:text-slate-200">لحم مشفى (3 أيام/أسبوع)</span>
                        <span class="font-mono text-primary">{{ $meatDaysCount }} / 3 أيام</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        @for($i = 1; $i <= 3; $i++)
                            <div class="h-2 flex-1 rounded-full {{ $i <= $meatDaysCount ? 'bg-primary' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                        @endfor
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-slate-700 dark:text-slate-200">دواجن (4 أيام/أسبوع)</span>
                        <span class="font-mono text-emerald-600">{{ $poultryDaysCount }} / 4 أيام</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        @for($i = 1; $i <= 4; $i++)
                            <div class="h-2 flex-1 rounded-full {{ $i <= $poultryDaysCount ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Daily Cooking Decision & Census Form -->
    <div class="erp-card p-6">
        <form action="{{ route('site.census.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="hospital_id" value="{{ $hospital?->id }}">
            <input type="hidden" name="census_date" value="{{ $date }}">

            <!-- Choice 1: Protein -->
            <div class="border-b border-slate-200 dark:border-slate-700 pb-5">
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">
                    1. اختيار بروتين تشغيل اليوم (قرار مدير الموقع والشيف):
                </label>
                <p class="text-xs text-slate-500 mb-3">حدد الصنف الذي سيتم طهيه اليوم للنزلاء والعاملين</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-primary transition-colors {{ old('protein_choice', $census?->protein_choice ?? 'meat') === 'meat' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-slate-200 dark:border-slate-700' }}">
                        <input type="radio" name="protein_choice" value="meat" class="w-4 h-4 text-primary focus:ring-primary" {{ old('protein_choice', $census?->protein_choice ?? 'meat') === 'meat' ? 'checked' : '' }}>
                        <div class="mr-3">
                            <span class="block text-sm font-bold text-slate-800 dark:text-slate-100">لحم بقري مشفى (150 جم للوجبة)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">المقنن المعياري 150 جم للفرد - حصة 3 أيام أسبوعياً</span>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:border-emerald-500 transition-colors {{ old('protein_choice', $census?->protein_choice) === 'poultry' ? 'border-emerald-500 bg-emerald-500/5 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700' }}">
                        <input type="radio" name="protein_choice" value="poultry" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500" {{ old('protein_choice', $census?->protein_choice) === 'poultry' ? 'checked' : '' }}>
                        <div class="mr-3">
                            <span class="block text-sm font-bold text-slate-800 dark:text-slate-100">دواجن طازجة (ربع فرخة - 275 جم للوجبة)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">المقنن المعياري 275 جم للفرد - حصة 4 أيام أسبوعياً</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Choice 2: Carbs & Vegetables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-200 dark:border-slate-700 pb-5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">
                        2. النشويات المجهزة اليوم:
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="carb_choice" value="rice_vermicelli" class="w-4 h-4 text-primary" {{ old('carb_choice', $census?->carb_choice ?? 'rice_vermicelli') === 'rice_vermicelli' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">أرز بالشعرية (85 جم أرز + 15 جم شعرية)</span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="carb_choice" value="plain_rice" class="w-4 h-4 text-primary" {{ old('carb_choice', $census?->carb_choice) === 'plain_rice' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">أرز أبيض بدون شعرية (100 جم)</span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="carb_choice" value="pasta" class="w-4 h-4 text-primary" {{ old('carb_choice', $census?->carb_choice) === 'pasta' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">مكرونة معبأة بالصلصة (100 جم)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">
                        3. الخضار / البقوليات المجهزة اليوم:
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="veg_choice" value="cooked_veg" class="w-4 h-4 text-primary" {{ old('veg_choice', $census?->veg_choice ?? 'cooked_veg') === 'cooked_veg' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">خضار طازج مطبوخ (200 جم للفرد)</span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="veg_choice" value="beans" class="w-4 h-4 text-primary" {{ old('veg_choice', $census?->veg_choice) === 'beans' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">فاصوليا بيضاء جافة معبأة (80 جم للفرد)</span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="radio" name="veg_choice" value="cowpea" class="w-4 h-4 text-primary" {{ old('veg_choice', $census?->veg_choice) === 'cowpea' ? 'checked' : '' }}>
                            <span class="mr-3 text-xs font-semibold text-slate-700 dark:text-slate-200">لوبيا جافة معبأة (80 جم للفرد)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Choice 3: Patient Counts by Diet Schedule -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            4. أعداد المستشفى الفعلية لليوم (تعداد التمريض المعتمد):
                        </h4>
                        <p class="text-xs text-slate-500">أدخل عدد الوجبات المستحقة لكل جدول تعاقدي</p>
                    </div>
                    @if($census)
                        <span class="erp-badge erp-badge-success font-mono font-bold">
                            إجمالي المسجل: {{ number_format($census->total_patient_count) }} وجبة
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>كود الجدول</th>
                                <th>اسم الجدول والنوعية</th>
                                <th>نوع الوجبة</th>
                                <th>حصة الخبز اليومية</th>
                                <th>تعداد الأفراد / الوجبات لليوم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $sched)
                                @php
                                    $itemCount = $census?->items->firstWhere('diet_schedule_id', $sched->id)?->patient_count ?? 0;
                                @endphp
                                <tr>
                                    <td class="font-mono text-xs font-bold text-primary">{{ $sched->code }}</td>
                                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $sched->name }}</td>
                                    <td><span class="erp-badge erp-badge-info">{{ $sched->meal_type }}</span></td>
                                    <td class="font-mono text-xs">{{ $sched->daily_bread_count }} رغيف</td>
                                    <td>
                                        <input type="number" name="items[{{ $sched->id }}]" value="{{ old('items.'.$sched->id, $itemCount) }}" min="0" class="erp-form-control w-32 font-mono font-bold" placeholder="0">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <label class="erp-form-label">ملاحظات تشغيل اليوم أو استثناءات الطوارئ</label>
                <textarea name="notes" rows="2" class="erp-form-control text-xs" placeholder="أي ملاحظات تخص تأخير توريد أو وجبات إضافية بتصريح الإدارة">{{ old('notes', $census?->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="erp-btn erp-btn-primary px-6">
                    <x-icon name="check" size="18" />
                    <span>حفظ واعتماد تشغيل وأعداد اليوم</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Recent Censuses History Table -->
    @if($recentCensuses->isNotEmpty())
        <div class="erp-card">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">سجل تشغيلات الأيام السابقة بالموقع</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>البروتين المطهي</th>
                            <th>النشويات</th>
                            <th>الخضار</th>
                            <th>إجمالي الوجبات</th>
                            <th>المسؤول</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCensuses as $row)
                            <tr>
                                <td class="font-mono text-xs font-bold">{{ $row->census_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="erp-badge {{ $row->protein_choice === 'meat' ? 'erp-badge-primary' : 'erp-badge-success' }}">
                                        {{ $row->protein_label }}
                                    </span>
                                </td>
                                <td class="text-xs">{{ $row->carb_label }}</td>
                                <td class="text-xs">{{ $row->veg_label }}</td>
                                <td class="font-mono font-bold">{{ number_format($row->total_patient_count) }}</td>
                                <td class="text-xs text-slate-500">{{ $row->siteManager?->name ?? 'مدير الموقع' }}</td>
                                <td><span class="erp-badge erp-badge-success">معتمد ومثبت</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
