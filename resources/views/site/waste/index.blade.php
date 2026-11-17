@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="محاضر وسجل الهدر والتوالف وسواقط الطهي (Waste & Spoilage)" :breadcrumbs="['عمليات الموقع والمطبخ' => route('site.census.index'), 'محاضر الهدر والتوالف' => route('site.waste.index')]">
        <x-slot:actions>
            <a href="#new-waste-modal" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل محضر هدر/تالف</span>
            </a>
            <a href="{{ route('site.kitchen-issues.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="chef-hat" size="16" />
                <span>صرف خامات المطبخ</span>
            </a>
            <a href="{{ route('site.census.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>تعداد النزلاء</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي المحاضر:</span>
                <strong>{{ $wasteRecords->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('message'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="erp-card p-4">
        <form method="GET" action="{{ route('site.waste.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="erp-label">المستشفى / الموقع</label>
                <select name="hospital_id" class="erp-input">
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}" {{ $selectedHospitalId == $hosp->id ? 'selected' : '' }}>
                            {{ $hosp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">تصنيف الهدر</label>
                <select name="category" class="erp-input">
                    <option value="">-- كافة التصنيفات --</option>
                    <option value="normal_cooking_loss" {{ $selectedCategory === 'normal_cooking_loss' ? 'selected' : '' }}>فاقد طهي وتجهيز معتاد</option>
                    <option value="butchery_spoilage" {{ $selectedCategory === 'butchery_spoilage' ? 'selected' : '' }}>سواقط جزارة وعظام</option>
                    <option value="expired_batch" {{ $selectedCategory === 'expired_batch' ? 'selected' : '' }}>انتهاء صلاحية</option>
                    <option value="transit_damage" {{ $selectedCategory === 'transit_damage' ? 'selected' : '' }}>تلف أثناء النقل</option>
                    <option value="kitchen_burn" {{ $selectedCategory === 'kitchen_burn' ? 'selected' : '' }}>احتراق أو تلف أثناء الطهي</option>
                    <option value="unexplained_shortage" {{ $selectedCategory === 'unexplained_shortage' ? 'selected' : '' }}>عجز غير مبرر</option>
                </select>
            </div>
            <div>
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="filter" size="16" />
                    <span>تصفية النتائج</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Add Waste Form Card -->
    <div class="erp-card p-5 border border-amber-200 bg-amber-50/20" id="new-waste-modal">
        <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
            <x-icon name="alert-triangle" size="18" class="text-amber-600" />
            <span>تسجيل محضر هدر أو تالف جديد بالموقع</span>
        </h4>

        <form action="{{ route('site.waste.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="erp-label">المستشفى المستهدف <span class="text-rose-500">*</span></label>
                <select name="hospital_id" class="erp-input" required>
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}" {{ $selectedHospitalId == $hosp->id ? 'selected' : '' }}>
                            {{ $hosp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">الصنف الهالك <span class="text-rose-500">*</span></label>
                <select name="product_id" class="erp-input" required>
                    <option value="">-- اختر الصنف --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->name }} (سعر: {{ number_format($prod->cost_price, 2) }} ج.م)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">تاريخ المحضر <span class="text-rose-500">*</span></label>
                <input type="date" name="waste_date" class="erp-input font-numeric" value="{{ date('Y-m-d') }}" required />
            </div>
            <div>
                <label class="erp-label">الكمية التالفة / الهدر <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" name="quantity" class="erp-input font-numeric" placeholder="مثال: 5.50" required />
            </div>

            <div>
                <label class="erp-label">تصنيف الهدر <span class="text-rose-500">*</span></label>
                <select name="waste_category" class="erp-input" required>
                    <option value="normal_cooking_loss">فاقد طهي وتجهيز معتاد</option>
                    <option value="butchery_spoilage">سواقط جزارة وتشذيب</option>
                    <option value="expired_batch">انتهاء صلاحية</option>
                    <option value="kitchen_burn">تلف أو احتراق بالمطبخ</option>
                    <option value="transit_damage">تلف أثناء النقل</option>
                    <option value="unexplained_shortage">عجز غير مبرر</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="erp-label">السبب والتبرير الفني <span class="text-rose-500">*</span></label>
                <input type="text" name="reason" class="erp-input" placeholder="مثال: فاقد تقشير وتنظيف بطاطس زائد..." required />
            </div>
            <div>
                <label class="erp-label">&nbsp;</label>
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="check" size="16" />
                    <span>حفظ واعتماد المحضر</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Waste Records Table -->
    <div class="erp-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم المحضر</th>
                        <th>التاريخ</th>
                        <th>المستشفى</th>
                        <th>الصنف</th>
                        <th>الكمية</th>
                        <th>تكلفة الوحدة</th>
                        <th>إجمالي الخسارة</th>
                        <th>التصنيف</th>
                        <th>السبب</th>
                        <th>المسؤول</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wasteRecords as $w)
                        <tr>
                            <td class="font-mono font-bold text-sky-700">{{ $w->waste_number }}</td>
                            <td class="font-numeric">{{ $w->waste_date?->format('Y-m-d') }}</td>
                            <td>{{ $w->hospital->name }}</td>
                            <td class="font-bold text-slate-800">{{ $w->product->name }}</td>
                            <td class="font-numeric font-bold text-rose-700">{{ number_format($w->quantity, 2) }} {{ $w->unit }}</td>
                            <td class="font-numeric">{{ number_format($w->unit_cost, 2) }}</td>
                            <td class="font-numeric font-bold text-rose-800">{{ number_format($w->total_cost_value, 2) }} ج.م</td>
                            <td>
                                @if($w->is_abnormal)
                                    <span class="erp-badge erp-badge-danger">غير طبيعي (تنبيه)</span>
                                @else
                                    <span class="erp-badge erp-badge-neutral">{{ $w->waste_category }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600 max-w-xs truncate">{{ $w->reason }}</td>
                            <td>{{ $w->reporter->name ?? 'النظام' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                لا توجد محاضر هدر مسجلة لهذه المعايير
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wasteRecords->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $wasteRecords->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
