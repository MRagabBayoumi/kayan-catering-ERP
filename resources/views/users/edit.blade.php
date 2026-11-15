@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل بيانات المستخدم: {{ $user->name }}" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'المستخدمون' => route('users.index'), 'تعديل المستخدم' => route('users.edit', $user)]">
        <x-slot:actions>
            <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمستخدمين</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div class="user-form-container">
        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية:</strong>
                    <ul class="my-1 mx-4 text-xs list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="user-form-card">
            <div class="user-form-header">
                <div class="erp-card-title">
                    <x-icon name="user-check" size="18" />
                    <span>تعديل بيانات الحساب: {{ $user->name }}</span>
                </div>
                <span class="erp-badge erp-badge-primary text-xs">{{ $user->role_label }}</span>
            </div>

            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="user-form-body">
                    <!-- Row 1: Name, Email & Phone -->
                    <div class="erp-form-grid mb-4">
                        <div class="erp-form-group">
                            <label for="userName" class="erp-form-label">
                                اسم المستخدم الكامل <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="text"
                                id="userName"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                class="erp-input @error('name') erp-input-error @enderror"
                                required
                                autofocus
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="userEmail" class="erp-form-label">
                                البريد الإلكتروني للدخول <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="email"
                                id="userEmail"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="erp-input font-numeric @error('email') erp-input-error @enderror"
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="userPhone" class="erp-form-label">
                                رقم الموبايل / واتساب
                            </label>
                            <input
                                type="text"
                                id="userPhone"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                class="erp-input font-numeric @error('phone') erp-input-error @enderror"
                                placeholder="010XXXXXXXX"
                            >
                        </div>
                    </div>

                    <!-- Row 2: Password & Role -->
                    <div class="erp-form-row-2col mb-4">
                        <div class="erp-form-group">
                            <label for="userPassword" class="erp-form-label">
                                كلمة المرور الجديدة (اختياري)
                            </label>
                            <input
                                type="password"
                                id="userPassword"
                                name="password"
                                class="erp-input font-numeric @error('password') erp-input-error @enderror"
                                placeholder="اتركها فارغة إذا كنت لا تريد تغييرها"
                            >
                            <small class="text-[11px] text-[var(--text-muted)] mt-1">اترك هذا الحقل فارغاً للاحتفاظ بكلمة المرور الحالية</small>
                        </div>

                        <div class="erp-form-group">
                            <label for="userRole" class="erp-form-label">
                                الدور الوظيفي والصلاحيات <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select id="userRole" name="role" class="erp-input @error('role') erp-input-error @enderror" required>
                                <option value="">-- اختر الدور الوظيفي --</option>
                                @foreach($roles as $key => $label)
                                    <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-[11px] text-[var(--text-muted)] mt-1">تحديد الدور ومصفوفة الصلاحيات الممنوحة</small>
                        </div>
                    </div>

                    <!-- Row 3: Workplace & Warehouse Access Permissions -->
                    <div class="user-status-card mb-4">
                        <div class="w-full">
                            <div class="font-bold text-sm text-[var(--text-main)] flex items-center gap-1.5 mb-1">
                                <x-icon name="home" size="16" class="text-[var(--primary)]" />
                                <span>المخزن المعين وموقع العمل</span>
                            </div>
                            <div class="text-xs text-[var(--text-muted)] mb-3">
                                حدد المخزن الرئيسي التابع له أمين المخزن أو السائق.
                            </div>

                            <div class="erp-form-group mb-0">
                                <label for="userWarehouse" class="erp-form-label text-xs font-semibold">
                                    مكان العمل / المخزن التابع له
                                </label>
                                <select id="userWarehouse" name="assigned_warehouse_id" class="erp-input">
                                    <option value="">-- كل المخازن أو غير محدد --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (string) old('assigned_warehouse_id', $user->assigned_warehouse_id ?? '') === (string) $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                                <div class="erp-form-group mb-0 flex flex-col justify-center">
                                    <label class="erp-form-label text-xs font-semibold">صلاحية التبديل بين المستودعات</label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none mt-1 p-2 border border-[var(--border-color)] rounded-[var(--border-radius-sm)] bg-[var(--bg-surface)]">
                                        <input type="checkbox" name="can_access_all_warehouses" value="1" {{ old('can_access_all_warehouses', $user->can_access_all_warehouses ? '1' : '0') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                                        <span class="font-semibold text-xs text-[var(--text-main)]">السماح بمعالجة المعاملات والبيع من عدة مستودعات</span>
                                    </label>
                                    <small class="text-[11px] text-[var(--text-muted)] mt-1">إذا لم يتم التفعيل، سيقتصر الكاشير على مستودعه المحدد فقط</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Supervised Hospitals (صلاحيات المحاسب ومسؤول التكاليف) -->
                    <div class="user-status-card mb-4">
                        <div class="w-full">
                            <div class="font-bold text-sm text-[var(--text-main)] flex items-center gap-1.5 mb-1">
                                <x-icon name="building-2" size="16" class="text-[var(--primary)]" />
                                <span>المستشفيات والجهات المشرف عليها (صلاحيات محاسب التكاليف)</span>
                            </div>
                            <div class="text-xs text-[var(--text-muted)] mb-3">
                                حدد المستشفيات التي يحق للمحاسب الإشراف عليها والاطلاع على مقنناتها وتكاليفها وحساباتها (المدير العام يرى الكل تلقائياً).
                            </div>

                            @php
                                $assignedHospitalIds = old('supervised_hospital_ids', $user->supervisedHospitals->pluck('id')->toArray());
                            @endphp

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                @foreach($hospitals as $hosp)
                                    <label class="flex items-center gap-2 p-2.5 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                                        <input type="checkbox" name="supervised_hospital_ids[]" value="{{ $hosp->id }}" {{ in_array($hosp->id, $assignedHospitalIds) ? 'checked' : '' }} class="w-4 h-4 text-primary rounded">
                                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $hosp->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Account Active Status Card -->
                    <div class="user-status-card">
                        <div>
                            <div class="font-bold text-sm text-[var(--text-main)]">حالة تفعيل الحساب</div>
                            <div class="text-xs text-[var(--text-muted)] mt-0.5">عند تعطيل الحساب، لن يتمكن المستخدم من تسجيل الدخول للنظام</div>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ? '1' : '0') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                            <span class="font-semibold text-xs text-[var(--text-main)]">مفعل</span>
                        </label>
                    </div>
                </div>

                <div class="user-form-footer">
                    <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>حفظ التعديلات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
