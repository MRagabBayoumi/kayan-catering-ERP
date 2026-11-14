<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlindAuditController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\DriverSettlementController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\UserController;
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

    // Dashboard
    Route::get('/', function () {
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
    })->name('dashboard');

    Route::get('/home', [HomeController::class, 'home'])->name('home');

    // AI Copilot Query Endpoint
    Route::post('/ai/ask', [AiCopilotController::class, 'ask'])->name('ai.ask');

    // 1. Warehouse & FIFO Department Routes
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');

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
    });

    // 2. Costing Department Routes (المقننات والتكاليف)
    Route::prefix('costing')->name('costing.')->group(function () {
        Route::get('/recipes', [CostingController::class, 'indexRecipes'])->name('recipes.index');
        Route::get('/recipes/create', [CostingController::class, 'createRecipe'])->name('recipes.create');
        Route::post('/recipes', [CostingController::class, 'storeRecipe'])->name('recipes.store');

        Route::get('/daily-order', [CostingController::class, 'dailyOrder'])->name('daily-order');

        Route::get('/consumption', [CostingController::class, 'consumptionIndex'])->name('consumption.index');
        Route::post('/consumption', [CostingController::class, 'storeConsumption'])->name('consumption.store');
        Route::get('/variance', [CostingController::class, 'consumptionIndex'])->name('variance');
    });

    // 3. Sales & Tenders Department Routes (المبيعات والمناقصات)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/hospitals', [HospitalController::class, 'index'])->name('hospitals.index');
        Route::get('/hospitals/create', [HospitalController::class, 'create'])->name('hospitals.create');
        Route::post('/hospitals', [HospitalController::class, 'store'])->name('hospitals.store');
        Route::post('/hospitals/{id}/terminate', [HospitalController::class, 'terminate'])->name('hospitals.terminate');

        Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
        Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
        Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');

        Route::get('/competitors', [TenderController::class, 'competitors'])->name('competitors.index');
        Route::post('/competitors', [TenderController::class, 'storeCompetitor'])->name('competitors.store');
    });

    // 4. Procurement Department Routes (إدارة المشتريات والتوريدات)
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/orders', [ProcurementController::class, 'orders'])->name('orders.index');
        Route::get('/orders/create', [ProcurementController::class, 'createOrder'])->name('orders.create');
        Route::post('/orders', [ProcurementController::class, 'storeOrder'])->name('orders.store');

        Route::get('/suppliers', [ProcurementController::class, 'suppliers'])->name('suppliers.index');
        Route::post('/suppliers', [ProcurementController::class, 'storeSupplier'])->name('suppliers.store');
    });

    // 5. Finance & Accounts Department Routes (إدارة الحسابات والمالية)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/suppliers', [FinanceController::class, 'suppliers'])->name('suppliers.index');

        Route::get('/treasury', [FinanceController::class, 'treasury'])->name('treasury.index');
        Route::post('/treasury/vouchers', [FinanceController::class, 'storeVoucher'])->name('treasury.vouchers.store');

        Route::get('/closing', [FinanceController::class, 'closing'])->name('closing.index');
        Route::post('/closing', [FinanceController::class, 'storeClosing'])->name('closing.store');
    });

    // 6. Settings & Permissions (إعدادات النظام والمستخدمين والصلاحيات)
    Route::get('/settings', [CompanySettingController::class, 'index'])->name('settings.index');
    Route::match(['post', 'put'], '/settings', [CompanySettingController::class, 'update'])->name('settings.update');
    Route::match(['post', 'put'], '/settings/barcode', [CompanySettingController::class, 'updateBarcode'])->name('settings.barcode.update');
    Route::match(['post', 'put'], '/settings/printers', [CompanySettingController::class, 'updatePrinters'])->name('settings.printers.update');

    Route::resource('users', UserController::class);

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
});
