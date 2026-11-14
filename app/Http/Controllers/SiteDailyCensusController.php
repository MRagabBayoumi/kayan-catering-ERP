<?php

namespace App\Http\Controllers;

use App\Models\DailySiteCensus;
use App\Models\DailySiteCensusItem;
use App\Models\DietSchedule;
use App\Models\Hospital;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SiteDailyCensusController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();

        // 1. Identify hospital: Site Manager is locked to assigned hospital
        if ($user->role === 'site_manager' && $user->assignedHospital) {
            $hospital = $user->assignedHospital;
            $allHospitals = collect([$hospital]);
        } else {
            $allHospitals = Hospital::where('is_active', true)->orderBy('name')->get();
            $hospitalId = $request->input('hospital_id', $allHospitals->first()?->id);
            $hospital = $allHospitals->firstWhere('id', (int) $hospitalId) ?? $allHospitals->first();
        }

        // 2. Date defaults to today
        $date = $request->input('date', Carbon::today()->toDateString());
        $carbonDate = Carbon::parse($date);

        // 3. Find existing census for this hospital and date
        $census = null;
        if ($hospital) {
            $census = DailySiteCensus::with('items.dietSchedule')
                ->where('hospital_id', $hospital->id)
                ->whereDate('census_date', $date)
                ->first();
        }

        // 4. Active Diet Schedules
        $schedules = DietSchedule::where(function ($q) use ($hospital) {
            $q->whereNull('hospital_id');
            if ($hospital) {
                $q->orWhere('hospital_id', $hospital->id);
            }
        })
            ->where('is_active', true)
            ->get();

        // 5. Weekly Quota Status (حصة الأسبوع من اللحوم والدواجن)
        $startOfWeek = $carbonDate->copy()->startOfWeek(Carbon::SATURDAY);
        $endOfWeek = $carbonDate->copy()->endOfWeek(Carbon::FRIDAY);

        $weeklyCensuses = collect();
        if ($hospital) {
            $weeklyCensuses = DailySiteCensus::where('hospital_id', $hospital->id)
                ->whereBetween('census_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->get();
        }

        $meatDaysCount = $weeklyCensuses->where('protein_choice', DailySiteCensus::PROTEIN_MEAT)->count();
        $poultryDaysCount = $weeklyCensuses->where('protein_choice', DailySiteCensus::PROTEIN_POULTRY)->count();

        // Recent censuses history
        $recentCensuses = collect();
        if ($hospital) {
            $recentCensuses = DailySiteCensus::where('hospital_id', $hospital->id)
                ->orderBy('census_date', 'desc')
                ->take(7)
                ->get();
        }

        return view('site.census.index', compact(
            'hospital',
            'allHospitals',
            'date',
            'carbonDate',
            'census',
            'schedules',
            'meatDaysCount',
            'poultryDaysCount',
            'recentCensuses'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        $hospitalId = ($user->role === 'site_manager' && $user->assigned_hospital_id)
            ? $user->assigned_hospital_id
            : $request->input('hospital_id');

        $validated = $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'census_date' => 'required|date',
            'protein_choice' => 'required|in:meat,poultry',
            'carb_choice' => 'required|in:rice_vermicelli,plain_rice,pasta',
            'veg_choice' => 'required|in:cooked_veg,beans,cowpea',
            'items' => 'required|array',
            'items.*' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $totalCount = 0;
        foreach ($validated['items'] as $count) {
            $totalCount += (int) $count;
        }

        DB::transaction(function () use ($hospitalId, $user, $validated, $totalCount) {
            $census = DailySiteCensus::updateOrCreate(
                [
                    'hospital_id' => $hospitalId,
                    'census_date' => $validated['census_date'],
                ],
                [
                    'site_manager_id' => $user->id,
                    'protein_choice' => $validated['protein_choice'],
                    'carb_choice' => $validated['carb_choice'],
                    'veg_choice' => $validated['veg_choice'],
                    'total_patient_count' => $totalCount,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'submitted',
                ]
            );

            // Save items
            foreach ($validated['items'] as $scheduleId => $count) {
                if ((int) $count >= 0) {
                    DailySiteCensusItem::updateOrCreate(
                        [
                            'daily_site_census_id' => $census->id,
                            'diet_schedule_id' => $scheduleId,
                        ],
                        [
                            'patient_count' => (int) $count,
                        ]
                    );
                }
            }
        });

        return redirect()->route('site.census.index', [
            'date' => $validated['census_date'],
            'hospital_id' => $hospitalId,
        ])->with('success', 'تم تسجيل وتأكيد تشغيل وأعداد الموقع بنجاح طبقاً لاختيار الشيف ومدير الموقع.');
    }
}
