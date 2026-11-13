/**
 * Kayan ERP — Price Quotations Module Interactions
 * High-speed POS entry, Arabic-normalized search, dynamic unit handling, and real-time summary calculations.
 */

export function initQuotations() {
    const formPage = document.querySelector('[data-page="quotation-form"]');
    if (!formPage) {
        return;
    }

    let productsData = [];
    try {
        productsData = JSON.parse(formPage.getAttribute('data-products') || '[]');
    } catch (e) {
        productsData = [];
    }

    const form = document.getElementById('quotationForm');
    const tableBody = document.getElementById('quotationItemsBody');
    const emptyStateRow = document.getElementById('quotationEmptyStateRow');

    const customerSelect = document.getElementById('customerId');
    const priceTierSelect = document.getElementById('priceTierSelect');
    const searchModeSelect = document.getElementById('searchModeSelect');
    const quickProductInput = document.getElementById('quickProductInput');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const quickUnitSelect = document.getElementById('quickUnitSelect');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const quickQtyInput = document.getElementById('quickQtyInput');
    const quickPriceInput = document.getElementById('quickPriceInput');
    const quickLineDiscountInput = document.getElementById('quickLineDiscountInput');
    const quickAddBtn = document.getElementById('quickAddBtn');
    const clearQuotationBtn = document.getElementById('clearQuotationBtn');

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

    function getSelectedTier() {
        return priceTierSelect ? priceTierSelect.value : 'wholesale';
    }

    function getTierPrice(product, tier) {
        if (!product) return 0;
        if (tier === 'retail') return product.retail_price || 0;
        if (tier === 'distributor') return product.distributor_price || product.wholesale_price || 0;
        return product.wholesale_price || product.retail_price || 0;
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
            const priceInput = row.querySelector('.item-price-input');
            const discountInput = row.querySelector('.item-discount-input');
            const lineSubtotalText = row.querySelector('.item-subtotal-text');

            const qty = parseFloat(qtyInput?.value || 0);
            const price = parseFloat(priceInput?.value || 0);
            const discount = parseFloat(discountInput?.value || 0);

            const lineTotal = Math.max(0, (qty * price) - discount);
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

    // Auto update prices when tier changes
    if (priceTierSelect) {
        priceTierSelect.addEventListener('change', () => {
            const tier = getSelectedTier();
            if (activeSelectedProduct && quickPriceInput) {
                quickPriceInput.value = getTierPrice(activeSelectedProduct, tier).toFixed(2);
            }
        });
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
            const tierPrice = getTierPrice(p, getSelectedTier());
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
                        <span class="pos-suggestion-price font-bold text-sm text-[var(--primary)]">${tierPrice.toFixed(2)} <span class="text-[11px] font-normal text-[var(--text-muted)]">ج.م</span></span>
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
        if (quickPriceInput) {
            quickPriceInput.value = getTierPrice(product, getSelectedTier()).toFixed(2);
        }
        if (quickQtyInput) {
            quickQtyInput.value = '1';
        }
        if (quickLineDiscountInput) {
            quickLineDiscountInput.value = '0.00';
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

    // Enter in Quick Qty -> moves to Price
    if (quickQtyInput) {
        quickQtyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (quickPriceInput) {
                    quickPriceInput.focus();
                    quickPriceInput.select();
                }
            }
        });
    }

    // Enter in Quick Price -> moves to Discount or adds to table
    if (quickPriceInput) {
        quickPriceInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (quickLineDiscountInput) {
                    quickLineDiscountInput.focus();
                    quickLineDiscountInput.select();
                } else {
                    addItemToTable();
                }
            }
        });
    }

    // Enter in Quick Line Discount -> adds to table
    if (quickLineDiscountInput) {
        quickLineDiscountInput.addEventListener('keydown', (e) => {
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

        const price = parseFloat(quickPriceInput?.value || 0);
        if (price < 0) {
            alert('يرجى إدخال سعر صحيح');
            quickPriceInput?.focus();
            return;
        }

        const lineDiscount = parseFloat(quickLineDiscountInput?.value || 0);

        // Unit info
        const selectedUnitOption = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
        const unitId = selectedUnitOption ? selectedUnitOption.value : '';
        const unitName = selectedUnitOption ? selectedUnitOption.getAttribute('data-name') : (activeSelectedProduct.unit || 'قطعة');

        // Check if item already in table -> merge quantity
        const existingRow = tableBody.querySelector(`tr[data-product-id="${activeSelectedProduct.id}"]`);
        if (existingRow) {
            const currentQtyInput = existingRow.querySelector('.item-qty-input');
            const currentPriceInput = existingRow.querySelector('.item-price-input');
            const currentDiscountInput = existingRow.querySelector('.item-discount-input');

            if (currentQtyInput) {
                const currentQty = parseFloat(currentQtyInput.value || 0);
                currentQtyInput.value = (currentQty + qty).toString();
            }
            if (currentPriceInput && price > 0) {
                currentPriceInput.value = price.toFixed(2);
            }
            if (currentDiscountInput && lineDiscount > 0) {
                const currentDisc = parseFloat(currentDiscountInput.value || 0);
                currentDiscountInput.value = (currentDisc + lineDiscount).toFixed(2);
            }
            // Move row to top
            tableBody.prepend(existingRow);
        } else {
            // Create new row
            const rowIndex = ++rowCounter;
            const tr = document.createElement('tr');
            tr.setAttribute('data-product-id', activeSelectedProduct.id);

            const lineSubtotal = Math.max(0, (qty * price) - lineDiscount).toFixed(2);

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
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" value="${price.toFixed(2)}" class="erp-input font-numeric text-center text-xs font-bold text-[var(--primary)] w-full item-price-input" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][line_discount]" value="${lineDiscount.toFixed(2)}" class="erp-input font-numeric text-center text-xs w-full item-discount-input">
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
        if (quickPriceInput) quickPriceInput.value = '0.00';
        if (quickLineDiscountInput) quickLineDiscountInput.value = '0.00';
        updateStockBadge(null);

        recalculateTotals();
    }

    function attachRowListeners(row) {
        const qtyInput = row.querySelector('.item-qty-input');
        const priceInput = row.querySelector('.item-price-input');
        const discountInput = row.querySelector('.item-discount-input');
        const removeBtn = row.querySelector('.remove-item-btn');

        [qtyInput, priceInput, discountInput].forEach(inp => {
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
    if (clearQuotationBtn) {
        clearQuotationBtn.addEventListener('click', () => {
            if (!confirm('هل تريد بالتأكيد تفريغ جميع الأصناف من عرض السعر؟')) return;
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
        // F2 for Search
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
