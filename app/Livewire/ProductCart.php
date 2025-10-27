<?php

namespace App\Livewire;

use Livewire\Component;
use Gloudemans\Shoppingcart\Facades\Cart;
use Modules\Product\Entities\Product;
use Modules\Budget\Entities\MasterBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCart extends Component
{
    public $listeners = ['productSelected', 'discountModalRefresh', 'purchaseDateChanged', 'dateChanged' => 'handleDateChange'];

    public $cart_instance;
    public $budget_id;
    public $department_id;
    public $purchase_date;

    public $global_discount;
    public $global_tax;
    public $shipping;
    public $quantity;
    public $check_quantity;
    public $discount_type;
    public $item_discount;
    public $unit_price;
    public $data;

    public $grand_total = 0;
    public $budget = 0;
    public $sisa_budget = 0;

    private $product;

    public function mount($cartInstance = 'purchase', $budgetId = null, $departmentId = null, $purchaseDate = null, $data = null)
    {
        $this->cart_instance = $cartInstance;
        $this->budget_id = $budgetId;
        $this->department_id = $departmentId;
        $this->purchase_date = $purchaseDate ?? now()->format('Y-m-d');

        // Bersihkan cart lama
        Cart::instance($this->cart_instance)->destroy();

        if ($data) {
            $this->data = $data;
            $this->global_discount = $data->discount_percentage ?? 0;
            $this->global_tax = $data->tax_percentage ?? 0;
            $this->shipping = $data->shipping_amount ?? 0;

            $data->load('purchaseDetails.product');

            foreach ($data->purchaseDetails as $detail) {
                $product = $detail->product;
                if (!$product) continue;

                $sub_total = $detail->unit_price * $detail->quantity;

                Cart::instance($this->cart_instance)->add([
                    'id'      => $product->id,
                    'name'    => $product->product_name,
                    'qty'     => $detail->quantity,
                    'price'   => $detail->unit_price,
                    'weight'  => 1,
                    'options' => [
                        'code'                  => $product->product_code ?? '',
                        'stock'                 => $product->product_quantity ?? 0,
                        'unit'                  => $product->product_unit ?? '-',
                        'unit_price'            => $detail->unit_price,
                        'sub_total'             => $sub_total,
                        'product_discount'      => $detail->discount ?? 0,
                        'product_discount_type' => $detail->discount_type ?? 'fixed',
                        'date'                  => $data->date ?? now()->format('Y-m-d'),
                    ]
                ]);
            }

            // Set nilai Livewire array
            $cart_items = Cart::instance($this->cart_instance)->content();
            foreach ($cart_items as $cart_item) {
                $this->check_quantity[$cart_item->id] = $cart_item->options->stock;
                $this->quantity[$cart_item->id] = $cart_item->qty;
                $this->unit_price[$cart_item->id] = $cart_item->price;
                $this->discount_type[$cart_item->id] = $cart_item->options->product_discount_type;
                $this->item_discount[$cart_item->id] = $cart_item->options->product_discount;
            }
        } else {
            // Create baru
            $this->global_discount = 0;
            $this->global_tax = 0;
            $this->shipping = 0;
            $this->check_quantity = [];
            $this->quantity = [];
            $this->unit_price = [];
            $this->discount_type = [];
            $this->item_discount = [];
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

        // Hitung subtotal per item termasuk diskon item
        $subtotal = $cart_items->sum(function($item) {
            $sub = $item->qty * $item->price;
            $discount = $item->options->product_discount ?? 0;
            return $sub - $discount;
        });

        // Tambahkan global discount
        $total_after_discount = $subtotal;
        if ($this->global_discount) {
            $total_after_discount -= ($total_after_discount * $this->global_discount / 100);
        }

        // Tambahkan tax
        if ($this->global_tax) {
            $total_after_discount += ($total_after_discount * $this->global_tax / 100);
        }

        // Tambahkan shipping
        $this->grand_total = $total_after_discount + ($this->shipping ?? 0);

        // Ambil budget berdasarkan department dan bulan saat ini
        if ($this->department_id) {
            $purchaseDateObj = Carbon::parse($this->purchase_date);
            $month = $purchaseDateObj->month;
            $year = $purchaseDateObj->year;
        
            // Query pertama untuk menjumlahkan grandtotal
            $totalGrandtotal = MasterBudget::where('department_id', $this->department_id)
                ->where('bulan', $month)
                ->whereYear('periode_awal', $year)
                ->where('status', 'approved')
                ->sum('grandtotal');

            // Query kedua untuk menjumlahkan used_amount
            $totalUsedAmount = MasterBudget::where('department_id', $this->department_id)
                ->where('bulan', $month)
                ->whereYear('periode_awal', $year)
                ->where('status', 'approved')
                ->sum('used_amount');

            // Hitung selisihnya di PHP
            $this->budget = $totalGrandtotal - $totalUsedAmount;
        }

        $this->sisa_budget = $this->budget - $this->grand_total;

        // Update budget di database jika ada
        // if ($this->budget_id) {
        //     MasterBudget::where('id', $this->budget_id)
        //         ->update(['remaining_budget' => $this->sisa_budget]);
        // }

        
        // Dispatch ke JS
        // $this->grand_total = $this->calculateGrandTotal(); // atau sesuai function kamu
        // $this->sisa_budget = $this->budget - $this->grand_total;

        $this->dispatch('update-budget-fields', [
            'total_amount' => $this->grand_total,
            'master_budget_value' => $this->budget,
            'master_budget_remaining' => $this->sisa_budget,
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

        $calc = $this->calculate($product);

        $cart->add([
            'id'      => $product['id'],
            'name'    => $product['product_name'],
            'qty'     => 1,
            'price'   => $calc['price'],
            'weight'  => 1,
            'options' => [
                'product_discount'      => 0,
                'product_discount_type' => 'fixed',
                'sub_total'             => $calc['sub_total'],
                'code'                  => $product['product_code'] ?? '',
                'stock'                 => $product['product_quantity'] ?? 0,
                'unit'                  => $product['product_unit'] ?? '-',
                'unit_price'            => $calc['unit_price'],
                'date'                  => now()->format('Y-m-d'),
            ]
        ]);

        $this->check_quantity[$product['id']] = $product['product_quantity'] ?? 0;
        $this->quantity[$product['id']] = 1;
        $this->discount_type[$product['id']] = 'fixed';
        $this->item_discount[$product['id']] = 0;

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

        if (($this->check_quantity[$product_id] ?? 0) < ($this->quantity[$product_id] ?? 0)) {
            session()->flash('message', 'The requested quantity is not available in stock.');
            return;
        }

        Cart::instance($this->cart_instance)->update($row_id, $this->quantity[$product_id]);

        // Update subtotal
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);
        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => [
                'sub_total'             => $cart_item->price * $cart_item->qty - ($cart_item->options->product_discount ?? 0),
                'code'                  => $cart_item->options->code,
                'stock'                 => $cart_item->options->stock,
                'unit'                  => $cart_item->options->unit,
                'unit_price'            => $cart_item->options->unit_price,
                'product_discount'      => $cart_item->options->product_discount,
                'product_discount_type' => $cart_item->options->product_discount_type,
                'date'                  => $cart_item->options->date,
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

    public function calculate($product, $new_price = null)
    {
        $product_price = $new_price ?? ($product['product_price'] ?? 0);
        $quantity = $product['quantity'] ?? 1;
        $sub_total = $product_price * $quantity;

        return [
            'price' => $product_price,
            'unit_price' => $product_price,
            'product_tax' => 0,
            'sub_total' => $sub_total,
        ];
    }

    public function handleDateChange($date = null)
    {
        if ($date) {
            $this->purchase_date = $date;
            $this->refreshSummary();
            \Log::info('Livewire date updated: ' . $this->purchase_date);
        }
    }

}
