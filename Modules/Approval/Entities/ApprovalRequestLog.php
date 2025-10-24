<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalRequestLog extends Model
{
    protected $fillable = [
        'approval_request_id',
        'level',
        'approver_id',
        'status',
        'note',
        'processed_at',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }
}