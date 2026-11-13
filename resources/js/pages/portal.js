/**
 * Kayan ERP — Sales Representative Mobile Portal Interactions
 * Geolocation GPS, Field Customer Creation, Direct Product Search & Order Engine
 * Pure Modular JavaScript (Zero Inline JS, Full Hotwire Turbo SPA Compatibility)
 */

export function initPortalPage() {
    initPortalCustomers();
    initPortalCustomerCreate();
    initPortalCatalogFilters();
    initPortalOrderForm();
}

/**
 * Field Customer List interactions
 */
function initPortalCustomers() {
    const customerTypeInput = document.getElementById('customerTypeInput');

    document.querySelectorAll('input[name="pricing_tier"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (customerTypeInput) {
                customerTypeInput.value = this.value;
            }
        });
    });
}

/**
 * Field Customer Creation: Camera, Gallery & GPS Handling
 */
function initPortalCustomerCreate() {
    const triggerCameraBtn = document.getElementById('triggerCameraBtn');
    const triggerGalleryBtn = document.getElementById('triggerGalleryBtn');
    const cameraInput = document.getElementById('cameraInput');
    const galleryInput = document.getElementById('galleryInput');
    const storeImagesInput = document.getElementById('storeImagesInput');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const uploadEmptyHint = document.getElementById('uploadEmptyHint');
    const photoCountBadge = document.getElementById('photoCountBadge');

    const gpsBtn = document.getElementById('gpsBtn');
    const gpsBtnText = document.getElementById('gpsBtnText');
    const latInput = document.getElementById('latitudeInput');
    const lngInput = document.getElementById('longitudeInput');
    const gpsStatusBox = document.getElementById('gpsStatusBox');
    const gpsStatusText = document.getElementById('gpsStatusText');
    const gpsHelpTip = document.getElementById('gpsHelpTip');
    const useAttendanceGpsBtn = document.getElementById('useAttendanceGpsBtn');

    // 1. GPS Location Handling
    function setGpsSuccess(lat, lng, label) {
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
        if (gpsStatusBox) {
            gpsStatusBox.className = 'p-3 rounded-lg bg-emerald-50 border border-emerald-300 text-xs text-emerald-900 font-bold';
        }
        if (gpsStatusText) {
            gpsStatusText.innerHTML = `
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-600">✅</span>
                        <span>${label || 'تم التقاط إحداثيات المحل بنجاح'}:</span>
                        <span class="font-numeric dir-ltr">(${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)})</span>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="text-[11px] font-bold text-emerald-700 underline">
                        معاينة على الخريطة ↗
                    </a>
                </div>
            `;
        }
        if (gpsHelpTip) gpsHelpTip.classList.add('hidden');
    }

    if (latInput && latInput.value && lngInput && lngInput.value) {
        setGpsSuccess(latInput.value, lngInput.value, 'تم تحديد الموقع مسبقاً');
    }

    if (useAttendanceGpsBtn) {
        useAttendanceGpsBtn.addEventListener('click', function () {
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');
            if (lat && lng) {
                setGpsSuccess(lat, lng, 'تم استخدام موقع تسجيل الحضور الحالي');
            }
        });
    }

    if (gpsBtn) {
        gpsBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('عذراً، متصفح هاتفك لا يدعم تحديد الموقع الجغرافي.');
                return;
            }

            if (gpsStatusBox) {
                gpsStatusBox.className = 'p-3 rounded-lg bg-sky-50 border border-sky-300 text-xs text-sky-900 font-bold';
            }
            if (gpsStatusText) gpsStatusText.innerHTML = '⏳ جارٍ الاتصال بالأقمار الصناعية لتحديد موقع المحل...';
            gpsBtn.disabled = true;
            if (gpsBtnText) gpsBtnText.textContent = 'جارٍ تحديد الموقع...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    setGpsSuccess(lat, lng, 'تم التقاط موقع المحل الحالي بنجاح');
                    gpsBtn.disabled = false;
                    if (gpsBtnText) gpsBtnText.textContent = 'إعادة التقاط موقع المحل';
                },
                function (error) {
                    gpsBtn.disabled = false;
                    if (gpsBtnText) gpsBtnText.textContent = 'إعادة المحاولة (GPS)';

                    let message = 'تعذر التقاط الموقع الجغرافي.';
                    if (error.code === 1) {
                        message = 'تم رفض إذن الوصول للموقع في المتصفح. يمكنك إتمام التسجيل بدون GPS أو تفعيل الإذن.';
                        if (gpsHelpTip) gpsHelpTip.classList.remove('hidden');
                    } else if (error.code === 2) {
                        message = 'إشارة الـ GPS غير متاحة حالياً. تأكد من تفعيل خدمة الموقع بالهاتف.';
                    } else if (error.code === 3) {
                        message = 'استغرق تحديد الموقع وقتاً طويلاً. يرجى إعادة المحاولة في مكان مفتوح.';
                    }

                    if (gpsStatusBox) {
                        gpsStatusBox.className = 'p-3 rounded-lg bg-amber-50 border border-amber-300 text-xs text-amber-900 font-bold';
                    }
                    if (gpsStatusText) gpsStatusText.innerHTML = `⚠️ ${message}`;
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        });
    }

    // 2. Camera & Gallery Multi-Photo Upload
    let accumulatedFiles = [];
    const MAX_PHOTOS = 5;
    const MAX_SIZE_MB = 10;

    if (triggerCameraBtn && cameraInput) {
        triggerCameraBtn.addEventListener('click', () => cameraInput.click());
    }

    if (triggerGalleryBtn && galleryInput) {
        triggerGalleryBtn.addEventListener('click', () => galleryInput.click());
    }

    function handleIncomingFiles(fileList) {
        if (!fileList || fileList.length === 0) return;

        Array.from(fileList).forEach(file => {
            if (accumulatedFiles.length >= MAX_PHOTOS) {
                alert(`الحد الأقصى هو ${MAX_PHOTOS} صور للمحل.`);
                return;
            }

            if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                alert(`الصورة "${file.name}" أكبر من ${MAX_SIZE_MB} ميجابايت.`);
                return;
            }

            accumulatedFiles.push(file);
        });

        syncAndRenderPhotos();
    }

    if (cameraInput) {
        cameraInput.addEventListener('change', function (e) {
            handleIncomingFiles(e.target.files);
            cameraInput.value = '';
        });
    }

    if (galleryInput) {
        galleryInput.addEventListener('change', function (e) {
            handleIncomingFiles(e.target.files);
            galleryInput.value = '';
        });
    }

    function removeFile(index) {
        accumulatedFiles.splice(index, 1);
        syncAndRenderPhotos();
    }

    function syncAndRenderPhotos() {
        if (typeof DataTransfer !== 'undefined') {
            const dt = new DataTransfer();
            accumulatedFiles.forEach(file => dt.items.add(file));
            if (storeImagesInput) {
                storeImagesInput.files = dt.files;
            }
        }

        if (photoCountBadge) {
            photoCountBadge.textContent = `${accumulatedFiles.length} من ${MAX_PHOTOS} صور`;
            photoCountBadge.className = accumulatedFiles.length > 0
                ? 'erp-badge erp-badge-success text-[10px] font-numeric'
                : 'erp-badge erp-badge-neutral text-[10px] font-numeric';
        }

        if (previewContainer) {
            previewContainer.innerHTML = '';

            if (accumulatedFiles.length === 0) {
                previewContainer.classList.add('hidden');
                if (uploadEmptyHint) uploadEmptyHint.classList.remove('hidden');
                return;
            }

            previewContainer.classList.remove('hidden');
            if (uploadEmptyHint) uploadEmptyHint.classList.add('hidden');

            accumulatedFiles.forEach((file, index) => {
                const objectUrl = URL.createObjectURL(file);
                const div = document.createElement('div');
                div.className = 'relative aspect-square rounded-lg overflow-hidden border border-[var(--border-color)] bg-slate-100 shadow-xs group';

                const sizeInMb = (file.size / (1024 * 1024)).toFixed(1);

                div.innerHTML = `
                    <img src="${objectUrl}" alt="صورة ${index + 1}" class="w-full h-full object-cover">
                    <button type="button" class="btn-remove-photo absolute top-1 left-1 w-6 h-6 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center font-bold text-xs shadow-md active:scale-90 transition-transform" data-index="${index}" title="حذف الصورة">
                        ✕
                    </button>
                    <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-black/75 text-white font-numeric">
                        ${index + 1}
                    </span>
                    <span class="absolute bottom-1 left-1 px-1 py-0.2 rounded text-[8px] font-semibold bg-white/90 text-slate-800 font-numeric">
                        ${sizeInMb}MB
                    </span>
                `;

                previewContainer.appendChild(div);
            });

            previewContainer.querySelectorAll('.btn-remove-photo').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    removeFile(idx);
                });
            });
        }
    }
}

/**
 * Auto-submit on change for catalog filters
 */
function initPortalCatalogFilters() {
    document.querySelectorAll('.portal-auto-submit').forEach(el => {
        el.addEventListener('change', function () {
            if (this.form) {
                this.form.submit();
            }
        });
    });

    const catalogSearch = document.getElementById('catalogSearchInput');
    if (catalogSearch) {
        catalogSearch.addEventListener('change', function () {
            if (this.value && this.form) {
                this.form.submit();
            }
        });
    }
}

/**
 * Mobile Order Form Engine (Vanilla JS, Full Hotwire Turbo Compatibility)
 */
function initPortalOrderForm() {
    const root = document.getElementById('portalOrderApp');
    if (!root) return;

    let allProducts = [];
    try {
        allProducts = JSON.parse(root.dataset.products || '[]');
    } catch (e) {
        allProducts = [];
    }

    const repCommissionRate = Number(root.dataset.commissionRate) || 0;
    const repCommissionType = root.dataset.commissionType || 'percentage';
    const isAdmin = root.dataset.isAdmin === '1';
    const selectedProductId = root.dataset.selectedProduct || '';

    let currentPriceTier = isAdmin ? 'wholesale' : 'wholesale';
    let currentPaymentType = 'cash';
    let items = [];

    // Cache DOM Elements
    const form = document.getElementById('portalOrderForm');
    const customerSelect = document.getElementById('customerSelect');
    const priceTierSelector = document.getElementById('priceTierSelector');
    const paymentTypeSelector = document.getElementById('paymentTypeSelector');
    const paidAmountWrapper = document.getElementById('paidAmountWrapper');
    const directProductSearch = document.getElementById('directProductSearch');
    const directSearchResults = document.getElementById('directSearchResults');
    const portalOrderBarcodeInput = document.getElementById('portalOrderBarcodeInput');
    const openCatalogModalBtn = document.getElementById('openCatalogModalBtn');
    const closeCatalogModalBtn = document.getElementById('closeCatalogModalBtn');
    const closeCatalogModalBackdrop = document.getElementById('closeCatalogModalBackdrop');
    const productPickerModal = document.getElementById('productPickerModal');
    const modalProductSearch = document.getElementById('modalProductSearch');
    const modalProductList = document.getElementById('modalProductList');
    const orderItemsContainer = document.getElementById('orderItemsContainer');
    const orderItemsEmpty = document.getElementById('orderItemsEmpty');
    const orderItemsList = document.getElementById('orderItemsList');
    const orderSubtotalDisplay = document.getElementById('orderSubtotalDisplay');
    const orderCommissionDisplay = document.getElementById('orderCommissionDisplay');
    const orderItemsCountBadge = document.getElementById('orderItemsCountBadge');

    // 1. Price Tier Selector Setup
    if (priceTierSelector) {
        priceTierSelector.querySelectorAll('.price-tier-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const tier = this.dataset.tier;
                if (!tier) return;

                currentPriceTier = tier;

                // Update UI classes
                priceTierSelector.querySelectorAll('.price-tier-btn').forEach(b => {
                    const radio = b.querySelector('input[type="radio"]');
                    if (b.dataset.tier === tier) {
                        b.className = 'price-tier-btn flex items-center justify-center p-2.5 rounded-lg border border-[var(--primary)] bg-[var(--primary-soft)] text-[var(--primary)] text-xs font-bold cursor-pointer text-center transition-all select-none active';
                        if (radio) radio.checked = true;
                    } else {
                        b.className = 'price-tier-btn flex items-center justify-center p-2.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-hover)] text-[var(--text-secondary)] text-xs font-bold cursor-pointer text-center transition-all select-none';
                        if (radio) radio.checked = false;
                    }
                });

                // Update prices of all added items
                recalculateItemPrices();
            });
        });
    }

    // 2. Payment Type Selector Setup
    if (paymentTypeSelector) {
        paymentTypeSelector.querySelectorAll('.payment-type-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const pType = this.dataset.payment;
                if (!pType) return;

                currentPaymentType = pType;

                paymentTypeSelector.querySelectorAll('.payment-type-btn').forEach(b => {
                    const radio = b.querySelector('input[type="radio"]');
                    const isSelected = b.dataset.payment === pType;
                    if (radio) radio.checked = isSelected;

                    if (isSelected) {
                        if (pType === 'cash') {
                            b.className = 'payment-type-btn flex items-center justify-center p-2 rounded-lg border border-emerald-500 bg-emerald-50 text-emerald-700 text-xs font-bold cursor-pointer text-center transition-all select-none active';
                        } else if (pType === 'credit') {
                            b.className = 'payment-type-btn flex items-center justify-center p-2 rounded-lg border border-amber-500 bg-amber-50 text-amber-700 text-xs font-bold cursor-pointer text-center transition-all select-none active';
                        } else {
                            b.className = 'payment-type-btn flex items-center justify-center p-2 rounded-lg border border-[var(--primary)] bg-[var(--primary-soft)] text-[var(--primary)] text-xs font-bold cursor-pointer text-center transition-all select-none active';
                        }
                    } else {
                        b.className = 'payment-type-btn flex items-center justify-center p-2 rounded-lg border border-[var(--border-color)] bg-[var(--bg-hover)] text-[var(--text-secondary)] text-xs font-bold cursor-pointer text-center transition-all select-none';
                    }
                });

                if (paidAmountWrapper) {
                    if (pType === 'partial') {
                        paidAmountWrapper.classList.remove('hidden');
                    } else {
                        paidAmountWrapper.classList.add('hidden');
                    }
                }
            });
        });
    }

    // 3. Customer Selection Change
    if (customerSelect) {
        customerSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.tier) {
                const tier = opt.dataset.tier;
                if (isAdmin && priceTierSelector) {
                    const targetBtn = priceTierSelector.querySelector(`.price-tier-btn[data-tier="${tier}"]`);
                    if (targetBtn) {
                        targetBtn.click();
                        return;
                    }
                }
            }
            recalculateItemPrices();
        });
    }

    function getProductPrice(product) {
        if (!isAdmin) {
            return Number(product.wholesale_price) || 0;
        }
        if (currentPriceTier === 'wholesale') return Number(product.wholesale_price) || Number(product.retail_price) || 0;
        if (currentPriceTier === 'distributor') return Number(product.distributor_price) || Number(product.retail_price) || 0;
        return Number(product.retail_price) || 0;
    }

    function recalculateItemPrices() {
        items.forEach(item => {
            const prod = allProducts.find(p => p.id === item.product_id);
            if (prod) {
                item.unit_price = getProductPrice(prod);
            }
        });
        renderItemsList();
    }

    function formatMoney(num) {
        return (Number(num) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 4. Add / Select Product
    function addProductToOrder(product) {
        if (!product) return;

        const existing = items.find(i => i.product_id === product.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            items.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku || '',
                unit: product.unit || 'قطعة',
                pieces_per_package: product.pieces_per_package || 1,
                quantity: 1,
                unit_price: getProductPrice(product),
                line_discount: 0
            });
        }

        renderItemsList();

        if (navigator.vibrate) {
            navigator.vibrate([60]);
        }
    }

    // 5. Render Added Items List & Update Totals
    function renderItemsList() {
        if (!orderItemsList || !orderItemsEmpty) return;

        if (items.length === 0) {
            orderItemsEmpty.classList.remove('hidden');
            orderItemsList.innerHTML = '';
        } else {
            orderItemsEmpty.classList.add('hidden');
            orderItemsList.innerHTML = '';

            items.forEach((item, index) => {
                const lineTotal = Math.max(0, (item.quantity * item.unit_price) - (item.line_discount || 0));
                const itemCard = document.createElement('div');
                itemCard.className = 'p-3 bg-[var(--bg-hover)] rounded-lg border border-[var(--border-color)] space-y-2';

                itemCard.innerHTML = `
                    <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                    <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                    <input type="hidden" name="items[${index}][line_discount]" value="${item.line_discount || 0}">

                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs sm:text-sm font-bold text-[var(--text-main)] truncate">${item.name}</div>
                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-[var(--text-muted)] font-numeric">
                                <span>كود: ${item.sku || '---'}</span>
                                <span>·</span>
                                <span class="erp-badge erp-badge-neutral text-[9px] py-0.5 px-1">${item.unit}${item.pieces_per_package > 1 ? ' (' + item.pieces_per_package + ' قطعة)' : ''}</span>
                            </div>
                        </div>
                        <button type="button" class="btn-remove-item p-1 text-slate-400 hover:text-rose-600 transition-colors" data-index="${index}" title="حذف الصنف">
                            ✕
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-[var(--border-light)]">
                        <!-- Quantity Stepper -->
                        <div class="flex items-center gap-1 border border-[var(--border-color)] rounded-lg bg-white p-0.5">
                            <button type="button" class="btn-qty-minus w-7 h-7 flex items-center justify-center rounded font-bold text-slate-600 hover:bg-slate-100 active:scale-95 transition-all" data-index="${index}">-</button>
                            <input type="number" step="1" min="1" class="qty-input w-12 text-center text-xs font-bold font-numeric border-0 p-0 focus:ring-0" value="${item.quantity}" data-index="${index}">
                            <button type="button" class="btn-qty-plus w-7 h-7 flex items-center justify-center rounded font-bold text-slate-600 hover:bg-slate-100 active:scale-95 transition-all" data-index="${index}">+</button>
                        </div>

                        <!-- Price & Line Total -->
                        <div class="text-left font-numeric">
                            <div class="text-[10px] text-[var(--text-muted)]">${formatMoney(item.unit_price)} ج.م / ${item.unit}</div>
                            <div class="text-xs sm:text-sm font-extrabold text-[var(--text-main)]">${formatMoney(lineTotal)} ج.م</div>
                        </div>
                    </div>
                `;

                orderItemsList.appendChild(itemCard);
            });

            // Bind Stepper & Delete Events
            orderItemsList.querySelectorAll('.btn-qty-plus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const idx = parseInt(this.dataset.index, 10);
                    if (items[idx]) {
                        items[idx].quantity += 1;
                        renderItemsList();
                    }
                });
            });

            orderItemsList.querySelectorAll('.btn-qty-minus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const idx = parseInt(this.dataset.index, 10);
                    if (items[idx] && items[idx].quantity > 1) {
                        items[idx].quantity -= 1;
                        renderItemsList();
                    }
                });
            });

            orderItemsList.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('change', function () {
                    const idx = parseInt(this.dataset.index, 10);
                    const val = Math.max(1, parseInt(this.value, 10) || 1);
                    if (items[idx]) {
                        items[idx].quantity = val;
                        renderItemsList();
                    }
                });
            });

            orderItemsList.querySelectorAll('.btn-remove-item').forEach(btn => {
                btn.addEventListener('click', function () {
                    const idx = parseInt(this.dataset.index, 10);
                    if (items[idx]) {
                        items.splice(idx, 1);
                        renderItemsList();
                    }
                });
            });
        }

        // Calculate Subtotal & Commission
        let subtotal = 0;
        items.forEach(i => {
            subtotal += Math.max(0, (i.quantity * i.unit_price) - (i.line_discount || 0));
        });
        subtotal = Math.round(subtotal * 100) / 100;

        let estimatedCommission = 0;
        if (repCommissionType === 'percentage') {
            estimatedCommission = Math.round((subtotal * (repCommissionRate / 100)) * 100) / 100;
        } else {
            estimatedCommission = repCommissionRate;
        }

        if (orderSubtotalDisplay) {
            orderSubtotalDisplay.textContent = `${formatMoney(subtotal)} ج.م`;
        }
        if (orderCommissionDisplay) {
            orderCommissionDisplay.textContent = `+ ${formatMoney(estimatedCommission)} ج.م`;
        }
        if (orderItemsCountBadge) {
            orderItemsCountBadge.textContent = `${items.length} أصناف`;
        }
    }

    // 6. Direct Mobile Product Search
    function renderDirectSearchResults(searchTerm) {
        if (!directSearchResults) return;

        const term = (searchTerm || '').trim().toLowerCase();
        if (!term) {
            directSearchResults.classList.add('hidden');
            directSearchResults.innerHTML = '';
            return;
        }

        const matches = allProducts.filter(p =>
            (p.name && p.name.toLowerCase().includes(term)) ||
            (p.sku && p.sku.toLowerCase().includes(term)) ||
            (p.barcode && p.barcode.toLowerCase().includes(term))
        ).slice(0, 15);

        if (matches.length === 0) {
            directSearchResults.innerHTML = `
                <div class="p-3 text-center text-xs text-[var(--text-muted)]">
                    لا توجد أصناف مطابقة لـ «${term}»
                </div>
            `;
            directSearchResults.classList.remove('hidden');
            return;
        }

        directSearchResults.innerHTML = '';
        matches.forEach(product => {
            const itemPrice = getProductPrice(product);
            const row = document.createElement('div');
            row.className = 'p-2.5 flex items-center justify-between hover:bg-[var(--bg-hover)] transition-colors cursor-pointer gap-2';

            row.innerHTML = `
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    ${product.image_url
                        ? `<img src="${product.image_url}" alt="${product.name}" class="w-9 h-9 object-contain rounded bg-slate-100 border border-slate-200 shrink-0">`
                        : `<div class="w-9 h-9 rounded bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 text-slate-400 text-xs">📦</div>`
                    }
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-[var(--text-main)] truncate">${product.name}</div>
                        <div class="text-[10px] text-[var(--text-muted)] font-numeric">كود: ${product.sku || '---'} · ${product.stock_display || (product.is_available ? 'متوفر' : 'غير متوفر')}</div>
                    </div>
                </div>
                <div class="text-left shrink-0">
                    <div class="text-xs font-bold text-emerald-700 font-numeric">${formatMoney(itemPrice)} ج.م</div>
                    <button type="button" class="mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[var(--primary-soft)] text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition-colors">
                        + إضافة
                    </button>
                </div>
            `;

            row.addEventListener('click', () => {
                addProductToOrder(product);
                if (directProductSearch) directProductSearch.value = '';
                directSearchResults.classList.add('hidden');
                directSearchResults.innerHTML = '';
            });

            directSearchResults.appendChild(row);
        });

        directSearchResults.classList.remove('hidden');
    }

    if (directProductSearch) {
        directProductSearch.addEventListener('input', function () {
            renderDirectSearchResults(this.value);
        });

        document.addEventListener('click', (e) => {
            if (directSearchResults && !directSearchResults.contains(e.target) && e.target !== directProductSearch) {
                directSearchResults.classList.add('hidden');
            }
        });
    }

    // 7. Barcode Scanner Handling
    if (portalOrderBarcodeInput) {
        portalOrderBarcodeInput.addEventListener('change', function () {
            const raw = this.value ? this.value.trim().toLowerCase() : '';
            if (!raw) return;

            const matched = allProducts.find(p =>
                (p.barcode && p.barcode.toLowerCase() === raw) ||
                (p.sku && p.sku.toLowerCase() === raw)
            );

            if (matched) {
                addProductToOrder(matched);
            } else {
                alert('لم يتم العثور على صنف بالباركود: ' + raw);
            }

            this.value = '';
        });
    }

    // 8. Catalog Modal Handling
    function openCatalogModal() {
        if (!productPickerModal) return;
        productPickerModal.classList.remove('hidden');
        renderModalProductList('');
        if (modalProductSearch) {
            modalProductSearch.value = '';
            modalProductSearch.focus();
        }
    }

    function closeCatalogModal() {
        if (!productPickerModal) return;
        productPickerModal.classList.add('hidden');
    }

    function renderModalProductList(searchTerm) {
        if (!modalProductList) return;

        const term = (searchTerm || '').trim().toLowerCase();
        let matches = allProducts;
        if (term) {
            matches = allProducts.filter(p =>
                (p.name && p.name.toLowerCase().includes(term)) ||
                (p.sku && p.sku.toLowerCase().includes(term)) ||
                (p.barcode && p.barcode.toLowerCase().includes(term))
            );
        }
        matches = matches.slice(0, 40);

        if (matches.length === 0) {
            modalProductList.innerHTML = `
                <div class="p-8 text-center text-[var(--text-muted)] text-xs">
                    لا توجد أصناف مطابقة للبحث
                </div>
            `;
            return;
        }

        modalProductList.innerHTML = '';
        matches.forEach(product => {
            const itemPrice = getProductPrice(product);
            const card = document.createElement('div');
            card.className = 'p-3 rounded-lg border border-[var(--border-color)] hover:border-[var(--primary)] hover:bg-[var(--bg-hover)] transition-all flex items-center justify-between cursor-pointer gap-2.5';

            card.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    ${product.image_url
                        ? `<img src="${product.image_url}" alt="${product.name}" class="w-10 h-10 object-contain rounded bg-slate-100 border border-slate-200 shrink-0">`
                        : `<div class="w-10 h-10 rounded bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 text-slate-400 text-sm">📦</div>`
                    }
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-bold text-[var(--text-main)] truncate">${product.name}</div>
                        <div class="text-[11px] font-numeric mt-0.5 ${product.is_available ? 'text-emerald-700' : 'text-rose-600'}">
                            كود: ${product.sku || '---'} · ${product.stock_display || (product.is_available ? 'متوفر' : 'غير متوفر')}
                        </div>
                    </div>
                </div>
                <div class="text-left shrink-0">
                    <div class="text-xs font-extrabold text-emerald-700 font-numeric">${formatMoney(itemPrice)} ج.م</div>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-[var(--primary-soft)] text-[var(--primary)]">+ إضافة</span>
                </div>
            `;

            card.addEventListener('click', () => {
                addProductToOrder(product);
                closeCatalogModal();
            });

            modalProductList.appendChild(card);
        });
    }

    if (openCatalogModalBtn) openCatalogModalBtn.addEventListener('click', openCatalogModal);
    if (closeCatalogModalBtn) closeCatalogModalBtn.addEventListener('click', closeCatalogModal);
    if (closeCatalogModalBackdrop) closeCatalogModalBackdrop.addEventListener('click', closeCatalogModal);

    if (modalProductSearch) {
        modalProductSearch.addEventListener('input', function () {
            renderModalProductList(this.value);
        });
    }

    // 9. Initial Pre-selected Product
    if (selectedProductId) {
        const preselected = allProducts.find(p => String(p.id) === String(selectedProductId));
        if (preselected) {
            addProductToOrder(preselected);
        }
    }

    // 10. Form Submit Validation
    if (form) {
        form.addEventListener('submit', function (e) {
            if (items.length === 0) {
                e.preventDefault();
                alert('يرجى إضافة صنف واحد على الأقل للطلب قبل المتابعة.');
                return false;
            }
        });
    }
}
