<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $table = 'approval_rules'; // pastikan nama tabel sesuai

    protected $fillable = [
        'approval_type_id',
        'level',
        'amount_limit',
        'is_active',
    ];

    /**
     * Relasi ke ApprovalType
     * Satu ApprovalType bisa punya banyak ApprovalRule
     */
    public function type()
    {
        return $this->belongsTo(\Modules\Approval\Entities\ApprovalType::class, 'approval_type_id');
    }

    /**
     * Relasi ke ApprovalRuleUsers
     * Satu ApprovalRule bisa punya banyak user (requester/approver)
     */
    public function ruleUsers()
    {
        return $this->hasMany(\Modules\Approval\Entities\ApprovalRuleUser::class, 'approval_rule_id');
    }
}
