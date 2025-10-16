<?php

namespace Modules\Budget\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetDetail extends Model
{
    use HasFactory;

    protected $table = 'budget_details';
    protected $fillable = [
        'master_budget_id',
        'category_id',
        'category_name',
        'budget',
    ];

    public function masterBudget()
    {
        return $this->belongsTo(MasterBudget::class, 'master_budget_id');
    }
}
