<?php

namespace Modules\Approval\Services;

use Modules\Approval\Entities\ApprovalRequest;
use Modules\Approval\Entities\ApprovalRule;
use Modules\Approval\Entities\ApprovalRuleLevel;
use Modules\Approval\Entities\ApprovalRuleUser;
use Illuminate\Support\Facades\DB;

class ApprovalEngine
{
    /**
     * Membuat Approval Request baru berdasarkan ApprovalType dan Rule yang berlaku.
     */
    public function createRequest($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy)
    {
        return DB::transaction(function () use ($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy) {
            // cari rule yang cocok
            $rule = ApprovalRule::where('approval_types_id', $approvalTypesId)
                ->where('amount_limit', '>=', $amount)
                ->where('is_active', true)
                ->first();

            if (!$rule) {
                throw new \Exception('Approval rule not found for this amount.');
            }

            // buat request
            $approvalRequest = ApprovalRequest::create([
                'approval_types_id' => $approvalTypesId,
                'requestable_type' => $modelClass,
                'requestable_id' => $modelId,
                'requested_by' => $requestedBy,
                'status' => ApprovalRequest::STATUS_PENDING,
                'current_level' => 1,
            ]);

            // buat log untuk level pertama
            $level = ApprovalRuleLevel::where('approval_rules_id', $rule->id)
                ->orderBy('level')
                ->first();

            if ($level) {
                $approvers = ApprovalRuleUser::where('approval_rule_levels_id', $level->id)->get();

                foreach ($approvers as $approver) {
                    $approvalRequest->logs()->create([
                        'level' => $level->level,
                        'approver_id' => $approver->user_id,
                        'status' => 'waiting',
                    ]);
                }
            }

            return $approvalRequest;
        });
    }

    /**
     * Memproses approval (approve / reject).
     */
    public function process($approvalRequest, $userId, $action, $note = null)
    {
        return DB::transaction(function () use ($approvalRequest, $userId, $action, $note) {
            $log = $approvalRequest->logs()
                ->where('approver_id', $userId)
                ->where('level', $approvalRequest->current_level)
                ->first();

            if (!$log) {
                throw new \Exception('You are not authorized to approve this request.');
            }

            $log->update([
                'status' => $action,
                'note' => $note,
                'approved_at' => now(),
            ]);

            // jika reject
            if ($action === 'rejected') {
                $approvalRequest->update(['status' => 'rejected']);
                $this->syncStatusToSource($approvalRequest);
                return $approvalRequest;
            }

            // jika semua di level ini sudah approve, naik ke level berikutnya
            $pending = $approvalRequest->logs()
                ->where('level', $approvalRequest->current_level)
                ->where('status', 'waiting')
                ->count();

            if ($pending == 0) {
                $nextLevel = $approvalRequest->current_level + 1;

                $nextLevelData = \Modules\Approval\Entities\ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->type->rules->id)
                    ->where('level', $nextLevel)
                    ->first();

                if ($nextLevelData) {
                    // buat log untuk next level
                    foreach ($nextLevelData->users as $nextUser) {
                        $approvalRequest->logs()->create([
                            'level' => $nextLevel,
                            'approver_id' => $nextUser->user_id,
                            'status' => 'waiting',
                        ]);
                    }
                    $approvalRequest->update(['current_level' => $nextLevel]);
                } else {
                    // kalau tidak ada level berikutnya
                    $approvalRequest->update(['status' => 'approved']);
                    $this->syncStatusToSource($approvalRequest);
                }
            }

            return $approvalRequest;
        });
    }

    /**
     * Sinkronkan status ke model sumber (misal MasterBudget)
     */
    protected function syncStatusToSource(ApprovalRequest $approvalRequest)
    {
        $model = $approvalRequest->requestable;
        if (!$model) return;

        $status = match ($approvalRequest->status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default    => 'Pending',
        };

        $model->update(['status' => $status]);
    }

    public function approve(ApprovalRequest $request, $user)
    {
        $request->update(['status' => 'approved']);
        $this->syncStatusToSource($request);
    }



}