<?php

namespace App\Http\Controllers;

use App\Models\CompetitorPrice;
use App\Models\Hospital;
use App\Models\Tender;
use App\Services\SalesTendersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenderController extends Controller
{
    public function __construct(protected SalesTendersService $salesService) {}

    /**
     * List Tenders.
     */
    public function index(): View
    {
        Gate::authorize('manage-sales');

        $tenders = Tender::with(['hospital', 'competitorPrices'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.tenders.index', compact('tenders'));
    }

    /**
     * Create Tender Form.
     */
    public function create(): View
    {
        Gate::authorize('manage-sales');

        $hospitals = Hospital::where('is_active', true)->get();

        return view('sales.tenders.create', compact('hospitals'));
    }

    /**
     * Store Tender.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'entity_name' => 'required|string|max:255',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'announcement_date' => 'required|date',
            'envelope_opening_date' => 'nullable|date',
            'bid_bond_amount' => 'nullable|numeric|min:0',
            'final_guarantee_amount' => 'nullable|numeric|min:0',
            'booklet_cost' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $this->salesService->createTender($validated);

        return redirect()->route('sales.tenders.index')->with('success', 'تم تسجيل المناقصة بنجاح.');
    }

    /**
     * Competitor Prices Archive (أرشيف أسعار المنافسين للعام القادم).
     */
    public function competitors(Request $request): View
    {
        Gate::authorize('manage-sales');

        $query = CompetitorPrice::with('tender');

        if ($request->filled('year')) {
            $query->where('tender_year', $request->year);
        }

        $competitorPrices = $query->orderBy('tender_year', 'desc')->paginate(20);
        $tenders = Tender::orderBy('id', 'desc')->get();

        return view('sales.competitors.index', compact('competitorPrices', 'tenders'));
    }

    /**
     * Store Competitor Price.
     */
    public function storeCompetitor(Request $request): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'tender_id' => 'required|exists:tenders,id',
            'competitor_name' => 'required|string|max:255',
            'item_or_meal_name' => 'required|string|max:255',
            'our_bid_price' => 'nullable|numeric|min:0',
            'competitor_price' => 'required|numeric|min:0',
            'tender_year' => 'required|integer',
            'was_awarded' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $this->salesService->archiveCompetitorPrice($validated['tender_id'], $validated);

        return redirect()->route('sales.competitors.index')->with('success', 'تم أرشفة سعر المنافس للاستفادة منه في تسعير العام القادم.');
    }
}
