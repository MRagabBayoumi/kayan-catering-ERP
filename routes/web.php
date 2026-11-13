<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AuthController;
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

    Route::get('/home', fn () => redirect()->route('dashboard'))->name('home');

    // AI Copilot Query Endpoint
    Route::post('/ai/ask', [AiCopilotController::class, 'ask'])->name('ai.ask');
});
