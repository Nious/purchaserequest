<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Gloudemans\Shoppingcart\Facades\Cart;
use Modules\Purchase\DataTables\PurchaseDataTable;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchaseDetail;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\Purchase\Http\Requests\StorePurchaseRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseRequest;
use Modules\Product\Entities\Product;
use Modules\People\Entities\Supplier;
use Modules\Budget\Entities\MasterBudget;
use Modules\Department\Entities\Department;
use Carbon\Carbon;
use Modules\Approval\Services\ApprovalEngine;
use Illuminate\Support\Facades\Auth;
use Modules\Approval\Entities\ApprovalRule;
use Modules\Approval\Entities\ApprovalRequest;
use Modules\Approval\Entities\ApprovalRuleLevel;
use Modules\Approval\Entities\ApprovalRequestLog;
use Modules\Approval\Entities\ApprovalRuleUser;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    // protected $approvalEngine;

    // public function __construct(ApprovalEngine $approvalEngine)
    // {
    //     $this->approvalEngine = $approvalEngine;
    // }

    /**
     * Menampilkan daftar pembelian.
     */
    public function index(PurchaseDataTable $dataTable)
    {
        abort_if(Gate::denies('access_purchases'), 403);

        return $dataTable->render('purchase::index');
    }

    /**
     * Form untuk membuat purchase baru.
     */
    public function create()
    {
        // abort_if(Gate::denies('create_purchases'), 403);

        Cart::instance('purchase')->destroy();

        $prNumber = Purchase::generatePRNumber();
        $budgets = MasterBudget::all();


        return view('purchase::create', compact('prNumber', 'budgets'));
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $purchase = Purchase::findOrFail($id);

        // contoh otorisasi (bisa kamu sesuaikan)
        if (!auth()->user()->can('approve_purchases')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $purchase->status = ucfirst($request->status);
        $purchase->save();

        return response()->json(['success' => true, 'status' => $purchase->status]);
    }

    // public function approve($id)
    // {
    //     $purchase = Purchase::findOrFail($id);

    //     // 1. Cek otorisasi (sesuaikan nama Gate Anda)
    //     if (! Gate::allows('approve_purchases', $purchase)) {
    //         return response()->json(['error' => 'Anda tidak punya akses untuk approve.'], 403);
    //     }

    //     try {
    //         // 2. Gunakan Transaksi Database
    //         DB::transaction(function () use ($purchase) {
                
    //             // --- Cari Approval Request ---
    //             $approvalRequest = ApprovalRequest::where('requestable_type', 'Purchase Request') // <-- GANTI STRING
    //                                               ->where('requestable_id', $purchase->id)
    //                                               ->first();

    //             // 3. Jika request-nya ada, proses
    //             if ($approvalRequest) {
                    
    //                 // 3a. Update log PENGGUNA SAAT INI
    //                 $log = $approvalRequest->logs()
    //                     ->where('user_id', Auth::id())
    //                     ->where('level', $approvalRequest->current_level)   
    //                     ->where('action', 'assigned')
    //                     ->first(); 
                    
    //                 if (!$log) {
    //                     throw new \Exception('Anda tidak berwenang memproses permintaan ini di level saat ini.');
    //                 }
                    
    //                 $log->update([
    //                     'action'  => 'approved',
    //                     'comment' => 'Approved: ' . now()->format('d-m-Y H:i:s'),
    //                 ]);

    //                 // 3b. Cek apakah level ini sudah selesai
    //                 $pendingCount = $approvalRequest->logs()
    //                     ->where('level', $approvalRequest->current_level)
    //                     ->where('action', 'assigned')
    //                     ->count();

    //                 // 3c. Jika semua sudah approve di level ini
    //                 if ($pendingCount == 0) {
    //                     $nextLevelNumber = $approvalRequest->current_level + 1;

    //                     $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->approval_rules_id)
    //                         ->where('level', $nextLevelNumber)
    //                         ->first();

    //                     if ($nextLevelData) {
    //                         // --- MASIH ADA LEVEL BERIKUTNYA (LOGIKA BARU) ---
                            
    //                         // 1. Dapatkan ID user pembuat request
    //                         $requesterId = $approvalRequest->created_by;

    //                         // 2. Temukan data 'requester' di level BERIKUTNYA
    //                         $requesterRule = ApprovalRuleUser::where('approval_rule_levels_id', $nextLevelData->id)
    //                                             ->where('role', 'requester')
    //                                             ->where('user_id', $requesterId)
    //                                             ->first();

    //                         // 3. Cek jika requester ditemukan di alur level 2
    //                         if (!$requesterRule) {
    //                             // Jika requester tidak ditemukan, anggap alur selesai
    //                             $purchase->update(['status' => 'Approved']);
    //                             $approvalRequest->update(['status' => 'approved']);
    //                             // ... (Tambahkan logika update budget di sini juga)
    //                             if ($purchase->master_budget_id) {
    //                                 $budget = MasterBudget::find($purchase->master_budget_id);
    //                                 if ($budget) {
    //                                     // $budget->reserved_amount -= $purchase->total_amount; // Hati-hati double-counting jika pakai 'reserved'
    //                                     $budget->used_amount += $purchase->total_amount;
    //                                     $budget->save();
    //                                 }
    //                             }
                            
    //                         } else {
    //                             // --- JIKA REQUESTER DITEMUKAN, LANJUTKAN ALUR SEQUENCE ---
    //                             $targetSequence = $requesterRule->sequence;

    //                             $approvers = ApprovalRuleUser::where('approval_rule_levels_id', $nextLevelData->id)
    //                                                     ->where('role', 'approver')
    //                                                     ->where('sequence', $targetSequence)
    //                                                     ->get();

    //                             if ($approvers->isEmpty()) {
    //                                 throw new \Exception("Tidak ada approver yang ditemukan untuk sequence {$targetSequence} di level {$nextLevelNumber}.");
    //                             }

    //                             // Buat log tugas hanya untuk approver yang ditemukan
    //                             foreach ($approvers as $nextUser) {
    //                                 $approvalRequest->logs()->create([
    //                                     'level'   => $nextLevelNumber,
    //                                     'user_id' => $nextUser->user_id,
    //                                     'action'  => 'assigned',
    //                                 ]);
    //                             }
                                
    //                             // Naikkan level request
    //                             $approvalRequest->update(['current_level' => $nextLevelNumber]);
    //                         }
    //                         // --- BATAS PERUBAHAN ---

    //                     } else {
    //                         // --- INI ADALAH LEVEL TERAKHIR (TIDAK ADA $nextLevelData) ---
    //                         $purchase->update(['status' => 'Approved']);
    //                         $approvalRequest->update(['status' => 'approved']);
                            
    //                         // --- (OPSIONAL) UPDATE BUDGET SETELAH APPROVE ---
    //                         if ($purchase->master_budget_id) {
    //                             $budget = MasterBudget::find($purchase->master_budget_id);
    //                             if ($budget) {
    //                                 // $budget->reserved_amount -= $purchase->total_amount;
    //                                 $budget->used_amount += $purchase->total_amount;
    //                                 $budget->save();
    //                             }
    //                         }
    //                     }
    //                 }
                
    //             } else {
    //                 // Fallback jika tidak ada Approval Request
    //                 $purchase->update(['status' => 'Approved']);
    //             }
    //         }); // Transaksi selesai

    //         // 4. Beri respons sukses
    //         return response()->json(['success' => true, 'message' => 'Purchase Request berhasil disetujui.']);
            
    //     } catch (\Throwable $e) {
    //         // Tangkap jika ada error
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function approve($id)
    {
        $purchase = Purchase::findOrFail($id);

        if (! Gate::allows('approve_purchases', $purchase)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk approve.'], 403);
        }

        try {
            DB::transaction(function () use ($purchase) {
                
                // --- Cari Approval Request TERKAIT DENGAN PURCHASE INI (Apapun Type-nya) ---
                $approvalRequest = ApprovalRequest::where('requestable_id', $purchase->id)
                                        // Cari berdasarkan ID Purchase, Type bisa 'Purchase Request' atau 'Over Budget'
                                        ->whereIn('requestable_type', ['Purchase Request', 'Over Budget']) 
                                        ->first();

                if (!$approvalRequest) {
                     // Fallback jika tidak ada Approval Request (seharusnya tidak terjadi jika store benar)
                     $budgetSnapshot = $this->getBudgetSnapshot($purchase->department_id, $purchase->date);

                    $purchase->update([
                        'status' => 'approved',
                        'master_budget_value' => $budgetSnapshot->total,
                        'master_budget_remaining' => $budgetSnapshot->remaining - $purchase->total_amount
                    ]);
                     // Mungkin tambahkan logika update budget dasar di sini jika diperlukan
                     Log::warning("No ApprovalRequest found for Purchase ID: {$purchase->id} during approval.");
                     return; // Keluar dari transaksi
                }

                // --- Proses Approval Log (Sama seperti sebelumnya) ---
                $log = $approvalRequest->logs()
                    ->where('user_id', Auth::id())
                    ->where('level', $approvalRequest->current_level)  
                    ->where('action', 'assigned')
                    ->first(); 
                
                if (!$log) {
                    throw new \Exception('Anda tidak berwenang memproses permintaan ini di level saat ini.');
                }
                
                $log->update([
                    'action'  => 'approved',
                    'comment' => 'Approved: ' . now()->format('d-m-Y H:i:s'),
                ]);

                // --- Cek Penyelesaian Level (Sama seperti sebelumnya) ---
                $pendingCount = $approvalRequest->logs()
                    ->where('level', $approvalRequest->current_level)
                    ->where('action', 'assigned')
                    ->count();

                // --- Jika Level Selesai ---
                if ($pendingCount == 0) {
                    $nextLevelNumber = $approvalRequest->current_level + 1;
                    $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->approval_rules_id)
                        ->where('level', $nextLevelNumber)
                        ->first();

                        if ($nextLevelData) {
                            // --- MASIH ADA LEVEL BERIKUTNYA (LOGIKA BARU) ---
                            
                            // 1. Dapatkan ID user pembuat request
                            $requesterId = $approvalRequest->created_by;

                            // 2. Temukan data 'requester' di level BERIKUTNYA
                            $requesterRule = ApprovalRuleUser::where('approval_rule_levels_id', $nextLevelData->id)
                                                ->where('role', 'requester')
                                                ->where('user_id', $requesterId)
                                                ->first();

                            // 3. Cek jika requester ditemukan di alur level 2
                            if (!$requesterRule) {
                                // Jika requester tidak ditemukan, anggap alur selesai
                                $budgetSnapshot = $this->getBudgetSnapshot($purchase->department_id, $purchase->date);

                                $purchase->update([
                                    'status' => 'approved',
                                    'master_budget_value' => $budgetSnapshot->total,
                                    'master_budget_remaining' => $budgetSnapshot->remaining - $purchase->total_amount
                                ]);
                                $approvalRequest->update(['status' => 'approved']);
                                // ... (Tambahkan logika update budget di sini juga)
                                if ($purchase->master_budget_id) {
                                    $budget = MasterBudget::find($purchase->master_budget_id);
                                    if ($budget) {
                                        // $budget->reserved_amount -= $purchase->total_amount; // Hati-hati double-counting jika pakai 'reserved'
                                        $budget->used_amount += $purchase->total_amount;
                                        $budget->save();
                                    }
                                }
                            
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

                                // Buat log tugas hanya untuk approver yang ditemukan
                                foreach ($approvers as $nextUser) {
                                    $approvalRequest->logs()->create([
                                        'level'   => $nextLevelNumber,
                                        'user_id' => $nextUser->user_id,
                                        'action'  => 'assigned',
                                    ]);
                                }
                                
                                // Naikkan level request
                                $approvalRequest->update(['current_level' => $nextLevelNumber]);
                            }
                            // --- BATAS PERUBAHAN ---

                        } else {
                        // --- INI ADALAH LEVEL APPROVAL TERAKHIR ---
                        
                        $budgetSnapshot = $this->getBudgetSnapshot($purchase->department_id, $purchase->date);

                        $purchase->update([
                            'status' => 'approved',
                            'master_budget_value' => $budgetSnapshot->total,
                            'master_budget_remaining' => $budgetSnapshot->remaining - $purchase->total_amount
                        ]);
                        $approvalRequest->update(['status' => 'approved']);
                        
                        // --- 2. UPDATE BUDGET BERDASARKAN TIPE APPROVAL ---
                        $purchaseDate = Carbon::parse($purchase->date);
                        $month = $purchaseDate->month;
                        $year = $purchaseDate->year;

                        if ($approvalRequest->requestable_type === 'Over Budget') {
                            // === Logika Budget untuk Over Budget ===

                            // a. Update Budget Departemen (+ used_amount total)
                            $budgetDepartemen = MasterBudget::where('department_id', $purchase->department_id)
                                ->where('bulan', $month)
                                ->whereYear('periode_awal', $year)
                                ->where('status', 'Approved')
                                ->first();
                            
                            if ($budgetDepartemen) {
                                $overageAmount = abs($purchase->master_budget_remaining ?? 0); 
                                $amountUsedFromDept = $purchase->total_amount - $overageAmount;
                                $budgetDepartemen->used_amount += $amountUsedFromDept;
                                $budgetDepartemen->save();
                            } else {
                                throw new \Exception("Budget departemen untuk $month/$year tidak ditemukan saat final approval Over Budget.");
                            }

                            // b. Update Budget Non-Departemen (+ used_amount overage)
                            $nonDeptBudget = MasterBudget::where('department_id', 0) // Asumsi ID 0 untuk non-dept
                                ->where('bulan', $month)
                                ->whereYear('periode_awal', $year)
                                ->where('status', 'approved')
                                ->first();
                            
                            if ($nonDeptBudget) {
                                // Ambil nilai negatif dari remaining_budget, jadikan positif
                                $overageAmount = abs($purchase->master_budget_remaining ?? 0); 
                                $nonDeptBudget->used_amount += $overageAmount;
                                $nonDeptBudget->save();
                            } else {
                                throw new \Exception("Budget Non-Departemen untuk $month/$year tidak ditemukan saat final approval Over Budget.");
                            }

                        } else { // Berarti tipenya 'Purchase Request' (Alur Normal)
                            // === Logika Budget untuk Purchase Request (Normal) ===

                            $budgetDepartemen = MasterBudget::find($purchase->master_budget_id); // Bisa pakai ID yg disimpan
                            
                            if ($budgetDepartemen) {
                                // Pindahkan dari reserved ke used
                                // $budgetDepartemen->reserved_amount -= $purchase->total_amount;
                                $budgetDepartemen->used_amount += $purchase->total_amount;
                                
                                // Pastikan reserved tidak negatif (jaga-jaga)
                                // if($budgetDepartemen->reserved_amount < 0) $budgetDepartemen->reserved_amount = 0;

                                $budgetDepartemen->save();
                            } else {
                                // Ini aneh jika terjadi di alur normal, karena ID seharusnya ada
                                Log::warning("MasterBudget ID: {$purchase->master_budget_id} not found for Purchase ID: {$purchase->id} during normal approval.");
                                // throw new \Exception("Budget departemen terkait tidak ditemukan saat final approval."); 
                                // Mungkin tidak perlu throw error, cukup log
                            }
                        } 
                    } // End if final level
                } // End if pendingCount == 0
                
            }); // Transaksi selesai

            return response()->json(['success' => true, 'message' => 'Purchase Request berhasil disetujui.']);
            
        } catch (\Throwable $e) {
            Log::error("Error approving purchase ID {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString()); // Tambahkan logging
            return response()->json(['error' => 'Gagal menyetujui: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menolak Purchase Request.
     */
    public function reject(Request $request, $id)
{
    $request->validate([
        'notes' => 'required|string|min:5',
    ]);
    
    $purchase = Purchase::findOrFail($id);

    if (! Gate::allows('approve_purchases', $purchase)) {
        return response()->json(['error' => 'Anda tidak punya akses untuk reject.'], 403);
    }

    try {
        DB::transaction(function () use ($purchase, $request) {
            
            // --- 1. Cari Approval Request TERKAIT (Bisa Normal atau Over Budget) ---
            $approvalRequest = ApprovalRequest::where('requestable_id', $purchase->id)
                ->whereIn('requestable_type', ['Purchase Request', 'Over Budget']) // Cari salah satu
                ->first();

            if ($approvalRequest) {
                
                // --- 2. Update Log Approval ---
                $log = $approvalRequest->logs()
                    ->where('user_id', Auth::id())
                    ->where('level', $approvalRequest->current_level)   
                    ->where('action', 'assigned')
                    ->first(); 
                
                if (!$log) {
                    throw new \Exception('Anda tidak berwenang memproses permintaan ini di level saat ini.');
                }
                
                $log->update([
                    'action'  => 'rejected',
                    'comment' => $request->notes,
                ]);

                $snapshot = $this->getBudgetSnapshot($purchase->department_id, $purchase->date, $purchase->total_amount);

                $purchase->update([
                    'status' => 'rejected',
                    // 'note'   => $request->notes,
                    'master_budget_value' => $snapshot->total,
                    'master_budget_remaining' => $snapshot->remaining
                ]);
                $approvalRequest->update(['status' => 'rejected']);
                
                // --- 4. KEMBALIKAN BUDGET BERDASARKAN TIPE APPROVAL ---
                $purchaseDate = Carbon::parse($purchase->date);
                $month = $purchaseDate->month;
                $year = $purchaseDate->year;

                if ($approvalRequest->requestable_type === 'Over Budget') {
                    // === Logika Rollback untuk Over Budget ===
                    $overageAmount = abs($purchase->master_budget_remaining ?? 0); 
                    $amountUsedFromDept = $purchase->total_amount - $overageAmount;

                    // a. Kembalikan budget Departemen
                    $budgetDepartemen = MasterBudget::where('department_id', $purchase->department_id)
                        ->where('bulan', $month)->whereYear('periode_awal', $year)->where('status', 'Approved')
                        ->first();
                    if ($budgetDepartemen) {
                        // (Asumsi 'Over Budget' langsung masuk 'used_amount' saat store, bukan 'reserved')
                        // Ganti 'used_amount' ke 'reserved_amount' jika Anda pakai 'reserved' di 'store'
                        $budgetDepartemen->used_amount -= $amountUsedFromDept;
                        if($budgetDepartemen->used_amount < 0) $budgetDepartemen->used_amount = 0;
                        $budgetDepartemen->save();
                    }

                    // b. Kembalikan budget Non-Departemen
                    $nonDeptBudget = MasterBudget::where('department_id', 0) // Asumsi ID 0
                        ->where('bulan', $month)->whereYear('periode_awal', $year)->where('status', 'Approved')
                        ->first();
                    if ($nonDeptBudget) {
                        $nonDeptBudget->used_amount -= $overageAmount;
                        if($nonDeptBudget->used_amount < 0) $nonDeptBudget->used_amount = 0;
                        $nonDeptBudget->save();
                    }

                } else { // Berarti tipenya 'Purchase Request' (Alur Normal)
                    // === Logika Rollback untuk Normal ===
                    if ($purchase->master_budget_id) {
                        $budget = MasterBudget::find($purchase->master_budget_id);
                        if ($budget) {
                            // Kembalikan 'reserved_amount' (karena 'store' Anda menambah 'reserved_amount')
                            $budget->reserved_amount -= $purchase->total_amount; 
                            if ($budget->reserved_amount < 0) $budget->reserved_amount = 0;
                            $budget->save();
                        }
                    }
                }
            
            } else {
                $snapshot = $this->getBudgetSnapshot($purchase->department_id, $purchase->date, $purchase->total_amount);

                $purchase->update([
                    'status' => 'rejected',
                    // 'note'   => $request->notes,
                    'master_budget_value' => $snapshot->total,
                    'master_budget_remaining' => $snapshot->remaining
                ]);
            }
        }); // Transaksi selesai

        return response()->json(['success' => true, 'message' => 'Purchase Request berhasil ditolak.']);
        
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * Simpan data purchase baru.
     */
    // public function store(StorePurchaseRequest $request)
    // {
    //     // Cek Gate Anda
    //     abort_if(Gate::denies('create_purchases'), 403);
        
    //     try {

    //         $rule = ApprovalRule::whereHas('type', function ($query) {
    //             $query->where('approval_name', 'Purchase Request'); // Sesuaikan nama jika perlu
    //         })->where('is_active', true)->first();

    //         if (!$rule) {
    //             throw new \Exception('Aturan Approval (Approval Rule) untuk "Purchase Request" tidak ditemukan.');
    //         }
    //         $approvalTypesId = $rule->approval_types_id;
            
    //         $approvalEngine = app(ApprovalEngine::class);

    //         $purchase = DB::transaction(function () use ($request, $approvalTypesId, $approvalEngine) {

    //             $total_amount     = $request->total_amount ?? 0;
    //             $budget_value     = $request->master_budget_value ?? 0;
    //             $remaining_budget = $request->master_budget_remaining ?? 0;
    //             $paid_amount      = $request->paid_amount ?? 0;
    //             $due_amount       = $total_amount - $paid_amount;
                
    //             $payment_status = 'Unpaid';
    //             if ($due_amount > 0 && $due_amount < $total_amount) {
    //                 $payment_status = 'Partial';
    //             } elseif ($due_amount <= 0) {
    //                 $payment_status = 'Paid';
    //             }

    //             $budget = null;
    //             if ($request->department_id && $request->date) {
    //                 $purchaseDate = Carbon::parse($request->date);
    //                 $month = $purchaseDate->month;
    //                 $year = $purchaseDate->year;

    //                 $budget = MasterBudget::where('department_id', $request->department_id)
    //                                     ->where('bulan', $month)
    //                                     ->whereYear('periode_awal', $year)
    //                                     ->where('status', 'Approved') // Pastikan 'Approved'
    //                                     ->first();
    //             }

    //             $newPurchase = Purchase::create([
    //                 'reference'        => Purchase::generatePRNumber(),
    //                 'date'             => $request->date ?? now(),
    //                 'supplier_id'      => $request->supplier_id ?? null,
    //                 'users_id'         => $request->users_id ?? auth()->id(),
    //                 'department_id'    => $request->department_id ?? optional(auth()->user())->department_id,
    //                 'master_budget_id' => $budget?->id, // Ambil ID dari budget yang ditemukan
    //                 'total_amount'     => $total_amount,
    //                 'master_budget_value' => $budget_value,
    //                 'master_budget_remaining' => $remaining_budget,
    //                 'due_amount'       => $due_amount,
    //                 'status'           => 'Pending', // Status awal
    //                 'payment_status'   => $payment_status,
    //                 'note'             => $request->note ?? '',
    //                 'tax_percentage'        => $request->tax_percentage ?? 0,
    //                 'discount_percentage'   => $request->discount_percentage ?? 0,
    //                 'shipping_amount'       => $request->shipping_amount ?? 0,
    //                 'payment_method'        => $request->payment_method ?? 'Cash',
    //                 'tax_amount'            => Cart::instance('purchase')->tax() ?? 0,
    //                 'discount_amount'       => Cart::instance('purchase')->discount() ?? 0,
    //             ]);

    //             foreach (Cart::instance('purchase')->content() as $cart_item) {
    //                 PurchaseDetail::create([
    //                     'purchase_id'             => $newPurchase->id,
    //                     'product_id'              => $cart_item->id,
    //                     'product_name'            => $cart_item->name,
    //                     'product_code'            => $cart_item->options->code,
    //                     'product_unit'            => $cart_item->options->unit ?? '-', // ✅ PERBAIKAN BUG
    //                     'quantity'                => $cart_item->qty,
    //                     'price'                   => $cart_item->price,
    //                     'unit_price'              => $cart_item->options->unit_price,
    //                     'sub_total'               => $cart_item->options->sub_total,
    //                     'product_discount_amount' => $cart_item->options->product_discount ?? 0,
    //                     'product_discount_type'   => $cart_item->options->product_discount_type ?? 'fixed',
    //                     'product_tax_amount'      => $cart_item->options->product_tax ?? 0,
    //                 ]);
    //             }
                
    //             if ($budget) {
    //                 // $budget->reserved_amount += $total_amount; 
    //                 $budget->save();
    //             }

    //             $approvalRequest = $approvalEngine->createRequest( // <-- Gunakan variabel $approvalEngine
    //                 'Purchase Request',   
    //                 $newPurchase->id,      
    //                 $approvalTypesId,
    //                 $newPurchase->total_amount,
    //                 Auth::id()
    //             );

    //             // --- Sinkronisasi Status ---
    //             if ($newPurchase->status !== ucfirst($approvalRequest->status)) {
    //                 $newPurchase->update(['status' => ucfirst($approvalRequest->status)]); 
    //             }
                
    //             Cart::instance('purchase')->destroy();
                
    //             return $newPurchase;
    //         }); 

    //         toast('Purchase Request Created Successfully!', 'success');
    //         return redirect()->route('purchases.index');

    //     } catch (\Throwable $e) {
    //         return back()->withErrors(['error' => 'Gagal membuat Purchase Request: ' . $e->getMessage()])->withInput();
    //     }
    // }
    public function store(StorePurchaseRequest $request)
    {
        // Cek Gate Anda
        abort_if(Gate::denies('create_purchases'), 403);
        
        try {
            $purchase = DB::transaction(function () use ($request) {

                // --- 1. Persiapan Data (Common) ---
                $total_amount     = $request->total_amount ?? 0;
                $budget_value     = $request->master_budget_value ?? 0;
                $remaining_budget = $request->master_budget_remaining ?? 0; // Ini akan negatif jika over budget
                $paid_amount      = $request->paid_amount ?? 0;
                $due_amount       = $total_amount - $paid_amount;
                
                $payment_status = 'Unpaid';
                if ($due_amount > 0 && $due_amount < $total_amount) {
                    $payment_status = 'Partial';
                } elseif ($due_amount <= 0) {
                    $payment_status = 'Paid';
                }

                $budgetDepartemen = null;
                $purchaseDate = Carbon::parse($request->date);
                $month = $purchaseDate->month;
                $year = $purchaseDate->year;

                if ($request->department_id && $request->date) {
                    $budgetDepartemen = MasterBudget::where('department_id', $request->department_id)
                                ->where('bulan', $month)
                                ->whereYear('periode_awal', $year)
                                ->where('status', 'Approved')
                                ->first();
                }

                // --- 2. Buat Data Purchase (Common) ---
                $newPurchase = Purchase::create([
                    'reference'         => Purchase::generatePRNumber(),
                    'date'              => $request->date ?? now(),
                    'supplier_id'       => $request->supplier_id ?? null,
                    'users_id'          => $request->users_id ?? auth()->id(),
                    'department_id'     => $request->department_id ?? optional(auth()->user())->department_id,
                    'master_budget_id'  => $budgetDepartemen?->id,
                    'total_amount'      => $total_amount,
                    'master_budget_value' => $budget_value,
                    'master_budget_remaining' => $remaining_budget, // Simpan nilai (bisa negatif)
                    'due_amount'        => $due_amount,
                    'status'            => 'Pending', // Status default, akan di-update oleh alur approval
                    'payment_status'    => $payment_status,
                    'note'              => $request->note ?? '',
                    'tax_percentage'    => $request->tax_percentage ?? 0,
                    'discount_percentage' => $request->discount_percentage ?? 0,
                    'shipping_amount'   => $request->shipping_amount ?? 0,
                    'payment_method'    => $request->payment_method ?? 'Cash',
                    'tax_amount'        => Cart::instance('purchase')->tax() ?? 0,
                    'discount_amount'   => Cart::instance('purchase')->discount() ?? 0,
                ]);

                // --- 3. Buat Data Purchase Details (Common) ---
                foreach (Cart::instance('purchase')->content() as $cart_item) {
                    PurchaseDetail::create([
                        'purchase_id'       => $newPurchase->id,
                        'product_id'        => $cart_item->id,
                        'product_name'      => $cart_item->name,
                        'product_code'      => $cart_item->options->code,
                        'product_unit'      => $cart_item->options->unit ?? '-',
                        'quantity'          => $cart_item->qty,
                        'price'             => $cart_item->price,
                        'unit_price'        => $cart_item->options->unit_price,
                        'sub_total'         => $cart_item->options->sub_total,
                        'product_discount_amount' => $cart_item->options->product_discount ?? 0,
                        'product_discount_type'   => $cart_item->options->product_discount_type ?? 'fixed',
                        'product_tax_amount'      => $cart_item->options->product_tax ?? 0,
                    ]);
                }

                // --- 4. Logika Alur (Normal vs Over Budget) ---
                $approvalEngine = app(ApprovalEngine::class);

                if ($remaining_budget < 0) {
                    // === ALUR OVER BUDGET ===

                    // 4a. Cari Aturan Approval "Over Budget"
                    $rule = ApprovalRule::whereHas('type', function ($query) {
                        $query->where('approval_name', 'Over Budget');
                    })->where('is_active', true)->first();

                    if (!$rule) {
                        throw new \Exception('Aturan Approval (Approval Rule) untuk "Over Budget" tidak ditemukan.');
                    }
                    $approvalTypesId = $rule->approval_types_id;

                    // 4b. Buat Approval Request "Over Budget"
                    $approvalRequest = $approvalEngine->createRequest(
                        'Over Budget',              // requestable_type
                        $newPurchase->id,            // requestable_id (ID Purchase yg baru)
                        $approvalTypesId,         
                        $newPurchase->total_amount, // amount
                        Auth::id()                  // created_by
                    );
                    
                    // 4c. Update status Purchase
                    $newPurchase->update([
                        'status' => ucfirst($approvalRequest->status), // Sinkronkan status
                        'note'   => $request->note . ' | [Diajukan sebagai Over Budget]' // Tambahkan catatan
                    ]);

                } else {
                    // === ALUR NORMAL (BUDGET CUKUP) ===

                    // 4a. Cari Aturan Approval "Purchase Request"
                    $rule = ApprovalRule::whereHas('type', function ($query) {
                        $query->where('approval_name', 'Purchase Request');
                    })->where('is_active', true)->first();

                    if (!$rule) {
                        throw new \Exception('Aturan Approval (Approval Rule) untuk "Purchase Request" tidak ditemukan.');
                    }
                    $approvalTypesId = $rule->approval_types_id;

                    // 4b. Buat Approval Request "Purchase Request"
                    $approvalRequest = $approvalEngine->createRequest(
                        'Purchase Request',        
                        $newPurchase->id,           
                        $approvalTypesId,        
                        $newPurchase->total_amount, 
                        Auth::id()                 
                    );

                    // 4c. Update status Purchase
                    if ($newPurchase->status !== ucfirst($approvalRequest->status)) {
                        $newPurchase->update(['status' => ucfirst($approvalRequest->status)]); 
                    }
                    
                    // 4d. Update Budget Departemen (tambah *reserved_amount*)
                    if ($budgetDepartemen) {
                        // $budgetDepartemen->reserved_amount += $total_amount; 
                        $budgetDepartemen->save();
                    }
                }

                // --- 5. Selesaikan Transaksi ---
                Cart::instance('purchase')->destroy();
                
                return $newPurchase; // Kembalikan data purchase dari transaksi
            }); 

            // Jika transaksi sukses
            toast('Purchase Request Created Successfully!', 'success');
            return redirect()->route('purchases.index');

        } catch (\Throwable $e) {
            // Jika ada error di dalam transaksi, akan di-rollback
            return back()->withErrors(['error' => 'Gagal membuat Purchase Request: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Edit Purchase.
     */
    public function edit(Purchase $purchase)
    {
        abort_if(Gate::denies('edit_purchases'), 403);

        $purchase_details = $purchase->purchaseDetails;
        $budgets = MasterBudget::all();

        Cart::instance('purchase')->destroy();

        $cart = Cart::instance('purchase');

        foreach ($purchase_details as $detail) {
            $cart->add([
                'id' => $detail->product_id,
                'name' => $detail->product_name,
                'qty' => $detail->quantity,
                'price' => $detail->price,
                'weight' => 1,
                'options' => [
                    'product_discount' => $detail->product_discount_amount,
                    'product_discount_type' => $detail->product_discount_type,
                    'sub_total' => $detail->sub_total,
                    'code' => $detail->product_code,
                    'stock' => Product::findOrFail($detail->product_id)->product_quantity,
                    'product_tax' => $detail->product_tax_amount,
                    'unit_price' => $detail->unit_price
                ]
            ]);
        }

        return view('purchase::edit', compact('purchase', 'budgets'));
    }

    /**
     * Update Purchase.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        abort_if(Gate::denies('edit_purchases'), 403);

        if (!in_array($purchase->status, ['pending'])) {
             return redirect()->route('purchases.show', $purchase->id)
                         ->withErrors(['error' => 'Hanya PR dengan status Pending yang bisa diupdate.']);
        }

        try {
            $approvalEngine = app(ApprovalEngine::class);

            DB::transaction(function () use ($request, $purchase, $approvalEngine) {

                $total_amount     = $request->total_amount ?? 0;
                $budget_value     = $request->master_budget_value ?? 0;
                $remaining_budget = $request->master_budget_remaining ?? 0; // Sisa budget (bisa negatif)
                $old_total_amount = $purchase->total_amount;
                $old_budget_id    = $purchase->master_budget_id;
                $paid_amount   = (int) str_replace(['.', ','], '', $request->paid_amount ?? 0);

                $due_amount = $total_amount - $paid_amount;

                if ($due_amount == $total_amount) {
                    $payment_status = 'Unpaid';
                } elseif ($due_amount > 0) {
                    $payment_status = 'Partial';
                } else {
                    $payment_status = 'Paid';
                }

                if ($old_budget_id) {
                    $oldBudget = MasterBudget::find($old_budget_id);
                    if ($oldBudget) {
                        // Asumsi 'Pending' menggunakan 'reserved_amount'
                        // $oldBudget->reserved_amount -= $old_total_amount; 
                        $oldBudget->save();
                    }
                }

                $newBudget = null;
                if ($request->department_id && $request->date) {
                    $purchaseDate = Carbon::parse($request->date);
                    $newBudget = MasterBudget::where('department_id', $request->department_id)
                                          ->where('bulan', $purchaseDate->month)
                                          ->whereYear('periode_awal', $purchaseDate->year)
                                          ->where('status', 'Approved')
                                          ->first();
                }

                if ($newBudget) {
                    // $newBudget->reserved_amount += $total_amount;
                    $newBudget->save();
                }

                $purchase->purchaseDetails()->delete(); 

                $purchase->update([
                    'date' => $request->date ?? now(),
                    'supplier_id' => $request->supplier_id ?? null,
                    'users_id' => $request->users_id ?? auth()->id(),
                    'department_id' => $request->department_id ?? optional(auth()->user())->department_id,
                    'master_budget_id' => $newBudget?->id,
                    'total_amount' => $total_amount,
                    'master_budget_value' => $budget_value,
                    'master_budget_remaining' => $remaining_budget,
                    'due_amount' => $due_amount ?? 0,
                    'status' => 'pending',
                    'payment_status' => $payment_status,
                    'note' => $request->note ?? '',
                ]);

                foreach (Cart::instance('purchase')->content() as $cart_item) {
                    PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $cart_item->id,
                        'product_name' => $cart_item->name,
                        'product_code' => $cart_item->options->code,
                        'product_unit' => $cart_item->options->unit ?? '-', // Ambil dari options
                        'quantity' => $cart_item->qty,
                        'price' => (int) $cart_item->price,
                        'unit_price' => (int) $cart_item->options->unit_price,
                        'sub_total' => (int) $cart_item->options->sub_total,
                        'product_discount_amount' => $cart_item->options->product_discount,
                        'product_discount_type' => $cart_item->options->product_discount_type,
                        'product_tax_amount' => (int) ($cart_item->options->product_tax ?? 0),
                    ]);
                }

                ApprovalRequest::where('requestable_id', $purchase->id)
                    ->whereIn('requestable_type', ['Purchase Request', 'Over Budget']) 
                    ->delete(); 

                $ruleName = '';
                $requestableType = '';

                if ($remaining_budget < 0) {
                    $ruleName = 'Over Budget'; 
                    $requestableType = 'Over Budget';
                } else {
                    $ruleName = 'Purchase Request';
                    $requestableType = 'Purchase Request';
                }

                $rule = ApprovalRule::whereHas('type', function ($query) use ($ruleName) {
                    $query->where('approval_name', $ruleName);
                })->where('is_active', true)->first();

                if (!$rule) {
                    throw new \Exception("Aturan Approval (Approval Rule) untuk '{$ruleName}' tidak ditemukan.");
                }
                $approvalTypesId = $rule->approval_types_id;

                $approvalRequest = $approvalEngine->createRequest(
                    $requestableType,
                    $purchase->id,
                    $approvalTypesId,
                    $purchase->total_amount,
                    Auth::id()
                );

                // Sinkronisasi Status
                if ($purchase->status !== ucfirst($approvalRequest->status)) {
                    $purchase->update(['status' => ucfirst($approvalRequest->status)]); 
                }

                Cart::instance('purchase')->destroy();
            }); // Transaksi Selesai

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal mengupdate Purchase Request: ' . $e->getMessage()])->withInput();
        }

        toast('Purchase Updated!', 'info');
        return redirect()->route('purchases.index');
    }

    /**
     * Show detail purchase.
     */
    public function show($id)
    {
        $purchase = Purchase::with(['purchaseDetails.product', 'department', 'user'])->findOrFail($id);

        // --- Inisialisasi Variabel ---
        $currentRemainingBudget = 0; // Ini akan menjadi Total Alokasi Budget Dept
        $sisaBudgetSetelahPRIni = 0; // Ini akan menjadi Sisa Budget Dept Saat Ini
        $saldoOverBudget = 0;         // Ini akan menjadi Sisa Budget Non-Dept
        $purchaseDateObj = null;
        $month = null;
        $year = null;

        // --- Ambil Approval Request (Dibutuhkan untuk cek tipe) ---
        $approvalRequest = ApprovalRequest::where('requestable_id', $id)
            ->whereIn('requestable_type', ['Purchase Request', 'Over Budget'])
            ->first();

        // --- Ambil Logs ---
        $approvalLogs = collect(); 
        if ($approvalRequest) {
            $approvalLogs = $approvalRequest->logs()->with('approver')->get();
        }

        // --- Kalkulasi Budget (jika data ada) ---
        if ($purchase->department_id && $purchase->date) {
            $purchaseDateObj = Carbon::parse($purchase->date);
            $month = $purchaseDateObj->month;
            $year = $purchaseDateObj->year;

            // 1. Ambil data Budget Departemen
            $result = MasterBudget::where('department_id', $purchase->department_id)
                                ->where('bulan', $month)
                                ->whereYear('periode_awal', $year)
                                ->where('status', 'Approved') 
                                ->selectRaw('SUM(grandtotal) as total_budget, SUM(used_amount) as total_used, SUM(reserved_amount) as total_reserved')
                                ->first();
            
            $currentRemainingBudget = $result->total_budget ?? 0; // Total Alokasi Dept
            $sisaBudgetSetelahPRIni = $currentRemainingBudget - ($result->total_used ?? 0) - ($result->total_reserved ?? 0); // Sisa Budget Dept Saat Ini

            // 2. Jika ini Over Budget, ambil juga data Budget Non-Departemen
            if (isset($approvalRequest) && $approvalRequest->requestable_type === 'Over Budget') {
                $nonDeptBudget = MasterBudget::where('department_id', 0) // Asumsi ID 0 untuk non-dept
                                            ->where('bulan', $month)
                                            ->whereYear('periode_awal', $year)
                                            ->where('status', 'Approved')
                                            ->selectRaw('SUM(grandtotal) as total, SUM(used_amount) as used, SUM(reserved_amount) as reserved')
                                            ->first();
                
                $saldoOverBudget = ($nonDeptBudget->total ?? 0) - ($nonDeptBudget->used ?? 0) - ($nonDeptBudget->reserved ?? 0);
            }
        }
        
        // Kirim semua data ke view
        return view('purchase::show', compact(
            'purchase', 
            'currentRemainingBudget', 
            'sisaBudgetSetelahPRIni',
            'approvalLogs',
            'approvalRequest', // Kirim request utama
            'saldoOverBudget'    // Kirim sisa budget non-dept
        ));
    }

    /**
     * Delete purchase.
     */
    public function destroy(Purchase $purchase)
    {
        abort_if(Gate::denies('delete_purchases'), 403);

        try {
            DB::transaction(function () use ($purchase) {

                $approvalRequest = ApprovalRequest::where('requestable_id', $purchase->id)
                                        ->whereIn('requestable_type', ['Purchase Request', 'Over Budget'])
                                        ->first();

                if (!in_array($purchase->status, ['rejected', 'cancelled'])) {
                    
                    $purchaseDate = Carbon::parse($purchase->date);
                    $month = $purchaseDate->month;
                    $year = $purchaseDate->year;

                    if ($approvalRequest && $approvalRequest->requestable_type === 'Over Budget' && $purchase->status === 'approved') {

                        $budgetDepartemen = MasterBudget::where('department_id', $purchase->department_id)
                            ->where('bulan', $month)->whereYear('periode_awal', $year)->where('status', 'approved')
                            ->first();
                        
                        if ($budgetDepartemen) {
                            $overageAmount = abs($purchase->master_budget_remaining ?? 0);
                            $amountUsedFromDept = $purchase->total_amount - $overageAmount;
                            
                            $budgetDepartemen->used_amount -= $amountUsedFromDept;
                            if ($budgetDepartemen->used_amount < 0) $budgetDepartemen->used_amount = 0; // Prevent negative
                            $budgetDepartemen->save();
                            Log::info("Reversed Dept Budget (Over Budget) for Purchase ID {$purchase->id}. Amount: -" . $amountUsedFromDept);
                        } else {
                            Log::warning("Dept Budget not found for Purchase ID {$purchase->id} during Over Budget deletion rollback.");
                        }

                        $nonDeptBudget = MasterBudget::where('department_id', 0) // Assuming 0 for non-dept
                            ->where('bulan', $month)->whereYear('periode_awal', $year)->where('status', 'approved')
                            ->first();
                            
                        if ($nonDeptBudget) {
                            $overageAmount = abs($purchase->master_budget_remaining ?? 0);
                            $nonDeptBudget->used_amount -= $overageAmount;
                            if ($nonDeptBudget->used_amount < 0) $nonDeptBudget->used_amount = 0; // Prevent negative
                            $nonDeptBudget->save();
                            Log::info("Reversed Non-Dept Budget (Over Budget) for Purchase ID {$purchase->id}. Amount: -" . $overageAmount);
                        } else {
                            Log::warning("Non-Dept Budget not found for Purchase ID {$purchase->id} during Over Budget deletion rollback.");
                        }

                    } elseif ($approvalRequest && $approvalRequest->requestable_type === 'Purchase Request') {
                        
                        $budgetDepartemen = MasterBudget::find($purchase->master_budget_id); // Use the stored ID

                        if ($budgetDepartemen) {
                            if ($purchase->status == 'Pending') {
                                $budgetDepartemen->reserved_amount -= $purchase->total_amount;
                                if ($budgetDepartemen->reserved_amount < 0) $budgetDepartemen->reserved_amount = 0;
                                Log::info("Reversed Dept Budget (Pending Normal PR) for Purchase ID {$purchase->id}. Reserved Amount: -" . $purchase->total_amount);

                            } elseif ($purchase->status == 'approved' || $purchase->status == 'Completed') {
                                $budgetDepartemen->used_amount -= $purchase->total_amount;
                                if ($budgetDepartemen->used_amount < 0) $budgetDepartemen->used_amount = 0;
                                Log::info("Reversed Dept Budget (Approved Normal PR) for Purchase ID {$purchase->id}. Used Amount: -" . $purchase->total_amount);
                            }
                            $budgetDepartemen->save();
                        } else {
                            Log::warning("Dept Budget (ID: {$purchase->master_budget_id}) not found for Purchase ID {$purchase->id} during Normal PR deletion rollback.");
                        }
                    }
                    else if ($purchase->master_budget_id) {
                        Log::warning("Could not determine correct budget rollback logic for Purchase ID {$purchase->id}. Status: {$purchase->status}, Approval Type: " . ($approvalRequest->requestable_type ?? 'None'));
                    }

                }

                $relatedApprovalRequests = ApprovalRequest::where('requestable_id', $purchase->id)
                                            ->whereIn('requestable_type', ['Purchase Request', 'Over Budget']) // Be specific
                                            ->get();
                
                foreach($relatedApprovalRequests as $req) {
                    $req->logs()->delete();
                    $req->delete();        
                }

                $purchase->delete();
            
            });

            toast('Purchase Deleted!', 'warning');
            return redirect()->route('purchases.index');

        } catch (\Throwable $e) {
            Log::error("Error deleting purchase ID {$purchase->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Gagal menghapus Purchase Request: ' . $e->getMessage()]);
        }
    }

    private function getBudgetSnapshot($department_id, $date, $purchaseTotalAmount = 0)
    {
        if (!$department_id || !$date) {
            return (object)['current_remaining' => 0, 'remaining_after_this_pr' => 0];
        }

        $purchaseDateObj = Carbon::parse($date);
        $month = $purchaseDateObj->month;
        $year = $purchaseDateObj->year;

        // 1. Ambil data budget (HANYA grandtotal dan used_amount, TANPA reserved_amount)
        //    (Sesuai dengan logika di method show() Anda)
        $result = MasterBudget::where('department_id', $department_id)
                            ->where('bulan', $month)
                            ->whereYear('periode_awal', $year)
                            ->where('status', 'Approved') // Pastikan 'Approved'
                            ->selectRaw('SUM(grandtotal) as total_budget, SUM(used_amount) as total_used')
                            ->first();
        
        // 2. Hitung Sisa Budget SAAT INI (Total Alokasi - Yang Sudah Dipakai)
        $currentRemainingBudget = ($result->total_budget ?? 0) - ($result->total_used ?? 0);
        
        // 3. Hitung Sisa Budget SETELAH DIKURANGI PR INI
        $sisaBudgetSetelahPRIni = $currentRemainingBudget - $purchaseTotalAmount;

        return (object)[
            // Ini adalah 'currentRemainingBudget' dari contoh Anda
            'total' => $currentRemainingBudget, 
            
            // Ini adalah 'sisaBudgetSetelahPRIni' dari contoh Anda
            'remaining' => $sisaBudgetSetelahPRIni
        ];
    }
}