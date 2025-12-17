<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\Purchase;
use Modules\Budget\Entities\MasterBudget;
// ✅ PERBAIKAN: Tambahkan import ini agar tidak error 500
use Modules\TargetSale\Entities\TargetSale; 

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        $approved_budget = MasterBudget::where('status', 'Approved')
            ->whereMonth('periode_awal', $month)
            ->whereYear('periode_awal', $year)
            ->sum('grandtotal');

        $purchases = Purchase::where('status', 'Approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('total_amount');

        return view('home', [
            'approved_budget' => $approved_budget,
            'purchases'       => $purchases,
            'month'           => $month,
            'year'            => $year,
            'monthName'       => Carbon::create()->month($month)->translatedFormat('F'),
        ]);
    }

    public function totalBudgetPurchase(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $approved_budget = MasterBudget::where('status', 'Approved')
            ->whereMonth('periode_awal', $month)
            ->whereYear('periode_awal', $year)
            ->sum('grandtotal');

        $purchases = Purchase::where('status', 'Approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('total_amount');

        return response()->json([
            'approved_budget' => $approved_budget,
            'purchases'       => $purchases,
        ]);
    }

    public function budgetPurchasesChart(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $labels = [];
        $approvedBudgets = [];
        $approvedPurchases = [];

        $totalBudget = MasterBudget::where('status', 'Approved')
            ->whereMonth('periode_awal', $month)
            ->whereYear('periode_awal', $year)
            ->sum('grandtotal');

        $cumulativePurchase = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $labels[] = $day;

            $dailyPurchase = Purchase::where('status', 'Approved')
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereDay('date', $day)
                ->sum('total_amount');

            $cumulativePurchase += $dailyPurchase;

            $approvedPurchases[] = $cumulativePurchase;
            $approvedBudgets[] = max($totalBudget - $cumulativePurchase, 0);
        }

        return response()->json([
            'labels' => $labels,
            'approvedBudgets' => $approvedBudgets,
            'approvedPurchases' => $approvedPurchases,
        ]);
    }

    public function budgetByDepartmentChart(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $budgets = MasterBudget::with('department')
            ->where('status', 'Approved')
            ->whereYear('periode_awal', $year)
            ->whereMonth('periode_awal', $month)
            ->get();

        $grouped = $budgets->groupBy(function ($item) {
            return $item->department ? $item->department->department_name : 'Budget Lain-lain';
        });

        $labels = [];
        $data = [];

        foreach ($grouped as $departmentName => $items) {
            $labels[] = $departmentName;
            $data[] = $items->sum('grandtotal');
        }

        if (empty($labels)) {
            $labels = ['No Data'];
            $data = [1];
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function currentMonthChart(Request $request)
    {
        abort_if(!request()->ajax(), 404);

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $budgets = MasterBudget::where('status', 'Approved')
            ->whereMonth('periode_awal', $month)
            ->whereYear('periode_awal', $year)
            ->select('department_id', DB::raw('SUM(grandtotal) as total'))
            ->groupBy('department_id')
            ->with('department')
            ->get();

        $labels = $budgets->map(fn($b) => $b->department->name ?? 'Unknown')->toArray();
        $data = $budgets->pluck('total')->toArray();

        if(empty($labels)) {
            $labels = ['No Data'];
            $data = [1];
        }

        $total = array_sum($data);
        $percentages = array_map(fn($value) => $total > 0 ? round(($value / $total) * 100, 2) : 0, $data);

        return response()->json([
            'labels' => $labels,
            'percentages' => $percentages,
        ]);
    }

    // --- METHOD BARU (DENGAN FIX IMPORT) ---
    public function targetVsPurchaseChart(Request $request)
    {
        $year = $request->get('year', now()->year);

        $labels = [];
        $targetLimits = []; 
        $actualPurchases = []; 

        for ($month = 1; $month <= 12; $month++) {
            // 1. Label Bulan
            $labels[] = Carbon::createFromDate($year, $month, 1)->translatedFormat('M');

            // 2. Ambil Target Sales bulan ini
            // Pastikan model TargetSale sudah di-import di atas
            $targetAmount = TargetSale::where('year', $year)
                ->where('month', $month)
                ->sum('target_amount');
            
            // Hitung 0.65% dari Target
            $limit = $targetAmount * 0.0065; 
            $targetLimits[] = $limit;

            // 3. Ambil Total Purchase Request (Approved) bulan ini
            $purchaseAmount = Purchase::where('status', 'Approved')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_amount');
            
            $actualPurchases[] = $purchaseAmount;
        }

        return response()->json([
            'labels' => $labels,
            'targetLimits' => $targetLimits,
            'actualPurchases' => $actualPurchases,
        ]);
    }
}