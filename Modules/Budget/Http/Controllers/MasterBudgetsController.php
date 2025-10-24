<?php

namespace Modules\Budget\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Budget\DataTables\MasterBudgetsDataTable;
use Modules\Budget\Entities\MasterBudget;
use Modules\Budget\Entities\BudgetDetail;
use Modules\Approval\Entities\ApprovalRequest;
use Modules\Approval\Services\ApprovalEngine;
use Illuminate\Support\Facades\Auth;

class MasterBudgetsController extends Controller
{
    protected $approvalEngine;

    public function __construct(ApprovalEngine $approvalEngine)
    {
        $this->approvalEngine = $approvalEngine;
    }

    public function index(MasterBudgetsDataTable $dataTable)
    {
        return $dataTable->render('budget::master_budget.index');
    }

    public function create()
    {
        $lastBudget = MasterBudget::orderBy('id', 'desc')->first();
        $nextNumber = $lastBudget ? (int) str_replace('BDGT', '', $lastBudget->no_budgeting) + 1 : 1;
        $noBudgeting = 'BDGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $departments = \Modules\Department\Entities\Departments::all();
        $categories  = \Modules\Product\Entities\Category::all();

        return view('budget::master_budget.create', compact('categories', 'departments', 'noBudgeting'));
    }

    public function store(Request $request)
    {
        $request->validate([
        'tgl_penyusunan' => 'required|date',
        'bulan'          => 'required',
        'periode_awal'   => 'required|date',
        'periode_akhir'  => 'required|date',
        'department_id'  => 'required|exists:departments,id',
        'grandtotal'     => 'required',
        'description'    => 'nullable|string',
    ]);

    // Buat nomor budgeting otomatis
    $lastBudget = MasterBudget::orderBy('id', 'desc')->first();
    $nextNumber = $lastBudget ? (int) str_replace('BDGT', '', $lastBudget->no_budgeting) + 1 : 1;
    $noBudgeting = 'BDGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    DB::transaction(function () use ($request, $noBudgeting) {

        // Pastikan nilai grandtotal numerik (hapus format "Rp" atau koma)
        $grandtotal = preg_replace('/[^0-9.]/', '', $request->grandtotal);

        $master = MasterBudget::create([
            'no_budgeting'    => $noBudgeting,
            'tgl_penyusunan'  => $request->tgl_penyusunan,
            'bulan'           => $request->bulan,
            'periode_awal'    => $request->periode_awal,
            'periode_akhir'   => $request->periode_akhir,
            'department_id'   => $request->department_id,
            'description'     => $request->description,
            'grandtotal'      => (float) $grandtotal,
            'status'          => 'Pending',
            'used_amount'     => 0,
            'reserved_amount' => 0,
        ]);

        // Simpan detail
        if ($request->filled('items')) {
            foreach ($request->items as $item) {
                BudgetDetail::create([
                    'master_budget_id' => $master->id,
                    'category_id'      => $item['category_id'] ?? null,
                    'category_name'    => $item['category_name'] ?? null,
                    'budget'           => (float) preg_replace('/[^0-9.]/', '', $item['budget'] ?? 0),
                ]);
            }
        }
        
        // // 🔗 Integrasi Approval
        // $approvalTypesId = config('approval.types.master_budget'); // bisa hardcode dulu misal 1
        // $approval = app(\Modules\Approval\Services\ApprovalEngine::class)
        //     ->createRequest(
        //         MasterBudget::class,
        //         $master->id,
        //         $approvalTypesId,
        //         $master->grandtotal,
        //         auth()->id()
        //     );

        // $master->update(['approval_request_id' => $approval->id]);
    });

    

    return redirect()
        ->route('master_budget.index')
        ->with('success', 'Budget berhasil disimpan.');
}

    public function show($id)
    {
        $budget = MasterBudget::with('details', 'department')->findOrFail($id);
        return view('budget::master_budget.show', compact('budget'));
    }

    public function edit($id)
    {
        $masterBudget = MasterBudget::with('details')->findOrFail($id);
        $departments  = \Modules\Department\Entities\Departments::all();
        $categories   = \Modules\Product\Entities\Category::all();

        return view('budget::master_budget.edit', compact('masterBudget', 'departments', 'categories'));
    }

    public function update(Request $request, $id)
    {
         $masterBudget = MasterBudget::with('details')->findOrFail($id);

        // Validasi input
        $request->validate([
            'tgl_penyusunan' => 'required|date',
            'bulan'          => 'required',
            'periode_awal'   => 'required|date',
            'periode_akhir'  => 'required|date',
            'department_id'  => 'required|exists:departments,id',
            'grandtotal'     => 'required',
            'description'    => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $masterBudget) {
            // Bersihkan format angka (hilangkan Rp dan tanda koma)
            $grandtotal = preg_replace('/[^0-9.]/', '', $request->grandtotal);

            // Update data master tanpa mengubah approval_status, used_amount, dan reserved_amount
            $masterBudget->update([
                'tgl_penyusunan' => $request->tgl_penyusunan,
                'bulan'          => $request->bulan,
                'periode_awal'   => $request->periode_awal,
                'periode_akhir'  => $request->periode_akhir,
                'department_id'  => $request->department_id,
                'description'    => $request->description,
                'grandtotal'     => (float) $grandtotal,
            ]);

            // 🔄 Update detail — hapus dulu lalu isi ulang, bisa juga pakai upsert kalau mau lebih efisien
            $masterBudget->details()->delete();

            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    BudgetDetail::create([
                        'master_budget_id' => $masterBudget->id,
                        'category_id'      => $item['category_id'] ?? null,
                        'category_name'    => $item['category_name'] ?? null,
                        'budget'           => (float) preg_replace('/[^0-9.]/', '', $item['budget'] ?? 0),
                    ]);
                }
            }
        });

        return redirect()
            ->route('master_budget.index')
            ->with('success', 'Data master budget berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $budget = MasterBudget::findOrFail($id);
        $budget->details()->delete();
        $budget->delete();

        return redirect()->route('master_budget.index')->with('success', 'Budget berhasil dihapus.');
    }

    public function approve($id)
    {
        $budget = MasterBudget::findOrFail($id);

        if (! Gate::allows('approve-budget', $budget)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk approve.'], 403);
        }

        $budget->update(['status' => 'Approved']);

        return response()->json(['success' => true, 'message' => 'Budget berhasil disetujui.']);
    }

    public function reject(Request $request, $id)
    {
        $budget = MasterBudget::findOrFail($id);

        if (! Gate::allows('approve-budget', $budget)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk reject.'], 403);
        }

        $budget->update([
            'status' => 'Rejected',
            'notes' => $request->notes
        ]);

        return response()->json(['success' => true, 'message' => 'Budget ditolak.']);
    }

    public function updateStatus(Request $request, $id)
    {
        $budget = MasterBudget::findOrFail($id);

        $status = $request->input('status');
        $reason = $request->input('reason');

        // Update status
        $budget->status = $status;

        // Jika rejected, simpan alasan di kolom notes
        if ($status === 'rejected') {
            $budget->notes = $reason;
        }

        $budget->save();

        return response()->json(['success' => true]);
    }



}