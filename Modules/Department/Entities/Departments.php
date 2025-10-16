<?php

namespace Modules\Department\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departments extends Model
{
    protected $fillable = [
        'department_code',
        'department_name',
    ];

    // Relasi ke Master Budget
    public function budgets()
    {
        return $this->hasMany(\Modules\Budget\Entities\MasterBudget::class, 'department_id');
    }

    
}