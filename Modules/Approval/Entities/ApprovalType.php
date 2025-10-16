<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalType extends Model
{
     use HasFactory; // <- ini penting

    protected $table = 'approval_types'; // pastikan sesuai tabel

    protected $fillable = [
        'approval_code',
        'approval_name',
    ];

     //Relasi ke ApprovalRules
     //Satu ApprovalType bisa punya banyak ApprovalRule
     //
    public function rules()
    {
        return $this->hasMany(\Modules\Approval\Entities\ApprovalRule::class, 'approval_type_id');
    }
}