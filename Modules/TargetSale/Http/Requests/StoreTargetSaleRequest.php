<?php

namespace Modules\TargetSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTargetSaleRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('create_target_sales');
    }

    /**
     * Persiapkan data sebelum divalidasi.
     * Hapus format Rp dan Titik agar terbaca sebagai angka.
     */
    protected function prepareForValidation()
    {
        if ($this->target_amount) {
            $this->merge([
                'target_amount' => (int) preg_replace('/[^0-9]/', '', $this->target_amount),
            ]);
        }
    }

    public function rules()
    {
        return [
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer',
            // Sekarang kita bisa memvalidasi ini sebagai numeric/integer
            'target_amount' => 'required|numeric|min:1', 
        ];
    }
}