<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalRuleUser extends Model
{
    use HasFactory;

    protected $table = 'approval_rules_users';

    protected $fillable = [
        'approval_rules_id',   // relasi ke approval_rules
        'users_id',            // relasi ke users
        'role_id',            // relasi ke roles (jika pakai role-based)               // requester / approver
    ];

    /**
     * Relasi ke ApprovalRule
     */
    public function rule()
    {
        return $this->belongsTo(\Modules\Approval\Entities\ApprovalRule::class, 'approval_rule_id');
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }

    /**
     * Relasi ke Role (opsional)
     */
}