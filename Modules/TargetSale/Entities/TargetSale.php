<?php

namespace Modules\TargetSale\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', // <--- PASTIKAN INI ADA
        'month', 
        'year', 
        'target_amount', 
        'description', 
        'status', 
        'created_by'
    ];

    // Helper untuk menampilkan nama bulan
    public function getMonthNameAttribute()
    {
        return \Carbon\Carbon::createFromDate(null, $this->month, 1)->translatedFormat('F');
    }

    // Helper untuk format rupiah
    public function getFormattedTargetAttribute()
    {
        return 'Rp ' . number_format($this->target_amount, 0, ',', '.');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}