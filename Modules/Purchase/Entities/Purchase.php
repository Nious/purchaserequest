<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function purchaseDetails() {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id', 'id');
    }

    public function purchasePayments() {
        return $this->hasMany(PurchasePayment::class, 'purchase_id', 'id');
    }
    
    public function department() {
        return $this->belongsTo(\Modules\Department\Entities\Departments::class, 'department_id', 'id');
    }

    public function user() {
        return $this->belongsTo(\App\Models\User::class, 'users_id', 'id');
    }

    public static function boot() {





        parent::boot();

        static::creating(function ($model) {
            $number = Purchase::max('id') + 1;
            $model->reference = make_reference_id('PR', $number);
        });
    }

    public static function generatePRNumber()
    {
        $prefix = "PR";
        $lastPR = self::orderBy('id', 'desc')->first();

        if (!$lastPR) {
            $number = 1;
        } else {
            // ambil angka dari PR terakhir
            $lastNumber = (int) str_replace($prefix, "", $lastPR->reference);
            $number = $lastNumber + 1;
        }

        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT); 
        // Hasil: PR00001, PR00002, dst.
    }

    public function scopeCompleted($query) {
        return $query->where('status', 'Completed');
    }

    public function getShippingAmountAttribute($value) {
        return $value;
    }

    public function getPaidAmountAttribute($value) {
        return $value;
    }

    public function getTotalAmountAttribute($value) {
        return $value;
    }

    public function getDueAmountAttribute($value) {
        return $value;
    }

    public function getTaxAmountAttribute($value) {
        return $value;
    }

    public function getDiscountAmountAttribute($value) {
        return $value;
    }
}
