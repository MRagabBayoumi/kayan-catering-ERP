@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دليل وتكويد الموردين المعتمدين" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'الموردين' => route('procurement.suppliers.index')]">
        <x-slot:actions>
            <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>طلبات الشراء</span>
            </a>
            <a href="{{ route('finance.suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="credit-card" size="16" />
                <span>حسابات الموردين والآجل (المالية)</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الموردين:</span>
                <strong>{{ $suppliers->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if(session('status') === 'success')
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Add Supplier Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="plus" size="16" class="text-[var(--primary)]" />
                <span>تكويد وإضافة مورد جديد لشبكة التوريدات</span>
            </div>
        </div>
        <div class="erp-card-body">
            <form method="POST" action="{{ route('procurement.suppliers.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">كود المورد *</label>
                    <input type="text" name="code" class="erp-input w-full" value="SUP-{{ rand(100,999) }}" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">اسم شركة المورد *</label>
                    <input type="text" name="name" class="erp-input w-full" placeholder="مثال: شركة النيل للحوم والدواجن" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">رقم الهاتف</label>
                    <input type="text" name="phone" class="erp-input w-full" placeholder="01xxxxxxxxx">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">شروط السداد</label>
                    <input type="text" name="payment_terms" class="erp-input w-full" value="آجل 30 يوم">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">العنوان والموقع</label>
                    <input type="text" name="address" class="erp-input w-full" placeholder="القاهرة / الجيزة">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">الرقم الضريبي</label>
                    <input type="text" name="tax_number" class="erp-input w-full" placeholder="xxx-xxx-xxx">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">الرصيد الافتتاحي (ج.م)</label>
                    <input type="number" step="0.01" name="current_balance" class="erp-input w-full" value="0.00">
                </div>
                <div>
                    <button type="submit" class="erp-btn erp-btn-primary w-full justify-center">
                        <x-icon name="save" size="16" />
                        <span>حفظ وتكويد المورد</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Suppliers Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="factory" size="16" class="text-[var(--primary)]" />
                <span>دليل الموردين المعتمدين لتغذية المستشفيات</span>
            </div>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>كود المورد</th>
                            <th>اسم المورد</th>
                            <th>رقم الهاتف</th>
                            <th>العنوان</th>
                            <th>شروط السداد</th>
                            <th>الرصيد الدفتري الحالي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $sup)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)]">{{ $sup->code }}</td>
                                <td class="font-bold text-[var(--text-main)]">{{ $sup->name }}</td>
                                <td class="font-numeric text-xs">{{ $sup->phone ?? '-' }}</td>
                                <td class="text-xs text-[var(--text-muted)]">{{ $sup->address ?? '-' }}</td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-xs">{{ $sup->payment_terms }}</span>
                                </td>
                                <td class="font-numeric font-bold {{ $sup->current_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ number_format($sup->current_balance, 2) }} ج.م
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-success">معتمد ونشط</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-[var(--text-muted)] text-xs">
                                    لا يوجد موردين مسجلين حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
