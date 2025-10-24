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
     * Membuat Approval Request baru berdasarkan Approval Type dan Rule yang berlaku.
     */
    public function createRequest($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy)
    {
        return DB::transaction(function () use ($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy) {

            // 🔍 Cari level rule yang sesuai berdasarkan amount
            $ruleLevel = ApprovalRuleLevel::whereHas('rule', function ($q) use ($approvalTypesId) {
                    $q->where('approval_types_id', $approvalTypesId)
                      ->where('is_active', true);
                })
                ->where('amount_limit', '>=', $amount)
                ->orderBy('level', 'asc')
                ->first();

            if (!$ruleLevel) {
                throw new \Exception('Approval rule level not found for this amount.');
            }

            // ambil rule utamanya
            $rule = $ruleLevel->rule;

            // 🆕 Buat Approval Request baru
            $approvalRequest = ApprovalRequest::create([
                'approval_types_id' => $approvalTypesId,
                'requestable_type' => $modelClass,
                'requestable_id' => $modelId,
                'requested_by' => $requestedBy,
                'status' => ApprovalRequest::STATUS_PENDING,
                'current_level' => $ruleLevel->level,
            ]);

            // 🔁 Buat log untuk level pertama
            $approvers = ApprovalRuleUser::where('approval_rule_levels_id', $ruleLevel->id)->get();

            foreach ($approvers as $approver) {
                $approvalRequest->logs()->create([
                    'level' => $ruleLevel->level,
                    'approver_id' => $approver->user_id,
                    'status' => 'waiting',
                ]);
            }

            return $approvalRequest;
        });
    }

    /**
     * Proses approval atau reject.
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

            // update log
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

            // cek apakah masih ada approver yang belum approve di level ini
            $pending = $approvalRequest->logs()
                ->where('level', $approvalRequest->current_level)
                ->where('status', 'waiting')
                ->count();

            // jika semua sudah approve di level ini
            if ($pending == 0) {
                $nextLevel = $approvalRequest->current_level + 1;

                $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->type->rules->id)
                    ->where('level', $nextLevel)
                    ->first();

                if ($nextLevelData) {
                    // Buat log untuk level berikutnya
                    foreach ($nextLevelData->users as $nextUser) {
                        $approvalRequest->logs()->create([
                            'level' => $nextLevel,
                            'approver_id' => $nextUser->user_id,
                            'status' => 'waiting',
                        ]);
                    }

                    // update level
                    $approvalRequest->update(['current_level' => $nextLevel]);
                } else {
                    // jika tidak ada level berikutnya, berarti selesai → approved
                    $approvalRequest->update(['status' => 'approved']);
                    $this->syncStatusToSource($approvalRequest);
                }
            }

            return $approvalRequest;
        });
    }

    /**
     * Sinkronkan status Approval ke model sumber (misal MasterBudget).
     * Model sumber wajib punya kolom "status".
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

    /**
     * Approve cepat (untuk admin override).
     */
    public function approve(ApprovalRequest $request, $user)
    {
        $request->update(['status' => 'approved']);
        $this->syncStatusToSource($request);
    }
}
