<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;
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

class PurchaseController extends Controller
{
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
        abort_if(Gate::denies('create_purchases'), 403);

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
    /**
     * Simpan data purchase baru.
     */
    public function store(StorePurchaseRequest $request)
    {
        abort_if(Gate::denies('create_purchases'), 403);

        DB::transaction(function () use ($request) {

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

            $purchase = Purchase::create([
                'reference' => Purchase::generatePRNumber(),
                'date' => $request->date ?? now(),
                'supplier_id' => $request->supplier_id ?? null,
                'users_id' => $request->users_id ?? auth()->id(),
                'department_id' => $request->department_id ?? optional(auth()->user())->department_id,
                'tax_percentage' => $request->tax_percentage ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'shipping_amount' => $shipping_amount ?? null,
                'paid_amount' => $paid_amount ?? null,
                'master_budget_id' => $budget?->id,
                'total_amount' => $total_amount,
                'master_budget_value' => $budget_value,
                'master_budget_remaining' => $remaining_budget,
                'due_amount' => $due_amount ?? null,
                'status' => $request->status ?? 'Pending',
                'payment_status' => $payment_status ?? 'Unpaid',
                'payment_method' => $request->payment_method ?? 'Cash',
                'note' => $request->note ?? '',
                'tax_amount' => (int) Cart::instance('purchase')->tax() ?? 0,
                'discount_amount' => (int) Cart::instance('purchase')->discount() ?? 0,
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

                // Jika status sudah complete, update stok produk
                if ($request->status == 'Completed') {
                    $product = Product::findOrFail($cart_item->id);
                    $product->update([
                        'product_quantity' => $product->product_quantity + $cart_item->qty
                    ]);
                }
            }

            // Jika ada pembayaran
            if ($purchase->paid_amount > 0) {
                PurchasePayment::create([
                    'date' => $request->date ?? now(),
                    'reference' => 'INV/' . $purchase->reference,
                    'amount' => $purchase->paid_amount,
                    'purchase_id' => $purchase->id,
                    'payment_method' => $request->payment_method ?? 'Cash'
                ]);
            }

            // Update sisa budget di tabel master_budgets
            if ($budget) {
                $budget->remaining_budget = $remaining_budget;
                $budget->save();
            }

            Cart::instance('purchase')->destroy();
        });

        toast('Purchase Created Successfully!', 'success');
        return redirect()->route('purchases.index');
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

    // Kirim data budget terkini ke view
    return view('purchase::show', compact(
        'purchase', 
        'currentRemainingBudget', 
        'sisaBudgetSetelahPRIni'
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