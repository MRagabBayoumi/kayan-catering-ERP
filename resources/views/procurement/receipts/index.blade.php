@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="أذون فحص واستلام التوريدات المخزنية (GRN)" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'فحص واستلام التوريدات' => route('procurement.receipts.index')]">
        <x-slot:actions>
            <a href="#new-receipt-modal" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إذن فحص واستلام جديد</span>
            </a>
            <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>أوامر الشراء المعتمدة</span>
            </a>
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>أرصدة المخازن والتشغيلات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي أذون الاستلام:</span>
                <strong>{{ $receipts->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $procurementTabs = [
            [
                'label' => 'أوامر الشراء والتوريد',
                'url' => route('procurement.orders.index'),
                'icon' => 'shopping-bag',
                'active' => request()->routeIs('procurement.orders*'),
            ],
            [
                'label' => 'فحص واستلام التوريدات (GRN)',
                'url' => route('procurement.receipts.index'),
                'icon' => 'clipboard-check',
                'active' => request()->routeIs('procurement.receipts*'),
                'badge' => $receipts->total(),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'سجل الموردين المعتمدين',
                'url' => route('procurement.suppliers.index'),
                'icon' => 'truck',
                'active' => request()->routeIs('procurement.suppliers*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$procurementTabs" />

    @if(session('status') === 'success')
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Fast Status KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-title">إجمالي أذون الاستلام المخزني</div>
            <div class="kpi-value font-numeric text-sky-600">{{ $receipts->total() }}</div>
            <div class="kpi-subtext">حركات توريد وفحص جودة</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">الموردين النشطين</div>
            <div class="kpi-value font-numeric text-emerald-600">{{ $suppliers->count() }}</div>
            <div class="kpi-subtext">شبكة الموردين المعتمدين</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">المستودعات المستلمة</div>
            <div class="kpi-value font-numeric text-indigo-600">{{ $warehouses->count() }}</div>
            <div class="kpi-subtext">المخازن المركزية والفرعية</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">الربط المحاسبي التلقائي</div>
            <div class="kpi-value font-numeric text-amber-600">100%</div>
            <div class="kpi-subtext">ترحيل قيود الاستحقاق والمخزون</div>
        </div>
    </div>

    <!-- Receipts Table -->
    <div class="erp-card overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <x-icon name="clipboard-check" size="18" class="text-sky-600" />
                <span>سجل أذون فحص واستلام التوريدات (Goods Receipt Notes)</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم إذن الفحص</th>
                        <th>تاريخ الاستلام</th>
                        <th>المورد</th>
                        <th>المستودع المستلم</th>
                        <th>الأصناف والكميات</th>
                        <th>حالة الفحص</th>
                        <th>فاتورة المورد</th>
                        <th>المسؤول</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $grn)
                        <tr>
                            <td class="font-mono font-bold text-sky-700">{{ $grn->grn_number }}</td>
                            <td class="font-numeric">{{ $grn->receipt_date->format('Y-m-d') }}</td>
                            <td class="font-semibold">{{ $grn->supplier->name }}</td>
                            <td>{{ $grn->warehouse->name }}</td>
                            <td>
                                @foreach($grn->items as $item)
                                    <div class="text-xs">
                                        <strong>{{ $item->product->name }}</strong>:
                                        <span class="font-numeric">{{ number_format($item->quantity_accepted, 2) }} {{ $item->unit }}</span>
                                        <span class="text-slate-500 font-numeric">({{ number_format($item->total_cost, 2) }} ج.م)</span>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-success">مفحوص ومخزن</span>
                            </td>
                            <td class="font-mono text-xs">{{ $grn->supplier_invoice_reference ?? '—' }}</td>
                            <td class="text-xs">{{ $grn->receiver->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد أذون استلام مخزني مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Receive Form Card -->
    <div class="erp-card p-5">
        <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
            <x-icon name="plus-circle" size="20" class="text-sky-600" />
            <span>تسجيل إذن فحص واستلام توريد جديد (Goods Receipt Note)</span>
        </h3>

        <form action="{{ route('procurement.receipts.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">المورد المعتمد <span class="text-rose-500">*</span></label>
                    <select name="supplier_id" class="erp-input" required>
                        <option value="">-- اختر المورد --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">المستودع المستلم <span class="text-rose-500">*</span></label>
                    <select name="warehouse_id" class="erp-input" required>
                        <option value="">-- اختر المستودع --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">تاريخ الاستلام <span class="text-rose-500">*</span></label>
                    <input type="date" name="receipt_date" class="erp-input font-numeric" value="{{ date('Y-m-d') }}" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="erp-label">الصنف الغذائي المستلم <span class="text-rose-500">*</span></label>
                    <select name="product_id" class="erp-input" required>
                        <option value="">-- اختر الصنف --</option>
                        @foreach(\App\Models\Product::where('is_active', true)->orderBy('name')->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (سعر التكلفة: {{ $p->cost_price }} ج.م)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">الكمية المسلمة (كجم/وحدة) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="quantity_delivered" class="erp-input font-numeric" placeholder="مثال: 100" required />
                </div>
                <div>
                    <label class="erp-label">الكمية المقبولة بعد الفحص <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="quantity_accepted" class="erp-input font-numeric" placeholder="مثال: 95" required />
                </div>
                <div>
                    <label class="erp-label">سعر الوحدة للتوريد (ج.م) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="unit_cost" class="erp-input font-numeric" placeholder="مثال: 350.00" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">رقم فاتورة المورد</label>
                    <input type="text" name="supplier_invoice_reference" class="erp-input font-mono" placeholder="INV-2026-XXXX" />
                </div>
                <div>
                    <label class="erp-label">تاريخ انتهاء الصلاحية للتشغيلة</label>
                    <input type="date" name="expiry_date" class="erp-input font-numeric" value="{{ date('Y-m-d', strtotime('+6 months')) }}" />
                </div>
                <div>
                    <label class="erp-label">درجة حرارة الفحص المئوية</label>
                    <input type="number" step="0.1" name="storage_temperature" class="erp-input font-numeric" placeholder="مثال: 4.0 مئوية" />
                </div>
            </div>

            <div>
                <label class="erp-label">ملاحظات وتقرير فحص الجودة (QC)</label>
                <textarea name="inspection_notes" class="erp-input" rows="2" placeholder="ملاحظات فحص الرائحة، اللون، التبريد، مطابقة كراسة الشروط..."></textarea>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check-circle" size="18" />
                    <span>تأكيد الفحص وتخزين الباتشات وترحيل القيد المحاسبي</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
