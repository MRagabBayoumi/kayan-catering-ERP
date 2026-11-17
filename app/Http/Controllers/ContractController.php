<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\FeasibilityStudy;
use App\Models\Hospital;
use App\Models\MealType;
use App\Models\Tender;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    /**
     * List all hospital contracts.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-sales');

        $status = $request->query('status', 'all');
        $query = Contract::with(['hospital', 'costCenter', 'mealPrices.mealType', 'approvedByUser'])
            ->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $contracts = $query->paginate(15)->withQueryString();

        $activeCount = Contract::where('status', Contract::STATUS_ACTIVE)->count();
        $totalContractsValue = (float) Contract::where('status', Contract::STATUS_ACTIVE)->sum('contract_value');
        $hospitalsCount = Hospital::where('is_active', true)->count();

        return view('sales.contracts.index', [
            'title' => 'دليل العقود والأسعار التعاقدية للمستشفيات',
            'contracts' => $contracts,
            'status' => $status,
            'activeCount' => $activeCount,
            'totalContractsValue' => $totalContractsValue,
            'hospitalsCount' => $hospitalsCount,
        ]);
    }

    /**
     * Show form to create/activate a new contract.
     */
    public function create(): View
    {
        Gate::authorize('manage-sales');

        $hospitals = Hospital::where('is_active', true)->orderBy('name')->get();
        $tenders = Tender::where('status', 'awarded')->orWhere('status', 'open')->orderBy('id', 'desc')->get();
        $mealTypes = MealType::all();

        return view('sales.contracts.create', [
            'title' => 'صياغة وتفعيل عقد توريد وتغذية جديد',
            'hospitals' => $hospitals,
            'tenders' => $tenders,
            'mealTypes' => $mealTypes,
        ]);
    }

    /**
     * Store new Contract and meal pricing matrix.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'hospital_id' => ['required', 'exists:hospitals,id'],
            'tender_id' => ['nullable', 'exists:tenders,id'],
            'contract_number' => ['required', 'string', 'max:50', 'unique:contracts,contract_number'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string'],
            'payment_terms_days' => ['required', 'integer', 'min:1'],
            'penalty_terms' => ['nullable', 'string'],
            'meal_prices' => ['required', 'array', 'min:1'],
            'meal_prices.*.meal_type_id' => ['required', 'exists:meal_types,id'],
            'meal_prices.*.price_per_meal' => ['required', 'numeric', 'min:0.01'],
            'meal_prices.*.minimum_daily_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $contractData = [
            'hospital_id' => $validated['hospital_id'],
            'tender_id' => $validated['tender_id'] ?? null,
            'contract_number' => $validated['contract_number'],
            'title' => $validated['title'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'contract_value' => $validated['contract_value'],
            'billing_cycle' => $validated['billing_cycle'],
            'payment_terms_days' => $validated['payment_terms_days'],
            'penalty_terms' => $validated['penalty_terms'] ?? null,
        ];

        $this->contractService->activateContract($contractData, $validated['meal_prices'], auth()->id() ?? 1);

        return redirect()->route('sales.contracts.index')
            ->with('success', 'تم تفعيل واعتماد العقد بنجاح وربطه بمركز تكلفة المستشفى وتحديد أسعار الوجبات.');
    }

    /**
     * List Feasibility Studies.
     */
    public function feasibility(): View
    {
        Gate::authorize('manage-sales');

        $studies = FeasibilityStudy::with(['tender', 'creator'])
            ->latest('id')
            ->paginate(15);

        return view('sales.feasibility.index', [
            'title' => 'دراسات الجدوى التقديرية وهوامش ربحية المناقصات',
            'studies' => $studies,
        ]);
    }

    /**
     * Create Feasibility Study Form.
     */
    public function createFeasibility(): View
    {
        Gate::authorize('manage-sales');

        $tenders = Tender::orderBy('id', 'desc')->get();

        return view('sales.feasibility.create', [
            'title' => 'إعداد دراسة جدوى مالية وتقدير تكاليف مناقصة',
            'tenders' => $tenders,
        ]);
    }

    /**
     * Store Feasibility Study.
     */
    public function storeFeasibility(Request $request): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'tender_id' => ['nullable', 'exists:tenders,id'],
            'planned_duration_months' => ['required', 'integer', 'min:1'],
            'expected_total_meals' => ['required', 'integer', 'min:1'],
            'target_selling_price_per_meal' => ['required', 'numeric', 'min:0.01'],
            'food_cost_per_meal' => ['required', 'numeric', 'min:0.01'],
            'labor_monthly_cost' => ['required', 'numeric', 'min:0'],
            'transport_monthly_cost' => ['required', 'numeric', 'min:0'],
            'consumables_monthly_cost' => ['required', 'numeric', 'min:0'],
            'operating_monthly_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $months = (int) $validated['planned_duration_months'];
        $meals = (int) $validated['expected_total_meals'];

        $costItems = [
            [
                'cost_category' => 'food',
                'item_name' => 'خامات الأغذية القياسية للوجبات',
                'quantity' => $meals,
                'unit' => 'meal',
                'unit_cost' => (float) $validated['food_cost_per_meal'],
            ],
            [
                'cost_category' => 'labor',
                'item_name' => 'أجور ومرتبات عمالة الموقع والمطبخ',
                'quantity' => $months,
                'unit' => 'month',
                'unit_cost' => (float) $validated['labor_monthly_cost'],
            ],
            [
                'cost_category' => 'transport',
                'item_name' => 'لوجستيات النقل والوقود للسيارات',
                'quantity' => $months,
                'unit' => 'month',
                'unit_cost' => (float) $validated['transport_monthly_cost'],
            ],
            [
                'cost_category' => 'consumables',
                'item_name' => 'الخبز والكايزر ومواد التغليف',
                'quantity' => $months,
                'unit' => 'month',
                'unit_cost' => (float) $validated['consumables_monthly_cost'],
            ],
            [
                'cost_category' => 'overhead',
                'item_name' => 'مصاريف تشغيل ومفاجآت الموقع',
                'quantity' => $months,
                'unit' => 'month',
                'unit_cost' => (float) $validated['operating_monthly_cost'],
            ],
        ];

        $studyData = [
            'hospital_name' => $validated['hospital_name'],
            'tender_id' => $validated['tender_id'] ?? null,
            'planned_duration_months' => $months,
            'expected_total_meals' => $meals,
            'target_selling_price_per_meal' => (float) $validated['target_selling_price_per_meal'],
            'notes' => $validated['notes'] ?? null,
        ];

        $this->contractService->createFeasibilityStudy($studyData, $costItems, auth()->id() ?? 1);

        return redirect()->route('sales.feasibility.index')
            ->with('success', 'تم حفظ وحساب دراسة الجدوى المالية وتحديد نقطة التعادل وهامش المساهمة.');
    }
}
