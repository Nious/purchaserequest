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

    public function approve($id)
    {
        $purchase = Purchase::findOrFail($id);

        // 1. Cek otorisasi (sesuaikan nama Gate Anda)
        if (! Gate::allows('approve_purchases', $purchase)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk approve.'], 403);
        }

        try {
            // 2. Gunakan Transaksi Database
            DB::transaction(function () use ($purchase) {
                
                // --- Cari Approval Request ---
                $approvalRequest = ApprovalRequest::where('requestable_type', 'Purchase Request') // <-- GANTI STRING
                                                  ->where('requestable_id', $purchase->id)
                                                  ->first();

                // 3. Jika request-nya ada, proses
                if ($approvalRequest) {
                    
                    // 3a. Update log PENGGUNA SAAT INI
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

                    // 3b. Cek apakah level ini sudah selesai
                    $pendingCount = $approvalRequest->logs()
                        ->where('level', $approvalRequest->current_level)
                        ->where('action', 'assigned')
                        ->count();

                    // 3c. Jika semua sudah approve di level ini
                    if ($pendingCount == 0) {
                        $nextLevelNumber = $approvalRequest->current_level + 1;

                        $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->approval_rules_id)
                            ->where('level', $nextLevelNumber)
                            ->first();

                        if ($nextLevelData) {
                            // --- MASIH ADA LEVEL BERIKUTNYA ---
                            foreach ($nextLevelData->users->where('role', 'approver') as $nextUser) {
                                $approvalRequest->logs()->create([
                                    'level'   => $nextLevelNumber,
                                    'user_id' => $nextUser->user_id,
                                    'action'  => 'assigned',
                                ]);
                            }
                            $approvalRequest->update(['current_level' => $nextLevelNumber]);
                        
                        } else {
                            // --- INI ADALAH LEVEL TERAKHIR ---
                            $purchase->update(['status' => 'Approved']);
                            $approvalRequest->update(['status' => 'approved']);
                            
                            // --- (OPSIONAL) UPDATE BUDGET SETELAH APPROVE ---
                            // Jika 'reserved_amount' digunakan, ubah menjadi 'used_amount'
                            if ($purchase->master_budget_id) {
                                $budget = MasterBudget::find($purchase->master_budget_id);
                                if ($budget) {
                                    $budget->reserved_amount -= $purchase->total_amount;
                                    $budget->used_amount += $purchase->total_amount;
                                    $budget->save();
                                }
                            }
                        }
                    }
                
                } else {
                    // Fallback jika tidak ada Approval Request
                    $purchase->update(['status' => 'Approved']);
                }
            }); // Transaksi selesai

            // 4. Beri respons sukses
            return response()->json(['success' => true, 'message' => 'Purchase Request berhasil disetujui.']);
            
        } catch (\Throwable $e) {
            // Tangkap jika ada error
            return response()->json(['error' => $e->getMessage()], 500);
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

        // 1. Cek otorisasi (sesuaikan nama Gate Anda)
        if (! Gate::allows('approve_purchases', $purchase)) {
            return response()->json(['error' => 'Anda tidak punya akses untuk reject.'], 403);
        }

        try {
            DB::transaction(function () use ($purchase, $request) {
                
                // --- Cari Approval Request ---
                $approvalRequest = ApprovalRequest::where('requestable_type', 'Purchase Request') // <-- GANTI STRING
                                                  ->where('requestable_id', $purchase->id)
                                                  ->first();

                if ($approvalRequest) {
                    
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

                    // --- Langsung hentikan dan update semua status ---
                    $purchase->update([
                        'status' => 'rejected',
                        'note'   => $request->notes
                    ]);
                    $approvalRequest->update(['status' => 'rejected']);
                    
                    // --- KEMBALIKAN BUDGET YANG DI-RESERVE ---
                    if ($purchase->master_budget_id) {
                        $budget = MasterBudget::find($purchase->master_budget_id);
                        if ($budget) {
                            $budget->reserved_amount -= $purchase->total_amount;
                            $budget->save();
                        }
                    }
                
                } else {
                    // Fallback jika tidak ada Approval Request
                    $purchase->update([
                        'status' => 'rejected',
                        'note'   => $request->notes
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
    public function store(StorePurchaseRequest $request)
    {
        // Cek Gate Anda
        abort_if(Gate::denies('create_purchases'), 403);
        
        try {

            $rule = ApprovalRule::whereHas('type', function ($query) {
                $query->where('approval_name', 'Purchase Request'); // Sesuaikan nama jika perlu
            })->where('is_active', true)->first();

            if (!$rule) {
                throw new \Exception('Aturan Approval (Approval Rule) untuk "Purchase Request" tidak ditemukan.');
            }
            $approvalTypesId = $rule->approval_types_id;
            
            $approvalEngine = app(ApprovalEngine::class);

            $purchase = DB::transaction(function () use ($request, $approvalTypesId, $approvalEngine) {

                $total_amount     = $request->total_amount ?? 0;
                $budget_value     = $request->master_budget_value ?? 0;
                $remaining_budget = $request->master_budget_remaining ?? 0;
                $paid_amount      = $request->paid_amount ?? 0;
                $due_amount       = $total_amount - $paid_amount;
                
                $payment_status = 'Unpaid';
                if ($due_amount > 0 && $due_amount < $total_amount) {
                    $payment_status = 'Partial';
                } elseif ($due_amount <= 0) {
                    $payment_status = 'Paid';
                }

                $budget = null;
                if ($request->department_id && $request->date) {
                    $purchaseDate = Carbon::parse($request->date);
                    $month = $purchaseDate->month;
                    $year = $purchaseDate->year;

                    $budget = MasterBudget::where('department_id', $request->department_id)
                                        ->where('bulan', $month)
                                        ->whereYear('periode_awal', $year)
                                        ->where('status', 'Approved') // Pastikan 'Approved'
                                        ->first();
                }

                $newPurchase = Purchase::create([
                    'reference'        => Purchase::generatePRNumber(),
                    'date'             => $request->date ?? now(),
                    'supplier_id'      => $request->supplier_id ?? null,
                    'users_id'         => $request->users_id ?? auth()->id(),
                    'department_id'    => $request->department_id ?? optional(auth()->user())->department_id,
                    'master_budget_id' => $budget?->id, // Ambil ID dari budget yang ditemukan
                    'total_amount'     => $total_amount,
                    'master_budget_value' => $budget_value,
                    'master_budget_remaining' => $remaining_budget,
                    'due_amount'       => $due_amount,
                    'status'           => 'Pending', // Status awal
                    'payment_status'   => $payment_status,
                    'note'             => $request->note ?? '',
                    'tax_percentage'        => $request->tax_percentage ?? 0,
                    'discount_percentage'   => $request->discount_percentage ?? 0,
                    'shipping_amount'       => $request->shipping_amount ?? 0,
                    'payment_method'        => $request->payment_method ?? 'Cash',
                    'tax_amount'            => Cart::instance('purchase')->tax() ?? 0,
                    'discount_amount'       => Cart::instance('purchase')->discount() ?? 0,
                ]);

                foreach (Cart::instance('purchase')->content() as $cart_item) {
                    PurchaseDetail::create([
                        'purchase_id'             => $newPurchase->id,
                        'product_id'              => $cart_item->id,
                        'product_name'            => $cart_item->name,
                        'product_code'            => $cart_item->options->code,
                        'product_unit'            => $cart_item->options->unit ?? '-', // ✅ PERBAIKAN BUG
                        'quantity'                => $cart_item->qty,
                        'price'                   => $cart_item->price,
                        'unit_price'              => $cart_item->options->unit_price,
                        'sub_total'               => $cart_item->options->sub_total,
                        'product_discount_amount' => $cart_item->options->product_discount ?? 0,
                        'product_discount_type'   => $cart_item->options->product_discount_type ?? 'fixed',
                        'product_tax_amount'      => $cart_item->options->product_tax ?? 0,
                    ]);
                }
                
                if ($budget) {
                    $budget->reserved_amount += $total_amount; 
                    $budget->save();
                }

                $approvalRequest = $approvalEngine->createRequest( // <-- Gunakan variabel $approvalEngine
                    'Purchase Request',   
                    $newPurchase->id,      
                    $approvalTypesId,
                    $newPurchase->total_amount,
                    Auth::id()
                );

                // --- Sinkronisasi Status ---
                if ($newPurchase->status !== ucfirst($approvalRequest->status)) {
                    $newPurchase->update(['status' => ucfirst($approvalRequest->status)]); 
                }
                
                Cart::instance('purchase')->destroy();
                
                return $newPurchase;
            }); 

            toast('Purchase Request Created Successfully!', 'success');
            return redirect()->route('purchases.index');

        } catch (\Throwable $e) {
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

        DB::transaction(function () use ($request, $purchase) {

            $paid_amount   = (int) str_replace(['.', ','], '', $request->paid_amount ?? 0);
            $total_amount  = (int) str_replace(['.', ','], '', $request->total_amount ?? 0);
            $shipping_amount = (int) str_replace(['.', ','], '', $request->shipping_amount ?? 0);
            $budget_value  = (int) str_replace(['.', ','], '', $request->master_budget_value ?? 0);
            $remaining_budget = (int) str_replace(['.', ','], '', $request->master_budget_remaining ?? 0);

            $due_amount = $total_amount - $paid_amount;

            if ($due_amount == $total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $budget = MasterBudget::find($request->master_budget_id);

            // Hapus detail lama
            foreach ($purchase->purchaseDetails as $detail) {
                if ($purchase->status == 'Completed') {
                    $product = Product::findOrFail($detail->product_id);
                    $product->update([
                        'product_quantity' => $product->product_quantity - $detail->quantity
                    ]);
                }
                $detail->delete();
            }

            $purchase->update([
                'date' => $request->date ?? now(),
                'supplier_id' => $request->supplier_id ?? null,
                'users_id' => $request->users_id ?? auth()->id(),
                'department_id' => $request->department_id ?? optional(auth()->user())->department_id,
                'master_budget_id' => $budget?->id,
                'total_amount' => $total_amount,
                'master_budget_value' => $budget_value,
                'master_budget_remaining' => $remaining_budget,
                'due_amount' => $due_amount ?? 0,
                'status' => $request->status ?? 'Pending',
                'payment_status' => $payment_status,
                'note' => $request->note ?? '',
            ]);

            foreach (Cart::instance('purchase')->content() as $cart_item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'product_unit' => $product->product_unit ?? '-',
                    'quantity' => $cart_item->qty,
                    'price' => (int) $cart_item->price,
                    'unit_price' => (int) $cart_item->options->unit_price,
                    'sub_total' => (int) $cart_item->options->sub_total,
                    'product_discount_amount' => (int) $cart_item->options->product_discount,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => (int) $cart_item->options->product_tax,
                ]);
            }

            if ($budget) {
                $budget->remaining_budget = $remaining_budget;
                $budget->save();
            }

            Cart::instance('purchase')->destroy();
        });

        toast('Purchase Updated!', 'info');
        return redirect()->route('purchases.index');
    }

    /**
     * Show detail purchase.
     */
    public function show($id)
    {
        $purchase = Purchase::with(['purchaseDetails.product', 'department', 'user'])->findOrFail($id);

        $currentRemainingBudget = 0;

        if ($purchase->department_id && $purchase->date) {
            $purchaseDateObj = Carbon::parse($purchase->date);
            $month = $purchaseDateObj->month;
            $year = $purchaseDateObj->year;

            $result = MasterBudget::where('department_id', $purchase->department_id)
                                ->where('bulan', $month)
                                ->whereYear('periode_awal', $year)
                                ->where('status', 'approved')
                                ->selectRaw('SUM(grandtotal) as total_budget, SUM(used_amount) as total_used')
                                ->first();
            $currentRemainingBudget = ($result->total_budget ?? 0) - ($result->total_used ?? 0); 
            $sisaBudgetSetelahPRIni = ($currentRemainingBudget ?? 0) - $purchase->total_amount;
        }
        // --- BATAS TAMBAHAN ---

        $approvalLogs = ApprovalRequestLog::with('approver') // Ambil juga data user approver
            ->whereHas('approvalRequest', function ($query) use ($id) {
                // Filter relasi 'approvalRequest'
                $query->where('requestable_type', 'Purchase Request') // Sesuaikan string 'Master Budget'
                    ->where('requestable_id', $id); // Cocokkan dengan ID MasterBudget
            })
            ->get();

        // Kirim data budget terkini ke view
        return view('purchase::show', compact(
            'purchase', 
            'currentRemainingBudget', 
            'sisaBudgetSetelahPRIni',
            'approvalLogs'
        ));
    }

    /**
     * Delete purchase.
     */
    public function destroy(Purchase $purchase)
    {
        abort_if(Gate::denies('delete_purchases'), 403);

        $purchase->delete();

        toast('Purchase Deleted!', 'warning');
        return redirect()->route('purchases.index');
    }
}