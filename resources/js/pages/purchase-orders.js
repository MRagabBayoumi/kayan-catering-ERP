/**
 * Kayan ERP — Purchase Orders Module Interactions
 * High-speed POS entry, Arabic-normalized search, dynamic unit handling, and real-time summary calculations.
 */

export function initPurchaseOrders() {
    const formPage = document.querySelector('[data-page="purchase-order-form"]');
    if (!formPage) {
        return;
    }

    let productsData = [];
    try {
        productsData = JSON.parse(formPage.getAttribute('data-products') || '[]');
    } catch (e) {
        productsData = [];
    }

    const form = document.getElementById('purchaseOrderForm');
    const tableBody = document.getElementById('poItemsBody');
    const emptyStateRow = document.getElementById('poEmptyStateRow');

    const searchModeSelect = document.getElementById('searchModeSelect');
    const quickProductInput = document.getElementById('quickProductInput');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const quickUnitSelect = document.getElementById('quickUnitSelect');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const quickQtyInput = document.getElementById('quickQtyInput');
    const quickCostInput = document.getElementById('quickCostInput');
    const quickAddBtn = document.getElementById('quickAddBtn');
    const clearOrderBtn = document.getElementById('clearOrderBtn');

    // Summary Elements
    const summaryItemsCount = document.getElementById('summaryItemsCount');
    const summaryTotalUnits = document.getElementById('summaryTotalUnits');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryDiscountDisplay = document.getElementById('summaryDiscountDisplay');
    const summaryShippingDisplay = document.getElementById('summaryShippingDisplay');
    const summaryGrandTotal = document.getElementById('summaryGrandTotal');
    const itemsCountBadge = document.getElementById('itemsCountBadge');

    // Financial Inputs
    const discountTypeSelect = document.getElementById('discountTypeSelect');
    const discountValueInput = document.getElementById('discountValueInput');
    const shippingCostInput = document.getElementById('shippingCostInput');

    let activeSelectedProduct = null;
    let activeSuggestionIndex = -1;
    let rowCounter = 1000;

    function cleanSearchTerm(term) {
        return (term || '').trim();
    }

    function normalizeArabic(text) {
        if (!text) return '';
        return cleanSearchTerm(text).toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/[\u064B-\u065F]/g, '');
    }

    function updateStockBadge(product) {
        if (!quickStockBadge) return;
        if (!product) {
            quickStockBadge.classList.add('hidden');
            return;
        }
        const stock = product.stock || 0;
        quickStockBadge.textContent = `رصيد المخزن: ${stock} ${product.unit || 'قطعة'}`;
        quickStockBadge.classList.remove('hidden');
    }

    function recalculateTotals() {
        if (!tableBody) return;
        const rows = tableBody.querySelectorAll('tr[data-product-id]');
        let subtotal = 0;
        let totalUnits = 0;
        let totalCount = 0;

        rows.forEach((row, idx) => {
            const rowNumber = row.querySelector('.row-number');
            if (rowNumber) rowNumber.textContent = idx + 1;

            const qtyInput = row.querySelector('.item-qty-input');
            const costInput = row.querySelector('.item-cost-input');
            const lineSubtotalText = row.querySelector('.item-subtotal-text');

            const qty = parseFloat(qtyInput?.value || 0);
            const cost = parseFloat(costInput?.value || 0);

            const lineTotal = Math.max(0, qty * cost);
            if (lineSubtotalText) {
                lineSubtotalText.textContent = lineTotal.toFixed(2) + ' ج.م';
            }

            subtotal += lineTotal;
            totalUnits += qty;
            totalCount++;
        });

        if (emptyStateRow) {
            if (totalCount === 0) {
                emptyStateRow.classList.remove('hidden');
            } else {
                emptyStateRow.classList.add('hidden');
            }
        }

        if (itemsCountBadge) {
            itemsCountBadge.textContent = totalCount;
        }
        if (summaryItemsCount) {
            summaryItemsCount.textContent = totalCount;
        }
        if (summaryTotalUnits) {
            summaryTotalUnits.textContent = totalUnits.toFixed(2).replace(/\.00$/, '');
        }
        if (summarySubtotal) {
            summarySubtotal.textContent = subtotal.toFixed(2) + ' ج.م';
        }

        const discType = discountTypeSelect?.value || 'fixed';
        const discVal = parseFloat(discountValueInput?.value || 0);
        let discountAmount = 0;

        if (discType === 'percentage') {
            discountAmount = (subtotal * discVal) / 100;
        } else {
            discountAmount = Math.min(subtotal, discVal);
        }

        if (summaryDiscountDisplay) {
            summaryDiscountDisplay.textContent = (discountAmount > 0 ? '-' : '') + discountAmount.toFixed(2) + ' ج.م';
        }

        const shipping = parseFloat(shippingCostInput?.value || 0);
        if (summaryShippingDisplay) {
            summaryShippingDisplay.textContent = (shipping > 0 ? '+' : '') + shipping.toFixed(2) + ' ج.م';
        }

        const grandTotal = Math.max(0, (subtotal - discountAmount) + shipping);
        if (summaryGrandTotal) {
            summaryGrandTotal.textContent = grandTotal.toFixed(2) + ' ج.م';
        }
    }

    // Autocomplete & Search Rendering
    function renderSuggestions(query) {
        if (!quickSearchResults) return;
        const q = cleanSearchTerm(query);
        if (!q || q.length < 1) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
            activeSuggestionIndex = -1;
            return;
        }

        const cleanQ = q.toLowerCase();
        const normQ = normalizeArabic(q);
        const mode = searchModeSelect ? searchModeSelect.value : 'barcode';

        const matches = productsData.filter(p => {
            const pBarcode = cleanSearchTerm(p.barcode || '').toLowerCase();
            const pSku = (p.sku || '').toLowerCase();
            const pNameNorm = normalizeArabic(p.name);

            if (mode === 'sku') {
                return pSku.includes(cleanQ) || pNameNorm.includes(normQ) || pBarcode.includes(cleanQ);
            }
            if (mode === 'name') {
                return pNameNorm.includes(normQ) || pSku.includes(cleanQ) || pBarcode.includes(cleanQ);
            }
            // default barcode
            return pBarcode.includes(cleanQ) || pNameNorm.includes(normQ) || pSku.includes(cleanQ);
        });

        if (!matches.length) {
            quickSearchResults.innerHTML = `
                <div class="px-3 py-3 text-xs text-[var(--text-muted)] text-center">
                    لا يوجد صنف مطابق لـ "${query}"
                </div>
            `;
            quickSearchResults.classList.remove('hidden');
            activeSuggestionIndex = -1;
            return;
        }

        quickSearchResults.innerHTML = matches.slice(0, 10).map((p, idx) => {
            const cost = p.cost_price || 0;
            const stock = p.stock || 0;
            let badgeClass = 'stock-in';
            let badgeText = `رصيد: ${stock}`;
            if (stock <= 0) {
                badgeClass = 'stock-out';
                badgeText = 'نفد (0)';
            } else if (stock <= 5) {
                badgeClass = 'stock-low';
                badgeText = `منخفض: ${stock}`;
            }

            return `
                <div class="suggestion-item pos-suggestion-item" data-index="${idx}" data-id="${p.id}">
                    <div class="flex flex-col flex-1 min-w-0 pr-1">
                        <span class="item-title truncate font-bold text-slate-800 dark:text-slate-100">${p.name}</span>
                        <div class="item-meta flex items-center gap-2 mt-1 text-xs text-[var(--text-muted)]">
                            ${p.sku ? `<span class="font-numeric">كود: ${p.sku}</span>` : ''}
                            ${p.barcode ? `<span class="font-numeric">· باركود: ${p.barcode}</span>` : ''}
                            <span class="pos-suggestion-badge ${badgeClass}">${badgeText}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric flex flex-col items-end pl-1 shrink-0">
                        <span class="pos-suggestion-price font-bold text-sm text-[var(--primary)]">${cost.toFixed(2)} <span class="text-[11px] font-normal text-[var(--text-muted)]">ج.م</span></span>
                        <span class="text-[10px] text-[var(--text-muted)] font-normal">${p.unit || 'قطعة'}</span>
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

    function selectProduct(product) {
        activeSelectedProduct = product;
        if (quickProductInput) {
            quickProductInput.value = product.name;
        }
        if (quickCostInput) {
            quickCostInput.value = (product.cost_price || 0).toFixed(2);
        }
        if (quickQtyInput) {
            quickQtyInput.value = '1';
        }
        updateStockBadge(product);

        if (quickSearchResults) {
            quickSearchResults.classList.add('hidden');
        }

        // Focus Qty input for fast editing
        if (quickQtyInput) {
            quickQtyInput.focus();
            quickQtyInput.select();
        }
    }

    if (quickProductInput) {
        quickProductInput.addEventListener('input', (e) => {
            renderSuggestions(e.target.value);
        });

        quickProductInput.addEventListener('keydown', (e) => {
            if (!quickSearchResults || quickSearchResults.classList.contains('hidden')) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    // Direct barcode scanner strike
                    const rawVal = cleanSearchTerm(quickProductInput.value);
                    if (!rawVal) return;
                    const directMatch = productsData.find(p => 
                        (p.barcode && p.barcode.toLowerCase() === rawVal.toLowerCase()) ||
                        (p.sku && p.sku.toLowerCase() === rawVal.toLowerCase())
                    );
                    if (directMatch) {
                        selectProduct(directMatch);
                        addItemToTable();
                    }
                }
                return;
            }

            const items = quickSearchResults.querySelectorAll('.suggestion-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                updateSuggestionHighlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                updateSuggestionHighlight(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                    const pId = parseInt(items[activeSuggestionIndex].dataset.id, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) selectProduct(found);
                } else if (items.length > 0) {
                    const pId = parseInt(items[0].dataset.id, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) selectProduct(found);
                }
            } else if (e.key === 'Escape') {
                quickSearchResults.classList.add('hidden');
            }
        });
    }

    function updateSuggestionHighlight(items) {
        items.forEach((it, idx) => {
            const isCurrent = idx === activeSuggestionIndex;
            it.classList.toggle('active', isCurrent);
            if (isCurrent) {
                it.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    // Hide suggestions on outside click
    document.addEventListener('click', (e) => {
        if (quickSearchResults && !quickSearchResults.contains(e.target) && e.target !== quickProductInput) {
            quickSearchResults.classList.add('hidden');
        }
    });

    // Enter in Quick Qty -> moves to Cost
    if (quickQtyInput) {
        quickQtyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (quickCostInput) {
                    quickCostInput.focus();
                    quickCostInput.select();
                }
            }
        });
    }

    // Enter in Quick Cost -> adds to table
    if (quickCostInput) {
        quickCostInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addItemToTable();
            }
        });
    }

    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addItemToTable();
        });
    }

    function addItemToTable() {
        if (!activeSelectedProduct) {
            alert('يرجى اختيار صنف أولاً من البحث');
            quickProductInput?.focus();
            return;
        }

        const qty = parseFloat(quickQtyInput?.value || 0);
        if (qty <= 0) {
            alert('يرجى إدخال كمية صحيحة أكبر من صفر');
            quickQtyInput?.focus();
            return;
        }

        const cost = parseFloat(quickCostInput?.value || 0);
        if (cost < 0) {
            alert('يرجى إدخال سعر تكلفة صحيح');
            quickCostInput?.focus();
            return;
        }

        // Unit info
        const selectedUnitOption = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
        const unitId = selectedUnitOption ? selectedUnitOption.value : '';
        const unitName = selectedUnitOption ? selectedUnitOption.getAttribute('data-name') : (activeSelectedProduct.unit || 'قطعة');

        // Check if item already in table -> merge quantity
        const existingRow = tableBody.querySelector(`tr[data-product-id="${activeSelectedProduct.id}"]`);
        if (existingRow) {
            const currentQtyInput = existingRow.querySelector('.item-qty-input');
            const currentCostInput = existingRow.querySelector('.item-cost-input');

            if (currentQtyInput) {
                const currentQty = parseFloat(currentQtyInput.value || 0);
                currentQtyInput.value = (currentQty + qty).toString();
            }
            if (currentCostInput && cost > 0) {
                currentCostInput.value = cost.toFixed(2);
            }
            // Move row to top
            tableBody.prepend(existingRow);
        } else {
            // Create new row
            const rowIndex = ++rowCounter;
            const tr = document.createElement('tr');
            tr.setAttribute('data-product-id', activeSelectedProduct.id);

            const lineSubtotal = (qty * cost).toFixed(2);

            tr.innerHTML = `
                <td class="text-center font-numeric text-xs text-[var(--text-muted)] row-number"></td>
                <td>
                    <input type="hidden" name="items[${rowIndex}][product_id]" value="${activeSelectedProduct.id}">
                    <div class="font-bold text-sm text-[var(--text-main)]">${activeSelectedProduct.name}</div>
                    <div class="text-xs text-[var(--text-muted)] flex items-center gap-2 mt-0.5 font-numeric">
                        ${activeSelectedProduct.sku ? `<span>كود: ${activeSelectedProduct.sku}</span>` : ''}
                        ${activeSelectedProduct.barcode ? `<span>باركود: ${activeSelectedProduct.barcode}</span>` : ''}
                    </div>
                </td>
                <td class="text-center text-xs">
                    <span class="inline-block bg-[var(--surface-muted)] px-2 py-0.5 rounded font-semibold text-[var(--text-main)]">
                        ${unitName}
                    </span>
                    <input type="hidden" name="items[${rowIndex}][unit_id]" value="${unitId}">
                </td>
                <td>
                    <input type="number" step="any" min="0.0001" name="items[${rowIndex}][quantity]" value="${qty}" class="erp-input font-numeric text-center text-xs font-bold w-full item-qty-input" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_cost]" value="${cost.toFixed(2)}" class="erp-input font-numeric text-center text-xs font-bold text-[var(--primary)] w-full item-cost-input" required>
                </td>
                <td class="font-numeric font-bold text-left pl-6 text-sm text-[var(--text-main)] item-subtotal-text">
                    ${lineSubtotal} ج.م
                </td>
                <td class="text-center">
                    <button type="button" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)] remove-item-btn" title="حذف الصنف">
                        <x-icon name="trash" size="14" />
                    </button>
                </td>
            `;

            attachRowListeners(tr);
            tableBody.prepend(tr);
        }

        // Reset quick input
        activeSelectedProduct = null;
        if (quickProductInput) {
            quickProductInput.value = '';
            quickProductInput.focus();
        }
        if (quickQtyInput) quickQtyInput.value = '1';
        if (quickCostInput) quickCostInput.value = '0.00';
        updateStockBadge(null);

        recalculateTotals();
    }

    function attachRowListeners(row) {
        const qtyInput = row.querySelector('.item-qty-input');
        const costInput = row.querySelector('.item-cost-input');
        const removeBtn = row.querySelector('.remove-item-btn');

        [qtyInput, costInput].forEach(inp => {
            if (inp) {
                inp.addEventListener('input', recalculateTotals);
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                recalculateTotals();
            });
        }
    }

    // Attach to existing pre-rendered rows
    if (tableBody) {
        tableBody.querySelectorAll('tr[data-product-id]').forEach(row => {
            attachRowListeners(row);
        });
    }

    // Clear All Items
    if (clearOrderBtn) {
        clearOrderBtn.addEventListener('click', () => {
            if (!confirm('هل تريد بالتأكيد تفريغ جميع الأصناف من أمر الشراء؟')) return;
            const rows = tableBody.querySelectorAll('tr[data-product-id]');
            rows.forEach(r => r.remove());
            recalculateTotals();
        });
    }

    // Global Discount & Shipping recalculations
    if (discountTypeSelect) discountTypeSelect.addEventListener('change', recalculateTotals);
    if (discountValueInput) discountValueInput.addEventListener('input', recalculateTotals);
    if (shippingCostInput) shippingCostInput.addEventListener('input', recalculateTotals);

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            if (quickProductInput) {
                quickProductInput.focus();
                quickProductInput.select();
            }
        }
    });

    // Initial calculation
    recalculateTotals();
}
