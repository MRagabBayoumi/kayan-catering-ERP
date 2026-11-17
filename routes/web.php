<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlindAuditController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CostCenterProfitabilityController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\CostingReconciliationController;
use App\Http\Controllers\DriverSettlementController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\MobileOperationsController;
use App\Http\Controllers\PrintDocumentsController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProductPrintController;
use App\Http\Controllers\RationVersionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteDailyCensusController;
use App\Http\Controllers\SiteKitchenIssueController;
use App\Http\Controllers\SiteWasteController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\TraceabilityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleDispatchTripController;
use App\Http\Controllers\WarehouseController;
use App\Models\DispatchOrder;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Odoo Apps Hub (Default Landing Page)
    Route::get('/', [HomeController::class, 'home'])->name('home');
    Route::get('/home', [HomeController::class, 'home']);

    // KPI & Operational Dashboard
    Route::get('/dashboard', function () {
        $today = Carbon::today();

        $stats = [
            'hospitals_count' => Hospital::where('is_active', true)->count(),
            'total_patient_capacity' => (int) Hospital::where('is_active', true)->sum('daily_patient_capacity'),
            'active_batches_count' => InventoryBatch::where('is_exhausted', false)->count(),
            'pending_dispatches_count' => DispatchOrder::whereIn('status', ['prepared', 'in_transit'])->count(),
            'recent_dispatches' => DispatchOrder::with('hospital', 'driver')->latest()->take(5)->get(),
            'expiring_soon_batches' => InventoryBatch::with('product', 'warehouse')
                ->where('is_exhausted', false)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', Carbon::now()->addDays(5))
                ->orderBy('expiry_date', 'asc')
                ->take(5)
                ->get(),
        ];

        return view('dashboard', $stats);
    })->name('dashboard')->middleware('can:access-dashboard');

    // AI Copilot Query Endpoint
    Route::post('/ai/ask', [AiCopilotController::class, 'ask'])
        ->name('ai.ask')
        ->middleware('can:use-ai-copilot');

    // 1. Warehouse & FIFO Department Routes
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/expiry', [WarehouseController::class, 'expiry'])->name('expiry');
        Route::get('/expiry/suggest/{batch}', [WarehouseController::class, 'suggestBatchConsumption'])->name('expiry.suggest');
        Route::get('/recipes/{recipe}/bom', [WarehouseController::class, 'calculateRecipeBom'])->name('recipes.bom');
        Route::get('/batches/{batch}/print-label', [ProductPrintController::class, 'printBatchLabel'])->name('batches.print-label');
        Route::post('/sync-barcodes', [WarehouseController::class, 'syncBarcodes'])->name('sync-barcodes');

        // Dispatches to Driver Vans
        Route::get('/dispatches', [WarehouseController::class, 'dispatches'])->name('dispatches');
        Route::get('/dispatches/create', [WarehouseController::class, 'createDispatch'])->name('dispatches.create');
        Route::post('/dispatches', [WarehouseController::class, 'storeDispatch'])->name('dispatches.store');

        // Blind Audits
        Route::get('/audits', [BlindAuditController::class, 'index'])->name('audits.index');
        Route::get('/audits/create', [BlindAuditController::class, 'create'])->name('audits.create');
        Route::post('/audits', [BlindAuditController::class, 'store'])->name('audits.store');
        Route::get('/audits/{id}', [BlindAuditController::class, 'show'])->name('audits.show');

        // Driver Settlements
        Route::get('/settlements', [DriverSettlementController::class, 'index'])->name('settlements.index');
        Route::get('/settlements/create', [DriverSettlementController::class, 'create'])->name('settlements.create');
        Route::post('/settlements', [DriverSettlementController::class, 'store'])->name('settlements.store');

        // Vehicle Trips & Fuel Expense Allocation
        Route::get('/trips', [VehicleDispatchTripController::class, 'index'])->name('trips.index');
        Route::get('/trips/create', [VehicleDispatchTripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [VehicleDispatchTripController::class, 'store'])->name('trips.store');
    });

    // Product Barcode Label Printing
    Route::get('/products/{product}/print-label', [ProductPrintController::class, 'printProductLabel'])->name('products.print-label');

    // Mobile / Field Operations (بوابة العمليات الميدانية للسائق وأمين الموقع)
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('/', [MobileOperationsController::class, 'index'])->name('index');
        Route::get('/driver/trips', [MobileOperationsController::class, 'driverTrips'])->name('driver.trips');
        Route::get('/receiving/{dispatch_id}', [MobileOperationsController::class, 'hospitalReceiving'])->name('receiving.show');
        Route::post('/receiving/{dispatch_id}', [MobileOperationsController::class, 'submitHospitalReceiving'])->name('receiving.submit');
        Route::get('/receipts/{id}', [MobileOperationsController::class, 'showReceipt'])->name('receipt.show');
    });

    // 2. Costing Department Routes (المقننات والتكاليف)
    Route::prefix('costing')->name('costing.')->group(function () {
        Route::get('/', fn () => redirect()->route('costing.recipes.index'))->name('index');
        Route::get('/recipes', [CostingController::class, 'indexRecipes'])->name('recipes.index');
        Route::get('/recipes/create', [CostingController::class, 'createRecipe'])->name('recipes.create');
        Route::post('/recipes', [CostingController::class, 'storeRecipe'])->name('recipes.store');
        Route::get('/recipes/{recipe}/versions', [RationVersionController::class, 'versions'])->name('recipes.versions');
        Route::post('/recipes/{recipe}/versions', [RationVersionController::class, 'storeVersion'])->name('recipes.versions.store');
        Route::get('/mrp', [RationVersionController::class, 'mrp'])->name('mrp.index');
        Route::post('/mrp/create-pr', [RationVersionController::class, 'createPrFromMrp'])->name('mrp.create-pr');

        Route::get('/daily-order', [CostingController::class, 'dailyOrder'])->name('daily-order');

        Route::get('/consumption', [CostingController::class, 'consumptionIndex'])->name('consumption.index');
        Route::post('/consumption', [CostingController::class, 'storeConsumption'])->name('consumption.store');
        Route::get('/variance', [CostingController::class, 'consumptionIndex'])->name('variance');
        Route::get('/reconciliation', [CostingReconciliationController::class, 'index'])->name('reconciliation.index');
    });

    // Site Operations Routes (مدير الموقع والمخزن الفرعي)
    Route::prefix('site')->name('site.')->group(function () {
        Route::get('/census', [SiteDailyCensusController::class, 'index'])->name('census.index');
        Route::post('/census', [SiteDailyCensusController::class, 'store'])->name('census.store');
        Route::post('/census/{id}/bill', [SiteDailyCensusController::class, 'billCensus'])->name('census.bill');

        Route::get('/kitchen-issues', [SiteKitchenIssueController::class, 'index'])->name('kitchen-issues.index');
        Route::post('/kitchen-issues', [SiteKitchenIssueController::class, 'store'])->name('kitchen-issues.store');

        Route::get('/waste', [SiteWasteController::class, 'index'])->name('waste.index');
        Route::post('/waste', [SiteWasteController::class, 'store'])->name('waste.store');
    });

    // 3. Sales & Tenders Department Routes (المبيعات والمناقصات)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/hospitals', [HospitalController::class, 'index'])->name('hospitals.index');
        Route::get('/hospitals/create', [HospitalController::class, 'create'])->name('hospitals.create');
        Route::post('/hospitals', [HospitalController::class, 'store'])->name('hospitals.store');
        Route::post('/hospitals/{id}/terminate', [HospitalController::class, 'terminate'])->name('hospitals.terminate');

        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
        Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');

        Route::get('/feasibility', [ContractController::class, 'feasibility'])->name('feasibility.index');
        Route::get('/feasibility/create', [ContractController::class, 'createFeasibility'])->name('feasibility.create');
        Route::post('/feasibility', [ContractController::class, 'storeFeasibility'])->name('feasibility.store');

        Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
        Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
        Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');

        Route::get('/competitors', [TenderController::class, 'competitors'])->name('competitors.index');
        Route::post('/competitors', [TenderController::class, 'storeCompetitor'])->name('competitors.store');
    });

    // 4. Procurement Department Routes (إدارة المشتريات والتوريدات)
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/requests', [ProcurementController::class, 'requests'])->name('requests.index');
        Route::get('/requests/create', [ProcurementController::class, 'createRequest'])->name('requests.create');
        Route::post('/requests', [ProcurementController::class, 'storeRequest'])->name('requests.store');
        Route::get('/requests/{id}', [ProcurementController::class, 'showRequest'])->name('requests.show');
        Route::post('/requests/{id}/approve', [ProcurementController::class, 'approveRequest'])->name('requests.approve');
        Route::post('/requests/{id}/reject', [ProcurementController::class, 'rejectRequest'])->name('requests.reject');
        Route::get('/requests/{id}/compare-quotes', [ProcurementController::class, 'compareQuotes'])->name('requests.compare');
        Route::post('/requests/{id}/convert-to-po', [ProcurementController::class, 'convertRequestToOrder'])->name('requests.convert');

        Route::get('/orders', [ProcurementController::class, 'orders'])->name('orders.index');
        Route::get('/orders/create', [ProcurementController::class, 'createOrder'])->name('orders.create');
        Route::post('/orders', [ProcurementController::class, 'storeOrder'])->name('orders.store');

        Route::get('/receipts', [ProcurementController::class, 'receipts'])->name('receipts.index');
        Route::post('/receipts', [ProcurementController::class, 'storeReceipt'])->name('receipts.store');

        Route::get('/suppliers', [ProcurementController::class, 'suppliers'])->name('suppliers.index');
        Route::post('/suppliers', [ProcurementController::class, 'storeSupplier'])->name('suppliers.store');
    });

    // 5. Finance & Accounts Department Routes (إدارة الحسابات والمالية)
    Route::prefix('finance')->name('finance.')->group(function () {
        // Profitability & Cost Centers (تحليل ربحية المستشفيات)
        Route::get('/profitability', [CostCenterProfitabilityController::class, 'index'])->name('profitability.index');

        // 1. Disbursement Orders (أذونات الصرف اللحظية)
        Route::get('/disbursements', [FinanceController::class, 'disbursements'])->name('disbursements.index');
        Route::post('/disbursements', [FinanceController::class, 'storeDisbursement'])->name('disbursements.store');
        Route::get('/disbursements/{id}/execute', [FinanceController::class, 'executeDisbursementView'])->name('disbursements.execute.view');
        Route::post('/disbursements/{id}/execute', [FinanceController::class, 'executeDisbursement'])->name('disbursements.execute');

        // 2. Receipts & Inflows (الوارد وترصيده واشعار المدير)
        Route::get('/receipts', [FinanceController::class, 'receipts'])->name('receipts.index');
        Route::post('/receipts', [FinanceController::class, 'storeReceipt'])->name('receipts.store');
        Route::post('/receipts/{id}/review', [FinanceController::class, 'reviewReceipt'])->name('receipts.review');

        // 3. Daily Cash Closing & Audit (التقفيل واليومية للمالك)
        Route::get('/closing', [FinanceController::class, 'closing'])->name('closing.index');
        Route::post('/closing', [FinanceController::class, 'storeClosing'])->name('closing.store');
        Route::post('/closing/{id}/review', [FinanceController::class, 'reviewClosing'])->name('closing.review');

        // 4. Automated Journal Entries (دفتر اليومية العامة والقيود التلقائية)
        Route::get('/journal-entries', [FinanceController::class, 'journalEntries'])->name('journal.index');

        // 5. General Ledger (دفتر الأستاذ العام لكل الحسابات)
        Route::get('/ledger', [FinanceController::class, 'ledger'])->name('ledger.index');

        // 6. Settlements for Custodies & Suppliers (ترصيد الموردين والعهد)
        Route::get('/settlements', [FinanceController::class, 'settlements'])->name('settlements.index');

        // 7. Monthly Itemized Breakdown (التفريدة الشهرية للجهات والأشخاص)
        Route::get('/monthly-breakdown', [FinanceController::class, 'monthlyBreakdown'])->name('breakdown.index');

        // 8. Suppliers & Treasuries Legacy Views
        Route::get('/suppliers', [FinanceController::class, 'suppliers'])->name('suppliers.index');
        Route::get('/treasury', [FinanceController::class, 'treasury'])->name('treasury.index');
        Route::post('/treasury/vouchers', [FinanceController::class, 'storeVoucher'])->name('treasury.vouchers.store');
    });

    // Document Traceability Engine (شجرة تتبع المستندات صعوداً وهبوطاً)
    Route::get('/traceability/{type}/{id}', [TraceabilityController::class, 'show'])->name('traceability.show');

    // Official Document & Thermal Printing Engine (طباعة المستندات والملصقات الحرارية)
    Route::prefix('prints')->name('prints.')->group(function () {
        Route::get('/purchase-order/{id}', [PrintDocumentsController::class, 'purchaseOrder'])->name('purchase-order');
        Route::get('/goods-receipt/{id}', [PrintDocumentsController::class, 'goodsReceipt'])->name('goods-receipt');
        Route::get('/dispatch-order/{id}', [PrintDocumentsController::class, 'dispatchOrder'])->name('dispatch-order');
        Route::get('/delivery-receipt/{id}', [PrintDocumentsController::class, 'deliveryReceipt'])->name('delivery-receipt');
        Route::get('/driver-settlement/{id}', [PrintDocumentsController::class, 'driverSettlement'])->name('driver-settlement');
        Route::get('/diet-labels/{id}', [PrintDocumentsController::class, 'dietLabels'])->name('diet-labels');
    });

    // 6. Settings & Permissions (إعدادات النظام والمستخدمين والصلاحيات)
    Route::middleware('can:manage-company-settings')->group(function () {
        Route::get('/settings', [CompanySettingController::class, 'index'])->name('settings.index');
        Route::match(['post', 'put'], '/settings', [CompanySettingController::class, 'update'])->name('settings.update');
        Route::match(['post', 'put'], '/settings/barcode', [CompanySettingController::class, 'updateBarcode'])->name('settings.barcode.update');
        Route::match(['post', 'put'], '/settings/printers', [CompanySettingController::class, 'updatePrinters'])->name('settings.printers.update');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class);
    });

    Route::middleware('can:manage-roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    });
});
