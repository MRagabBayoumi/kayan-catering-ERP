<div class="space-y-6">
    <!-- Step 1 & Step 2 Workflow Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch mb-6">
        <!-- Step 1: Download Template & Guidelines (6 cols) -->
        <div class="lg:col-span-6 flex flex-col">
            <div class="erp-card h-full flex flex-col m-0 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm bg-white dark:bg-slate-800">
                <div class="erp-card-header px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 rounded-t-xl">
                    <div class="erp-card-title text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-extrabold">1</span>
                        <span>الخطوة 1: تنزيل نموذج الأقسام والتصنيفات</span>
                    </div>
                </div>

                <div class="erp-card-body p-5 flex flex-col justify-between flex-1 gap-4">
                    <div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mb-3.5 leading-relaxed">
                            يمكنك تجهيز الأقسام والتصنيفات جماعياً عبر ملف Excel أو CSV واستيرادها إلى النظام مع الحفاظ على الأقسام والمنتجات الحالية دون أي مساس بها.
                        </p>

                        <!-- Download Guidelines Box -->
                        <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center">
                                        <x-icon name="file-text" size="20" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-0.5">نموذج استيراد الأقسام المعتمد</h4>
                                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">ملف إكسيل رسمي (.xlsx) جاهز للتعبئة</span>
                                    </div>
                                </div>
                                <span class="erp-badge erp-badge-success text-xs font-bold px-2.5 py-1">Excel .xlsx</span>
                            </div>

                            <ul class="text-xs text-slate-600 dark:text-slate-300 mb-4 pr-5 list-disc leading-loose">
                                <li><strong>الأعمدة الإلزامية:</strong> اسم القسم / التصنيف (إلزامي لكل سطر).</li>
                                <li><strong>الأكواد والتبعيات:</strong> كود القسم (فريد)، ويمكن تحديد كود أو اسم القسم الرئيسي لربط الأقسام الفرعية.</li>
                                <li><strong>الحالة والوصف:</strong> الحالة (نشط / معطل - الافتراضي نشط)، والوصف اختياري.</li>
                            </ul>

                            <div class="flex gap-2.5">
                                <a href="{{ route('settings.departments.template') }}" 
                                   class="erp-btn erp-btn-success flex-1 h-11 text-xs font-bold flex items-center justify-center gap-2 rounded-lg shadow-sm">
                                    <x-icon name="download" size="18" />
                                    <span>تحميل نموذج الأقسام (Excel .xlsx)</span>
                                </a>
                                <a href="{{ route('settings.departments.template', ['format' => 'csv']) }}" 
                                   class="erp-btn erp-btn-secondary h-11 px-3.5 text-xs font-semibold flex items-center justify-center rounded-lg" 
                                   title="تحميل بصيغة CSV">
                                    <span>CSV</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Upload File & Validate (6 cols) -->
        <div class="lg:col-span-6 flex flex-col">
            <div class="erp-card h-full flex flex-col m-0 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm bg-white dark:bg-slate-800">
                <div class="erp-card-header px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 rounded-t-xl">
                    <div class="erp-card-title text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-extrabold">2</span>
                        <span>الخطوة 2: رفع ملف الأقسام للفحص والتدقيق</span>
                    </div>
                </div>

                <div class="erp-card-body p-5 flex flex-col justify-between flex-1 gap-4">
                    <form action="{{ route('settings.departments.preview') }}" method="POST" enctype="multipart/form-data" id="deptImportForm" class="flex flex-col h-full justify-between gap-4">
                        @csrf
                        <div class="text-xs text-slate-500 leading-normal">
                            ارفع ملف Excel أو CSV بعد تعبئة الأقسام. سيقوم النظام بفحص الملف والتحقق من صحة البيانات والأكواد قبل الحفظ.
                        </div>

                        <!-- Interactive Styled Upload Dropzone -->
                        <div 
                            id="deptDropzone"
                            class="border-2 border-dashed border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/40 hover:border-primary rounded-xl p-5 text-center cursor-pointer transition min-h-[140px] flex flex-col items-center justify-center"
                        >
                            <input 
                                type="file" 
                                id="departments_import_file" 
                                name="import_file" 
                                accept=".xlsx,.csv,.txt" 
                                class="hidden" 
                                required 
                            />

                            <div id="deptDropzonePrompt" class="flex flex-col items-center">
                                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-2.5">
                                    <x-icon name="upload-cloud" size="26" />
                                </div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">
                                    اضغط لاختيار ملف الأقسام والتصنيفات أو اسحبه هنا
                                </div>
                                <div class="text-xs text-slate-500">
                                    يدعم ملفات <strong>Excel (.xlsx)</strong> أو ملفات <strong>CSV (.csv)</strong> بحد أقصى 10MB
                                </div>
                            </div>

                            <div id="deptFileSelectedBadge" class="hidden items-center gap-3 bg-white dark:bg-slate-700 p-3 rounded-lg border border-emerald-500 shadow-sm">
                                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center shrink-0">
                                    <x-icon name="check" size="22" />
                                </div>
                                <div class="text-right">
                                    <div id="deptSelectedFileName" class="text-xs font-bold text-slate-800 dark:text-slate-100 max-w-[250px] truncate"></div>
                                    <div id="deptSelectedFileSize" class="text-[11px] text-slate-500"></div>
                                </div>
                                <button type="button" id="clearDeptFileBtn" class="mr-3 text-xs text-rose-500 font-bold hover:underline">
                                    تغيير الملف
                                </button>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex flex-col gap-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-700">
                            <button type="submit" id="submitDeptImportBtn" class="erp-btn erp-btn-primary w-full h-11 text-sm font-bold flex items-center justify-center gap-2 rounded-lg shadow-sm">
                                <x-icon name="check-circle" size="18" />
                                <span>فحص وتدقيق بيانات الأقسام</span>
                            </button>

                            <div class="flex items-center justify-center gap-1.5 text-xs text-slate-500">
                                <x-icon name="shield" size="14" class="text-emerald-600" />
                                <span>فحص استرشادي آمن؛ لا يتم حفظ أي قسم حتى تراجع نتائج الفحص وتعتمدها بنفسك.</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Validation Preview Section (If Available) -->
    @if(isset($importPreview) && is_array($importPreview))
        <div class="erp-card border-2 border-primary">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="clipboard" size="18" class="text-primary" />
                    <span>نتائج الفحص والتدقيق المسبق قبل الحفظ</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs">إجمالي الأسطر: {{ $importPreview['summary']['total'] }}</span>
                    <span class="erp-badge erp-badge-success text-xs">صالحة: {{ $importPreview['summary']['valid'] }}</span>
                    @if($importPreview['summary']['invalid'] > 0)
                        <span class="erp-badge erp-badge-danger text-xs">أخطاء: {{ $importPreview['summary']['invalid'] }}</span>
                    @endif
                </div>
            </div>

            <div class="erp-card-body space-y-4">
                <!-- Errors Alert Table -->
                @if(!empty($importPreview['invalid_rows']))
                    <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 rounded-lg">
                        <div class="font-bold text-rose-600 text-sm mb-2 flex items-center gap-1">
                            <x-icon name="alert-triangle" size="16" />
                            <span>تنبيه: تم اكتشاف {{ count($importPreview['invalid_rows']) }} أسطر بها أخطاء أو تكرار:</span>
                        </div>
                        <div class="erp-table-responsive">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th class="w-16 text-center">رقم السطر</th>
                                        <th>اسم القسم المكتشف</th>
                                        <th>الكود</th>
                                        <th>أسباب الخطأ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importPreview['invalid_rows'] as $invRow)
                                        <tr>
                                            <td class="text-center font-mono font-bold">{{ $invRow['row_number'] }}</td>
                                            <td>{{ $invRow['name'] }}</td>
                                            <td class="font-mono">{{ $invRow['code'] }}</td>
                                            <td class="text-rose-600">
                                                <ul class="list-disc list-inside m-0 p-0">
                                                    @foreach($invRow['errors'] as $err)
                                                        <li>{{ $err }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Valid Rows Preview -->
                @if(!empty($importPreview['valid_rows']))
                    <div>
                        <div class="font-bold text-emerald-600 text-sm mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <x-icon name="check" size="16" />
                                <span>جاهز للاستيراد: {{ count($importPreview['valid_rows']) }} قسماً وتصنيفاً صالحاً ومطابقاً للشروط</span>
                            </div>

                            <form action="{{ route('settings.departments.import') }}" method="POST">
                                @csrf
                                <input type="hidden" name="valid_rows" value="{{ json_encode($importPreview['valid_rows'], JSON_UNESCAPED_UNICODE) }}">
                                <button type="submit" class="erp-btn erp-btn-success">
                                    <x-icon name="check-circle" size="16" />
                                    <span>تأكيد واعتماد استيراد ({{ count($importPreview['valid_rows']) }}) قسم</span>
                                </button>
                            </form>
                        </div>

                        <div class="erp-table-responsive max-h-80 overflow-y-auto">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th class="w-12 text-center">#</th>
                                        <th>كود القسم</th>
                                        <th>اسم القسم / التصنيف</th>
                                        <th>القسم الرئيسي</th>
                                        <th class="text-center">الحالة</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importPreview['valid_rows'] as $idx => $vRow)
                                        <tr>
                                            <td class="text-center font-mono">{{ $idx + 1 }}</td>
                                            <td class="font-mono font-bold">{{ $vRow['code'] }}</td>
                                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $vRow['name'] }}</td>
                                            <td>{{ $vRow['parent_ref'] ?: '— (قسم رئيسي)' }}</td>
                                            <td class="text-center">
                                                <span class="erp-badge {{ $vRow['is_active'] ? 'erp-badge-success' : 'erp-badge-neutral' }} text-[10px]">
                                                    {{ $vRow['is_active'] ? 'نشط' : 'معطل' }}
                                                </span>
                                            </td>
                                            <td class="text-slate-500">{{ $vRow['description'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- 4. Existing Categories & Departments Table -->
    <div class="erp-card">
        <div class="erp-card-header flex items-center justify-between">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="folder" size="18" class="text-primary" />
                <span>دليل الأقسام والتصنيفات الحالية بالنظام</span>
            </div>
            <span class="erp-badge erp-badge-neutral text-xs font-mono">
                إجمالي الأقسام: {{ count($categoriesList ?? []) }}
            </span>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table text-xs">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>كود القسم</th>
                            <th>اسم القسم / التصنيف</th>
                            <th>القسم الأب / التبعية</th>
                            <th class="text-center">عدد المنتجات المرتبطة</th>
                            <th class="text-center">الحالة</th>
                            <th>المعرف الدائم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoriesList ?? [] as $index => $cat)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $index + 1 }}</td>
                                <td class="font-mono font-bold">{{ $cat->code ?: '—' }}</td>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $cat->name }}</td>
                                <td>{{ $cat->parent?->name ?: 'قسم رئيسي' }}</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="erp-badge erp-badge-info text-xs">{{ $cat->products_count }} منتج</span>
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $cat->is_active ? 'erp-badge-success' : 'erp-badge-danger' }} text-[10px]">
                                        {{ $cat->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="font-mono text-slate-400">ID: #{{ $cat->id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-slate-400">
                                    لا توجد أقسام مسجلة بالنظام حتى الآن
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
