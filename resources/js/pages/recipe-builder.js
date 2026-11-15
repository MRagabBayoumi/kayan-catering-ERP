/**
 * Kayan ERP — Recipe Builder (Cashier POS Basket Mode)
 * Allows adding ingredients into a live cart/basket, calculating line costs,
 * handling unit conversions (gm to kg, ml to liter), and posting normalized
 * standard meal portion costs to ERP.
 */

export function initRecipeBuilder() {
    const container = document.getElementById('recipeBuilderContainer');
    if (!container) return;

    // Prevent duplicate initializations on Turbo SPA navigation
    if (container.dataset.builderInitialized === 'true') return;
    container.dataset.builderInitialized = 'true';

    const productSelect = document.getElementById('posProductSelect');
    const weightInput = document.getElementById('posWeightInput');
    const unitSelect = document.getElementById('posUnitSelect');
    const costInput = document.getElementById('posCostInput');
    const costUnitBadge = document.getElementById('posCostUnitBadge');
    const addBtn = document.getElementById('posAddToCartBtn');
    const cartTableBody = document.getElementById('recipeBasketBody');
    const emptyNotice = document.getElementById('recipeBasketEmpty');
    const countDisplay = document.getElementById('basketCountDisplay');
    const totalWeightDisplay = document.getElementById('basketTotalWeightDisplay');
    const totalCostDisplay = document.getElementById('basketTotalCostDisplay');
    const totalCostHidden = document.getElementById('standardCostPerMealHidden');
    const recipeForm = document.getElementById('recipePosForm');

    const basket = []; // array of { productId, productName, weight, unit, baseProductUnit, normalizedQty, unitCost, lineCost }

    function updateWeightPlaceholder() {
        if (!weightInput || !unitSelect) return;
        const u = unitSelect.value;
        if (u === 'gm') {
            weightInput.placeholder = '100 (جرام)';
            weightInput.step = '1';
        } else if (u === 'kg') {
            weightInput.placeholder = '0.150 (كجم)';
            weightInput.step = '0.001';
        } else if (u === 'pcs') {
            weightInput.placeholder = '1 (قطعة)';
            weightInput.step = '1';
        } else if (u === 'ml') {
            weightInput.placeholder = '50 (مل)';
            weightInput.step = '1';
        } else {
            weightInput.placeholder = '0.100';
            weightInput.step = '0.001';
        }
    }

    function updateProductDetails() {
        const opt = productSelect.options[productSelect.selectedIndex];
        if (!opt || !opt.value) {
            if (costInput) costInput.value = '';
            if (costUnitBadge) costUnitBadge.textContent = 'ج.م';
            return;
        }
        const cost = parseFloat(opt.dataset.cost || '0');
        const unit = opt.dataset.unit || 'kg';
        if (costInput) costInput.value = cost.toFixed(2);

        const unitLabelAr = unit === 'kg' ? 'كجم' : (unit === 'pcs' ? 'قطعة' : (unit === 'liter' ? 'لتر' : unit));
        if (costUnitBadge) costUnitBadge.textContent = `ج.م / ${unitLabelAr}`;

        if (unitSelect && unit) {
            let matched = false;
            for (let i = 0; i < unitSelect.options.length; i++) {
                if (unitSelect.options[i].value === unit) {
                    unitSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched && unit === 'kg') {
                unitSelect.value = 'kg';
            }
        }
        updateWeightPlaceholder();
    }

    productSelect?.addEventListener('change', updateProductDetails);
    unitSelect?.addEventListener('change', updateWeightPlaceholder);

    // Add to basket button
    addBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const productId = productSelect.value;
        const opt = productSelect.options[productSelect.selectedIndex];
        const productName = opt ? (opt.dataset.name || opt.text.split('(')[0].trim()) : '';
        const weight = parseFloat(weightInput.value || 0);
        const unit = unitSelect.value || 'kg';
        const baseProductUnit = opt?.dataset.unit || 'kg';
        const unitCost = parseFloat(costInput.value || (opt?.dataset.cost || 0));

        if (!productId) {
            alert('يرجى اختيار الصنف أولاً من القائمة');
            productSelect.focus();
            return;
        }

        if (weight <= 0 || isNaN(weight)) {
            alert(unit === 'gm' ? 'يرجى إدخال وزن المقنن بالجرام (مثلاً: 100)' : 'يرجى إدخال وزن المقنن للوجبة (مثلاً: 0.150 كجم)');
            weightInput.focus();
            return;
        }

        // Unit conversion: grams -> kg, ml -> liter when multiplied by base procurement cost
        let normalizedQty = weight;
        if ((unit === 'gm' || unit === 'g') && baseProductUnit === 'kg') {
            normalizedQty = weight / 1000;
        } else if (unit === 'ml' && baseProductUnit === 'liter') {
            normalizedQty = weight / 1000;
        }

        const lineCost = normalizedQty * unitCost;

        // Check if item already exists in basket -> increase weight
        const existingIndex = basket.findIndex(item => String(item.productId) === String(productId));
        if (existingIndex > -1) {
            basket[existingIndex].normalizedQty += normalizedQty;
            if (basket[existingIndex].unit === 'gm') {
                basket[existingIndex].weight = basket[existingIndex].normalizedQty * 1000;
            } else {
                basket[existingIndex].weight = basket[existingIndex].normalizedQty;
            }
            basket[existingIndex].lineCost = basket[existingIndex].normalizedQty * basket[existingIndex].unitCost;
        } else {
            basket.push({
                productId,
                productName,
                weight,
                unit,
                baseProductUnit,
                normalizedQty,
                unitCost,
                lineCost
            });
        }

        // Reset inputs for next scan/add
        weightInput.value = '';
        productSelect.selectedIndex = 0;
        if (costInput) costInput.value = '';
        if (costUnitBadge) costUnitBadge.textContent = 'ج.م';
        updateWeightPlaceholder();

        renderBasket();
        productSelect.focus();
    });

    // Remove from basket via event delegation
    cartTableBody?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-action="remove-ingredient"]');
        if (!removeBtn) return;
        const index = parseInt(removeBtn.dataset.index, 10);
        if (!isNaN(index) && index >= 0 && index < basket.length) {
            basket.splice(index, 1);
            renderBasket();
        }
    });

    // Validate basket is not empty before form submit
    recipeForm?.addEventListener('submit', (e) => {
        if (basket.length === 0) {
            e.preventDefault();
            alert('سلة المقنن فارغة! يرجى إضافة صنف خام واحد على الأقل للوجبة قبل الاعتماد والترحيل.');
            productSelect.focus();
        }
    });

    function renderBasket() {
        if (!cartTableBody) return;
        cartTableBody.innerHTML = '';

        if (basket.length === 0) {
            if (emptyNotice) emptyNotice.classList.remove('hidden');
            if (countDisplay) countDisplay.textContent = '0';
            if (totalWeightDisplay) totalWeightDisplay.textContent = '0.000 كجم';
            if (totalCostDisplay) totalCostDisplay.textContent = '0.00 ج.م';
            if (totalCostHidden) totalCostHidden.value = '0.00';
            return;
        }

        if (emptyNotice) emptyNotice.classList.add('hidden');

        let grandCost = 0.00;
        let grandWeight = 0.00;

        basket.forEach((item, index) => {
            grandCost += item.lineCost;
            grandWeight += item.normalizedQty;

            const unitLabel = item.unit === 'gm' ? 'جرام (gm)' : (item.unit === 'kg' ? 'كجم (kg)' : item.unit);
            const baseUnitLabel = item.baseProductUnit === 'kg' ? 'كجم' : (item.baseProductUnit === 'pcs' ? 'قطعة' : item.baseProductUnit);

            const weightDisplayHtml = item.unit === 'gm'
                ? `<div class="font-mono font-bold text-slate-900 dark:text-slate-100">${item.weight.toFixed(0)} <span class="text-xs font-normal text-slate-500">جم</span></div>
                   <div class="text-[11px] font-mono text-slate-400">(${item.normalizedQty.toFixed(3)} كجم)</div>`
                : `<div class="font-mono font-bold text-slate-900 dark:text-slate-100">${item.weight.toFixed(3)} <span class="text-xs font-normal text-slate-500">${item.unit}</span></div>`;

            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors';
            tr.innerHTML = `
                <td class="font-mono text-xs text-slate-400 py-3 px-3 text-center">${index + 1}</td>
                <td class="py-3 px-3">
                    <span class="font-bold text-slate-800 dark:text-slate-100 block">${item.productName}</span>
                    <input type="hidden" name="ingredients[${index}][product_id]" value="${item.productId}">
                </td>
                <td class="py-3 px-3">
                    ${weightDisplayHtml}
                    <input type="hidden" name="ingredients[${index}][raw_quantity_per_meal]" value="${item.normalizedQty.toFixed(4)}">
                </td>
                <td class="py-3 px-3 text-center">
                    <span class="erp-badge erp-badge-info text-xs">${unitLabel}</span>
                    <input type="hidden" name="ingredients[${index}][unit]" value="${item.baseProductUnit}">
                </td>
                <td class="py-3 px-3">
                    <div class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${item.unitCost.toFixed(2)} ج.م</div>
                    <div class="text-[10px] text-slate-400">لكل ${baseUnitLabel}</div>
                    <input type="hidden" name="ingredients[${index}][standard_unit_cost]" value="${item.unitCost.toFixed(4)}">
                </td>
                <td class="py-3 px-3">
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">${item.lineCost.toFixed(2)} ج.م</span>
                </td>
                <td class="py-3 px-3 text-center">
                    <button type="button" class="erp-btn erp-btn-danger text-xs p-1.5 inline-flex items-center justify-center rounded-lg hover:bg-rose-600 transition-colors" data-action="remove-ingredient" data-index="${index}" title="حذف من السلة">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </td>
            `;
            cartTableBody.appendChild(tr);
        });

        if (countDisplay) countDisplay.textContent = basket.length.toString();
        if (totalWeightDisplay) totalWeightDisplay.textContent = grandWeight.toFixed(3) + ' كجم';
        if (totalCostDisplay) totalCostDisplay.textContent = grandCost.toFixed(2) + ' ج.م';
        if (totalCostHidden) totalCostHidden.value = grandCost.toFixed(2);
    }
}
