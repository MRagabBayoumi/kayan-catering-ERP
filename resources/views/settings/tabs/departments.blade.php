<div class="space-y-6">
    <!-- Step 1 & Step 2 Workflow Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch mb-6">
        <!-- Step 1: Download Template & Guidelines (6 cols) -->
        <div class="lg:col-span-6 flex flex-col">
            <div class="erp-card h-full flex flex-col m-0" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: #ffffff;">
                <div class="erp-card-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; border-radius: 12px 12px 0 0;">
                    <div class="erp-card-title" style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #059669; color: #ffffff; font-size: 12px; font-weight: 800;">1</span>
                        <span>الخطوة 1: تنزيل نموذج الأقسام والتصنيفات</span>
                    </div>
                </div>

                <div class="erp-card-body" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 18px;">
                    <div>
                        <p style="font-size: 13px; color: #475569; margin: 0 0 14px 0; line-height: 1.6;">
                            يمكنك تجهيز الأقسام والتصنيفات جماعياً عبر ملف Excel أو CSV واستيرادها إلى النظام مع الحفاظ على الأقسام والمنتجات الحالية دون أي مساس بها.
                        </p>

                        <!-- Download Guidelines Box -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center;">
                                        <x-icon name="file-text" size="20" />
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">نموذج استيراد الأقسام المعتمد</h4>
                                        <span style="font-size: 12px; color: #059669; font-weight: 700;">ملف إكسيل رسمي (.xlsx) جاهز للتعبئة</span>
                                    </div>
                                </div>
                                <span class="erp-badge erp-badge-success" style="font-size: 11px; padding: 4px 10px; font-weight: 700;">Excel .xlsx</span>
                            </div>

                            <ul style="font-size: 12px; color: #334155; margin: 0 0 16px 0; padding-right: 20px; line-height: 1.9;">
                                <li><strong>الأعمدة الإلزامية:</strong> اسم القسم / التصنيف (إلزامي لكل سطر).</li>
                                <li><strong>الأكواد والتبعيات:</strong> كود القسم (فريد)، ويمكن تحديد كود أو اسم القسم الرئيسي لربط الأقسام الفرعية.</li>
                                <li><strong>الحالة والوصف:</strong> الحالة (نشط / معطل - الافتراضي نشط)، والوصف اختياري.</li>
                            </ul>

                            <div style="display: flex; gap: 10px;">
                                <a href="{{ route('settings.departments.template') }}" 
                                   class="erp-btn erp-btn-success" 
                                   style="flex: 1; height: 44px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                                    <x-icon name="download" size="18" />
                                    <span>تحميل نموذج الأقسام (Excel .xlsx)</span>
                                </a>
                                <a href="{{ route('settings.departments.template', ['format' => 'csv']) }}" 
                                   class="erp-btn erp-btn-secondary" 
                                   title="تحميل بصيغة CSV"
                                   style="height: 44px; padding: 0 14px; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
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
            <div class="erp-card h-full flex flex-col m-0" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: #ffffff;">
                <div class="erp-card-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; border-radius: 12px 12px 0 0;">
                    <div class="erp-card-title" style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #ffffff; font-size: 12px; font-weight: 800;">2</span>
                        <span>الخطوة 2: رفع ملف الأقسام للفحص والتدقيق</span>
                    </div>
                </div>

                <div class="erp-card-body" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 16px;">
                    <form action="{{ route('settings.departments.preview') }}" method="POST" enctype="multipart/form-data" id="deptImportForm" style="display: flex; flex-direction: column; height: 100%; justify-content: space-between; gap: 16px;">
                        @csrf
                        <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                            ارفع ملف Excel أو CSV بعد تعبئة الأقسام. سيقوم النظام بفحص الملف والتحقق من صحة البيانات والأكواد قبل الحفظ.
                        </div>

                        <!-- Interactive Styled Upload Dropzone -->
                        <div 
                            id="deptDropzone"
                            style="border: 2px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 22px 16px; text-align: center; cursor: pointer; transition: all 0.2s ease; min-height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                            onclick="document.getElementById('departments_import_file').click()"
                            onmouseover="this.style.borderColor='var(--primary)'; this.style.backgroundColor='#fcfbfe';"
                            onmouseout="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc';"
                            ondragover="event.preventDefault(); this.style.borderColor='var(--primary)'; this.style.backgroundColor='#fcfbfe';"
                            ondragleave="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc';"
                            ondrop="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc'; handleDeptDropFile(event)"
                        >
                            <input 
                                type="file" 
                                id="departments_import_file" 
                                name="import_file" 
                                accept=".xlsx,.csv,.txt" 
                                class="hidden" 
                                required 
                                onchange="handleDeptFileSelect(this)"
                            />

                            <div id="deptDropzonePrompt" style="display: flex; flex-direction: column; align-items: center;">
                                <div style="width: 46px; height: 46px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                    <x-icon name="upload-cloud" size="26" />
                                </div>
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                    اضغط لاختيار ملف الأقسام والتصنيفات أو اسحبه هنا
                                </div>
                                <div style="font-size: 12px; color: #64748b;">
                                    يدعم ملفات <strong>Excel (.xlsx)</strong> أو ملفات <strong>CSV (.csv)</strong> بحد أقصى 10MB
                                </div>
                            </div>

                            <div id="deptFileSelectedBadge" style="display: none; align-items: center; gap: 12px; background: #ffffff; padding: 12px 18px; border-radius: 10px; border: 1.5px solid #10b981; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                                <div style="width: 36px; height: 36px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; shrink-0;">
                                    <x-icon name="check" size="22" />
                                </div>
                                <div style="text-align: right;">
                                    <div id="deptSelectedFileName" style="font-size: 13px; font-weight: 700; color: #0f172a; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                                    <div id="deptSelectedFileSize" style="font-size: 11px; color: #64748b;"></div>
                                </div>
                                <button type="button" onclick="event.stopPropagation(); clearDeptSelectedFile();" style="margin-right: 12px; font-size: 12px; color: #ef4444; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                    تغيير الملف
                                </button>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div style="display: flex; flex-direction: column; gap: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                            <button type="submit" id="submitDeptImportBtn" class="erp-btn erp-btn-primary" style="width: 100%; height: 44px; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(113, 75, 103, 0.2);">
                                <x-icon name="check-circle" size="18" />
                                <span>فحص وتدقيق بيانات الأقسام</span>
                            </button>

                            <div style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #64748b;">
                                <x-icon name="shield" size="14" class="text-emerald-600" />
                                <span>فحص استرشادي آمن؛ لا يتم حفظ أي قسم حتى تراجع نتائج الفحص وتعتمدها بنفسك.</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleDeptFileSelect(input) {
            if (input.files && input.files[0]) {
                displayDeptFileInfo(input.files[0]);
            }
        }

        function handleDeptDropFile(event) {
            if (event.dataTransfer.files && event.dataTransfer.files[0]) {
                const input = document.getElementById('departments_import_file');
                input.files = event.dataTransfer.files;
                displayDeptFileInfo(event.dataTransfer.files[0]);
            }
        }

        function displayDeptFileInfo(file) {
            const prompt = document.getElementById('deptDropzonePrompt');
            const badge = document.getElementById('deptFileSelectedBadge');
            const nameEl = document.getElementById('deptSelectedFileName');
            const sizeEl = document.getElementById('deptSelectedFileSize');

            if (prompt && badge && nameEl && sizeEl) {
                prompt.style.display = 'none';
                badge.style.display = 'flex';
                nameEl.textContent = file.name;
                const sizeKb = (file.size / 1024).toFixed(1);
                sizeEl.textContent = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';
            }
        }

        function clearDeptSelectedFile() {
            const input = document.getElementById('departments_import_file');
            const prompt = document.getElementById('deptDropzonePrompt');
            const badge = document.getElementById('deptFileSelectedBadge');

            if (input) input.value = '';
            if (prompt) prompt.style.display = 'flex';
            if (badge) badge.style.display = 'none';
        }
    </script>

    <!-- 3. Validation Preview Section (If Available) -->
    @if(isset($importPreview) && is_array($importPreview))
        <div class="erp-card border-2 border-[var(--primary)]">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="clipboard" size="18" class="text-[var(--primary)]" />
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
                    <div class="p-4 bg-[var(--danger)]/10 border border-[var(--danger)] rounded">
                        <div class="font-bold text-[var(--danger)] text-sm mb-2 flex items-center gap-1">
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
                                            <td class="text-center font-numeric font-bold">{{ $invRow['row_number'] }}</td>
                                            <td>{{ $invRow['name'] }}</td>
                                            <td class="font-numeric">{{ $invRow['code'] }}</td>
                                            <td class="text-[var(--danger)]">
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
                        <div class="font-bold text-[var(--success)] text-sm mb-2 flex items-center justify-between">
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
                                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                                            <td class="font-numeric font-bold">{{ $vRow['code'] }}</td>
                                            <td class="font-semibold text-[var(--text-main)]">{{ $vRow['name'] }}</td>
                                            <td>{{ $vRow['parent_ref'] ?: '— (قسم رئيسي)' }}</td>
                                            <td class="text-center">
                                                <span class="erp-badge {{ $vRow['is_active'] ? 'erp-badge-success' : 'erp-badge-neutral' }} text-[10px]">
                                                    {{ $vRow['is_active'] ? 'نشط' : 'معطل' }}
                                                </span>
                                            </td>
                                            <td class="text-[var(--text-muted)]">{{ $vRow['description'] ?: '—' }}</td>
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
                <x-icon name="folder" size="18" class="text-[var(--primary)]" />
                <span>دليل الأقسام والتصنيفات الحالية بالنظام</span>
            </div>
            <span class="erp-badge erp-badge-neutral text-xs font-numeric">
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
                                <td class="text-center font-numeric text-[var(--text-muted)]">{{ $index + 1 }}</td>
                                <td class="font-numeric font-bold">{{ $cat->code ?: '—' }}</td>
                                <td class="font-semibold text-[var(--text-main)]">{{ $cat->name }}</td>
                                <td>{{ $cat->parent?->name ?: 'قسم رئيسي' }}</td>
                                <td class="text-center font-numeric font-bold">
                                    <span class="erp-badge erp-badge-info text-xs">{{ $cat->products_count }} منتج</span>
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $cat->is_active ? 'erp-badge-success' : 'erp-badge-danger' }} text-[10px]">
                                        {{ $cat->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="font-numeric text-[var(--text-muted)]">ID: #{{ $cat->id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
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
