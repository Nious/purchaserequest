<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Entities\Expense;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SalePayment;
use Modules\Budget\Entities\MasterBudget;
use Modules\SalesReturn\Entities\SaleReturnPayment;
use Modules\PurchasesReturn\Entities\PurchaseReturnPayment;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        $sales = Sale::completed()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('total_amount');

        $approved_budget = MasterBudget::where('status', 'Approved')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
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

    public function budgetPurchasesChart(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $labels = [];
        $approvedBudgets = [];
        $approvedPurchases = [];

        // Ambil total budget yang sudah disetujui untuk bulan ini
        $totalBudget = \Modules\Budget\Entities\MasterBudget::where('status', 'Approved')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('grandtotal');

        $cumulativePurchase = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            $labels[] = $day;

            // Ambil total purchase APPROVED hanya untuk bulan & hari ini
            $dailyPurchase = \Modules\Purchase\Entities\Purchase::where('status', 'Approved')
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereDay('date', $day)
                ->sum('total_amount');

            // Tetap akumulatif di bulan itu saja
            $cumulativePurchase += $dailyPurchase;

            $approvedPurchases[] = $cumulativePurchase;
            $approvedBudgets[] = max($totalBudget - $cumulativePurchase, 0); // tidak boleh negatif
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

        $budgets = \Modules\Budget\Entities\MasterBudget::with('department')
            ->where('status', 'Approved')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        $grouped = $budgets->groupBy(function ($item) {
            // Jika tidak ada department, beri label "Lain-lain"
            return $item->department ? $item->department->department_name : 'Budget Lain-lain';
        });

        $labels = [];
        $data = [];

        foreach ($grouped as $departmentName => $items) {
            $labels[] = $departmentName;
            $data[] = $items->sum('grandtotal');
        }

        // Jika tidak ada data sama sekali, tambahkan placeholder supaya chart tetap muncul
        if (empty($labels)) {
            $labels = ['No Data'];
            $data = [1]; // Chart.js butuh setidaknya 1 data
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

        // Ambil total budget per department untuk bulan & tahun yang dipilih
        $budgets = \Modules\Budget\Entities\MasterBudget::where('status', 'Approved')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->select('department_id', DB::raw('SUM(grandtotal) as total'))
            ->groupBy('department_id')
            ->with('department')
            ->get();

        $labels = $budgets->map(fn($b) => $b->department->name)->toArray();
        $data = $budgets->pluck('total')->toArray();

        // Jika tidak ada data, tampilkan placeholder 1 data kosong supaya chart tetap muncul
        if(empty($labels)) {
            $labels = ['No Data'];
            $data = [1]; // Chart membutuhkan setidaknya 1 data
        }

        $total = array_sum($data);

        // Hitung persentase
        $percentages = array_map(fn($value) => $total > 0 ? round(($value / $total) * 100, 2) : 0, $data);

        return response()->json([
            'labels' => $labels,
            'percentages' => $percentages,
        ]);
    }

}
