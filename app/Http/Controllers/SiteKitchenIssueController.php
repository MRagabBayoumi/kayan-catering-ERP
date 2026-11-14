<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Product;
use App\Models\SiteKitchenIssue;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SiteKitchenIssueController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        if ($user->role === 'site_manager' && $user->assignedHospital) {
            $hospital = $user->assignedHospital;
            $allHospitals = collect([$hospital]);
        } else {
            $allHospitals = Hospital::where('is_active', true)->orderBy('name')->get();
            $hospitalId = $request->input('hospital_id', $allHospitals->first()?->id);
            $hospital = $allHospitals->firstWhere('id', (int) $hospitalId) ?? $allHospitals->first();
        }

        $date = $request->input('date', Carbon::today()->toDateString());

        $issues = collect();
        if ($hospital) {
            $issues = SiteKitchenIssue::with(['product', 'issuer'])
                ->where('hospital_id', $hospital->id)
                ->whereDate('issue_date', $date)
                ->orderBy('id', 'desc')
                ->get();
        }

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('site.kitchen_issues.index', compact('hospital', 'allHospitals', 'date', 'issues', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        $hospitalId = ($user->role === 'site_manager' && $user->assigned_hospital_id)
            ? $user->assigned_hospital_id
            : $request->input('hospital_id');

        $request->merge([
            'items' => array_values(array_filter(
                (array) $request->input('items', []),
                fn ($item) => ! empty($item['product_id']) && (float) ($item['quantity_issued'] ?? 0) > 0
            )),
        ]);

        $validated = $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_issued' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $issueNumber = 'KITCHEN-'.date('Ymd').'-'.rand(100, 999);

        DB::transaction(function () use ($hospitalId, $user, $validated, $issueNumber) {
            foreach ($validated['items'] as $item) {
                SiteKitchenIssue::create([
                    'hospital_id' => $hospitalId,
                    'issue_date' => $validated['issue_date'],
                    'issue_number' => $issueNumber,
                    'product_id' => $item['product_id'],
                    'quantity_issued' => (float) $item['quantity_issued'],
                    'unit' => $item['unit'],
                    'issued_by' => $user->id,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('site.kitchen-issues.index', [
            'hospital_id' => $hospitalId,
            'date' => $validated['issue_date'],
        ])->with('success', 'تم تسجيل إذن صرف الخامات من المخزن الفرعي للمطبخ بنجاح.');
    }
}
