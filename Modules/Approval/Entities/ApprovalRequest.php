<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $table = 'approval_requests';

    protected $fillable = [
        'approval_types_id',
        'requestable_type',
        'approval_rules_id',
        'requestable_id',
        'amount',
        'status',
        'current_level',
        'created_by',
        'note',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function requestable()
    {
        return $this->morphTo();
    }

    public function type()
    {
        return $this->belongsTo(ApprovalType::class, 'approval_type_id');
    }

    public function logs()
    {
        return $this->hasMany(ApprovalRequestLog::class);
    }

    public function creator() // Nama diubah agar lebih jelas
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
