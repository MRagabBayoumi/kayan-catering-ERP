/**
 * Kayan ERP — Purchase Returns Module Interactions
 * Ergonomic POS/ERP barcode scanning, keyboard-driven navigation, fast unit conversion, and dynamic return items table.
 */

export function initPurchaseReturns() {
    const formPage = document.querySelector('[data-page="purchase-returns-form"]');
    if (!formPage) {
        return;
    }

    // 1. Load Data Payloads
    let productsData = [];
    let unitsData = [];
    let suppliersData = {};

    try {
        productsData = JSON.parse(formPage.getAttribute('data-products') || '[]');
    } catch (e) {
        productsData = [];
    }

    try {
        unitsData = JSON.parse(formPage.getAttribute('data-units') || '[]');
    } catch (e) {
        unitsData = [];
    }

    try {
        suppliersData = JSON.parse(formPage.getAttribute('data-suppliers') || '{}');
    } catch (e) {
        suppliersData = {};
    }

    // 2. DOM Elements
    const form = document.getElementById('purchaseReturnForm');
    const tableBody = document.getElementById('returnItemsBody');
    const emptyStateRow = document.getElementById('returnEmptyStateRow');

    // Supplier & Metadata
    const supplierSelect = document.getElementById('supplierId');
    const supplierBalanceBadge = document.getElementById('supplierBalanceBadge');
    const supplierBalanceText = document.getElementById('supplierBalanceText');
    const warehouseSelect = document.getElementById('warehouseId');
    const settlementSelect = document.getElementById('settlementType');
    const treasuryRow = document.getElementById('treasuryFieldRow');
    const treasurySelect = document.getElementById('treasuryId');

    // Quick Entry Bar
    const searchModeSelect = document.getElementById('searchModeSelect');
    const categoryFilterSelect = document.getElementById('categoryFilterSelect');
    const quickProductInput = document.getElementById('quickProductInput');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const selectedProductBadge = document.getElementById('selectedProductBadge');
    const selectedProductName = document.getElementById('selectedProductName');
    const clearSelectedProductBtn = document.getElementById('clearSelectedProductBtn');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const quickUnitSelect = document.getElementById('quickUnitSelect');
    const quickUnitPieceBtn = document.getElementById('quickUnitPieceBtn');
    const quickUnitDozenBtn = document.getElementById('quickUnitDozenBtn');
    const quickUnitDozenLabel = document.getElementById('quickUnitDozenLabel');
    const quickQtyInput = document.getElementById('quickQtyInput');
    const quickCostInput = document.getElementById('quickCostInput');
    const quickAddBtn = document.getElementById('quickAddBtn');

    // Summary Elements
    const returnItemsCount = document.getElementById('returnItemsCount');
    const returnTotalQty = document.getElementById('returnTotalQty');
    const returnGrandTotal = document.getElementById('returnGrandTotal');

    let selectedProduct = null;
    let activeSuggestionIndex = -1;

    // Helper: Get Product stock in current selected warehouse
    function getProductWarehouseStock(prod) {
        if (!prod || !prod.stocks) return 0;
        const currentWhId = warehouseSelect ? parseInt(warehouseSelect.value, 10) : null;
        if (currentWhId && prod.stocks[currentWhId] !== undefined) {
            return parseFloat(prod.stocks[currentWhId]) || 0;
        }
        return parseFloat(prod.total_stock) || 0;
    }

    // Helper: Format stock breakdown (pieces & packages)
    function formatStockBreakdown(prod, stockQty) {
        const factor = parseFloat(prod.package_factor || prod.unit_factor) || 12;
        if (factor > 1 && stockQty > 0) {
            const pkgs = Math.floor(stockQty / factor);
            const rem = Math.round(stockQty % factor);
            if (pkgs > 0 && rem > 0) return `${pkgs} دستة و ${rem} ق`;
            if (pkgs > 0) return `${pkgs} دستة`;
            return `${rem} ق`;
        }
        return `${stockQty} قطعة`;
    }

    // 3. Supplier Balance Live Badge
    function updateSupplierBalance() {
        if (!supplierSelect || !supplierBalanceBadge || !supplierBalanceText) return;
        const sId = supplierSelect.value;
        const sData = suppliersData[sId];
        if (sData) {
            const bal = parseFloat(sData.balance) || 0;
            supplierBalanceText.textContent = bal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م';
            supplierBalanceBadge.classList.remove('hidden');
            if (bal > 0) {
                supplierBalanceBadge.className = 'erp-badge erp-badge-danger text-xs font-numeric font-bold inline-flex items-center gap-1';
            } else if (bal < 0) {
                supplierBalanceBadge.className = 'erp-badge erp-badge-success text-xs font-numeric font-bold inline-flex items-center gap-1';
            } else {
                supplierBalanceBadge.className = 'erp-badge erp-badge-neutral text-xs font-numeric inline-flex items-center gap-1';
            }
        } else {
            supplierBalanceBadge.classList.add('hidden');
        }
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', updateSupplierBalance);
        updateSupplierBalance();
    }

    // 4. Settlement Type Toggle (Cash Refund vs Balance Deduction)
    if (settlementSelect && treasuryRow) {
        settlementSelect.addEventListener('change', function () {
            if (this.value === 'cash_refund') {
                treasuryRow.classList.remove('hidden');
                if (treasurySelect) treasurySelect.required = true;
            } else {
                treasuryRow.classList.add('hidden');
                if (treasurySelect) treasurySelect.required = false;
            }
        });
    }

    // 5. Warehouse change updates stock badges
    if (warehouseSelect) {
        warehouseSelect.addEventListener('change', function () {
            if (selectedProduct) {
                updateQuickStockBadge(selectedProduct);
            }
        });
    }

    function updateQuickStockBadge(prod) {
        if (!quickStockBadge) return;
        const stock = getProductWarehouseStock(prod);
        const text = formatStockBreakdown(prod, stock);
        quickStockBadge.textContent = `رصيد المستودع: ${text}`;
        quickStockBadge.classList.remove('hidden', 'in-stock', 'out-of-stock', 'low-stock');
        if (stock <= 0) {
            quickStockBadge.classList.add('out-of-stock');
        } else if (stock <= 5) {
            quickStockBadge.classList.add('low-stock');
        } else {
            quickStockBadge.classList.add('in-stock');
        }
    }

    // 6. Fast Unit Switcher Pills (Piece vs Dozen)
    function setQuickUnit(unitType) {
        if (!quickUnitSelect) return;
        const factor = selectedProduct ? (parseFloat(selectedProduct.package_factor || selectedProduct.unit_factor) || 12) : 12;

        if (unitType === 'piece') {
            if (quickUnitPieceBtn) quickUnitPieceBtn.classList.add('active');
            if (quickUnitDozenBtn) quickUnitDozenBtn.classList.remove('active');
            // Select unit with factor = 1
            for (let i = 0; i < quickUnitSelect.options.length; i++) {
                const optFactor = parseFloat(quickUnitSelect.options[i].dataset.factor) || 1.0;
                if (optFactor === 1.0 || quickUnitSelect.options[i].text.includes('قطعة')) {
                    quickUnitSelect.selectedIndex = i;
                    break;
                }
            }
        } else {
            if (quickUnitPieceBtn) quickUnitPieceBtn.classList.remove('active');
            if (quickUnitDozenBtn) quickUnitDozenBtn.classList.add('active');
            // Select unit with factor > 1
            for (let i = 0; i < quickUnitSelect.options.length; i++) {
                const optFactor = parseFloat(quickUnitSelect.options[i].dataset.factor) || 1.0;
                if (optFactor > 1.0 || quickUnitSelect.options[i].text.includes('دستة')) {
                    quickUnitSelect.selectedIndex = i;
                    break;
                }
            }
        }
        updateQuickCostBasedOnUnit();
    }

    if (quickUnitPieceBtn) {
        quickUnitPieceBtn.addEventListener('click', () => setQuickUnit('piece'));
    }
    if (quickUnitDozenBtn) {
        quickUnitDozenBtn.addEventListener('click', () => setQuickUnit('dozen'));
    }

    function updateQuickCostBasedOnUnit() {
        if (!selectedProduct || !quickCostInput || !quickUnitSelect) return;
        const baseCost = parseFloat(selectedProduct.cost_price) || 0;
        const selectedOpt = quickUnitSelect.options[quickUnitSelect.selectedIndex];
        const factor = parseFloat(selectedOpt?.dataset?.factor) || 1.0;
        quickCostInput.value = (baseCost * factor).toFixed(2);
    }

    if (quickUnitSelect) {
        quickUnitSelect.addEventListener('change', function () {
            const factor = parseFloat(this.options[this.selectedIndex]?.dataset?.factor) || 1.0;
            if (factor > 1) {
                if (quickUnitDozenBtn) quickUnitDozenBtn.classList.add('active');
                if (quickUnitPieceBtn) quickUnitPieceBtn.classList.remove('active');
            } else {
                if (quickUnitPieceBtn) quickUnitPieceBtn.classList.add('active');
                if (quickUnitDozenBtn) quickUnitDozenBtn.classList.remove('active');
            }
            updateQuickCostBasedOnUnit();
        });
    }

    // 7. Autocomplete Search & Product Selection
    function cleanSearchTerm(term) {
        if (!term) return '';
        let str = term.toString().trim();
        const arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < arabicIndic.length; i++) {
            str = str.replaceAll(arabicIndic[i], standardDigits[i]);
        }
        str = str.replace(/^\][A-Za-z0-9]{2}/, '');
        str = str.replace(/[\r\n\t\x00-\x1F\x7F-\x9F]/g, '');
        return str.trim();
    }

    function normalizeArabic(text) {
        if (!text) return '';
        return cleanSearchTerm(text).toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/[\u064B-\u065F]/g, '');
    }

    function findProductByTerm(term) {
        if (!term) return null;
        const clean = cleanSearchTerm(term).toLowerCase();
        if (!clean) return null;
        const norm = normalizeArabic(clean);

        // 1. Exact barcode
        let match = productsData.find(p => p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === clean);
        if (match) return match;
        // 2. Exact SKU
        match = productsData.find(p => p.sku && cleanSearchTerm(p.sku).toLowerCase() === clean);
        if (match) return match;
        // 3. Exact Name (normalized)
        match = productsData.find(p => p.name && normalizeArabic(p.name) === norm);
        if (match) return match;
        // 4. Starts with barcode or SKU
        match = productsData.find(p => (p.barcode && cleanSearchTerm(p.barcode).toLowerCase().startsWith(clean)) || (p.sku && cleanSearchTerm(p.sku).toLowerCase().startsWith(clean)));
        if (match) return match;
        // 5. Name contains term
        match = productsData.find(p => p.name && normalizeArabic(p.name).includes(norm));
        return match || null;
    }

    function selectProduct(prod) {
        selectedProduct = prod;
        if (quickProductInput) {
            quickProductInput.value = prod.name;
        }
        if (selectedProductBadge && selectedProductName) {
            selectedProductName.textContent = `${prod.name} (${prod.sku})`;
            selectedProductBadge.classList.remove('hidden');
        }
        updateQuickStockBadge(prod);

        // Update Dozen label pill
        const pkgFactor = parseFloat(prod.package_factor || prod.unit_factor) || 12;
        if (quickUnitDozenLabel) {
            quickUnitDozenLabel.textContent = `دستة (* ${pkgFactor})`;
        }

        // Set cost
        setQuickUnit('piece');

        if (quickSearchResults) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
        }

        // Auto move focus to quantity input and select text (like Sales)
        if (quickQtyInput) {
            setTimeout(() => {
                quickQtyInput.focus();
                quickQtyInput.select();
            }, 30);
        }
    }

    function clearSelectedProduct() {
        selectedProduct = null;
        if (quickProductInput) {
            quickProductInput.value = '';
            setTimeout(() => {
                quickProductInput.focus();
            }, 30);
        }
        if (selectedProductBadge) {
            selectedProductBadge.classList.add('hidden');
        }
        if (quickStockBadge) {
            quickStockBadge.classList.add('hidden');
        }
        if (quickCostInput) {
            quickCostInput.value = '0.00';
        }
        if (quickQtyInput) {
            quickQtyInput.value = '1';
        }
        setQuickUnit('piece');
    }

    if (clearSelectedProductBtn) {
        clearSelectedProductBtn.addEventListener('click', clearSelectedProduct);
    }

    function searchProducts(query) {
        if (!quickSearchResults) return;
        const clean = cleanSearchTerm(query).toLowerCase();
        const norm = normalizeArabic(clean);
        if (!clean) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
            return;
        }

        const mode = searchModeSelect ? searchModeSelect.value : 'name';
        const selectedCat = categoryFilterSelect ? categoryFilterSelect.value : '';

        const matches = productsData.filter(p => {
            // Category filter
            if (selectedCat && String(p.category_id) !== String(selectedCat)) {
                return false;
            }

            const pNameNorm = normalizeArabic(p.name || '');
            const pSkuClean = cleanSearchTerm(p.sku || '').toLowerCase();
            const pBarClean = cleanSearchTerm(p.barcode || '').toLowerCase();

            if (mode === 'barcode') {
                return pBarClean.includes(clean) || pSkuClean.includes(clean);
            } else if (mode === 'sku') {
                return pSkuClean.includes(clean);
            }
            // default 'name' matches normalized name, sku, or barcode
            return pNameNorm.includes(norm) || pSkuClean.includes(clean) || pBarClean.includes(clean);
        }).slice(0, 15);

        if (matches.length === 0) {
            quickSearchResults.innerHTML = `
                <div class="p-3 text-center text-xs text-[var(--text-muted)] flex items-center justify-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>لا توجد أصناف مطابقة لكلمة البحث</span>
                </div>
            `;
            quickSearchResults.classList.remove('hidden');
            return;
        }

        quickSearchResults.innerHTML = matches.map((p, idx) => {
            const costPrice = parseFloat(p.cost_price) || 0;
            const stock = getProductWarehouseStock(p);
            const formattedStock = formatStockBreakdown(p, stock);
            let badgeClass = 'stock-in';
            let badgeText = `المخزن: ${formattedStock}`;
            if (stock <= 0) {
                badgeClass = 'stock-out';
                badgeText = 'نفد من المخزن (0)';
            } else if (stock <= 5) {
                badgeClass = 'stock-low';
                badgeText = `منخفض: ${formattedStock}`;
            }

            return `
                <div class="suggestion-item pos-suggestion-item purchases-suggestion-item" data-index="${idx}" data-id="${p.id}">
                    <div class="flex flex-col flex-1 min-w-0 pr-1">
                        <span class="item-title truncate font-bold text-xs">${p.name}</span>
                        <div class="item-meta flex items-center gap-2 mt-1 flex-wrap">
                            <span class="font-numeric">كود: <span class="font-mono">${p.sku}</span></span>
                            ${p.barcode ? '<span class="font-numeric">· باركود: <span class="font-mono">' + p.barcode + '</span></span>' : ''}
                            <span class="pos-suggestion-badge ${badgeClass}">${badgeText}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric flex flex-col items-end pl-1 shrink-0">
                        <span class="pos-suggestion-price">${costPrice.toFixed(2)} <span class="text-[11px] font-normal text-[var(--text-muted)]">ج.م</span></span>
                        <span class="text-[11px] text-[var(--text-muted)]">${p.category_name || 'عام'}</span>
                    </div>
                </div>
            `;
        }).join('');

        quickSearchResults.querySelectorAll('.suggestion-item').forEach(el => {
            el.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const pId = parseInt(el.dataset.id, 10);
                const found = productsData.find(item => item.id === pId);
                if (found) {
                    selectProduct(found);
                }
            });
        });

        quickSearchResults.classList.remove('hidden');
        activeSuggestionIndex = -1;
    }

    if (quickProductInput) {
        quickProductInput.addEventListener('input', function () {
            searchProducts(this.value);
        });

        quickProductInput.addEventListener('keydown', function (e) {
            const term = this.value.trim().toLowerCase();

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const items = quickSearchResults.querySelectorAll('.suggestion-item');
                if (items.length > 0) {
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                    highlightSuggestion(items);
                }
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const items = quickSearchResults.querySelectorAll('.suggestion-item');
                if (items.length > 0) {
                    activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                    highlightSuggestion(items);
                }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault(); // Never submit main form on enter in search
                const items = quickSearchResults ? quickSearchResults.querySelectorAll('.suggestion-item') : [];

                // 1. If an item in dropdown is active (via Arrow keys)
                if (items.length > 0 && activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                    const pId = parseInt(items[activeSuggestionIndex].dataset.id, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) {
                        selectProduct(found);
                        return;
                    }
                }

                // 2. Exact or fuzzy match from search term
                if (term) {
                    const matched = findProductByTerm(term);
                    if (matched) {
                        selectProduct(matched);
                        return;
                    }

                    // 3. If items exist in dropdown, pick the first one
                    if (items.length > 0) {
                        const firstId = parseInt(items[0].dataset.id, 10);
                        const found = productsData.find(item => item.id === firstId);
                        if (found) {
                            selectProduct(found);
                            return;
                        }
                    }
                } else if (items.length > 0) {
                    const firstId = parseInt(items[0].dataset.id, 10);
                    const found = productsData.find(item => item.id === firstId);
                    if (found) {
                        selectProduct(found);
                        return;
                    }
                }
            }

            if (e.key === 'Escape') {
                if (quickSearchResults) quickSearchResults.classList.add('hidden');
            }
        });
    }

    // Keyboard Flow: Unit -> Enter or Tab -> Quantity
    if (quickUnitSelect) {
        quickUnitSelect.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || (e.key === 'Tab' && !e.shiftKey)) {
                e.preventDefault();
                if (quickQtyInput) {
                    quickQtyInput.focus();
                    quickQtyInput.select();
                }
            }
        });
    }

    // Keyboard Flow: Quantity -> Enter to Commit into Table, Tab to Cost
    if (quickQtyInput) {
        quickQtyInput.addEventListener('focus', function () {
            this.select();
        });
        quickQtyInput.addEventListener('keydown', function (e) {
            // Unit shortcuts: * for dozen, / for piece
            if (e.key === '*') {
                e.preventDefault();
                setQuickUnit('dozen');
                return;
            }
            if (e.key === '/') {
                e.preventDefault();
                setQuickUnit('piece');
                return;
            }

            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (quickCostInput) {
                    quickCostInput.focus();
                    quickCostInput.select();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                addProductToTable();
            }
        });
    }

    // Keyboard Flow: Cost -> Enter to Commit into Table
    if (quickCostInput) {
        quickCostInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addProductToTable();
            }
        });
    }

    function highlightSuggestion(items) {
        items.forEach((it, idx) => {
            const isCurrent = (idx === activeSuggestionIndex);
            it.classList.toggle('active', isCurrent);
            it.classList.toggle('bg-selected', isCurrent);
            if (isCurrent) {
                it.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    // Close autocomplete on click outside
    document.addEventListener('click', function (e) {
        if (quickSearchResults && !quickSearchResults.contains(e.target) && e.target !== quickProductInput) {
            quickSearchResults.classList.add('hidden');
        }
    });

    // 8. Add Product to Return Table
    function addProductToTable() {
        if (!selectedProduct) {
            if (quickProductInput && quickProductInput.value.trim()) {
                const val = quickProductInput.value.trim();
                const found = findProductByTerm(val);
                if (found) {
                    selectedProduct = found;
                } else {
                    alert('يرجى اختيار صنف صحيح من قائمة البحث أو قراءة الباركود.');
                    quickProductInput.focus();
                    return;
                }
            } else {
                alert('يرجى اختيار الصنف المراد إرجاعه أولاً.');
                quickProductInput?.focus();
                return;
            }
        }

        const qty = parseFloat(quickQtyInput ? quickQtyInput.value : 1) || 1;
        if (qty <= 0) {
            alert('يجب أن تكون الكمية المرتجعة أكبر من الصفر.');
            quickQtyInput?.focus();
            return;
        }

        const cost = parseFloat(quickCostInput ? quickCostInput.value : 0) || 0;
        const selectedOpt = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
        const unitId = selectedOpt ? selectedOpt.value : (unitsData[0]?.id || 1);
        const unitName = selectedOpt ? selectedOpt.dataset.name : 'قطعة';
        const factor = selectedOpt ? (parseFloat(selectedOpt.dataset.factor) || 1.0) : 1.0;

        // Check if item with same product and unit already in table
        const existingRow = tableBody.querySelector(`.return-item-row[data-product-id="${selectedProduct.id}"][data-unit-id="${unitId}"]`);
        if (existingRow) {
            const rowQtyInput = existingRow.querySelector('.return-qty-input');
            if (rowQtyInput) {
                const currentQty = parseFloat(rowQtyInput.value) || 0;
                const newQty = currentQty + qty;
                rowQtyInput.value = (newQty % 1 === 0 ? newQty.toFixed(0) : newQty.toFixed(2));
                updateRowTotals(existingRow);
                // Move updated row to the top of table (latest modified is at the top)
                tableBody.prepend(existingRow);
                existingRow.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'transition-colors', 'duration-700');
                setTimeout(() => {
                    existingRow.classList.remove('bg-emerald-50', 'dark:bg-emerald-950/40');
                }, 800);
                reindexRows();
                recalculateTotals();
                clearSelectedProduct();
                return;
            }
        }

        // Hide empty state
        if (emptyStateRow) {
            emptyStateRow.classList.add('hidden');
        }

        const currentCount = tableBody.querySelectorAll('.return-item-row').length;
        const newTotalCount = currentCount + 1;
        const lineTotal = (qty * cost).toFixed(2);
        const formattedQty = (qty % 1 === 0 ? qty.toFixed(0) : qty.toFixed(2));
        const stock = getProductWarehouseStock(selectedProduct);
        const formattedStock = formatStockBreakdown(selectedProduct, stock);
        const stockBadge = stock > 0 
            ? `<span class="erp-badge erp-badge-info text-[10px] py-0 px-2 font-bold">المخزن: ${formattedStock}</span>` 
            : `<span class="erp-badge erp-badge-danger text-[10px] py-0 px-2 font-bold">نفد من المخزن</span>`;

        const rowHtml = `
            <tr class="return-item-row" data-row-index="0" data-product-id="${selectedProduct.id}" data-unit-id="${unitId}">
                <td class="text-center font-numeric row-num-cell">${newTotalCount}</td>
                <td>
                    <input type="hidden" name="items[0][product_id]" value="${selectedProduct.id}">
                    <input type="hidden" name="items[0][unit_id]" value="${unitId}">
                    <input type="hidden" name="items[0][conversion_factor]" value="${factor}">
                    <div class="font-bold text-sm text-[var(--text-main)] mb-1 leading-snug">${selectedProduct.name}</div>
                    <div class="text-[11px] text-[var(--text-muted)] font-numeric flex flex-wrap items-center gap-2">
                        <span class="font-semibold">كود: <span class="font-mono text-[var(--text-main)]">${selectedProduct.sku}</span></span>
                        ${selectedProduct.barcode ? `<span>· باركود: <span class="font-mono">${selectedProduct.barcode}</span></span>` : ''}
                        ${selectedProduct.category_name ? `<span class="erp-badge erp-badge-neutral text-[10px] py-0 px-2 font-bold">${selectedProduct.category_name}</span>` : ''}
                        ${stockBadge}
                    </div>
                </td>
                <td class="text-center">
                    <span class="inline-block bg-[var(--surface-muted)] px-3 py-1 rounded text-xs font-bold text-[var(--text-main)] border border-[var(--border-color)]">
                        ${unitName}
                    </span>
                    ${factor > 1 ? `<div class="text-[11px] text-[var(--primary)] font-bold font-numeric mt-1">(×${factor} قطعة)</div>` : ''}
                </td>
                <td>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        name="items[0][unit_cost]" 
                        value="${cost.toFixed(2)}" 
                        class="erp-input text-xs font-numeric text-center return-cost-input w-full" 
                        required
                    >
                </td>
                <td>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        name="items[0][quantity]" 
                        value="${formattedQty}" 
                        class="erp-input text-xs font-numeric text-center return-qty-input font-bold w-full" 
                        required
                    >
                </td>
                <td class="font-numeric font-bold text-sm text-[var(--danger)] text-center return-line-subtotal">
                    ${lineTotal}
                </td>
                <td>
                    <input 
                        type="text" 
                        name="items[0][notes]" 
                        class="erp-input text-xs w-full" 
                        placeholder="سبب الإرجاع..."
                    >
                </td>
                <td class="text-center">
                    <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn p-1" title="حذف الصنف">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;

        // Prepend to top of table (الصنف المضاف ينزل في أول السلة)
        tableBody.insertAdjacentHTML('afterbegin', rowHtml);
        const insertedRow = tableBody.firstElementChild;
        insertedRow.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'transition-colors', 'duration-700');
        setTimeout(() => {
            insertedRow.classList.remove('bg-emerald-50', 'dark:bg-emerald-950/40');
        }, 800);

        attachRowEvents(insertedRow);
        reindexRows();
        recalculateTotals();
        clearSelectedProduct();
    }

    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', addProductToTable);
    }

    // 9. Table Row Events & Calculations
    function updateRowTotals(row) {
        const costInput = row.querySelector('.return-cost-input');
        const qtyInput = row.querySelector('.return-qty-input');
        const subtotalCell = row.querySelector('.return-line-subtotal');
        if (costInput && qtyInput && subtotalCell) {
            const cost = parseFloat(costInput.value) || 0;
            const qty = parseFloat(qtyInput.value) || 0;
            subtotalCell.textContent = (cost * qty).toFixed(2);
        }
    }

    function attachRowEvents(row) {
        const costInput = row.querySelector('.return-cost-input');
        const qtyInput = row.querySelector('.return-qty-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        if (costInput) {
            costInput.addEventListener('focus', () => costInput.select());
            costInput.addEventListener('input', () => {
                updateRowTotals(row);
                recalculateTotals();
            });
            costInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateRowTotals(row);
                    recalculateTotals();
                    quickProductInput?.focus();
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('focus', () => qtyInput.select());
            qtyInput.addEventListener('input', () => {
                row.classList.remove('bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-400');
                updateRowTotals(row);
                recalculateTotals();
            });
            qtyInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateRowTotals(row);
                    recalculateTotals();
                    quickProductInput?.focus();
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexRows();
                recalculateTotals();
            });
        }
    }

    function reindexRows() {
        const rows = tableBody.querySelectorAll('.return-item-row');
        const total = rows.length;
        if (total === 0) {
            if (emptyStateRow) emptyStateRow.classList.remove('hidden');
        } else {
            if (emptyStateRow) emptyStateRow.classList.add('hidden');
        }

        rows.forEach((row, idx) => {
            row.setAttribute('data-row-index', idx);
            const numCell = row.querySelector('.row-num-cell');
            if (numCell) {
                // الصنف في أول السلة وترقيمه الأخير (تنازلي حتى 1 في قاع السلة)
                numCell.textContent = total - idx;
            }

            row.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
                }
            });
        });
    }

    function recalculateTotals() {
        let totalSum = 0;
        let totalItems = 0;
        let totalQtySum = 0;

        const rows = tableBody.querySelectorAll('.return-item-row');
        rows.forEach(row => {
            const cost = parseFloat(row.querySelector('.return-cost-input')?.value || 0) || 0;
            const qty = parseFloat(row.querySelector('.return-qty-input')?.value || 0) || 0;
            totalSum += cost * qty;
            totalQtySum += qty;
            totalItems++;
        });

        if (returnItemsCount) returnItemsCount.textContent = totalItems;
        if (returnTotalQty) returnTotalQty.textContent = totalQtySum.toFixed(2);
        if (returnGrandTotal) returnGrandTotal.textContent = totalSum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Attach events to existing rows
    tableBody.querySelectorAll('.return-item-row').forEach(row => attachRowEvents(row));
    reindexRows();
    recalculateTotals();

    // 10. Form Submit Validation & Keydown Guard
    if (form) {
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
                e.preventDefault();
                if (e.target === quickProductInput || e.target === quickQtyInput || e.target === quickCostInput) {
                    addProductToTable();
                }
            }
        });

        form.addEventListener('submit', function (e) {
            const rows = tableBody.querySelectorAll('.return-item-row');
            if (rows.length === 0) {
                e.preventDefault();
                alert('يجب إضافة صنف واحد على الأقل في مرتجع المشتريات.');
                quickProductInput?.focus();
                return;
            }

            // If action is "post" (ترحيل فوري وخصم من المخزون), validate warehouse stock for every row
            const submitter = e.submitter;
            const action = submitter ? submitter.value : null;

            if (action === 'post') {
                for (const row of rows) {
                    const pId = parseInt(row.getAttribute('data-product-id') || row.querySelector('input[name*="[product_id]"]')?.value, 10);
                    const prod = productsData.find(p => p.id === pId);
                    const qtyInput = row.querySelector('.return-qty-input');
                    const factorInput = row.querySelector('input[name*="[conversion_factor]"]');
                    const factor = parseFloat(factorInput?.value) || 1.0;
                    const qty = parseFloat(qtyInput?.value) || 0;
                    const baseQty = qty * factor;

                    if (prod) {
                        const stock = getProductWarehouseStock(prod);
                        if (stock < baseQty) {
                            e.preventDefault();
                            row.classList.add('bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-400');
                            alert(`تنبيه رصيد المخزن:\nالصنف: "${prod.name}"\nالرصيد المتاح بالمخزن: ${stock} قطعة\nالكمية المطلوب إرجاعها: ${baseQty} قطعة\n\nالرصيد لا يكفي للترحيل، يرجى تعديل الكمية.`);
                            qtyInput?.focus();
                            qtyInput?.select();
                            return;
                        }
                    }
                }
            }
        });
    }
}
