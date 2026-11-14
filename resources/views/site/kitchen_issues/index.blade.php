@extends('layouts.app')

@section('title', 'صرف خامات المخزن الفرعي للمطبخ - إدارة المواقع')

@section('control_panel')
    <x-control-panel title="صرف خامات المخزن الفرعي إلى شيف المطبخ" :breadcrumbs="['إدارة تشغيل المواقع' => route('site.census.index'), 'صرف المطبخ' => route('site.kitchen-issues.index')]">
        <x-slot:actions>
            <a href="{{ route('site.census.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calendar" size="16" />
                <span>أعداد وتشغيل الموقع</span>
            </a>
            <a href="{{ route('costing.reconciliation.index') }}" class="erp-btn erp-btn-primary">
                <x-icon name="clipboard-check" size="16" />
                <span>تقرير مطابقة الهدر والعجز</span>
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

    <!-- Form: Issue Ingredients to Kitchen -->
    <div class="erp-card p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-700 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                    تسجيل إذن صرف خامات من المخزن الفرعي إلى المطبخ
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    إثبات الكميات الفعلية المسلمة لشيف المطبخ لإعداد وجبات اليوم
                </p>
            </div>
            <span class="font-mono text-xs erp-badge erp-badge-neutral">
                التاريخ: {{ $date }}
            </span>
        </div>

        <form action="{{ route('site.kitchen-issues.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="hospital_id" value="{{ $hospital?->id }}">
            <input type="hidden" name="issue_date" value="{{ $date }}">

            <div class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="md:col-span-2">
                        <label class="erp-form-label text-xs">الصنف المنصرف</label>
                        <select name="items[0][product_id]" class="erp-form-control" required>
                            <option value="">-- اختر الصنف --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الكمية الفعلية المنصرفة</label>
                        <input type="number" step="0.001" name="items[0][quantity_issued]" class="erp-form-control font-mono font-bold" placeholder="مثلاً: 18.5" required>
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الوحدة</label>
                        <select name="items[0][unit]" class="erp-form-control">
                            <option value="kg">كيلوجرام (kg)</option>
                            <option value="pcs">قطعة / رغيف (pcs)</option>
                            <option value="packet">باكت / علبة</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="md:col-span-2">
                        <label class="erp-form-label text-xs">صنف إضافي (2)</label>
                        <select name="items[1][product_id]" class="erp-form-control">
                            <option value="">-- اختياري --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الكمية</label>
                        <input type="number" step="0.001" name="items[1][quantity_issued]" class="erp-form-control font-mono font-bold" placeholder="0.00">
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الوحدة</label>
                        <select name="items[1][unit]" class="erp-form-control">
                            <option value="kg">كيلوجرام (kg)</option>
                            <option value="pcs">قطعة / رغيف (pcs)</option>
                            <option value="packet">باكت / علبة</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="md:col-span-2">
                        <label class="erp-form-label text-xs">صنف إضافي (3)</label>
                        <select name="items[2][product_id]" class="erp-form-control">
                            <option value="">-- اختياري --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الكمية</label>
                        <input type="number" step="0.001" name="items[2][quantity_issued]" class="erp-form-control font-mono font-bold" placeholder="0.00">
                    </div>
                    <div>
                        <label class="erp-form-label text-xs">الوحدة</label>
                        <select name="items[2][unit]" class="erp-form-control">
                            <option value="kg">كيلوجرام (kg)</option>
                            <option value="pcs">قطعة / رغيف (pcs)</option>
                            <option value="packet">باكت / علبة</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="erp-form-label text-xs">ملاحظات إذن الصرف</label>
                <input type="text" name="notes" class="erp-form-control text-xs" placeholder="مثال: تسليم لشيف الوردية الصباحية لإعداد وجبة الغداء">
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>تأكيد صرف الخامات للمطبخ</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Table of Today's Issues -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                    سجل المنصرف الفعلي للمطبخ لتاريخ {{ $date }}
                </h4>
                <p class="text-xs text-slate-500">إجمالي البنود المسلمة للشيف اليوم: {{ count($issues) }} صنف</p>
            </div>
            <a href="{{ route('costing.reconciliation.index', ['hospital_id' => $hospital?->id, 'date' => $date]) }}" class="erp-btn erp-btn-secondary text-xs">
                <x-icon name="bar-chart-2" size="14" />
                <span>عرض تقرير المطابقة مع المقنن</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم الإذن</th>
                        <th>الصنف المنصرف</th>
                        <th>الكمية الفعلية</th>
                        <th>الوحدة</th>
                        <th>مسؤول الصرف</th>
                        <th>وقت التسجيل</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td class="font-mono text-xs font-bold text-primary">{{ $issue->issue_number }}</td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $issue->product->name }}</td>
                            <td class="font-mono font-bold text-base text-slate-900 dark:text-slate-100">
                                {{ number_format($issue->quantity_issued, 3) }}
                            </td>
                            <td><span class="erp-badge erp-badge-info">{{ $issue->unit }}</span></td>
                            <td class="text-xs text-slate-600 dark:text-slate-400">{{ $issue->issuer?->name ?? 'أمين المخزن الفرعي' }}</td>
                            <td class="font-mono text-xs text-slate-400">{{ $issue->created_at->format('H:i') }}</td>
                            <td class="text-xs text-slate-500">{{ $issue->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                لم يتم تسجيل أي أذون صرف للمطبخ في هذا التاريخ حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
