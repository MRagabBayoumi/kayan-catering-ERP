@extends('layouts.app')

@section('module_name', 'شاشة التطبيقات (Odoo Apps Hub)')

@section('control_panel')
    <x-control-panel title="منظومة {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan Catering ERP') }}" :breadcrumbs="['الرئيسية' => route('home'), 'التطبيقات والمديولات' => '#']">
        <x-slot:actions>
            <a href="{{ route('dashboard') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layout-dashboard" size="16" />
                <span>لوحة المؤشرات والرقابة</span>
            </a>
            <button type="button" class="erp-btn erp-btn-primary" data-action="open-ai-copilot">
                <x-icon name="sparkles" size="16" />
                <span>مساعد كيان الذكي (AI)</span>
            </button>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral">
                <x-icon name="user" size="14" />
                <span>{{ auth()->user()->name }} ({{ auth()->user()->role_label }})</span>
            </span>
            <span class="erp-badge erp-badge-info font-numeric">
                <x-icon name="calendar" size="14" />
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l، d F Y') }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="home-apps">
        <!-- Status Alerts -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Welcome Apps Hub Hero -->
        <div class="home-hero-card mb-5">
            <div class="flex items-center gap-4">
                <div class="app-icon-box bg-purple-500/10 text-purple-600 border border-purple-500/20">
                    <x-icon name="grid" size="32" />
                </div>
                <div class="home-hero-content">
                    <div class="home-hero-badge">
                        <x-icon name="sparkles" size="14" />
                        <span>منظومة إدارة إعاشة وتغذية المستشفيات (Catering ERP)</span>
                    </div>
                    <h1 class="home-hero-title">مرحباً بك، {{ auth()->user()->name }}</h1>
                    <p class="home-hero-desc">
                        اختر الإدارة أو التطبيق المطلوب للمتابعة. انقر على أي موديول للدخول إليه ومتابعة تبويباته وتشغيلاته اللحظية.
                    </p>
                </div>
            </div>
            <div class="home-hero-meta">
                <div class="home-hero-workplace">
                    <span class="text-xs text-[var(--text-muted)]">صلاحية الحساب:</span>
                    <strong class="text-sm text-[var(--primary)] font-bold">
                        {{ auth()->user()->role_label }}
                    </strong>
                    <span class="text-[11px] text-[var(--text-muted)] font-numeric">
                        تاريخ اليوم: {{ \Carbon\Carbon::now()->format('Y-m-d') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick App Search Filter -->
        <div class="erp-card p-3 mb-5 flex items-center gap-3">
            <div class="relative flex-1">
                <input type="search" id="homeAppSearch" class="erp-input w-full pr-10" placeholder="ابحث في التطبيقات والموديولات (مخازن، تكاليف، مستشفيات، مناقصات، مشتريات، مالية، صلاحيات...)" autocomplete="off">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <x-icon name="search" size="18" />
                </span>
            </div>
            <span class="text-xs text-slate-400 font-numeric whitespace-nowrap">11 تطبيق متاح</span>
        </div>

        <!-- Odoo-Style Applications Launcher Grid -->
        <div class="apps-hub-grid" id="appsHubGrid">

            <!-- 1. Warehouse & FIFO Operations -->
            <div class="app-card app-card-blue" data-app-keywords="مخازن تشغيلات باتشات فيفو صرف مناديب جرد أعمى warehouse fifo batches">
                <div class="app-card-header">
                    <div class="app-icon-box bg-blue-500/10 text-blue-600">
                        <x-icon name="warehouse" size="28" />
                    </div>
                    <span class="app-badge badge-blue font-numeric">{{ $batches_count ?? 0 }} تشغيلة نشطة</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إدارة المخازن والتشغيلات (FIFO)</h3>
                    <p class="app-desc">مراقبة تشغيلات الشراء، تواريخ الصلاحية، الصرف بالأولوية الفورية (FIFO)، أذون التحميل، والجرد الأعمى وتسويات المناديب.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-primary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>فتح إدارة المخازن</span>
                    </a>
                </div>
            </div>

            <!-- 2. Costing & BOM -->
            <div class="app-card app-card-emerald" data-app-keywords="تكاليف مقننات وجبات مطبخ هدر بوم انحراف costing bom recipes consumption">
                <div class="app-card-header">
                    <div class="app-icon-box bg-emerald-500/10 text-emerald-600">
                        <x-icon name="calculator" size="28" />
                    </div>
                    <span class="app-badge badge-emerald font-numeric">{{ number_format($capacity_count ?? 0) }} وجبة/يوم</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إدارة التكاليف والمقننات (BOM)</h3>
                    <p class="app-desc">مقننات الوجبات القياسية (BOM)، حساب طلبات المطبخ لسعة المرضى، ومطابقة المنصرف الفعلي مع المعياري لكشف الهدر.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-primary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>فتح المقننات والتكاليف</span>
                    </a>
                </div>
            </div>

            <!-- 3. Hospital Contracts & Sales -->
            <div class="app-card app-card-cyan" data-app-keywords="مستشفيات تعاقدات مبيعات جهات سعة مرضى hospitals sales contracts">
                <div class="app-card-header">
                    <div class="app-icon-box bg-cyan-500/10 text-cyan-600">
                        <x-icon name="building-2" size="28" />
                    </div>
                    <span class="app-badge badge-cyan font-numeric">{{ $hospitals_count ?? 0 }} مستشفى متعاقدة</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">المستشفيات والتعاقدات</h3>
                    <p class="app-desc">إدارة عقود توريد الوجبات الغذائية، سعة النزلاء والمرافقين، أسعار الوجبات المعتمدة، وسجل بدء وإنهاء التعاقدات.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>سجل تعاقدات المستشفيات</span>
                    </a>
                </div>
            </div>

            <!-- 4. Tenders & Competitor Archiving -->
            <div class="app-card app-card-amber" data-app-keywords="مناقصات عطاءات مظاريف منافسين أرشيف tenders sales competitors">
                <div class="app-card-header">
                    <div class="app-icon-box bg-amber-500/10 text-amber-600">
                        <x-icon name="file-text" size="28" />
                    </div>
                    <span class="app-badge badge-amber font-numeric">{{ $tenders_count ?? 0 }} مناقصة مسجلة</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">المناقصات والعطاءات (Tenders)</h3>
                    <p class="app-desc">متابعة كراسات الشروط، ترسية العطاءات، وأرشيف أسعار المنافسين السابقة للاستفادة منها في تسعير الوجبات والمناقصات القادمة.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>فتح المناقصات والعطاءات</span>
                    </a>
                </div>
            </div>

            <!-- 5. Procurement & Supply Orders -->
            <div class="app-card app-card-teal" data-app-keywords="مشتريات توريدات أوامر شراء عروض أسعار خامات procurement orders suppliers">
                <div class="app-card-header">
                    <div class="app-icon-box bg-teal-500/10 text-teal-600">
                        <x-icon name="shopping-bag" size="28" />
                    </div>
                    <span class="app-badge badge-teal">أوامر التوريد</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إدارة المشتريات والتوريدات</h3>
                    <p class="app-desc">طلبات شراء اللحوم والأغذية الجافة، عروض أسعار الموردين، اعتمادات الإدارة، وتكويد الموردين وأسعار الخامات التنافسية.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>إدارة أوامر الشراء</span>
                    </a>
                </div>
            </div>

            <!-- 6. Finance & Supplier Ledgers -->
            <div class="app-card app-card-purple" data-app-keywords="حسابات مالية كشوف موردين أعمار ديون نثريات خزينة finance suppliers ledger treasury">
                <div class="app-card-header">
                    <div class="app-icon-box bg-purple-500/10 text-purple-600">
                        <x-icon name="wallet" size="28" />
                    </div>
                    <span class="app-badge badge-purple font-numeric">{{ $suppliers_count ?? 0 }} مورد</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">الحسابات والمالية</h3>
                    <p class="app-desc">كشوف حسابات الموردين، مطابقة الفواتير والدفعات، جداول أعمار الديون، وإدارة عهد النثريات النقدية بمواقع المستشفيات (شراء الخبز اليومي).</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('finance.suppliers.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>فتح كشوف الحسابات</span>
                    </a>
                </div>
            </div>

            <!-- 7. Daily Closing & Owner Profit Reconciliation -->
            <div class="app-card app-card-indigo" data-app-keywords="تقفيل يومية أرباح ضرائب دمغات شريك مالك daily closing profit taxes">
                <div class="app-card-header">
                    <div class="app-icon-box bg-indigo-500/10 text-indigo-600">
                        <x-icon name="pie-chart" size="28" />
                    </div>
                    <span class="app-badge badge-indigo">إقفال الأرباح</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">التقفيل واليومية للمالك</h3>
                    <p class="app-desc">تسوية الإيرادات اليومية وتوزيع الأرباح وصافي حصص الشركاء بعد خصم نسبة المطبخ، الضرائب، الدمغات، والتأمينات.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('finance.closing.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>عرض اليومية والتقفيل</span>
                    </a>
                </div>
            </div>

            <!-- 8. Master Dashboard & Monitoring -->
            <div class="app-card app-card-violet" data-app-keywords="لوحة تحكم مؤشرات كفاءة رقابة صلاحية kpi dashboard analytics">
                <div class="app-card-header">
                    <div class="app-icon-box bg-violet-500/10 text-violet-600">
                        <x-icon name="layout-dashboard" size="28" />
                    </div>
                    <span class="app-badge badge-violet">مؤشرات حية</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">لوحة المؤشرات والرقابة العامة</h3>
                    <p class="app-desc">شاشة الرقابة المركزية لكافة مؤشرات الأداء (KPIs)، تنبيهات تواريخ الصلاحية الحرجة، وحركة سيارات توزيع الوجبات للمستشفيات.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('dashboard') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>الانتقال للمؤشرات</span>
                    </a>
                </div>
            </div>

            <!-- 9. Kayan AI Copilot -->
            <div class="app-card app-card-rose" data-app-keywords="ذكاء اصطناعي مساعد شات كوبايلوت لحمة عسل جيزة إمبابة ai copilot chat bot">
                <div class="app-card-header">
                    <div class="app-icon-box bg-rose-500/10 text-rose-600">
                        <x-icon name="sparkles" size="28" />
                    </div>
                    <span class="app-badge badge-rose">Kayan AI 24/7</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">مساعد كيان الذكي (AI Copilot)</h3>
                    <p class="app-desc">إجابة ذكية فورية باللغة العربية على أرصدة اللحوم والعسل، تكلفة مستشفى إمبابة وهدر البضاعة، وأسعار مناقصات الجيزة السابقة.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <button type="button" class="erp-btn erp-btn-primary w-full justify-center" data-action="open-ai-copilot">
                        <x-icon name="message-circle" size="16" />
                        <span>بدء محادثة المساعد الذكي</span>
                    </button>
                </div>
            </div>

            <!-- 10. Users & Permissions Matrix -->
            <div class="app-card app-card-neutral" data-app-keywords="مستخدمين صلاحيات مصفوفة موظفين مستودعات users permissions rbac roles">
                <div class="app-card-header">
                    <div class="app-icon-box bg-slate-500/10 text-slate-700 dark:text-slate-200">
                        <x-icon name="users" size="28" />
                    </div>
                    <span class="app-badge badge-neutral font-numeric">{{ $users_count ?? 0 }} مستخدم</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">المستخدمين ومصفوفة الصلاحيات</h3>
                    <p class="app-desc">إدارة حسابات فريق العمل، تعيين الفروع والمستودعات، وضبط مصفوفة الصلاحيات (RBAC) للأقسام الأربعة المعزولة.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>إدارة المستخدمين والصلاحيات</span>
                    </a>
                </div>
            </div>

            <!-- 11. System Settings & Configuration -->
            <div class="app-card app-card-neutral" data-app-keywords="إعدادات نظام شركة سجل تجاري ضريبة هوية settings configuration company">
                <div class="app-card-header">
                    <div class="app-icon-box bg-slate-500/10 text-slate-700 dark:text-slate-200">
                        <x-icon name="settings" size="28" />
                    </div>
                    <span class="app-badge badge-neutral">تهيئة النظام</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إعدادات المنظومة والشركة</h3>
                    <p class="app-desc">بيانات المنشأة، الشعار، الرقم الضريبي، سنة التشغيل الحالية، وتخصيص بيئة عمل كيان كاترينج لكافة الفروع.</p>
                </div>
                <div class="app-card-footer flex flex-col gap-2">
                    <a href="{{ route('settings.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="arrow-left" size="16" />
                        <span>فتح إعدادات المنظومة</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Empty Search State -->
        <div id="appsNoResults" class="erp-card p-8 text-center hidden">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                <x-icon name="search" size="28" />
            </div>
            <h4 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-1">لا توجد تطبيقات مطابقة لبحثك</h4>
            <p class="text-xs text-slate-500">جرب البحث بكلمات أخرى مثل: مخازن، تكاليف، مستشفيات، مناقصات، مشتريات، مالية، صلاحيات.</p>
        </div>
    </div>
@endsection
