<?php

// app/Models/MasterBudget.php
namespace Modules\Budget\Entities;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBudget extends Model
{
    use HasFactory;

    protected $table = 'master_budget';
    protected $fillable = [
        'no_budgeting',
        'tgl_penyusunan',
        'bulan',
        'periode_awal',
        'periode_akhir',
        'department_id',
        'description',
        'grandtotal',
        'approval_status',
    ];

        public function scopeApproved($query)
    {
        return $query->where('approval_status', 'Approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'Pending');
    }

    public function details()
    {
        return $this->hasMany(BudgetDetail::class, 'master_budget_id');
    }

    public function getGrandtotalFormattedAttribute()
    {
        return 'Rp ' . number_format($this->grandtotal, 0, ',', '.');
    }
    public function getBulanTextAttribute()
    {
        // kalau bulan disimpan sebagai angka (1 = Januari)
        return \Carbon\Carbon::create(null, $this->bulan, 1)->translatedFormat('F Y');
    }

    // Relasi ke Department
    public function department()
    {
        return $this->belongsTo(\Modules\Department\Entities\Departments::class, 'department_id');
    }
    public function budgetdetail()
    {
        return $this->hasMany(BudgetDetail::class, 'master_budget_id');
    }

    public function approvalRequest()
    {
        return $this->morphOne(\Modules\Approval\Entities\ApprovalRequest::class, 'requestable');
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }
    
}
