<?php

namespace Modules\Reports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Purchase\Entities\Purchase;
use Modules\Budget\Entities\MasterBudget;
use Modules\Reports\Http\Controllers\ReportsController;
use PDF;

class ReportsController extends Controller
{

    public function profitLossReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::profit-loss.index');
    }

    public function paymentsReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::payments.index');
    }

    public function salesReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::sales.index');
    }

    public function purchasesReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::purchases.index');
    }

    public function printPurchasesReport(Request $request)
    {
        abort_if(Gate::denies('access_reports'), 403);

        // Ambil filter dari parameter URL
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $departmentId = $request->query('department_id');
        $purchaseStatus = $request->query('purchase_status');

        // Buat query yang sama dengan komponen Livewire
        $query = Purchase::with('department')
            ->whereBetween('date', [$startDate, $endDate]);

        // 🔒 Filter keamanan berdasarkan departemen user
        $userDeptId = auth()->user()->department_id;

        if ($userDeptId != 0) {
            $query->where('department_id', $userDeptId);
            $departmentName = auth()->user()->department->department_name ?? 'Unknown Department';
        } elseif ($departmentId) {
            $query->where('department_id', $departmentId);
            $departmentName = optional(\Modules\Department\Entities\Departments::find($departmentId))->department_name ?? 'All Departemen';
        } else {
            $departmentName = 'All Departemen';
        }

        // 🔹 Filter status pembelian
        if ($purchaseStatus) {
            $query->where('status', $purchaseStatus);
        }

        // Ambil semua data
        $purchases = $query->orderBy('date', 'desc')->get();

        // Format tanggal untuk nama file (contoh: "1 Okt 2025")
        $formattedStart = \Carbon\Carbon::parse($startDate)->translatedFormat('j M Y');
        $formattedEnd = \Carbon\Carbon::parse($endDate)->translatedFormat('j M Y');

        // 🔹 Nama file yang diinginkan
        $fileName = sprintf(
            'Purchase Request Report - %s - (%s - %s).pdf',
            $departmentName,
            $formattedStart,
            $formattedEnd
        );

        // Generate PDF
        $pdf = \PDF::loadView('reports::purchases.print', compact('purchases', 'startDate', 'endDate', 'departmentName'))
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0);

        // Stream PDF ke browser dengan nama file rapi
        return $pdf->stream($fileName);
    }

    public function budgetReport()
    {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::budgets.index');
    }

    public function printBudgetReport(Request $request)
    {
        abort_if(Gate::denies('access_reports'), 403);

        // Ambil filter dari parameter URL
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');

        // Buat query yang sama persis seperti di komponen Livewire Anda
        $query = MasterBudget::with(['department', 'purchases'])
            ->whereBetween('tgl_penyusunan', [$startDate, $endDate]);

        // Terapkan filter keamanan departemen (SAMA SEPERTI DI LIVEWIRE)
        $userDeptId = auth()->user()->department_id;
        if ($userDeptId != 0) {
            $query->where(function($q) use ($userDeptId) {
                $q->where('department_id', $userDeptId)
                ->orWhere('department_id', 0);
            });
        } elseif ($departmentId !== '' && $departmentId !== null) {
            if ($departmentId == '0') {
                $query->where('department_id', 0);
            } else {
                $query->where(function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId)
                    ->orWhere('department_id', 0);
                });
            }
        }
        
        if ($status) {
            $query->where('status', $status);
        }

        // Ambil SEMUA data
        $allBudgets = $query->orderBy('department_id')->orderBy('tgl_penyusunan', 'desc')->get();

        // --- LOGIKA PENGELOMPOKAN BARU ---
        // Kelompokkan berdasarkan department_id
        $groupedBudgets = $allBudgets->groupBy('department_id');
        // Pisahkan budget "Over Budget" (department_id = 0)
        $overBudgets = $groupedBudgets->pull(0);
        // Sisanya adalah budget per departemen
        $departmentBudgets = $groupedBudgets;
        // --- BATAS LOGIKA BARU ---

        // Buat PDF menggunakan dompdf
        $pdf = PDF::loadView('reports::budgets.print', [
            'departmentBudgets' => $departmentBudgets, // Kirim data terkelompok
            'overBudgets' => $overBudgets,             // Kirim data over budget
            'startDate' => $startDate,
            'endDate' => $endDate
        ])->setPaper('a4', 'portrait')
        ->setOption('margin-top', 0)
        ->setOption('margin-right', 0)
        ->setOption('margin-bottom', 0)
        ->setOption('margin-left', 0);

        return $pdf->stream('laporan-master-budget.pdf');
    }

    public function salesReturnReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::sales-return.index');
    }

    public function purchasesReturnReport() {
        abort_if(Gate::denies('access_reports'), 403);

        return view('reports::purchases-return.index');
    }
}
