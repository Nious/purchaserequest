<?php

namespace App\Livewire;

use Livewire\Component;
use Gloudemans\Shoppingcart\Facades\Cart;
use Modules\Product\Entities\Product;
use Modules\Budget\Entities\MasterBudget;
use Carbon\Carbon;

class ProductCart extends Component
{
    public $listeners = ['productSelected', 'purchaseDateChanged', 'dateChanged' => 'handleDateChange'];

    public $cart_instance;
    public $department_id;
    public $purchase_date;
    public $data; // optional purchase data

    public $global_discount = 0;
    public $global_tax = 0;
    public $shipping = 0;

    public $quantity = [];
    public $unit_price = [];
    public $item_discount = [];
    public $discount_type = [];
    public $check_quantity = [];

    public $grand_total = 0;
    public $budget = 0;
    public $sisa_budget = 0;
    public $non_dept_budget_remaining = 0;

    public function mount($cartInstance = 'purchase', $departmentId = null, $purchaseDate = null, $data = null)
    {
        $this->cart_instance = $cartInstance;
        $this->department_id = $departmentId;
        $this->purchase_date = $purchaseDate ?? now()->format('Y-m-d');
        $this->data = $data;

        // Bersihkan cart lama
        Cart::instance($this->cart_instance)->destroy();

        if ($data) {
            $this->global_discount = $data->discount_percentage ?? 0;
            $this->global_tax = $data->tax_percentage ?? 0;
            $this->shipping = $data->shipping_amount ?? 0;

            $data->load('purchaseDetails.product');

            foreach ($data->purchaseDetails as $detail) {
                $product = $detail->product;
                if (!$product) continue;

                $sub_total = ($detail->unit_price * $detail->quantity) - ($detail->discount ?? 0);

                Cart::instance($this->cart_instance)->add([
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'qty' => $detail->quantity,
                    'price' => $detail->unit_price,
                    'weight' => 1,
                    'options' => [
                        'code' => $product->product_code ?? '',
                        'stock' => $product->product_quantity ?? 0,
                        'unit' => $product->product_unit ?? '-',
                        'unit_price' => $detail->unit_price,
                        'sub_total' => $sub_total,
                        'product_discount' => $detail->discount ?? 0,
                        'product_discount_type' => $detail->discount_type ?? 'fixed',
                        'date' => $data->date ?? now()->format('Y-m-d'),
                    ]
                ]);
            }

            // Set nilai Livewire array
            foreach (Cart::instance($this->cart_instance)->content() as $cart_item) {
                $this->check_quantity[$cart_item->id] = $cart_item->options->stock;
                $this->quantity[$cart_item->id] = $cart_item->qty;
                $this->unit_price[$cart_item->id] = $cart_item->price;
                $this->discount_type[$cart_item->id] = $cart_item->options->product_discount_type;
                $this->item_discount[$cart_item->id] = $cart_item->options->product_discount;
            }
        }

        $this->refreshSummary();
    }

    public function render()
    {
        return view('livewire.product-cart', [
            'cart_items' => Cart::instance($this->cart_instance)->content(),
        ]);
    }

    public function refreshSummary()
    {
        $cart_items = Cart::instance($this->cart_instance)->content();

        // Hitung subtotal tiap item
        $subtotal = $cart_items->sum(function ($item) {
            return ($item->price * $item->qty) - ($item->options->product_discount ?? 0);
        });

        // Global discount
        $total_after_discount = $subtotal;
        if ($this->global_discount) {
            $total_after_discount -= ($total_after_discount * $this->global_discount / 100);
        }

        // Global tax
        if ($this->global_tax) {
            $total_after_discount += ($total_after_discount * $this->global_tax / 100);
        }

        $this->grand_total = $total_after_discount + ($this->shipping ?? 0);

        $purchaseDateObj = Carbon::parse($this->purchase_date);
        $month = $purchaseDateObj->month;
        $year = $purchaseDateObj->year;

        // === Hitung budget department ===
        if ($this->data && $this->data->status === 'approved') {
            $this->budget = $this->data->master_budget_value ?? 0;
        } else {
            $masterBudget = MasterBudget::where('department_id', $this->department_id)
                ->where('bulan', $month)
                ->whereYear('periode_awal', $year)
                ->where('status', 'approved')
                ->first();

            $this->budget = $masterBudget
                ? ($masterBudget->grandtotal - $masterBudget->used_amount - $masterBudget->reserved_amount)
                : 0;
        }

        $this->sisa_budget = $this->budget - $this->grand_total;

        // === Hitung dana Over Budget (non-dept) ===
        $overBudget = MasterBudget::where('department_id', 0) // 0 = non-dept / over budget
            ->where('bulan', $month)
            ->whereYear('periode_awal', $year)
            ->where('status', 'approved')
            ->first();

        $this->non_dept_budget_remaining = $overBudget
            ? ($overBudget->grandtotal - $overBudget->used_amount - $overBudget->reserved_amount)
            : 0;

        // Dispatch data ke JS
        $this->dispatch('update-budget-fields', [
            'total_amount' => $this->grand_total,
            'master_budget_value' => $this->budget,
            'master_budget_remaining' => $this->sisa_budget,
            'non_dept_budget_remaining' => $this->non_dept_budget_remaining,
            'over_budget_min_sisa_budget' => 0, // optional
        ]);
    }

    public function productSelected($product)
    {
        $cart = Cart::instance($this->cart_instance);

        $exists = $cart->search(fn($cartItem) => $cartItem->id == $product['id']);
        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product already exists in the cart!');
            return;
        }

        $qty = 1;
        $cart->add([
            'id' => $product['id'],
            'name' => $product['product_name'],
            'qty' => $qty,
            'price' => $product['product_price'],
            'weight' => 1,
            'options' => [
                'sub_total' => $product['product_price'] * $qty,
                'product_discount' => 0,
                'product_discount_type' => 'fixed',
                'unit_price' => $product['product_price'],
                'code' => $product['product_code'] ?? '',
                'stock' => $product['product_quantity'] ?? 0,
                'unit' => $product['product_unit'] ?? '-',
                'date' => now()->format('Y-m-d'),
            ]
        ]);

        $this->quantity[$product['id']] = $qty;
        $this->unit_price[$product['id']] = $product['product_price'];
        $this->discount_type[$product['id']] = 'fixed';
        $this->item_discount[$product['id']] = 0;
        $this->check_quantity[$product['id']] = $product['product_quantity'] ?? 0;

        $this->refreshSummary();
    }

    public function removeItem($row_id)
    {
        Cart::instance($this->cart_instance)->remove($row_id);
        $this->refreshSummary();
    }

    public function updateQuantity($row_id, $product_id)
    {
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);
        if (!$cart_item) return;

        $qty = $this->quantity[$product_id] ?? 1;

        Cart::instance($this->cart_instance)->update($row_id, [
            'qty' => $qty,
            'options' => [
                'sub_total' => ($cart_item->price * $qty) - ($cart_item->options->product_discount ?? 0),
                'product_discount' => $cart_item->options->product_discount ?? 0,
                'product_discount_type' => $cart_item->options->product_discount_type ?? 'fixed',
                'unit_price' => $cart_item->options->unit_price,
                'code' => $cart_item->options->code,
                'stock' => $cart_item->options->stock,
                'unit' => $cart_item->options->unit,
                'date' => $cart_item->options->date,
            ]
        ]);

        $this->refreshSummary();
    }

    public function updatePrice($row_id, $product_id)
    {
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);
        if (!$cart_item) return;

        $new_price = $this->unit_price[$product_id] ?? $cart_item->price;

        Cart::instance($this->cart_instance)->update($row_id, [
            'price' => $new_price,
            'options' => [
                'sub_total' => ($new_price * $cart_item->qty) - ($cart_item->options->product_discount ?? 0),
                'product_discount' => $cart_item->options->product_discount ?? 0,
                'product_discount_type' => $cart_item->options->product_discount_type ?? 'fixed',
                'unit_price' => $new_price,
                'code' => $cart_item->options->code,
                'stock' => $cart_item->options->stock,
                'unit' => $cart_item->options->unit,
                'date' => $cart_item->options->date,
            ]
        ]);

        $this->refreshSummary();
    }

    public function updatedGlobalDiscount()
    {
        $this->refreshSummary();
    }

    public function updatedGlobalTax()
    {
        $this->refreshSummary();
    }

    public function handleDateChange($date = null)
    {
        if ($date) {
            $this->purchase_date = $date;
            $this->refreshSummary();
        }
    }
}
