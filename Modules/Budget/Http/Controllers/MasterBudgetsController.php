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
use Modules\Approval\Entities\ApprovalType;
use Modules\Approval\Entities\ApprovalRule;
use Modules\Approval\Entities\ApprovalRuleLevel;
use Modules\Approval\Entities\ApprovalRequestLog;
use Modules\Approval\Entities\ApprovalRuleUser;
use PDF;

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
        'department_id' => [
            'required',
            'integer',
            function ($attribute, $value, $fail) {
                if ($value != 0 && !\DB::table('departments')->where('id', $value)->exists()) {
                    $fail('Departemen tidak valid.');
                }
            },
        ],
        'grandtotal'     => 'required',
        'description'    => 'nullable|string',
    ]);

    $rule = ApprovalRule::whereHas('type', function ($query) {
        $query->where('approval_name', 'Master Budget');
    })->first();

    if (!$rule) {
        // Jika tidak ditemukan, berarti tidak ada aturan yang terhubung dengan tipe tersebut
        return back()->withErrors(['error' => 'Aturan Approval (Approval Rule) untuk "Master Budget" tidak ditemukan atau belum dikonfigurasi.'])->withInput();
    }

    $approvalTypesId = $rule->approval_types_id;

    // Buat nomor budgeting otomatis
    $lastBudget = MasterBudget::orderBy('id', 'desc')->first();
    $nextNumber = $lastBudget ? (int) str_replace('BDGT', '', $lastBudget->no_budgeting) + 1 : 1;
    $noBudgeting = 'BDGT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    try {
        DB::transaction(function () use ($request, $noBudgeting, $approvalTypesId) {

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
            
            // 🔗 Integrasi Approval
            $approval = $this->approvalEngine->createRequest( // Gunakan $this->approvalEngine
                'Master Budget',
                $master->id,
                $approvalTypesId,
                $master->grandtotal,
                auth()->id()
            );
    
            // (Opsional) Update status MasterBudget sesuai hasil dari engine
            if ($master->status !== $approval->status) {
                $master->update(['status' => $approval->status]);
            }
    
            $master->update(['approval_request_id' => $approval->id]);
        });
    
        return redirect()
            ->route('master_budget.index')
            ->with('success', 'Budget berhasil disimpan.');
    }catch (\Throwable $e) { // <-- BLOK CATCH AKAN MENANGKAP ERROR
        
        // Kirim pengguna kembali ke form dengan pesan error
        return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }
}

    public function show($id)
    {
        // Ambil data MasterBudget
        $budget = MasterBudget::with('details', 'department')->findOrFail($id);

        // 2. Ambil Approval Logs sesuai alur yang Anda minta:
        //    Mulai dari ApprovalRequestLog -> cek relasi approvalRequest -> cocokkan ID & Tipe
        $approvalLogs = ApprovalRequestLog::with('approver') // Ambil juga data user approver
            ->whereHas('approvalRequest', function ($query) use ($id) {
                // Filter relasi 'approvalRequest'
                $query->where('requestable_type', 'Master Budget') // Sesuaikan string 'Master Budget'
                    ->where('requestable_id', $id); // Cocokkan dengan ID MasterBudget
            })
            ->get(); // Ambil semua log yang cocok

        // 3. Kirim kedua data ke view
        return view('budget::master_budget.show', compact('budget', 'approvalLogs'));
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

         if (strtolower($masterBudget->status) !== 'pending') {
            return redirect()
                ->route('master_budget.show', $masterBudget->id) // Arahkan kembali ke halaman 'show'
                ->withErrors(['error' => 'Hanya Master Budget dengan status "Pending" yang dapat diedit.']);
        }

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

        try {
            DB::transaction(function () use ($budget) {

                $approvalRequest = ApprovalRequest::where('requestable_type', 'Master Budget')
                                                ->where('requestable_id', $budget->id)
                                                ->first();
                
                if ($approvalRequest) {
                    $approvalRequest->logs()->delete();
                    $approvalRequest->delete(); 
                }

                $budget->details()->delete();

                $budget->delete();
            
            });

            return redirect()->route('master_budget.index')->with('success', 'Budget berhasil dihapus.');

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal menghapus Master Budget: ' . $e->getMessage()]);
        }
    }

    public function approve($id)
    {
        $budget = MasterBudget::findOrFail($id);

        if (! Gate::allows('approve-budget', $budget)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk approve.'], 403);
        }

        try {
            DB::transaction(function () use ($budget) {
                
                // --- Cari Approval Request ---
                $approvalRequest = ApprovalRequest::where('requestable_type', 'Master Budget') 
                                                ->where('requestable_id', $budget->id)
                                                ->first();

                // Jika request-nya ada, proses
                if ($approvalRequest) {
                    
                    // Update log PENGGUNA SAAT INI
                    $log = $approvalRequest->logs()
                        ->where('user_id', Auth::id())
                        ->where('level', $approvalRequest->current_level)   
                        ->where('action', 'assigned')
                        ->first(); 
                    
                    if (!$log) {
                        // Jika user tidak berhak/sudah approve, lempar error
                        throw new \Exception('Anda tidak berwenang memproses permintaan ini di level saat ini.');
                    }
                    
                    $log->update([
                        'action'  => 'approved',
                        'comment' => 'Approved: ' . now()->format('d-m-Y H:i:s'),
                    ]);

                    // Cek apakah level ini sudah selesai
                    $pendingCount = $approvalRequest->logs()
                        ->where('level', $approvalRequest->current_level)
                        ->where('action', 'assigned')
                        ->count();

                    // Jika semua sudah approve di level ini
                    if ($pendingCount == 0) {
                        $nextLevelNumber = $approvalRequest->current_level + 1;

                        $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->approval_rules_id)
                            ->where('level', $nextLevelNumber)
                            ->first();

                            if ($nextLevelData) {
                                // --- MASIH ADA LEVEL BERIKUTNYA ---
                                
                                // 1. Dapatkan ID user pembuat request
                                $requesterId = $approvalRequest->created_by;
                            
                                // 2. Temukan data 'requester' di level BERIKUTNYA
                                $requesterRule = ApprovalRuleUser::where('approval_rule_levels_id', $nextLevelData->id)
                                                     ->where('role', 'requester')
                                                     ->where('user_id', $requesterId)
                                                     ->first();
                            
                                // --- INI PERUBAHANNYA ---
                                if (!$requesterRule) {
                                    // Jika requester tidak ditemukan di alur level 2,
                                    // anggap alur selesai (SAMA SEPERTI TIDAK ADA LEVEL BERIKUTNYA).
                                    $budget->update(['status' => 'Approved']);
                                    $approvalRequest->update(['status' => 'approved']);
                                
                                } else {
                                    // --- JIKA REQUESTER DITEMUKAN, LANJUTKAN ALUR SEQUENCE ---
                                    $targetSequence = $requesterRule->sequence;
                            
                                    $approvers = ApprovalRuleUser::where('approval_rule_levels_id', $nextLevelData->id)
                                                               ->where('role', 'approver')
                                                               ->where('sequence', $targetSequence)
                                                               ->get();
                            
                                    if ($approvers->isEmpty()) {
                                        throw new \Exception("Tidak ada approver yang ditemukan untuk sequence {$targetSequence} di level {$nextLevelNumber}.");
                                    }
                            
                                    foreach ($approvers as $nextUser) {
                                        $approvalRequest->logs()->create([
                                            'level'   => $nextLevelNumber,
                                            'user_id' => $nextUser->user_id,
                                            'action'  => 'assigned',
                                        ]);
                                    }
                                    
                                    $approvalRequest->update(['current_level' => $nextLevelNumber]);
                                }
                            
                            } else {
                            // --- JIKA INI ADALAH LEVEL TERAKHIR ---
                            // Update status MasterBudget
                            $budget->update(['status' => 'Approved']);
                            // Update status ApprovalRequest
                            $approvalRequest->update(['status' => 'approved']);
                        }
                    }
                
                } else {
                    $budget->update(['status' => 'Approved']);
                }
            });

            // Beri respons sukses
            return response()->json(['success' => true, 'message' => 'Budget berhasil disetujui.']);
            
        } catch (\Throwable $e) {
            // Tangkap jika ada error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|min:5',
        ]);
        
        $budget = MasterBudget::findOrFail($id);

        if (! Gate::allows('approve-budget', $budget)) { // Asumsi Gate-nya sama
            return response()->json(['error' => 'Anda tidak punya akses untuk reject.'], 403);
        }

        try {
            DB::transaction(function () use ($budget, $request) {
                
                // --- Cari Approval Request ---
                $approvalRequest = ApprovalRequest::where('requestable_type', 'Master Budget') 
                                                ->where('requestable_id', $budget->id)
                                                ->first();

                // Jika request-nya ada, proses
                if ($approvalRequest) {
                    
                    // Update log PENGGUNA SAAT INI
                    $log = $approvalRequest->logs()
                        ->where('user_id', Auth::id())
                        ->where('level', $approvalRequest->current_level)   
                        ->where('action', 'assigned')
                        ->first(); 
                    
                    if (!$log) {
                        // Jika user tidak berhak/sudah memproses, lempar error
                        throw new \Exception('Anda tidak berwenang memproses permintaan ini di level saat ini.');
                    }
                    
                    // Update log menjadi 'rejected'
                    $log->update([
                        'action'  => 'rejected',
                        'comment' => $request->notes,
                    ]);

                    // Langsung hentikan dan update semua status
                    $budget->update([
                        'status' => 'Rejected',
                        'notes'  => $request->notes
                    ]);
                    $approvalRequest->update(['status' => 'rejected']);
                
                } else {
                    // Fallback jika tidak ada Approval Request
                    $budget->update([
                        'status' => 'Rejected',
                        'notes'  => $request->notes
                    ]);
                }
            }); // Transaksi selesai

            // Beri respons sukses
            return response()->json(['success' => true, 'message' => 'Budget berhasil ditolak.']);
            
        } catch (\Throwable $e) {
            // Tangkap jika ada error
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
            // $budget->notes = $reason;
        }

        $budget->save();

        return response()->json(['success' => true]);
    }

    public function pending(Request $request) // <-- Tambahkan Request
    {
        // (Opsional) Amankan halaman ini
        // abort_if(Gate::denies('access_pending_budgets'), 403); 

        // 1. Ambil status dari URL, default-nya adalah 'pending'
        $activeStatus = $request->query('status', 'pending');

        // 2. Mulai query, muat relasi
        $query = MasterBudget::with(['department', 'approvalRequest.logs.approver']);

        // 3. Terapkan filter JIKA status bukan 'all'
        if ($activeStatus !== 'all') {
            // Gunakan ucfirst() untuk mengubah 'pending' -> 'Pending'
            $query->where('status', ucfirst($activeStatus)); 
        }
        
        // 4. Ambil data
        $allBudgets = $query->orderBy('tgl_penyusunan', 'desc')->get(); // Ubah ke 'desc' agar data terbaru di atas
            
        // 5. Kirim data DAN status aktif ke view
        return view('budget::master_budget.pending', [
            'pendingBudgets' => $allBudgets, // Kirim data yang sudah difilter
            'activeStatus'   => $activeStatus  // Kirim nama status yang sedang aktif
        ]);
    }

    public function printAll(Request $request)
    {
        // 1. Mulai query dasar (sama seperti di query() DataTable)
        $query = MasterBudget::with('department');

        // 2. Tiru filter search dari DataTable
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            
            // Sesuaikan ini agar cocok dengan kolom yang bisa dicari
            $query->where(function($q) use ($search) {
                $q->where('no_budgeting', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhereHas('department', function($dq) use ($search) {
                      $dq->where('department_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        $budgets = $query->orderBy('tgl_penyusunan', 'desc')->get();
        
        // 3. Muat view 'print_all' dengan data
        $pdf = PDF::loadView('budget::master_budget.print_all', compact('budgets'))
        ->setPaper('a4', 'portrait')
        ->setOption('margin-top', 0)
        ->setOption('margin-right', 0)
        ->setOption('margin-bottom', 0)
        ->setOption('margin-left', 0);

        return $pdf->stream('semua-master-budget.pdf');
    }
}