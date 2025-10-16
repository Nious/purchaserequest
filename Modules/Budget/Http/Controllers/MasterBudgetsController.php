<?php

namespace Modules\Budget\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Budget\DataTables\MasterBudgetsDataTable;
use Modules\Budget\Entities\MasterBudget;
use Modules\Budget\Entities\BudgetDetail;
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
            'grandtotal'     => 'required|numeric|min:0',
        ]);

        $lastBudget = MasterBudget::orderBy('id', 'desc')->first();
        $nextNumber = $lastBudget ? (int) str_replace('BDGT', '', $lastBudget->no_budgeting) + 1 : 1;
        $noBudgeting = 'BDGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $noBudgeting) {
            $master = MasterBudget::create([
                'no_budgeting'   => $noBudgeting,
                'tgl_penyusunan' => $request->tgl_penyusunan,
                'bulan'          => $request->bulan,
                'periode_awal'   => $request->periode_awal,
                'periode_akhir'  => $request->periode_akhir,
                'department_id'  => $request->department_id,
                'description'    => $request->description,
                'grandtotal'     => (float) str_replace(',', '', $request->grandtotal),
                'approval_status'=> 'Pending',
            ]);

            // detail budget
            if ($request->filled('category_id')) {
                foreach ($request->category_id as $i => $catId) {
                    BudgetDetail::create([
                        'master_budget_id' => $master->id,
                        'category_id'      => $catId,
                        'category_name'    => $request->category_name[$i] ?? null,
                        'budget'           => (float) str_replace(',', '', $request->budget[$i] ?? 0),
                    ]);
                }
            }

            // 🔹 Buat approval request otomatis
            $approvalTypeId = 1; // ID sesuai master approval_types
            $this->approvalEngine->createRequest(
                MasterBudget::class,
                $master->id,
                $approvalTypeId,
                $master->grandtotal,
                Auth::id()
            );
        });

        return redirect()->route('master_budget.index')->with('success', 'Budget berhasil disimpan dan menunggu approval.');
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
        $masterBudget = MasterBudget::findOrFail($id);

        DB::transaction(function () use ($request, $masterBudget) {
            $masterBudget->update([
                'tgl_penyusunan' => $request->tgl_penyusunan,
                'bulan'          => $request->bulan,
                'periode_awal'   => $request->periode_awal,
                'periode_akhir'  => $request->periode_akhir,
                'department_id'  => $request->department_id,
                'description'    => $request->description,
                'grandtotal'     => (float) str_replace(',', '', $request->grandtotal),
            ]);

            $masterBudget->details()->delete();

            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    BudgetDetail::create([
                        'master_budget_id' => $masterBudget->id,
                        'category_id'      => $item['category_id'],
                        'category_name'    => $item['category_name'] ?? null,
                        'budget'           => (float) str_replace(',', '', $item['budget'] ?? 0),
                    ]);
                }
            }
        });

        return redirect()->route('master_budget.index')->with('success', 'Budget berhasil diupdate.');
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
            abort(403, 'Anda tidak punya akses untuk approve.');
        }

        $budget->update(['approval_status' => 'Approved']);
        return redirect()->route('master_budget.index')->with('success', 'Budget berhasil disetujui.');
    }

    public function reject($id)
    {
        $budget = MasterBudget::findOrFail($id);

        if (! Gate::allows('approve-budget', $budget)) {
            abort(403, 'Anda tidak punya akses untuk reject.');
        }

        $budget->update(['approval_status' => 'Rejected']);
        return redirect()->route('master_budget.index')->with('warning', 'Budget ditolak.');
    }
}
