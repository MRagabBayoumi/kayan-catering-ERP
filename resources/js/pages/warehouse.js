/**
 * Kayan Catering — Warehouse Management & FEFO Engine Client Script
 * Handles:
 * 1. Expiry Batch Consumption Suggestions Modal
 * 2. Dispatch Kitchen BOM & Yield/Waste Rate Calculator
 * 3. Dynamic Dispatch Items Row Management
 */

export function initWarehousePage() {
    initBatchConsumptionModal();
    initDispatchBomCalculator();
    initDispatchRowsManager();
}

/**
 * 1. Expiry Batch Consumption Suggestions Modal
 */
function initBatchConsumptionModal() {
    const modal = document.getElementById('batchSuggestionModal');
    if (!modal) return;

    const closeBtn = document.getElementById('closeModalBtn');
    const loadingState = document.getElementById('modalLoadingState');
    const contentState = document.getElementById('modalContentState');
    const subtitle = document.getElementById('modalBatchSubtitle');
    const remainingQtyEl = document.getElementById('modalRemainingQty');
    const tbody = document.getElementById('modalSuggestionsTbody');

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    closeBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    document.querySelectorAll('[data-action="suggest-batch-consumption"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const batchId = btn.getAttribute('data-batch-id');
            if (!batchId) return;

            modal.classList.remove('hidden');
            loadingState?.classList.remove('hidden');
            contentState?.classList.add('hidden');
            if (subtitle) subtitle.textContent = 'جاري فحص المقننات الغذائية والوصفات...';

            try {
                const response = await fetch(`/warehouse/expiry/suggest/${batchId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('فشل جلب مقترحات الوصفات');
                }

                const data = await response.json();
                loadingState?.classList.add('hidden');
                contentState?.classList.remove('hidden');

                if (subtitle) {
                    subtitle.textContent = `تشغيلة: ${data.batch.batch_number} - الصنف: ${data.batch.product_name}`;
                }

                if (remainingQtyEl) {
                    remainingQtyEl.textContent = `${Number(data.batch.remaining_quantity).toLocaleString('en-US', { minimumFractionDigits: 2 })} ${data.batch.unit}`;
                }

                if (tbody) {
                    if (!data.suggestions || data.suggestions.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-6 text-xs text-slate-500">
                                    لا توجد مقننات غذائية مسجلة حالياً تستهلك هذا الصنف بشكل مباشر.
                                </td>
                            </tr>
                        `;
                    } else {
                        tbody.innerHTML = data.suggestions.map((s) => `
                            <tr>
                                <td class="font-bold text-slate-800 dark:text-slate-100">
                                    ${escapeHtml(s.recipe_name)}
                                    <div class="text-[10px] text-slate-400 font-mono">${escapeHtml(s.recipe_code)}</div>
                                </td>
                                <td>${escapeHtml(s.meal_type)}</td>
                                <td>${escapeHtml(s.hospital_name)}</td>
                                <td class="font-mono text-xs font-semibold">${Number(s.raw_per_meal).toFixed(3)} ${escapeHtml(s.unit)}</td>
                                <td class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-sm">
                                    ${Number(s.meals_possible).toLocaleString()} وجبة
                                </td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (err) {
                if (loadingState) {
                    loadingState.innerHTML = `<span class="text-rose-500 font-semibold">${escapeHtml(err.message)}</span>`;
                }
            }
        });
    });
}

/**
 * 2. Dispatch Kitchen BOM & Yield/Waste Rate Calculator
 */
function initDispatchBomCalculator() {
    const btnCalculate = document.getElementById('btnCalculateBom');
    const recipeSelect = document.getElementById('recipeSelector');
    const mealsInput = document.getElementById('plannedMealsCount');
    const warehouseSelect = document.getElementById('sourceWarehouseSelect');
    const container = document.getElementById('bomCalculationContainer');
    const previewTbody = document.getElementById('bomPreviewTbody');
    const btnApply = document.getElementById('btnApplyBomToTable');

    if (!btnCalculate || !recipeSelect || !mealsInput) return;

    let currentCalculatedItems = [];

    btnCalculate.addEventListener('click', async () => {
        const recipeId = recipeSelect.value;
        const mealsCount = parseInt(mealsInput.value, 10);
        const warehouseId = warehouseSelect?.value || '';

        if (!recipeId) {
            alert('يرجى اختيار الوجبة / المقنن المعتمد أولاً.');
            recipeSelect.focus();
            return;
        }

        if (isNaN(mealsCount) || mealsCount <= 0) {
            alert('يرجى إدخال عدد وجبات صحيح أكبر من صفر.');
            mealsInput.focus();
            return;
        }

        btnCalculate.disabled = true;
        const originalBtnHtml = btnCalculate.innerHTML;
        btnCalculate.innerHTML = '<span>جاري الاحتساب...</span>';

        try {
            const url = `/warehouse/recipes/${recipeId}/bom?meals_count=${mealsCount}&warehouse_id=${encodeURIComponent(warehouseId)}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('فشل احتساب مقننات الوجبة من الخادم');
            }

            const data = await response.json();
            currentCalculatedItems = data.items || [];

            if (container) container.classList.remove('hidden');

            if (previewTbody) {
                if (currentCalculatedItems.length === 0) {
                    previewTbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-6 text-xs text-slate-500">
                                لم يتم العثور على خامات معرفة ضمن هذا المقنن.
                            </td>
                        </tr>
                    `;
                } else {
                    previewTbody.innerHTML = currentCalculatedItems.map((item) => {
                        const statusBadge = item.is_sufficient
                            ? '<span class="erp-badge erp-badge-success text-[10px]">رصيد كافٍ</span>'
                            : '<span class="erp-badge erp-badge-danger text-[10px]">عجز بالمخزن</span>';

                        const barcodeText = item.barcode ? ` [${escapeHtml(item.barcode)}]` : '';

                        return `
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">${escapeHtml(item.product_name)}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">${escapeHtml(item.product_sku)}${barcodeText}</div>
                                </td>
                                <td class="font-mono text-xs">${Number(item.raw_per_meal).toFixed(3)} ${escapeHtml(item.unit)}</td>
                                <td class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">${Number(item.net_qty).toFixed(2)} ${escapeHtml(item.unit)}</td>
                                <td class="font-mono text-xs text-amber-600 dark:text-amber-400 font-bold">${Number(item.acceptable_waste_pct).toFixed(1)}%</td>
                                <td class="font-mono text-xs font-semibold text-amber-700 dark:text-amber-300">${Number(item.waste_buffer_qty).toFixed(2)} ${escapeHtml(item.unit)}</td>
                                <td class="font-mono text-xs font-black text-indigo-600 dark:text-indigo-400">${Number(item.recommended_dispatch_qty).toFixed(2)} ${escapeHtml(item.unit)}</td>
                                <td class="font-mono text-xs">${Number(item.available_stock).toFixed(2)} ${escapeHtml(item.unit)}</td>
                                <td>${statusBadge}</td>
                            </tr>
                        `;
                    }).join('');
                }
            }
        } catch (err) {
            alert(err.message);
        } finally {
            btnCalculate.disabled = false;
            btnCalculate.innerHTML = originalBtnHtml;
        }
    });

    btnApply?.addEventListener('click', () => {
        if (!currentCalculatedItems || currentCalculatedItems.length === 0) {
            alert('لا توجد مقادير محسوبة لتطبيقها.');
            return;
        }

        const itemsContainer = document.getElementById('dispatchItemsContainer');
        if (!itemsContainer) return;

        // Populate items container with BOM calculated items
        itemsContainer.innerHTML = '';
        currentCalculatedItems.forEach((item, index) => {
            const barcodeHtml = item.barcode ? ` [${escapeHtml(item.barcode)}]` : '';
            const rowHtml = `
                <div class="dispatch-row grid grid-cols-1 md:grid-cols-12 gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700 items-center">
                    <div class="md:col-span-5">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">الصنف الغذائي</label>
                        <select name="items[${index}][product_id]" class="product-selector w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            <option value="${item.product_id}" selected>${escapeHtml(item.product_name)} (${escapeHtml(item.unit)})${barcodeHtml}</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">الكمية الصافية المقننة</label>
                        <input type="number" step="0.01" min="0" name="items[${index}][planned_qty]" value="${Number(item.net_qty).toFixed(2)}" class="planned-qty-input w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">هالك التحضير والأمان (${Number(item.acceptable_waste_pct).toFixed(1)}%)</label>
                        <input type="number" step="0.01" min="0" name="items[${index}][buffer_qty]" value="${Number(item.waste_buffer_qty).toFixed(2)}" class="buffer-qty-input w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                    </div>
                    <div class="md:col-span-1 text-center pt-4">
                        <button type="button" class="btn-remove-row text-slate-400 hover:text-rose-500 p-1" title="حذف السطر">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </div>
            `;
            itemsContainer.insertAdjacentHTML('beforeend', rowHtml);
        });

        // Re-bind row removal
        initDispatchRowsManager();

        // Scroll to items
        itemsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

/**
 * 3. Dynamic Dispatch Items Row Management
 */
function initDispatchRowsManager() {
    const container = document.getElementById('dispatchItemsContainer');
    const addBtn = document.getElementById('btnAddRowBtn');

    if (!container) return;

    // Remove buttons handler
    container.querySelectorAll('.btn-remove-row').forEach((btn) => {
        btn.onclick = () => {
            const rows = container.querySelectorAll('.dispatch-row');
            if (rows.length <= 1) {
                alert('يجب الإبقاء على صنف واحد على الأقل في إذن الصرف.');
                return;
            }
            btn.closest('.dispatch-row')?.remove();
        };
    });

    if (addBtn && !addBtn.dataset.bound) {
        addBtn.dataset.bound = 'true';
        addBtn.addEventListener('click', () => {
            const rows = container.querySelectorAll('.dispatch-row');
            const newIndex = rows.length;
            const firstRow = rows[0];

            if (!firstRow) return;

            const cloned = firstRow.cloneNode(true);
            const select = cloned.querySelector('select');
            const plannedInput = cloned.querySelector('.planned-qty-input');
            const bufferInput = cloned.querySelector('.buffer-qty-input');

            if (select) {
                select.name = `items[${newIndex}][product_id]`;
                select.selectedIndex = 0;
            }
            if (plannedInput) {
                plannedInput.name = `items[${newIndex}][planned_qty]`;
                plannedInput.value = '';
            }
            if (bufferInput) {
                bufferInput.name = `items[${newIndex}][buffer_qty]`;
                bufferInput.value = '';
            }

            container.appendChild(cloned);
            initDispatchRowsManager();
        });
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
