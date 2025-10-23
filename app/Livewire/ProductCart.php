<?php

namespace App\Livewire;

use Livewire\Component;
use Gloudemans\Shoppingcart\Facades\Cart;
use Modules\Product\Entities\Product;
use Modules\Budget\Entities\MasterBudget;

class ProductCart extends Component
{
    public $listeners = ['productSelected', 'discountModalRefresh'];

    public $cart_instance;
    public $budget_id;

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

    public function mount($cartInstance = 'purchase', $budgetId = null, $data = null)
    {
        $this->cart_instance = $cartInstance;
        $this->budget_id = $budgetId;

        // === Jika sedang edit purchase ===
        if ($data) {
            $this->data = $data;
            $this->global_discount = $data->discount_percentage ?? 0;
            $this->global_tax = $data->tax_percentage ?? 0;
            $this->shipping = $data->shipping_amount ?? 0;

            // Bersihkan cart lama agar tidak bentrok
            Cart::instance($this->cart_instance)->destroy();

            // Pastikan relasi purchaseDetails tersedia
            if (method_exists($data, 'purchaseDetails')) {
                foreach ($data->purchaseDetails as $detail) {
                    $product = $detail->product;

                    // ✅ Hitung ulang subtotal agar tidak kosong
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
                            'unit'                  => $product->product_unit ?? '-', // ✅ UOM muncul
                            'unit_price'            => $detail->unit_price,
                            'sub_total'             => $sub_total, // ✅ Subtotal muncul
                            'product_discount'      => $detail->discount ?? 0,
                            'product_discount_type' => $detail->discount_type ?? 'fixed',
                            'date'                  => $data->date ?? now()->format('Y-m-d'),
                        ]
                    ]);
                }
            }

            // Set nilai variabel Livewire dari cart
            $cart_items = Cart::instance($this->cart_instance)->content();
            foreach ($cart_items as $cart_item) {
                $this->check_quantity[$cart_item->id] = $cart_item->options->stock;
                $this->quantity[$cart_item->id] = $cart_item->qty;
                $this->unit_price[$cart_item->id] = $cart_item->price;
                $this->discount_type[$cart_item->id] = $cart_item->options->product_discount_type;
                $this->item_discount[$cart_item->id] = $cart_item->options->product_discount;
            }

            // ✅ Pastikan global tax & discount diterapkan
            $this->updatedGlobalDiscount();
            $this->updatedGlobalTax();
        } 
        // === Jika create baru ===
        else {
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
        $cart_items = Cart::instance($this->cart_instance)->content();

        return view('livewire.product-cart', [
            'cart_items' => $cart_items,
        ]);
    }

    public function refreshSummary()
    {
        // Hitung subtotal cart
        $subtotal = Cart::instance($this->cart_instance)->subtotal(0, '', '');
        $this->grand_total = (float) str_replace([',', 'Rp', ' '], '', $subtotal);

        // Ambil budget dari DB
        if ($this->budget_id) {
            $masterBudget = MasterBudget::find($this->budget_id);
            $this->budget = $masterBudget->amount ?? 0;
        }

        // Hitung sisa budget
        $this->sisa_budget = $this->budget - $this->grand_total;

        // Update sisa budget di database
        if ($this->budget_id) {
            MasterBudget::where('id', $this->budget_id)
                ->update(['remaining_budget' => $this->sisa_budget]);
        }

        // Kirim event ke frontend (Livewire v3)
        $this->dispatch('update-budget-fields', [
            'total_amount' => $this->grand_total,
            'master_budget_value' => $this->budget,
            'master_budget_remaining' => $this->sisa_budget,
        ]);
    }

    public function productSelected($product)
    {
        $cart = Cart::instance($this->cart_instance);

        // Cegah duplikat produk
        $exists = $cart->search(fn($cartItem) => $cartItem->id == $product['id']);
        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product already exists in the cart!');
            return;
        }

        $this->product = $product;
        $calc = $this->calculate($product);

        // Tambahkan ke cart
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
                'unit'                  => $product['product_unit'] ?? '-', // ✅ UOM juga muncul di create
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

    public function updatedGlobalTax()
    {
        Cart::instance($this->cart_instance)->setGlobalTax((integer)$this->global_tax);
    }

    public function updatedGlobalDiscount()
    {
        Cart::instance($this->cart_instance)->setGlobalDiscount((integer)$this->global_discount);
    }

    public function updateQuantity($row_id, $product_id)
    {
        if ($this->cart_instance == 'sale' || $this->cart_instance == 'purchase_return') {
            if (($this->check_quantity[$product_id] ?? 0) < ($this->quantity[$product_id] ?? 0)) {
                session()->flash('message', 'The requested quantity is not available in stock.');
                return;
            }
        }

        Cart::instance($this->cart_instance)->update($row_id, $this->quantity[$product_id]);

        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => [
                'sub_total'             => $cart_item->price * $cart_item->qty,
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

    /**
     * Hitung harga produk sederhana tanpa tax
     */
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
}



