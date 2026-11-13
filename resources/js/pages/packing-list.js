export function initPackingListPage() {
    const packingListBody = document.getElementById('packingListBody');
    if (!packingListBody) return;
    if (packingListBody.dataset.initialized === 'true') return;
    packingListBody.dataset.initialized = 'true';

    const STORAGE_KEY = 'kayan_packing_list_v1';
    let packingItems = [];
    let currentSelectedProduct = null;
    let searchDebounceTimer = null;

    // Check if a saved list was passed from controller for viewing/editing
    const packingListRoot = document.getElementById('packingListApp') || document.getElementById('packingListRoot');
    let serverLoadedList = null;
    if (packingListRoot && packingListRoot.dataset.loadedData) {
        try {
            serverLoadedList = JSON.parse(packingListRoot.dataset.loadedData);
        } catch (e) {
            console.error('Failed to parse loadedData', e);
        }
    }
    let currentListId = serverLoadedList ? serverLoadedList.id : null;

    // Elements
    const docNumberInput = document.getElementById('docNumberInput');
    const docDateInput = document.getElementById('docDateInput');
    const customerNameInput = document.getElementById('customerNameInput');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const defaultPriceModeSelect = document.getElementById('defaultPriceModeSelect');
    const docNotesInput = document.getElementById('docNotesInput');
    const fastScanModeCheckbox = document.getElementById('fastScanModeCheckbox');
    const fastScanText = document.getElementById('fastScanText');

    // Column Toggles
    const toggleShowQtyCheckbox = document.getElementById('toggleShowQtyCheckbox');
    const toggleShowPriceCheckbox = document.getElementById('toggleShowPriceCheckbox');
    const toggleShowPackagesCheckbox = document.getElementById('toggleShowPackagesCheckbox');

    // Quick entry wrappers
    const quickQtyWrapper = document.getElementById('quickQtyWrapper');
    const quickPriceWrapper = document.getElementById('quickPriceWrapper');
    const quickPackagesWrapper = document.getElementById('quickPackagesWrapper');
    const kpiQtyCard = document.getElementById('kpiQtyCard');
    const kpiAmountCard = document.getElementById('kpiAmountCard');
    const kpiPackagesCard = document.getElementById('kpiPackagesCard');

    // Search & Add elements
    const productSearchInput = document.getElementById('productSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const itemUnitSelect = document.getElementById('itemUnitSelect');
    const detectedUnitBadge = document.getElementById('detectedUnitBadge');
    let currentCandidateProduct = null;
    let currentSearchResults = [];
    let activeDropdownIndex = -1;
    const itemQtyInput = document.getElementById('itemQtyInput');
    const qtyMinusBtn = document.getElementById('qtyMinusBtn');
    const qtyPlusBtn = document.getElementById('qtyPlusBtn');
    const itemPackagesInput = document.getElementById('itemPackagesInput');
    const itemPriceInput = document.getElementById('itemPriceInput');
    const selectedPriceTierBadge = document.getElementById('selectedPriceTierBadge');
    const itemNotesInput = document.getElementById('itemNotesInput');
    const addItemBtn = document.getElementById('addItemBtn');

    // Table elements
    const footTotalQty = document.getElementById('footTotalQty');
    const footTotalPackages = document.getElementById('footTotalPackages');
    const footTotalAmount = document.getElementById('footTotalAmount');

    // Badges & Counters
    const topItemsCountBadge = document.getElementById('topItemsCountBadge');
    const topQtyCountBadge = document.getElementById('topQtyCountBadge');
    const tableCountBadge = document.getElementById('tableCountBadge');
    const cardItemsCount = document.getElementById('cardItemsCount');
    const cardTotalQty = document.getElementById('cardTotalQty');
    const cardTotalPackages = document.getElementById('cardTotalPackages');
    const cardTotalAmount = document.getElementById('cardTotalAmount');

    // Action buttons
    const saveToArchiveBtn = document.getElementById('saveToArchiveBtn');
    const saveBtnText = document.getElementById('saveBtnText');
    const clearListBtn = document.getElementById('clearListBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');

    // Load persisted state
    loadSavedState();
    applyColumnVisibility();

    // Toggle Column Visibility Listeners
    [toggleShowQtyCheckbox, toggleShowPriceCheckbox, toggleShowPackagesCheckbox].forEach(chk => {
        chk.addEventListener('change', function () {
            applyColumnVisibility();
            renderTable();
            saveState();
        });
    });

    function applyColumnVisibility() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        // Table headers and footer cells
        document.querySelectorAll('.col-qty').forEach(el => el.style.display = showQty ? '' : 'none');
        document.querySelectorAll('.col-packages').forEach(el => el.style.display = showPackages ? '' : 'none');
        document.querySelectorAll('.col-unit-price').forEach(el => el.style.display = showPrice ? '' : 'none');
        document.querySelectorAll('.col-line-total').forEach(el => el.style.display = showLineTotal ? '' : 'none');

        // Quick Entry Wrappers
        if (quickQtyWrapper) quickQtyWrapper.style.display = showQty ? '' : 'none';
        if (quickPackagesWrapper) quickPackagesWrapper.style.display = showPackages ? '' : 'none';
        if (quickPriceWrapper) quickPriceWrapper.style.display = showPrice ? '' : 'none';

        // Table Footer visibility: hide whole footer if neither Qty, Packages nor LineTotal is active
        const tableFooter = document.getElementById('tableFooter');
        if (tableFooter) {
            tableFooter.style.display = (showQty || showPackages || showLineTotal) ? '' : 'none';
        }

        // KPI Cards
        if (kpiQtyCard) kpiQtyCard.style.display = showQty ? '' : 'none';
        if (kpiPackagesCard) kpiPackagesCard.style.display = showPackages ? '' : 'none';
        if (kpiAmountCard) kpiAmountCard.style.display = showLineTotal ? '' : 'none';

        // Top Header Badges
        if (topQtyCountBadge) topQtyCountBadge.style.display = showQty ? '' : 'none';
    }

    let currentSearchSeq = 0;

    function cleanSearchTerm(term) {
        if (!term) return '';
        let str = term.toString().trim();
        const arabicIndic = ['ظ ', 'ظ،', 'ظ¢', 'ظ£', 'ظ¤', 'ظ¥', 'ظ¦', 'ظ§', 'ظ¨', 'ظ©', 'غ°', 'غ±', 'غ²', 'غ³', 'غ´', 'غµ', 'غ¶', 'غ·', 'غ¸', 'غ¹'];
        const standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < arabicIndic.length; i++) {
            str = str.replaceAll(arabicIndic[i], standardDigits[i]);
        }
        str = str.replace(/^\][A-Za-z0-9]{2}/, '');
        str = str.replace(/[\r\n\t\x00-\x1F\x7F-\x9F]/g, '');
        return str.trim();
    }

    // Fast-scan toggle text change
    fastScanModeCheckbox.addEventListener('change', function () {
        fastScanText.textContent = this.checked ? 'ط§ظ„ظ…ط³ط­ ط§ظ„ظ…ط¨ط§ط´ط± ط§ظ„ط³ط±ظٹط¹ (ظ…ظپط¹ظ„)' : 'ط§ظ„ظ…ط³ط­ ط§ظ„ظ…ط¨ط§ط´ط± ط§ظ„ط³ط±ظٹط¹ (ظ…ط¹ط·ظ„)';
        localStorage.setItem('packing_list_fast_scan', this.checked ? '1' : '0');
    });

    // Load saved fast-scan preference
    const savedFastScan = localStorage.getItem('packing_list_fast_scan');
    if (savedFastScan !== null) {
        fastScanModeCheckbox.checked = savedFastScan === '1';
        fastScanText.textContent = fastScanModeCheckbox.checked ? 'ط§ظ„ظ…ط³ط­ ط§ظ„ظ…ط¨ط§ط´ط± ط§ظ„ط³ط±ظٹط¹ (ظ…ظپط¹ظ„)' : 'ط§ظ„ظ…ط³ط­ ط§ظ„ظ…ط¨ط§ط´ط± ط§ظ„ط³ط±ظٹط¹ (ظ…ط¹ط·ظ„)';
    }

    // Global Barcode Scanner Listener & Keyboard Shortcuts
    let globalBarcodeBuffer = '';
    let globalBarcodeLastTime = 0;

    window.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            productSearchInput.focus();
            productSearchInput.select();
            return;
        }

        const target = e.target;
        const isOtherInput = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') && target !== productSearchInput;

        if (isOtherInput) {
            return;
        }

        const now = Date.now();
        const interval = now - globalBarcodeLastTime;
        globalBarcodeLastTime = now;

        if (e.key === 'Enter') {
            if (globalBarcodeBuffer.length >= 3 && interval < 70) {
                e.preventDefault();
                const scanned = cleanSearchTerm(globalBarcodeBuffer);
                globalBarcodeBuffer = '';
                productSearchInput.value = '';
                handleBarcodeOrInputCommit(scanned);
                return;
            }
            globalBarcodeBuffer = '';
        } else if (e.key.length === 1) {
            if (interval > 140) {
                globalBarcodeBuffer = '';
            }
            globalBarcodeBuffer += e.key;
        }
    });

    // Search Input Typing Listener
    productSearchInput.addEventListener('input', function () {
        const raw = this.value;
        const val = cleanSearchTerm(raw);
        clearSearchBtn.classList.toggle('hidden', raw.trim().length === 0);

        clearTimeout(searchDebounceTimer);
        currentSearchSeq++;

        if (val.length === 0) {
            hideDropdown();
            currentSelectedProduct = null;
            currentCandidateProduct = null;
            currentSearchResults = [];
            activeDropdownIndex = -1;
            quickStockBadge.classList.add('hidden');
            if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
            setUnit('ظ‚ط·ط¹ط©', false);
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            fetchProducts(val);
        }, 120);
    });

    // Search Input Keydown Listener (Barcode gun Enter / Tab & Keyboard navigation)
    productSearchInput.addEventListener('keydown', async function (e) {
        if (e.key === 'ArrowDown') {
            if (currentSearchResults.length > 0) {
                e.preventDefault();
                activeDropdownIndex = (activeDropdownIndex + 1) % currentSearchResults.length;
                updateDropdownHighlight();
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            if (currentSearchResults.length > 0) {
                e.preventDefault();
                activeDropdownIndex = (activeDropdownIndex - 1 + currentSearchResults.length) % currentSearchResults.length;
                updateDropdownHighlight();
            }
            return;
        }

        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            const term = cleanSearchTerm(this.value);
            if (!term) return;

            clearTimeout(searchDebounceTimer);
            currentSearchSeq++;
            hideDropdown();

            await handleBarcodeOrInputCommit(term);
        } else if (e.key === 'Escape') {
            clearTimeout(searchDebounceTimer);
            currentSearchSeq++;
            hideDropdown();
        }
    });

    clearSearchBtn.addEventListener('click', function () {
        clearTimeout(searchDebounceTimer);
        currentSearchSeq++;
        productSearchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        quickStockBadge.classList.add('hidden');
        if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
        setUnit('ظ‚ط·ط¹ط©', false);
        hideDropdown();
        currentSelectedProduct = null;
        currentCandidateProduct = null;
        currentSearchResults = [];
        activeDropdownIndex = -1;
        productSearchInput.focus();
    });

    // Steppers
    qtyMinusBtn.addEventListener('click', function () {
        let q = parseFloat(itemQtyInput.value) || 1;
        if (q > 1) {
            itemQtyInput.value = Math.max(1, q - 1);
        }
    });

    qtyPlusBtn.addEventListener('click', function () {
        let q = parseFloat(itemQtyInput.value) || 1;
        itemQtyInput.value = q + 1;
    });

    // Add Item Click
    addItemBtn.addEventListener('click', function () {
        if (currentSelectedProduct) {
            addProductDirectlyToTable(currentSelectedProduct);
        } else if (currentCandidateProduct) {
            addProductDirectlyToTable(currentCandidateProduct);
        } else {
            addItemFromForm();
        }
    });

    // Enter in inputs adds item
    [itemQtyInput, itemPackagesInput, itemPriceInput, itemNotesInput].forEach(el => {
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addItemFromForm();
            }
        });
    });

    // Change default price mode
    defaultPriceModeSelect.addEventListener('change', function () {
        const mode = this.value;
        if (packingItems.length > 0) {
            if (confirm('ظ‡ظ„ طھط±ط؛ط¨ ظپظٹ طھط­ط¯ظٹط« ط£ط³ط¹ط§ط± ظƒط§ظپط© ط§ظ„ط£طµظ†ط§ظپ ط§ظ„ظ…ظˆط¬ظˆط¯ط© ط¨ط§ظ„ظ‚ط§ط¦ظ…ط© ظ„طھطھظˆط§ظپظ‚ ظ…ط¹ ط§ظ„ظ†ظˆط¹ ط§ظ„ظ…ط®طھط§ط± (' + getPriceModeLabel(mode) + ')طں')) {
                packingItems.forEach(item => {
                    item.price_tier = mode;
                    item.price = resolveItemPrice(item, mode);
                    item.total = item.qty * item.price;
                });
                renderTable();
                saveState();
            }
        }
        if (currentSelectedProduct) {
            itemPriceInput.value = resolveItemPrice(currentSelectedProduct, mode);
        }
    });

    // Clear List Button
    clearListBtn.addEventListener('click', function () {
        if (packingItems.length === 0 && !customerNameInput.value.trim() && !docNotesInput.value.trim()) return;
        if (confirm('ظ‡ظ„ ط£ظ†طھ ظ…طھط£ظƒط¯ ظ…ظ† طھظپط±ظٹط؛ ظƒط§ظپط© ط£طµظ†ط§ظپ ظˆط¨ظٹط§ظ†ط§طھ ظ‚ط§ط¦ظ…ط© ط§ظ„طھط¹ط¨ط¦ط©طں')) {
            packingItems = [];
            currentListId = null;
            customerNameInput.value = '';
            docNotesInput.value = '';
            if (saveBtnText) saveBtnText.textContent = 'ط­ظپط¸ ظپظٹ ط§ظ„ط³ط¬ظ„';
            localStorage.removeItem(STORAGE_KEY);
            pendingDraft = null;
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            renderTable();
            productSearchInput.focus();
            showToast('طھظ… طھظپط±ظٹط؛ ط§ظ„ظ‚ط§ط¦ظ…ط© ط¨ط§ظ„ظƒط§ظ…ظ„.', 'info');
        }
    });

    // Unified Robust Form Action Submitter (Exports & Prints)
    function submitAction(url, target = '_self', extraParams = {}) {
        if (packingItems.length === 0) {
            alert('ظٹط±ط¬ظ‰ ط¥ط¶ط§ظپط© ط£طµظ†ط§ظپ ط¥ظ„ظ‰ ظ‚ط§ط¦ظ…ط© ط§ظ„طھط¹ط¨ط¦ط© ط£ظˆظ„ط§ظ‹ ظ‚ط¨ظ„ ط§ظ„ظ…طھط§ط¨ط¹ط©.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = target;
        form.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const data = {
            _token: csrfToken || '',
            doc_number: docNumberInput.value.trim(),
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value.trim(),
            warehouse_name: warehouseSelect.value,
            price_type: defaultPriceModeSelect.value,
            notes: docNotesInput.value.trim(),
            show_quantity: toggleShowQtyCheckbox.checked ? '1' : '0',
            show_price: toggleShowPriceCheckbox.checked ? '1' : '0',
            show_packages: toggleShowPackagesCheckbox.checked ? '1' : '0',
            items: JSON.stringify(packingItems),
            ...extraParams
        };

        for (const [key, val] of Object.entries(data)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 2500);
    }

    // Export Excel via Dropdown
    exportExcelBtn.addEventListener('click', function () {
        submitAction('/packing-list/export-excel', '_self');
    });

    // Export CSV via Dropdown
    exportCsvBtn.addEventListener('click', function () {
        submitAction('/packing-list/export-csv', '_self');
    });

    // Print Formats (3 sizes)
    document.querySelectorAll('[data-print-format]').forEach(btn => {
        btn.addEventListener('click', function () {
            const format = this.dataset.printFormat;
            submitAction('/packing-list/print', '_blank', { format: format });
        });
    });

    // Auto-save on metadata change
    [docNumberInput, docDateInput, customerNameInput, warehouseSelect, docNotesInput].forEach(el => {
        el.addEventListener('change', saveState);
        el.addEventListener('input', saveState);
    });

    // Functions

    function setUnit(unitName, showBadge = true) {
        if (!unitName) unitName = 'ظ‚ط·ط¹ط©';
        unitName = unitName.trim();

        let found = false;
        for (let i = 0; i < itemUnitSelect.options.length; i++) {
            if (itemUnitSelect.options[i].value.trim().toLowerCase() === unitName.toLowerCase()) {
                itemUnitSelect.selectedIndex = i;
                found = true;
                break;
            }
        }

        if (!found) {
            const opt = document.createElement('option');
            opt.value = unitName;
            opt.textContent = unitName;
            opt.selected = true;
            itemUnitSelect.appendChild(opt);
            itemUnitSelect.value = unitName;
        }

        if (detectedUnitBadge) {
            if (showBadge && unitName) {
                detectedUnitBadge.textContent = 'ظˆط­ط¯ط© ط§ظ„طµظ†ظپ: ' + unitName;
                detectedUnitBadge.classList.remove('hidden');
            } else {
                detectedUnitBadge.classList.add('hidden');
            }
        }
    }

    async function fetchProducts(q) {
        const seq = ++currentSearchSeq;
        const term = cleanSearchTerm(q);
        if (!term) {
            hideDropdown();
            return;
        }

        try {
            const resp = await fetch('/packing-list/search-products?q=' + encodeURIComponent(term));
            const data = await resp.json();

            // Ignore obsolete responses or if input was cleared!
            if (seq !== currentSearchSeq || !productSearchInput.value.trim()) {
                hideDropdown();
                return;
            }

            if (data.success && data.products.length > 0) {
                currentSearchResults = data.products;
                activeDropdownIndex = 0;
                renderDropdown(data.products);

                // Auto-detect and show unit of measurement for closest matching product as user types
                const qNorm = term.toLowerCase();
                const matched = data.products.find(p => 
                    (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === qNorm) ||
                    (p.sku && cleanSearchTerm(p.sku).toLowerCase() === qNorm) ||
                    p.name.trim().toLowerCase() === qNorm
                ) || data.products.find(p => 
                    p.name.trim().toLowerCase().startsWith(qNorm)
                ) || data.products[0];

                if (matched) {
                    currentCandidateProduct = matched;
                    setUnit(matched.unit, true);

                    quickStockBadge.textContent = 'ط§ظ„ظ…طھط§ط­: ' + matched.total_stock + ' ' + (matched.unit || 'ظ‚ط·ط¹ط©');
                    quickStockBadge.classList.remove('hidden');

                    const mode = defaultPriceModeSelect.value;
                    const price = resolveItemPrice(matched, mode);
                    itemPriceInput.value = price.toFixed(2);
                    selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';
                }
            } else {
                currentSearchResults = [];
                currentCandidateProduct = null;
                activeDropdownIndex = -1;
                renderDropdown([]);
                if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
                quickStockBadge.classList.add('hidden');
            }
        } catch (e) {
            console.error('Failed to search products:', e);
        }
    }

    async function handleBarcodeOrInputCommit(rawTerm) {
        const term = cleanSearchTerm(rawTerm);
        if (!term) return;

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
        currentSearchSeq++;
        hideDropdown();

        const isFastScan = fastScanModeCheckbox.checked;
        const termLower = term.toLowerCase();

        // 1. If actively selected via Arrow keys
        let matched = null;
        if (activeDropdownIndex >= 0 && currentSearchResults[activeDropdownIndex]) {
            matched = currentSearchResults[activeDropdownIndex];
        }

        // 2. Exact match in cached current results
        if (!matched && currentSearchResults.length > 0) {
            matched = currentSearchResults.find(p => 
                (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === termLower) ||
                (p.sku && cleanSearchTerm(p.sku).toLowerCase() === termLower)
            );
        }

        // 3. Direct server lookup
        if (!matched) {
            try {
                const resp = await fetch('/packing-list/search-products?q=' + encodeURIComponent(term));
                const data = await resp.json();
                if (data.success && data.products.length > 0) {
                    matched = data.products.find(p => 
                        (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === termLower) ||
                        (p.sku && cleanSearchTerm(p.sku).toLowerCase() === termLower)
                    ) || data.products[0];
                }
            } catch (err) {
                console.error('Scan lookup error:', err);
            }
        }

        if (matched) {
            if (isFastScan) {
                addProductDirectlyToTable(matched);
            } else {
                selectProduct(matched);
                hideDropdown();
                if (toggleShowQtyCheckbox.checked) {
                    itemQtyInput.focus();
                    itemQtyInput.select();
                } else {
                    addItemBtn.focus();
                }
            }
        } else {
            if (isFastScan) {
                showToast(`ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ طµظ†ظپ ط¨ط§ظ„ط¨ط§ط±ظƒظˆط¯: "${term}"`, 'error');
                productSearchInput.select();
            } else {
                addItemFromForm();
            }
        }
    }

    function addProductDirectlyToTable(p, quantityToAdd = 1) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
        currentSearchSeq++;
        hideDropdown();

        let qty = 1;
        if (toggleShowQtyCheckbox.checked) {
            qty = Math.max(0.01, parseFloat(itemQtyInput.value) || quantityToAdd);
        }
        let packages = 0;
        if (toggleShowPackagesCheckbox.checked) {
            packages = parseFloat(itemPackagesInput.value) || 0;
            if (packages === 0 && p.package_factor && p.package_factor > 1) {
                packages = 1;
            }
        }

        const mode = defaultPriceModeSelect.value;
        const price = resolveItemPrice(p, mode);
        const unit = p.unit || itemUnitSelect.value || 'ظ‚ط·ط¹ط©';
        const notes = itemNotesInput.value.trim();

        const pBarcode = cleanSearchTerm(p.barcode);
        const pSku = cleanSearchTerm(p.sku);

        let existingIndex = packingItems.findIndex(i => 
            (p.id && i.id === p.id) || 
            (pBarcode && cleanSearchTerm(i.barcode) === pBarcode) ||
            (pSku && cleanSearchTerm(i.sku) === pSku)
        );

        if (existingIndex >= 0) {
            packingItems[existingIndex].qty += qty;
            packingItems[existingIndex].packages_count += packages;
            if (price > 0 && packingItems[existingIndex].price === 0) {
                packingItems[existingIndex].price = price;
            }
            packingItems[existingIndex].total = packingItems[existingIndex].qty * packingItems[existingIndex].price;
            if (notes) {
                packingItems[existingIndex].notes = packingItems[existingIndex].notes ? (packingItems[existingIndex].notes + ' / ' + notes) : notes;
            }

            renderTable();
            saveState();
            highlightTableRow(existingIndex);

            if (toggleShowQtyCheckbox.checked) {
                showToast(`âœ“ طھظ… ط²ظٹط§ط¯ط© ظƒظ…ظٹط© (${p.name}) ط¥ظ„ظ‰ ${packingItems[existingIndex].qty}`, 'success');
            } else {
                showToast(`ط§ظ„طµظ†ظپ (${p.name}) ظ…ظˆط¬ظˆط¯ ط¨ط§ظ„ظپط¹ظ„ ظپظٹ ط§ظ„ظ‚ط§ط¦ظ…ط© ط¨ط§ظ„ط³ط·ط± #${existingIndex + 1}`, 'info');
            }
        } else {
            const wP = parseFloat(p.wholesale_price || 0) || price;
            const rP = parseFloat(p.retail_price || 0) || price;
            const dP = parseFloat(p.distributor_price || 0) || wP;
            const cP = parseFloat(p.cost_price || 0) || price;
            const newItem = {
                id: p.id,
                sku: p.sku || '',
                barcode: p.barcode || '',
                name: p.name,
                category_name: p.category_name || 'ط¹ط§ظ…',
                unit: unit,
                qty: qty,
                packages_count: packages,
                price: price,
                price_tier: defaultPriceModeSelect.value,
                wholesale_price: wP,
                retail_price: rP,
                distributor_price: dP,
                cost_price: cP,
                total: qty * price,
                notes: notes,
            };
            packingItems.push(newItem);

            renderTable();
            saveState();

            const newIndex = packingItems.length - 1;
            highlightTableRow(newIndex);
            showToast(`âœ“ طھظ… ط¥ط¯ط±ط§ط¬ ط§ظ„طµظ†ظپ ط¨ظ†ط¬ط§ط­: ${p.name}`, 'success');
        }

        // Clean inputs & refocus search box
        productSearchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        quickStockBadge.classList.add('hidden');
        if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
        setUnit('ظ‚ط·ط¹ط©', false);
        itemQtyInput.value = '1';
        itemPackagesInput.value = '0';
        itemNotesInput.value = '';
        currentSelectedProduct = null;
        currentCandidateProduct = null;
        currentSearchResults = [];
        activeDropdownIndex = -1;

        productSearchInput.focus();
    }

    function highlightTableRow(index) {
        setTimeout(() => {
            const row = packingListBody.querySelector(`tr[data-row-index="${index}"]`);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                row.classList.add('pos-row-highlight');
                setTimeout(() => {
                    row.classList.remove('pos-row-highlight');
                }, 1400);
            }
        }, 50);
    }

    function updateDropdownHighlight() {
        const items = searchResultsDropdown.querySelectorAll('.purchases-suggestion-item');
        items.forEach((el, idx) => {
            if (idx === activeDropdownIndex) {
                el.classList.add('bg-[var(--bg-hover)]', 'border-r-4', 'border-r-[var(--primary)]');
                const prod = currentSearchResults[idx];
                if (prod) {
                    currentCandidateProduct = prod;
                    setUnit(prod.unit, true);
                    quickStockBadge.textContent = 'ط§ظ„ظ…طھط§ط­: ' + prod.total_stock + ' ' + (prod.unit || 'ظ‚ط·ط¹ط©');
                    quickStockBadge.classList.remove('hidden');
                    const mode = defaultPriceModeSelect.value;
                    const price = resolveItemPrice(prod, mode);
                    itemPriceInput.value = price.toFixed(2);
                    selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';
                }
            } else {
                el.classList.remove('bg-[var(--bg-hover)]', 'border-r-4', 'border-r-[var(--primary)]');
            }
        });
    }

    function renderDropdown(products) {
        if (!searchResultsDropdown) return;
        if (products.length === 0) {
            searchResultsDropdown.innerHTML = '<div class="p-3 text-center text-xs text-[var(--text-muted)]">ظ„ط§ طھظˆط¬ط¯ ظ†طھط§ط¦ط¬ ظ…ط·ط§ط¨ظ‚ط©</div>';
            searchResultsDropdown.classList.remove('hidden');
            searchResultsDropdown.style.display = 'block';
            return;
        }

        let html = '';
        products.forEach((p, idx) => {
            const mode = defaultPriceModeSelect.value;
            const price = resolveItemPrice(p, mode);
            const isFirst = idx === 0 ? 'bg-[var(--bg-hover)] border-r-4 border-r-[var(--primary)]' : '';
            html += `
                <div class="purchases-suggestion-item p-2.5 hover:bg-[var(--bg-hover)] cursor-pointer border-b border-[var(--border-light)] last:border-b-0 flex items-center justify-between ${isFirst}" data-index="${idx}">
                    <div>
                        <div class="font-bold text-xs text-[var(--text-main)]">${escapeHtml(p.name)}</div>
                        <div class="text-[11px] text-[var(--text-muted)] flex items-center gap-2 mt-0.5 font-numeric">
                            <span class="font-mono text-[var(--primary)] font-semibold">${p.sku || '-'}</span>
                            <span>آ·</span>
                            <span>ط¨ط§ط±ظƒظˆط¯: <span class="font-mono">${p.barcode || '-'}</span></span>
                            <span>آ·</span>
                            <span>${p.category_name || 'ط¹ط§ظ…'}</span>
                            <span>آ·</span>
                            <span class="text-emerald-700 font-bold bg-emerald-50 px-1 rounded">ط§ظ„ظˆط­ط¯ط©: ${escapeHtml(p.unit || 'ظ‚ط·ط¹ط©')}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric">
                        <div class="font-bold text-xs text-[var(--primary)]">${price > 0 ? price.toFixed(2) + ' ط¬.ظ…' : 'ط¨ط¯ظˆظ† ط³ط¹ط±'}</div>
                        <div class="text-[10px] text-[var(--text-muted)]">ط§ظ„ظ…طھط§ط­: ${p.total_stock} ${p.unit}</div>
                    </div>
                </div>
            `;
        });

        searchResultsDropdown.innerHTML = html;
        searchResultsDropdown.classList.remove('hidden');
        searchResultsDropdown.style.display = 'block';

        searchResultsDropdown.querySelectorAll('.purchases-suggestion-item').forEach((el) => {
            const idx = parseInt(el.dataset.index);
            el.addEventListener('mouseenter', function () {
                activeDropdownIndex = idx;
                updateDropdownHighlight();
            });

            el.addEventListener('click', function () {
                const prod = products[idx];
                selectProduct(prod);
                hideDropdown();
                itemQtyInput.focus();
                itemQtyInput.select();
            });
        });
    }

    function hideDropdown() {
        if (!searchResultsDropdown) return;
        if (searchResultsDropdown.style.display === 'none' && searchResultsDropdown.classList.contains('hidden')) {
            return;
        }
        searchResultsDropdown.classList.add('hidden');
        searchResultsDropdown.style.display = 'none';
        searchResultsDropdown.innerHTML = '';
        currentSearchResults = [];
        activeDropdownIndex = -1;
    }

    function selectProduct(p) {
        currentSelectedProduct = p;
        currentCandidateProduct = p;
        productSearchInput.value = p.name;
        clearSearchBtn.classList.remove('hidden');

        quickStockBadge.textContent = 'ط§ظ„ظ…طھط§ط­: ' + p.total_stock + ' ' + (p.unit || 'ظ‚ط·ط¹ط©');
        quickStockBadge.classList.remove('hidden');

        setUnit(p.unit, true);

        const mode = defaultPriceModeSelect.value;
        const price = resolveItemPrice(p, mode);
        itemPriceInput.value = price.toFixed(2);
        selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';

        if (p.package_factor && p.package_factor > 1) {
            itemPackagesInput.value = '1';
        }
    }

    function resolveItemPrice(itemOrProd, mode) {
        if (mode === 'none' || mode === 'zero') return 0;
        const curPrice = parseFloat(itemOrProd.price || 0);
        const w = parseFloat(itemOrProd.wholesale_price || 0);
        const r = parseFloat(itemOrProd.retail_price || 0);
        const d = parseFloat(itemOrProd.distributor_price || 0);
        const c = parseFloat(itemOrProd.cost_price || 0);

        const fallbackWholesale = w > 0 ? w : (curPrice > 0 ? curPrice : (r > 0 ? r : 0));
        const fallbackRetail = r > 0 ? r : (curPrice > 0 ? curPrice : fallbackWholesale);
        const fallbackDistributor = d > 0 ? d : fallbackWholesale;
        const fallbackCost = c > 0 ? c : fallbackWholesale;

        if (mode === 'retail') return fallbackRetail;
        if (mode === 'wholesale') return fallbackWholesale;
        if (mode === 'distributor') return fallbackDistributor;
        if (mode === 'cost') return fallbackCost;
        return fallbackWholesale;
    }

    function getPriceModeLabel(mode) {
        switch (mode) {
            case 'wholesale': return 'ط¬ظ…ظ„ط©';
            case 'retail': return 'ظ‚ط·ط§ط¹ظٹ';
            case 'distributor': return 'ظ…ظˆط²ط¹';
            case 'cost': return 'طھظƒظ„ظپط©';
            case 'none': return 'ط¨ط¯ظˆظ† ط³ط¹ط±';
            default: return 'ط³ط¹ط± ظ…ط®طµطµ';
        }
    }

    function addItemFromForm() {
        let name = productSearchInput.value.trim();
        if (!name) {
            productSearchInput.focus();
            return;
        }

        const activeProduct = currentSelectedProduct || (currentCandidateProduct && (currentCandidateProduct.name.trim().toLowerCase() === name.toLowerCase() || !name) ? currentCandidateProduct : null);

        let qty = parseFloat(itemQtyInput.value) || 1;
        let packages = parseFloat(itemPackagesInput.value) || 0;
        let price = parseFloat(itemPriceInput.value) || 0;
        let unit = itemUnitSelect.value;
        let notes = itemNotesInput.value.trim();

        let existingIndex = -1;
        if (activeProduct) {
            existingIndex = packingItems.findIndex(i => i.id === activeProduct.id);
        } else {
            existingIndex = packingItems.findIndex(i => i.name.toLowerCase() === name.toLowerCase());
        }

        if (existingIndex >= 0) {
            packingItems[existingIndex].qty += qty;
            packingItems[existingIndex].packages_count += packages;
            if (price > 0) packingItems[existingIndex].price = price;
            packingItems[existingIndex].total = packingItems[existingIndex].qty * packingItems[existingIndex].price;
            if (notes) {
                packingItems[existingIndex].notes = packingItems[existingIndex].notes ? (packingItems[existingIndex].notes + ' / ' + notes) : notes;
            }
        } else {
            const wP = activeProduct ? (parseFloat(activeProduct.wholesale_price || 0) || price) : price;
            const rP = activeProduct ? (parseFloat(activeProduct.retail_price || 0) || price) : price;
            const dP = activeProduct ? (parseFloat(activeProduct.distributor_price || 0) || wP) : price;
            const cP = activeProduct ? (parseFloat(activeProduct.cost_price || 0) || price) : price;
            const newItem = {
                id: activeProduct ? activeProduct.id : null,
                sku: activeProduct ? activeProduct.sku : '',
                barcode: activeProduct ? activeProduct.barcode : '',
                name: activeProduct ? activeProduct.name : name,
                category_name: activeProduct ? activeProduct.category_name : 'ط¹ط§ظ…',
                unit: unit,
                qty: qty,
                packages_count: packages,
                price: price,
                price_tier: defaultPriceModeSelect.value,
                wholesale_price: wP,
                retail_price: rP,
                distributor_price: dP,
                cost_price: cP,
                total: qty * price,
                notes: notes,
            };
            packingItems.push(newItem);
        }

        productSearchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        quickStockBadge.classList.add('hidden');
        if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
        setUnit('ظ‚ط·ط¹ط©', false);
        itemQtyInput.value = '1';
        itemPackagesInput.value = '0';
        itemNotesInput.value = '';
        currentSelectedProduct = null;
        currentCandidateProduct = null;
        currentSearchResults = [];
        activeDropdownIndex = -1;
        hideDropdown();

        renderTable();
        saveState();

        productSearchInput.focus();
    }

    function renderTable() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        if (packingItems.length === 0) {
            packingListBody.innerHTML = `
                <tr id="emptyRowPlaceholder">
                    <td colspan="11" class="py-10 text-center text-[var(--text-muted)]">
                        <div class="flex flex-col items-center justify-center gap-1.5">
                            <x-icon name="clipboard" size="32" class="text-gray-300 dark:text-gray-600" />
                            <span class="text-sm font-semibold">ظ‚ط§ط¦ظ…ط© ط§ظ„طھط¹ط¨ط¦ط© ظپط§ط±ط؛ط© ط­ط§ظ„ظٹط§ظ‹</span>
                            <span class="text-xs text-[var(--text-muted)]">ط§ط³طھط®ط¯ظ… ط´ط±ظٹط· ط§ظ„ط¨ط­ط« ط¨ط§ظ„ط£ط¹ظ„ظ‰ ط£ظˆ ظ…ط³ط¯ط³ ط§ظ„ط¨ط§ط±ظƒظˆط¯ ظ„ط¥ط¯ط±ط§ط¬ ط§ظ„ط£طµظ†ط§ظپ</span>
                        </div>
                    </td>
                </tr>
            `;
            updateTotals();
            return;
        }

        let html = '';
        packingItems.forEach((item, idx) => {
            const lineTotal = item.qty * item.price;
            const effectiveTier = item.price_tier || defaultPriceModeSelect.value || 'wholesale';
            html += `
                <tr class="hover:bg-[var(--bg-hover)] transition-colors" data-row-index="${idx}">
                    <td class="text-center text-[var(--text-muted)] font-numeric font-semibold">${idx + 1}</td>
                    <td class="text-center font-mono font-semibold text-[var(--primary)] text-xs">${escapeHtml(item.sku || 'â€”')}</td>
                    <td class="text-center font-mono text-[var(--text-muted)] text-xs">${escapeHtml(item.barcode || 'â€”')}</td>
                    <td class="py-2">
                        <div class="font-bold text-sm text-[var(--text-main)] leading-snug">${escapeHtml(item.name)}</div>
                        <div class="text-[11px] text-[var(--text-muted)] font-semibold mt-0.5">${escapeHtml(item.category_name || 'ط¹ط§ظ…')}</div>
                    </td>
                    <td class="text-center">
                        <span class="erp-badge erp-badge-neutral text-xs font-semibold">
                            ${escapeHtml(item.unit || 'ظ‚ط·ط¹ط©')}
                        </span>
                    </td>
                    <td class="text-center col-qty" style="${showQty ? '' : 'display: none;'}">
                        <div class="pos-qty-stepper mx-auto" style="width: 105px; height: 32px;">
                            <button type="button" class="pos-qty-btn table-qty-minus text-sm font-bold" data-index="${idx}" tabindex="-1">âˆ’</button>
                            <input type="number" step="any" min="0.01" value="${item.qty}" class="pos-qty-input font-numeric font-bold text-xs table-qty-input" data-index="${idx}">
                            <button type="button" class="pos-qty-btn table-qty-plus text-sm font-bold" data-index="${idx}" tabindex="-1">+</button>
                        </div>
                    </td>
                    <td class="text-center col-packages" style="${showPackages ? '' : 'display: none;'}">
                        <input type="number" min="0" step="1" value="${item.packages_count}" class="erp-input font-numeric font-semibold text-center text-xs table-packages-input" style="width: 65px; height: 32px; margin: 0 auto;" data-index="${idx}">
                    </td>
                    <td class="text-center col-unit-price" style="${showPrice ? '' : 'display: none;'}">
                        <div style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 6px; background-color: var(--bg-surface); overflow: hidden; height: 32px; width: 165px; margin: 0 auto; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                            <input 
                                type="number" 
                                min="0" 
                                step="0.01" 
                                value="${item.price}" 
                                class="table-price-input font-numeric" 
                                style="width: 88px; height: 100%; border: none; background: transparent; text-align: center; font-size: 13px; font-weight: 700; color: var(--primary); outline: none; padding: 0 4px;" 
                                data-index="${idx}"
                                title="ط³ط¹ط± ط§ظ„ظˆط­ط¯ط©"
                            >
                            <span style="width: 1px; height: 18px; background-color: var(--border-color); flex-shrink: 0;"></span>
                            <select 
                                class="table-price-tier-select" 
                                style="width: 76px; height: 100%; border: none; background-color: var(--bg-surface-alt); text-align: center; font-size: 11px; font-weight: 600; color: var(--text-main); outline: none; cursor: pointer; padding: 0 4px; -webkit-appearance: menulist; appearance: menulist;" 
                                data-index="${idx}"
                                title="ظ†ظˆط¹ ط§ظ„ط³ط¹ط±"
                            >
                                <option value="wholesale" ${effectiveTier === 'wholesale' ? 'selected' : ''}>ط¬ظ…ظ„ط©</option>
                                <option value="distributor" ${effectiveTier === 'distributor' ? 'selected' : ''}>ظ…ظˆط²ط¹</option>
                                <option value="retail" ${effectiveTier === 'retail' ? 'selected' : ''}>ظ‚ط·ط§ط¹ظٹ</option>
                                <option value="cost" ${effectiveTier === 'cost' ? 'selected' : ''}>طھظƒظ„ظپط©</option>
                                <option value="custom" ${effectiveTier === 'custom' ? 'selected' : ''}>ظٹط¯ظˆظٹ</option>
                                <option value="zero" ${effectiveTier === 'zero' || item.price === 0 ? 'selected' : ''}>ظ…ط¬ط§ظ†ظٹ (0)</option>
                            </select>
                        </div>
                    </td>
                    <td class="text-center font-numeric font-extrabold text-emerald-600 dark:text-emerald-400 text-sm col-line-total" style="${showLineTotal ? '' : 'display: none;'}">
                        ${lineTotal > 0 ? lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="text-[10px] font-normal text-[var(--text-muted)]">ط¬.ظ…</span>' : 'â€”'}
                    </td>
                    <td>
                        <input type="text" value="${escapeHtml(item.notes || '')}" placeholder="ظ…ظ„ط§ط­ط¸ط©..." class="erp-input text-xs table-notes-input" style="height: 32px;" data-index="${idx}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-rose-500 hover:text-rose-700 remove-row-btn" data-index="${idx}" title="ط­ط°ظپ">
                            <x-icon name="trash-2" size="14" />
                        </button>
                    </td>
                </tr>
            `;
        });

        packingListBody.innerHTML = html;

        // Wire up table events
        packingListBody.querySelectorAll('.table-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const q = parseFloat(this.value) || 1;
                packingItems[idx].qty = q;
                packingItems[idx].total = q * packingItems[idx].price;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-qty-minus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                if (packingItems[idx].qty > 1) {
                    packingItems[idx].qty = Math.max(1, packingItems[idx].qty - 1);
                    packingItems[idx].total = packingItems[idx].qty * packingItems[idx].price;
                    renderTable();
                    saveState();
                }
            });
        });

        packingListBody.querySelectorAll('.table-qty-plus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].qty += 1;
                packingItems[idx].total = packingItems[idx].qty * packingItems[idx].price;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-packages-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].packages_count = parseFloat(this.value) || 0;
                saveState();
                updateTotals();
            });
        });

        packingListBody.querySelectorAll('.table-price-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const p = parseFloat(this.value) || 0;
                packingItems[idx].price = p;
                packingItems[idx].price_tier = 'custom';
                packingItems[idx].total = packingItems[idx].qty * p;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-price-tier-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const mode = this.value;
                const newPrice = resolveItemPrice(packingItems[idx], mode);

                packingItems[idx].price_tier = mode;
                packingItems[idx].price = newPrice;
                packingItems[idx].total = packingItems[idx].qty * newPrice;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-notes-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].notes = this.value.trim();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.remove-row-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                packingItems.splice(idx, 1);
                renderTable();
                saveState();
            });
        });

        updateTotals();
    }

    function updateTotals() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        let totQty = 0;
        let totPackages = 0;
        let totAmount = 0;

        packingItems.forEach(item => {
            if (showQty) totQty += item.qty;
            if (showPackages) totPackages += item.packages_count;
            if (showLineTotal) totAmount += (item.qty * item.price);
        });

        footTotalQty.textContent = totQty.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        footTotalPackages.textContent = totPackages > 0 ? totPackages.toLocaleString('en-US') : '0';
        footTotalAmount.textContent = showLineTotal ? (totAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ط¬.ظ…') : 'â€”';

        topItemsCountBadge.textContent = packingItems.length + ' ط£طµظ†ط§ظپ';
        topQtyCountBadge.textContent = totQty.toLocaleString('en-US') + ' ط¥ط¬ظ…ط§ظ„ظٹ ط§ظ„ظƒظ…ظٹط§طھ';
        tableCountBadge.textContent = packingItems.length + ' ط£طµظ†ط§ظپ';

        cardItemsCount.textContent = packingItems.length;
        cardTotalQty.textContent = totQty.toLocaleString('en-US');
        cardTotalPackages.textContent = totPackages > 0 ? totPackages.toLocaleString('en-US') : '0';
        cardTotalAmount.textContent = showLineTotal ? (totAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ط¬.ظ…') : 'â€”';
    }

    let saveStateDebounceTimer = null;
    function saveState() {
        clearTimeout(saveStateDebounceTimer);
        saveStateDebounceTimer = setTimeout(performSaveState, 200);
    }

    function performSaveState() {
        if (packingItems.length === 0 && !customerNameInput.value.trim() && !docNotesInput.value.trim()) {
            localStorage.removeItem(STORAGE_KEY);
            return;
        }
        const state = {
            doc_number: docNumberInput.value.trim(),
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value.trim(),
            warehouse_name: warehouseSelect.value,
            price_mode: defaultPriceModeSelect.value,
            notes: docNotesInput.value.trim(),
            show_quantity: toggleShowQtyCheckbox.checked,
            show_price: toggleShowPriceCheckbox.checked,
            show_packages: toggleShowPackagesCheckbox.checked,
            items: packingItems,
        };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // QuotaExceeded or storage disabled
        }
    }

    let pendingDraft = null;

    function loadSavedState() {
        if (serverLoadedList) {
            // Priority 1: Load directly from database record passed by controller
            if (serverLoadedList.doc_number) docNumberInput.value = serverLoadedList.doc_number;
            if (serverLoadedList.doc_date) docDateInput.value = serverLoadedList.doc_date;
            if (serverLoadedList.customer_name) customerNameInput.value = serverLoadedList.customer_name;
            if (serverLoadedList.warehouse_name) warehouseSelect.value = serverLoadedList.warehouse_name;
            if (serverLoadedList.price_type) defaultPriceModeSelect.value = serverLoadedList.price_type;
            if (serverLoadedList.notes) docNotesInput.value = serverLoadedList.notes;
            if (typeof serverLoadedList.show_quantity === 'boolean') toggleShowQtyCheckbox.checked = serverLoadedList.show_quantity;
            if (typeof serverLoadedList.show_price === 'boolean') toggleShowPriceCheckbox.checked = serverLoadedList.show_price;
            if (typeof serverLoadedList.show_packages === 'boolean') toggleShowPackagesCheckbox.checked = serverLoadedList.show_packages;

            if (Array.isArray(serverLoadedList.items) && serverLoadedList.items.length > 0) {
                packingItems = serverLoadedList.items;
            }
            renderTable();
            return;
        }

        // Priority 2: Fallback to localStorage draft - with user-controlled restore banner
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                renderTable();
                return;
            }
            const state = JSON.parse(raw);
            if (state && Array.isArray(state.items) && state.items.length > 0) {
                pendingDraft = state;
                const draftAlert = document.getElementById('unconfirmedDraftAlert');
                const draftDetails = document.getElementById('unconfirmedDraftDetails');
                if (draftAlert && draftDetails) {
                    draftDetails.textContent = `(${state.items.length} طµظ†ظپ${state.customer_name ? ' ظ„ظ„ط¹ظ…ظٹظ„: ' + state.customer_name : ''})`;
                    draftAlert.style.display = 'flex';
                }
            }
        } catch (e) {
            console.error('Failed to parse draft state:', e);
            localStorage.removeItem(STORAGE_KEY);
        }
        renderTable();
    }

    // Draft restore & discard buttons
    const restoreDraftBtn = document.getElementById('restoreDraftBtn');
    const discardDraftBtn = document.getElementById('discardDraftBtn');
    if (restoreDraftBtn) {
        restoreDraftBtn.addEventListener('click', function () {
            if (!pendingDraft) return;
            if (pendingDraft.doc_number) docNumberInput.value = pendingDraft.doc_number;
            if (pendingDraft.doc_date) docDateInput.value = pendingDraft.doc_date;
            if (pendingDraft.customer_name) customerNameInput.value = pendingDraft.customer_name;
            if (pendingDraft.warehouse_name) warehouseSelect.value = pendingDraft.warehouse_name;
            if (pendingDraft.price_mode) defaultPriceModeSelect.value = pendingDraft.price_mode;
            if (pendingDraft.notes) docNotesInput.value = pendingDraft.notes;
            if (typeof pendingDraft.show_quantity === 'boolean') toggleShowQtyCheckbox.checked = pendingDraft.show_quantity;
            if (typeof pendingDraft.show_price === 'boolean') toggleShowPriceCheckbox.checked = pendingDraft.show_price;
            if (typeof pendingDraft.show_packages === 'boolean') toggleShowPackagesCheckbox.checked = pendingDraft.show_packages;

            packingItems = pendingDraft.items || [];
            applyColumnVisibility();
            renderTable();
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            showToast('طھظ…طھ ط§ط³طھط¹ط§ط¯ط© ط§ظ„ظ…ط³ظˆط¯ط© ط¨ظ†ط¬ط§ط­.', 'success');
        });
    }

    if (discardDraftBtn) {
        discardDraftBtn.addEventListener('click', function () {
            localStorage.removeItem(STORAGE_KEY);
            pendingDraft = null;
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            showToast('طھظ… طھط¬ط§ظ‡ظ„ ط§ظ„ظ…ط³ظˆط¯ط© ظˆط¨ط¯ط، ظ‚ط§ط¦ظ…ط© ط¬ط¯ظٹط¯ط© ظپط§ط±ط؛ط©.', 'info');
        });
    }

    // Refresh Prices from System Database
    async function handleRefreshPrices() {
        if (packingItems.length === 0) {
            showToast('ظ„ط§ طھظˆط¬ط¯ ط£طµظ†ط§ظپ ظپظٹ ط§ظ„ظ‚ط§ط¦ظ…ط© ظ„طھط­ط¯ظٹط« ط£ط³ط¹ط§ط±ظ‡ط§.', 'warning');
            return;
        }

        const btn1 = document.getElementById('refreshPricesBtn');
        const btn2 = document.getElementById('refreshPricesTopBtn');
        const orig1 = btn1 ? btn1.innerHTML : '';
        const orig2 = btn2 ? btn2.innerHTML : '';

        [btn1, btn2].forEach(b => {
            if (b) {
                b.disabled = true;
                b.innerHTML = `
                    <span class="inline-block animate-spin mr-1">âŒ›</span>
                    <span>ط¬ط§ط±ظٹ طھط­ط¯ظٹط« ط§ظ„ط£ط³ط¹ط§ط±...</span>
                `;
            }
        });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/packing-list/refresh-prices', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ items: packingItems })
            });

            const data = await res.json();
            if (data.success && Array.isArray(data.products) && data.products.length > 0) {
                const refreshedMap = new Map();
                data.products.forEach(p => {
                    if (p.product_id) refreshedMap.set('id_' + p.product_id, p);
                    if (p.barcode) refreshedMap.set('bc_' + p.barcode, p);
                    if (p.sku) refreshedMap.set('sku_' + p.sku, p);
                });

                let updatedCount = 0;
                const currentMode = defaultPriceModeSelect.value;

                packingItems.forEach(item => {
                    const match = (item.id && refreshedMap.get('id_' + item.id))
                        || (item.barcode && refreshedMap.get('bc_' + item.barcode))
                        || (item.sku && refreshedMap.get('sku_' + item.sku));

                    if (match) {
                        item.id = match.product_id;
                        item.name = match.product_name || item.name;
                        item.unit = match.unit || item.unit;
                        item.category_name = match.category_name || item.category_name;
                        item.wholesale_price = match.wholesale_price;
                        item.retail_price = match.retail_price;
                        item.distributor_price = match.distributor_price;
                        item.cost_price = match.cost_price;

                        const tier = item.price_tier || currentMode;
                        item.price = resolveItemPrice(item, tier);
                        item.total = item.qty * item.price;
                        updatedCount++;
                    }
                });

                renderTable();
                saveState();
                showToast(`طھظ… طھط­ط¯ظٹط« ط£ط³ط¹ط§ط± ${updatedCount} طµظ†ظپ ظˆظپظ‚ط§ظ‹ ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ†ط¸ط§ظ…. ط§ط¶ط؛ط· ط¹ظ„ظ‰ 'ط­ظپط¸ ظˆطھط¹ط¯ظٹظ„ ظپظٹ ط§ظ„ط³ط¬ظ„' ظ„ط§ط¹طھظ…ط§ط¯ ط§ظ„طھط¹ط¯ظٹظ„.`, 'success');
            } else {
                showToast(data.message || 'ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ طھط­ط¯ظٹط«ط§طھ ط¬ط¯ظٹط¯ط© ظ„ظ„ط£ط³ط¹ط§ط±.', 'info');
            }
        } catch (err) {
            console.error('Error refreshing prices:', err);
            showToast('طھط¹ط°ط± ط§ظ„ط§طھطµط§ظ„ ط¨ط§ظ„ط®ط§ط¯ظ… ظ„طھط­ط¯ظٹط« ط§ظ„ط£ط³ط¹ط§ط±.', 'error');
        } finally {
            if (btn1) {
                btn1.disabled = false;
                btn1.innerHTML = orig1;
            }
            if (btn2) {
                btn2.disabled = false;
                btn2.innerHTML = orig2;
            }
        }
    }

    const refreshPricesBtn = document.getElementById('refreshPricesBtn');
    const refreshPricesTopBtn = document.getElementById('refreshPricesTopBtn');
    if (refreshPricesBtn) refreshPricesBtn.addEventListener('click', handleRefreshPrices);
    if (refreshPricesTopBtn) refreshPricesTopBtn.addEventListener('click', handleRefreshPrices);

    // Save To Database Archive Listener
    if (saveToArchiveBtn) {
        saveToArchiveBtn.addEventListener('click', async function () {
            if (packingItems.length === 0) {
                showToast('ظٹط±ط¬ظ‰ ط¥ط¶ط§ظپط© طµظ†ظپ ظˆط§ط­ط¯ ط¹ظ„ظ‰ ط§ظ„ط£ظ‚ظ„ ظ‚ط¨ظ„ ط§ظ„ط­ظپط¸ ظپظٹ ط§ظ„ط³ط¬ظ„.', 'warning');
                return;
            }

            const origHtml = saveToArchiveBtn.innerHTML;
            saveToArchiveBtn.disabled = true;
            saveToArchiveBtn.innerHTML = `
                <span class="inline-block animate-spin mr-1">âŒ›</span>
                <span>ط¬ط§ط±ظٹ ط§ظ„ط­ظپط¸ ظپظٹ ط§ظ„ط³ط¬ظ„...</span>
            `;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const payload = {
                    packing_list_id: currentListId,
                    doc_number: docNumberInput.value.trim(),
                    doc_date: docDateInput.value,
                    customer_name: customerNameInput.value.trim(),
                    warehouse_name: warehouseSelect.value,
                    price_type: defaultPriceModeSelect.value,
                    notes: docNotesInput.value.trim(),
                    show_quantity: toggleShowQtyCheckbox.checked ? 1 : 0,
                    show_price: toggleShowPriceCheckbox.checked ? 1 : 0,
                    show_packages: toggleShowPackagesCheckbox.checked ? 1 : 0,
                    items: packingItems,
                };

                const res = await fetch('/packing-list/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.success) {
                    currentListId = data.id;
                    localStorage.removeItem(STORAGE_KEY);
                    pendingDraft = null;

                    if (data.packing_number) {
                        docNumberInput.value = data.packing_number;
                    }
                    if (saveBtnText) {
                        saveBtnText.textContent = 'ط­ظپط¸ ظˆطھط¹ط¯ظٹظ„ ظپظٹ ط§ظ„ط³ط¬ظ„';
                    }
                    const indicator = document.getElementById('autoSaveIndicator');
                    if (indicator) {
                        indicator.innerHTML = `
                            <svg class="w-3.5 h-3.5 text-emerald-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-emerald-700 font-bold">ظ…ط­ظپظˆط¸ ظپظٹ ط§ظ„ط³ط¬ظ„ ط¨ط±ظ‚ظ… ${escapeHtml(data.packing_number)}</span>
                        `;
                    }
                    showToast(data.message || 'طھظ… ط­ظپط¸ ظ‚ط§ط¦ظ…ط© ط§ظ„طھط¹ط¨ط¦ط© ط¨ظ†ط¬ط§ط­ ظپظٹ ط§ظ„ط³ط¬ظ„. ط¬ط§ط±ظٹ ط§ظ„ط§ظ†طھظ‚ط§ظ„ ظ„ظ„ط³ط¬ظ„...', 'success');
                    setTimeout(() => {
                        window.location.href = '/packing-list';
                    }, 1200);
                } else {
                    showToast(data.message || 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط­ظپط¸ ط§ظ„ظ‚ط§ط¦ظ…ط© ظپظٹ ط§ظ„ط³ط¬ظ„.', 'error');
                }
            } catch (err) {
                console.error('Error saving packing list:', err);
                showToast('طھط¹ط°ط± ط§ظ„ط§طھطµط§ظ„ ط¨ط§ظ„ط®ط§ط¯ظ… ظ„ط­ظپط¸ ط§ظ„ظ‚ط§ط¦ظ…ط©.', 'error');
            } finally {
                saveToArchiveBtn.disabled = false;
                saveToArchiveBtn.innerHTML = `
                    <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    <span>${currentListId ? 'ط­ظپط¸ ظˆطھط¹ط¯ظٹظ„ ظپظٹ ط§ظ„ط³ط¬ظ„' : 'ط­ظپط¸ ظپظٹ ط§ظ„ط³ط¬ظ„'}</span>
                `;
            }
        });
    }

    // ==========================================
    // Upload Customer Order File Handler
    // ==========================================
    const uploadCustomerOrderFileBtn = document.getElementById('uploadCustomerOrderFileBtn');
    const customerOrderFileInput = document.getElementById('customerOrderFileInput');

    if (uploadCustomerOrderFileBtn && customerOrderFileInput) {
        uploadCustomerOrderFileBtn.addEventListener('click', function () {
            customerOrderFileInput.click();
        });

        customerOrderFileInput.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const origHtml = uploadCustomerOrderFileBtn.innerHTML;
            uploadCustomerOrderFileBtn.disabled = true;
            uploadCustomerOrderFileBtn.innerHTML = `
                <span class="inline-block animate-spin mr-1">âŒ›</span>
                <span>ط¬ط§ط±ظچ ظپط­طµ ط§ظ„ط´ظٹطھ ظˆظ…ط·ط§ط¨ظ‚ط© ط§ظ„ظ…ط®ط²ظ†...</span>
            `;

            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('warehouse_name', warehouseSelect.value);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/packing-list/import-customer-order', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (data.success && Array.isArray(data.items) && data.items.length > 0) {
                    // Populate packing list with items from the customer sheet
                    packingItems = data.items;
                    renderTable();
                    saveState();

                    // Suggest customer name from file if not entered
                    if (!customerNameInput.value.trim() && data.filename) {
                        const cleanName = data.filename.replace(/\.[^/.]+$/, '').replace(/[_\-]+/g, ' ').trim();
                        if (cleanName) customerNameInput.value = cleanName;
                    }

                    showToast(data.message, 'success');

                    // Immediately open the Stock Inspector modal with the 3 allocation options!
                    inspectorItems = JSON.parse(JSON.stringify(data.items));
                    const customer = customerNameInput.value.trim() || 'ط¹ظ…ظٹظ„ ط¹ط§ظ…';
                    inspectorCustomerNameBadge.textContent = 'ط§ظ„ط¹ظ…ظٹظ„: ' + customer;
                    renderInspectorKPIs(data.summary);
                    renderInspectorTable();
                    stockInspectorModal.style.display = 'flex';
                    stockInspectorModal.classList.add('open');
                    stockInspectorModal.classList.remove('hidden');
                } else {
                    showToast(data.message || 'ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ ط£طµظ†ط§ظپ ظ…ط·ط§ط¨ظ‚ط© ط¯ط§ط®ظ„ ط§ظ„ظ…ظ„ظپ.', 'error');
                }
            } catch (err) {
                console.error('Error importing customer order file:', err);
                showToast('ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط±ظپط¹ ظˆطھط­ظ„ظٹظ„ ظ…ظ„ظپ ط§ظ„ط¹ظ…ظٹظ„.', 'error');
            } finally {
                uploadCustomerOrderFileBtn.disabled = false;
                uploadCustomerOrderFileBtn.innerHTML = origHtml;
                customerOrderFileInput.value = '';
            }
        });
    }

    // ==========================================
    // Stock Inspector & Customer Quantity Allocation
    // ==========================================
    const openStockInspectorBtn = document.getElementById('openStockInspectorBtn');
    const exportCustomerStockExcelBtn = document.getElementById('exportCustomerStockExcelBtn');
    const stockInspectorModal = document.getElementById('stockInspectorModal');
    const closeInspectorModalBtn = document.getElementById('closeInspectorModalBtn');
    const inspectorCustomerNameBadge = document.getElementById('inspectorCustomerNameBadge');
    const inspectorTotalItems = document.getElementById('inspectorTotalItems');
    const inspectorInStockCount = document.getElementById('inspectorInStockCount');
    const inspectorZeroStockCount = document.getElementById('inspectorZeroStockCount');
    const inspectorAvailabilityRate = document.getElementById('inspectorAvailabilityRate');
    const inspectorLoadingState = document.getElementById('inspectorLoadingState');
    const inspectorTableWrapper = document.getElementById('inspectorTableWrapper');
    const inspectorTableBody = document.getElementById('inspectorTableBody');
    const allocateAvailableBtn = document.getElementById('allocateAvailableBtn');
    const allocateMinAlertBtn = document.getElementById('allocateMinAlertBtn');
    const customAllocationQtyInput = document.getElementById('customAllocationQtyInput');
    const allocateCustomQtyBtn = document.getElementById('allocateCustomQtyBtn');
    const zeroShortagesBtn = document.getElementById('zeroShortagesBtn');
    const applyAllocationToMainTableBtn = document.getElementById('applyAllocationToMainTableBtn');
    const exportCustomerExcelDirectBtn = document.getElementById('exportCustomerExcelDirectBtn');

    let inspectorItems = [];

    function openStockInspector() {
        if (packingItems.length === 0) {
            showToast('ظٹط±ط¬ظ‰ ط¥ط¶ط§ظپط© ط£ظˆ ط±ظپط¹ ط£طµظ†ط§ظپ ط·ظ„ط¨ ط§ظ„ط¹ظ…ظٹظ„ ط£ظˆظ„ط§ظ‹ ظ‚ط¨ظ„ ط§ظ„ظپط­طµ.', 'warning');
            return;
        }

        const customer = customerNameInput.value.trim() || 'ط¹ظ…ظٹظ„ ط¹ط§ظ…';
        if (inspectorCustomerNameBadge) {
            inspectorCustomerNameBadge.textContent = 'ط§ظ„ط¹ظ…ظٹظ„: ' + customer;
        }
        stockInspectorModal.style.display = 'flex';
        stockInspectorModal.classList.add('open');
        stockInspectorModal.classList.remove('hidden');

        fetchStockInspection();
    }

    function closeStockInspector() {
        stockInspectorModal.style.display = 'none';
        stockInspectorModal.classList.remove('open');
        stockInspectorModal.classList.add('hidden');
    }

    if (openStockInspectorBtn) {
        openStockInspectorBtn.addEventListener('click', openStockInspector);
    }

    if (closeInspectorModalBtn) {
        closeInspectorModalBtn.addEventListener('click', closeStockInspector);
    }

    stockInspectorModal?.addEventListener('click', function (e) {
        if (e.target === stockInspectorModal) closeStockInspector();
    });

    async function fetchStockInspection() {
        inspectorLoadingState.classList.remove('hidden');
        inspectorTableWrapper.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/packing-list/inspect-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({
                    items: packingItems,
                    warehouse_name: warehouseSelect.value
                })
            });

            const data = await res.json();
            if (data.success) {
                inspectorItems = data.items || [];
                renderInspectorKPIs(data.summary);
                renderInspectorTable();
            } else {
                showToast(data.message || 'ظپط´ظ„ ظپط­طµ ط±طµظٹط¯ ط§ظ„ظ…ط®ط²ظ†.', 'error');
            }
        } catch (err) {
            console.error('Error fetching stock inspection:', err);
            showToast('طھط¹ط°ط± ط§ظ„ط§طھطµط§ظ„ ط¨ط§ظ„ط®ط§ط¯ظ… ظ„ظ…ط·ط§ط¨ظ‚ط© ط±طµظٹط¯ ط§ظ„ظ…ط®ط²ظ†.', 'error');
        } finally {
            inspectorLoadingState.classList.add('hidden');
            inspectorTableWrapper.classList.remove('hidden');
        }
    }

    function renderInspectorKPIs(summary) {
        if (!summary) return;
        inspectorTotalItems.textContent = summary.total_items;
        inspectorInStockCount.textContent = summary.in_stock_count;
        inspectorZeroStockCount.textContent = summary.zero_stock_count;
        inspectorAvailabilityRate.textContent = summary.availability_rate + '%';
    }

    function renderInspectorTable() {
        if (!inspectorTableBody) return;
        inspectorTableBody.innerHTML = '';

        if (inspectorItems.length === 0) {
            inspectorTableBody.innerHTML = `
                <tr><td colspan="10" class="text-center py-8 text-[var(--text-muted)] font-semibold">ظ„ط§ طھظˆط¬ط¯ ط£طµظ†ط§ظپ ظ„ظ„ظپط­طµ.</td></tr>
            `;
            return;
        }

        inspectorItems.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.className = item.is_in_stock
                ? 'hover:bg-[var(--bg-hover)] transition-colors'
                : 'bg-rose-500/5 hover:bg-rose-500/10 transition-colors';

            const statusBadge = item.is_in_stock
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">ظ…طھظˆظپط±</span>`
                : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800">ط±طµظٹط¯ طµظپط±</span>`;

            tr.innerHTML = `
                <td class="text-center font-numeric text-xs font-semibold py-2.5 px-1" style="color: var(--text-muted);">${idx + 1}</td>
                <td class="text-center font-mono text-xs font-bold py-2.5 px-1.5 text-blue-600 dark:text-blue-400" dir="ltr">${escapeHtml(item.sku || 'â€”')}</td>
                <td class="text-center font-mono text-xs py-2.5 px-1.5" style="color: var(--text-muted);" dir="ltr">${escapeHtml(item.barcode || 'â€”')}</td>
                <td class="py-2.5 px-2 text-right">
                    <div class="font-bold text-xs truncate max-w-[280px]" style="color: var(--text-main);" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                    <div class="text-[10px] mt-0.5" style="color: var(--text-muted);">${escapeHtml(item.category_name || 'ط¹ط§ظ…')}</div>
                </td>
                <td class="text-center py-2.5 px-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border" style="background: var(--bg-surface-alt); color: var(--text-main); border-color: var(--border-color);">${escapeHtml(item.unit || 'ظ‚ط·ط¹ط©')}</span>
                </td>
                <td class="text-center font-numeric font-extrabold text-xs py-2.5 px-1.5 ${item.is_in_stock ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}">
                    ${item.stock_in_unit}
                </td>
                <td class="text-center text-[11px] font-numeric py-2.5 px-1.5" style="color: var(--text-muted);">
                    ${escapeHtml(item.stock_breakdown || '0')}
                </td>
                <td class="text-center font-numeric text-[11px] py-2.5 px-1" style="color: var(--text-muted);">
                    ${item.min_stock_alert > 0 ? item.min_stock_alert : 'â€”'}
                </td>
                <td class="text-center py-1.5 px-1.5 bg-emerald-500/5">
                    <input type="number" step="any" min="0" value="${item.qty}" data-inspector-idx="${idx}" class="inspector-qty-input font-numeric font-extrabold text-xs text-center rounded border transition-shadow focus:ring-2 focus:ring-emerald-500 focus:outline-none" style="width: 78px; height: 30px; margin: 0 auto; color: var(--text-main); background: var(--bg-surface); border-color: #10b981;">
                </td>
                <td class="text-center py-2.5 px-1.5">${statusBadge}</td>
            `;

            inspectorTableBody.appendChild(tr);
        });

        inspectorTableBody.querySelectorAll('.inspector-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.inspectorIdx);
                const val = parseFloat(this.value) || 0;
                if (inspectorItems[idx]) {
                    inspectorItems[idx].qty = Math.max(0, val);
                }
            });
            input.addEventListener('input', function () {
                const idx = parseInt(this.dataset.inspectorIdx);
                const val = parseFloat(this.value) || 0;
                if (inspectorItems[idx]) {
                    inspectorItems[idx].qty = Math.max(0, val);
                }
            });
        });
    }

    // Option A: Allocate Full Available Stock
    allocateAvailableBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                item.qty = item.stock_in_unit;
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('طھظ… طھط®طµظٹطµ ط§ظ„ط±طµظٹط¯ ط§ظ„ظ…طھط§ط­ ط¨ط§ظ„ظƒط§ظ…ظ„ ظ„ط¬ظ…ظٹط¹ ط§ظ„ط£طµظ†ط§ظپ ط§ظ„ظ…طھظˆظپط±ط©.', 'success');
    });

    // Option B: Allocate Minimum Stock Alert
    allocateMinAlertBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                const minTarget = item.min_stock_alert > 0 ? item.min_stock_alert : 1;
                item.qty = Math.min(minTarget, item.stock_in_unit);
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('طھظ… طھط®طµظٹطµ ظƒظ…ظٹط§طھ ط§ظ„ط­ط¯ ط§ظ„ط£ط¯ظ†ظ‰ ظ„ظ„ط£طµظ†ط§ظپ ط§ظ„ظ…طھط§ط­ط© ط¨ط§ظ„ظ…ط®ط²ظ†.', 'success');
    });

    // Option C: Allocate Custom Uniform Quantity
    allocateCustomQtyBtn?.addEventListener('click', function () {
        const val = parseFloat(customAllocationQtyInput.value) || 1;
        if (val <= 0) {
            alert('ظٹط±ط¬ظ‰ ط¥ط¯ط®ط§ظ„ ظƒظ…ظٹط© طµط­ظٹط­ط© ط£ظƒط¨ط± ظ…ظ† ط§ظ„طµظپط±.');
            return;
        }
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                item.qty = Math.min(val, item.stock_in_unit);
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast(`طھظ… طھط·ط¨ظٹظ‚ ظƒظ…ظٹط© (${val}) ط¹ظ„ظ‰ ظƒط§ظپط© ط§ظ„ط£طµظ†ط§ظپ ط§ظ„ظ…طھط§ط­ط© ط¨ط§ظ„ظ…ط®ط²ظ†.`, 'success');
    });

    // Reset shortages to 0
    zeroShortagesBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (!item.is_in_stock || item.stock_in_unit <= 0) {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('طھظ… طھطµظپظٹط± ظƒظ…ظٹط§طھ ط§ظ„ط£طµظ†ط§ظپ ط؛ظٹط± ط§ظ„ظ…طھظˆظپط±ط© ط¨ط§ظ„ظ…ط®ط²ظ†.', 'info');
    });

    // Apply allocated quantities to the main packing list table
    applyAllocationToMainTableBtn?.addEventListener('click', function () {
        if (inspectorItems.length === 0) return;

        // Map by SKU or Barcode or index to preserve references
        inspectorItems.forEach((inspItem, i) => {
            let target = null;
            if (inspItem.sku) {
                target = packingItems.find(it => it.sku === inspItem.sku);
            }
            if (!target && inspItem.barcode) {
                target = packingItems.find(it => it.barcode === inspItem.barcode);
            }
            if (!target && packingItems[i]) {
                target = packingItems[i];
            }

            if (target) {
                target.qty = inspItem.qty;
                target.total = target.qty * target.price;
                if (typeof inspItem.stock_in_unit !== 'undefined') {
                    target.stock_in_unit = inspItem.stock_in_unit;
                    target.stock_breakdown = inspItem.stock_breakdown;
                    target.is_in_stock = inspItem.is_in_stock;
                }
            }
        });

        renderTable();
        saveState();
        closeStockInspector();
        showToast('طھظ… طھط·ط¨ظٹظ‚ ط§ظ„ظƒظ…ظٹط§طھ ط§ظ„ظ…ط¹طھظ…ط¯ط© ط¨ظ†ط¬ط§ط­ ط¹ظ„ظ‰ ط¬ط¯ظˆظ„ ظ‚ط§ط¦ظ…ط© ط§ظ„طھط¹ط¨ط¦ط©!', 'success');
    });

    // Direct Customer Multi-sheet Excel Export Function
    function exportCustomerStockExcel(itemsToExport) {
        const items = (Array.isArray(itemsToExport) && itemsToExport.length > 0)
            ? itemsToExport
            : packingItems;

        if (!items || items.length === 0) {
            showToast('ظ„ط§ طھظˆط¬ط¯ ط£طµظ†ط§ظپ ظ„طھطµط¯ظٹط±ظ‡ط§ ظ„ظ„ط¹ظ…ظٹظ„.', 'warning');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/packing-list/export-customer-stock-excel';
        form.target = '_self';
        form.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrfToken;
            form.appendChild(input);
        }

        const params = {
            doc_number: docNumberInput.value,
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value,
            warehouse_name: warehouseSelect.value,
            notes: docNotesInput.value,
            items: items
        };

        for (const [key, val] of Object.entries(params)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = typeof val === 'object' ? JSON.stringify(val) : val;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 2500);
    }

    // Direct Customer Export from Modal
    exportCustomerExcelDirectBtn?.addEventListener('click', function () {
        exportCustomerStockExcel(inspectorItems.length > 0 ? inspectorItems : packingItems);
    });

    // Export from Top Dropdown
    exportCustomerStockExcelBtn?.addEventListener('click', function () {
        exportCustomerStockExcel(packingItems);
    });

    function showToast(message, type = 'success') {
        const toast = document.getElementById('packingNotificationToast');
        const toastMsg = document.getElementById('packingToastMessage');
        if (!toast || !toastMsg) return;

        toastMsg.textContent = message;
        if (type === 'error') {
            toast.style.borderColor = '#ef4444';
            toast.style.background = '#fef2f2';
            toast.style.color = '#991b1b';
        } else if (type === 'warning') {
            toast.style.borderColor = '#f59e0b';
            toast.style.background = '#fffbeb';
            toast.style.color = '#92400e';
        } else {
            toast.style.borderColor = '#10b981';
            toast.style.background = '#f0fdf4';
            toast.style.color = '#166534';
        }

        toast.classList.remove('hidden');
        toast.style.opacity = '1';

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3500);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    document.addEventListener('click', function (e) {
        if (searchResultsDropdown && !searchResultsDropdown.contains(e.target) && e.target !== productSearchInput) {
            hideDropdown();
        }
    });

    window.addEventListener('scroll', function () {
        hideDropdown();
    }, { passive: true });

    // Ensure dropdown starts 100% hidden and empty on page load
    hideDropdown();
}
