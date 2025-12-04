<?php

namespace Modules\TargetSale\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Modules\TargetSale\DataTables\TargetSalesDataTable;
use Modules\TargetSale\Entities\TargetSale;
use Modules\TargetSale\Http\Requests\StoreTargetSaleRequest;
use Modules\TargetSale\Http\Requests\UpdateTargetSaleRequest;
// use Modules\Approval\Services\ApprovalEngine; // Tidak dipakai sementara
// use Modules\Approval\Entities\ApprovalRequest; // Tidak dipakai sementara
// use Modules\Approval\Entities\ApprovalRule; // Tidak dipakai sementara
// use Modules\Approval\Entities\ApprovalRuleLevel; // Tidak dipakai sementara
// use Modules\Approval\Entities\ApprovalRuleUser; // Tidak dipakai sementara
use PDF;

class TargetSalesController extends Controller
{
    // protected $approvalEngine;

    // public function __construct(ApprovalEngine $approvalEngine)
    // {
    //     $this->approvalEngine = $approvalEngine;
    // }

    public function index(TargetSalesDataTable $dataTable)
    {
        abort_if(Gate::denies('access_target_sales'), 403);
        return $dataTable->render('targetsale::index');
    }

    public function create()
    {
        abort_if(Gate::denies('create_target_sales'), 403);
        
        // Generate Reference (TRGT-001)
        $lastTarget = TargetSale::orderBy('id', 'desc')->first();
        $nextNumber = $lastTarget ? (int) str_replace('TRGT', '', $lastTarget->reference) + 1 : 1;
        $reference = 'TRGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // $departments = Departments::all(); // Tidak dipakai karena global

        return view('targetsale::create', compact('reference'));
    }

    public function store(Request $request)
    {
        // 1. Bersihkan Format Rupiah SEBELUM Validasi
        $rawAmount = $request->target_amount;
        // Hapus semua karakter kecuali angka
        $cleanAmount = (float) preg_replace('/[^0-9]/', '', $rawAmount);

        // Ganti input request dengan angka bersih
        $request->merge(['target_amount' => $cleanAmount]);

        // 2. Validasi Input
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer',
            'target_amount' => 'required|numeric|min:1',
        ]);

        // 3. Cek Duplikasi (Agar tidak ada double data di bulan & tahun yang sama)
        $exists = TargetSale::where('month', $request->month)
                            ->where('year', $request->year)
                            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Target Sales untuk periode bulan & tahun ini sudah ada.'])->withInput();
        }

        // 4. Proses Simpan (BYPASS APPROVAL)
        try {
            DB::transaction(function () use ($request, $cleanAmount) {
                
                // A. Generate Reference
                $lastTarget = TargetSale::orderBy('id', 'desc')->first();
                $nextNumber = $lastTarget ? (int) str_replace('TRGT', '', $lastTarget->reference) + 1 : 1;
                $reference = 'TRGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                // B. Create Data (LANGSUNG APPROVED)
                TargetSale::create([
                    'reference'     => $reference,
                    'month'         => $request->month,
                    'year'          => $request->year,
                    'target_amount' => $cleanAmount,
                    'description'   => $request->description,
                    'status'        => 'Approved', // <--- Ubah dari Pending ke Approved
                    'created_by'    => Auth::id(),
                ]);

                // LOGIKA APPROVAL DIHAPUS SEMENTARA
            });

            return redirect()->route('target_sales.index')
                             ->with('success', 'Target Sales berhasil dibuat.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        abort_if(Gate::denies('show_target_sales'), 403);

        $targetSale = TargetSale::with(['creator'])->findOrFail($id);

        // Approval Logs tidak diperlukan karena bypass
        $approvalLogs = collect(); 

        return view('targetsale::show', compact('targetSale', 'approvalLogs'));
    }

    public function edit($id)
    {
        abort_if(Gate::denies('edit_target_sales'), 403);

        $targetSale = TargetSale::findOrFail($id);

        // Karena bypass, kita izinkan edit meskipun Approved (opsional, atau batasi jika perlu)
        // if (strtolower($targetSale->status) !== 'pending') { ... }

        return view('targetsale::edit', compact('targetSale'));
    }

    public function update(Request $request, $id)
    {
        $targetSale = TargetSale::findOrFail($id);

        // Validasi input manual karena tidak pakai Request Class khusus
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'target_amount' => 'required',
        ]);

        // --- TAMBAHAN: Cek Duplikasi saat Update ---
        // Cek apakah ada data LAIN (selain id ini) yang punya bulan & tahun sama
        $exists = TargetSale::where('month', $request->month)
                            ->where('year', $request->year)
                            ->where('id', '!=', $id) // Kecualikan data ini sendiri
                            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Gagal update: Target Sales untuk periode bulan & tahun ini sudah ada.'])->withInput();
        }
        // -------------------------------------------

        try {
            DB::transaction(function () use ($request, $targetSale) {
                // Bersihkan format rupiah
                $amount = preg_replace('/[^0-9]/', '', $request->target_amount);

                $targetSale->update([
                    'month'         => $request->month,
                    'year'          => $request->year,
                    'target_amount' => (float) $amount,
                    'description'   => $request->description,
                    'status'        => 'Approved', // Tetap Approved setelah edit
                ]);

                // LOGIKA RESET APPROVAL DIHAPUS
            });

            return redirect()->route('target_sales.index')->with('success', 'Target Sales berhasil diperbarui.');

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('delete_target_sales'), 403);
        
        $targetSale = TargetSale::findOrFail($id);

        try {
            DB::transaction(function () use ($targetSale) {
                // Hapus Approval Request terkait jika ada (untuk jaga-jaga data lama)
                /* $approvalRequest = ApprovalRequest::where('requestable_type', 'Target Sales')
                    ->where('requestable_id', $targetSale->id)
                    ->first();
                if ($approvalRequest) { ... }
                */

                $targetSale->delete();
            });

            return redirect()->route('target_sales.index')->with('success', 'Target Sales berhasil dihapus.');

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // Method pending, approve, reject tidak relevan saat bypass, 
    // tapi dibiarkan kosong atau me-return view kosong agar tidak error route.
    public function pending(Request $request) {
        return view('targetsale::pending', ['pendingTargets' => collect([]), 'activeStatus' => 'pending']);
    }
    
    public function approve($id) { return response()->json(['success' => true]); }
    public function reject(Request $request, $id) { return response()->json(['success' => true]); }
    
    public function printAll(Request $request)
    {
        $query = TargetSale::query(); 
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('year', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%");
        }
        
        $targets = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $pdf = PDF::loadView('targetsale::print_all', compact('targets'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('semua-target-sales.pdf');
    }
}