<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Entities\Expense;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\PurchasesReturn\Entities\PurchaseReturn;
use Modules\PurchasesReturn\Entities\PurchaseReturnPayment;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SalePayment;
use Modules\SalesReturn\Entities\SaleReturn;
use Modules\SalesReturn\Entities\SaleReturnPayment;

class HomeController extends Controller
{

    public function index() {
        $sales = Sale::completed()->sum('total_amount');
        $sale_returns = SaleReturn::completed()->sum('total_amount');
        $purchase_returns = PurchaseReturn::completed()->sum('total_amount');
        $product_costs = 0;

        foreach (Sale::completed()->with('saleDetails')->get() as $sale) {
            foreach ($sale->saleDetails as $saleDetail) {
                if (!is_null($saleDetail->product)) {
                    $product_costs += $saleDetail->product->product_cost * $saleDetail->quantity;
                }
            }
        }

        $revenue = ($sales - $sale_returns) / 100;
        $profit = $revenue - $product_costs;

        return view('home', [
            'revenue'          => $revenue,
            'sale_returns'     => $sale_returns / 100,
            'purchase_returns' => $purchase_returns / 100,
            'profit'           => $profit
        ]);
    }


    public function currentMonthChart() {
        abort_if(!request()->ajax(), 404);

        $currentMonthSales = Sale::where('status', 'Completed')->whereMonth('date', date('m'))
                ->whereYear('date', date('Y'))
                ->sum('total_amount') / 100;
        $currentMonthPurchases = Purchase::where('status', 'Completed')->whereMonth('date', date('m'))
                ->whereYear('date', date('Y'))
                ->sum('total_amount') / 100;

        return response()->json([
            'sales'     => $currentMonthSales,
            'purchases' => $currentMonthPurchases,
        ]);
    }


    public function salesPurchasesChart() {
        abort_if(!request()->ajax(), 404);

        $sales = $this->salesChartData();
        $purchases = $this->purchasesChartData();

        return response()->json(['sales' => $sales, 'purchases' => $purchases]);
    }



    public function salesChartData() {
            $start = Carbon::now('Asia/Jakarta')->startOfMonth();
            $end   = Carbon::now('Asia/Jakarta')->endOfMonth();

            // Buat semua tanggal di bulan ini default = 0
            $dates = collect();
            $period = new \DatePeriod(
                new \DateTime($start),
                new \DateInterval('P1D'),
                new \DateTime($end) // biar inclusive
            );

            foreach ($period as $date) {
                $dates->put($date->format('d-m-Y'), 0);
            }

            // Ambil data sales bulan ini
            $sales = Sale::where('status', 'Completed')
                ->whereBetween('date', [$start, $end])
                ->groupBy(DB::raw("DATE_FORMAT(date,'%d-%m-%Y')"))
                ->orderBy('date')
                ->get([
                    DB::raw("DATE_FORMAT(date,'%d-%m-%Y') as date"),
                    DB::raw('SUM(total_amount) AS count'),
                ])
                ->pluck('count', 'date');

            // Gabungkan dengan template tanggal
            $dates = $dates->merge($sales);

            $data = [];
            $days = [];
            foreach ($dates as $key => $value) {
                $data[] = $value / 100; // konversi ke rupiah
                $days[] = $key;
            }

            return response()->json(['data' => $data, 'days' => $days]);
        }


    public function purchasesChartData() {
        $start = Carbon::now('Asia/Jakarta')->startOfMonth();
        $end   = Carbon::now('Asia/Jakarta')->endOfMonth();

        $dates = collect();
        $period = new \DatePeriod(
            new \DateTime($start),
            new \DateInterval('P1D'),
            new \DateTime($end)
        );

        foreach ($period as $date) {
            $dates->put($date->format('d-m-Y'), 0);
        }

        $purchases = Purchase::where('status', 'Completed')
            ->whereBetween('date', [$start, $end])
            ->groupBy(DB::raw("DATE_FORMAT(date,'%d-%m-%Y')"))
            ->orderBy('date')
            ->get([
                DB::raw("DATE_FORMAT(date,'%d-%m-%Y') as date"),
                DB::raw('SUM(total_amount) AS count'),
            ])
            ->pluck('count', 'date');

        $dates = $dates->merge($purchases);

        $data = [];
        $days = [];
        foreach ($dates as $key => $value) {
            $data[] = $value / 100;
            $days[] = $key;
        }

        return response()->json(['data' => $data, 'days' => $days]);
    }
}