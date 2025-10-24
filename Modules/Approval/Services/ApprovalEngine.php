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

            $ruleLevel = ApprovalRuleLevel::whereHas('rule', function ($q) use ($approvalTypesId) {
                $q->where('approval_types_id', $approvalTypesId)->where('is_active', true);
            })
            ->where('amount_limit', '>=', $amount)
            ->orderBy('level', 'asc')
            ->first();

            // Jika tidak ada level yang butuh approval (amount di bawah limit terendah)
            if (!$ruleLevel) {
                // Buat request dengan status langsung 'approved'
                $approvalRequest = ApprovalRequest::create([
                    'approval_types_id' => $approvalTypesId,
                    'requestable_type'  => $modelClass,
                    'requestable_id'    => $modelId,
                    'created_by'        => $requestedBy,
                    'amount'            => $amount,
                    'status'            => 'approved', // Langsung approved
                    'current_level'     => null, // Tidak ada level approval
                    'approval_rules_id' => null, // Tidak ada rule yang terpakai
                ]);
                $this->syncStatusToSource($approvalRequest); // Langsung sinkronkan status
                return $approvalRequest;
            }
            
            // Buat Approval Request baru dengan status 'pending'
            $approvalRequest = ApprovalRequest::create([
                'approval_types_id' => $approvalTypesId,
                'requestable_type'  => $modelClass,
                'requestable_id'    => $modelId,
                'created_by'        => $requestedBy,
                'amount'            => $amount,
                'status'            => 'pending',
                'current_level'     => $ruleLevel->level,
                'approval_rules_id' => $ruleLevel->approval_rules_id,
            ]);

            // Buat log untuk level pertama menggunakan relasi
            foreach ($ruleLevel->users as $approver) {
                $approvalRequest->logs()->create([
                    'level'   => $ruleLevel->level,
                    'user_id' => $approver->user_id, // ✅ DIPERBAIKI
                    'action'  => 'assigned',
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
                ->where('user_id', $userId) // ✅ DIPERBAIKI
                ->where('level', $approvalRequest->current_level)
                ->where('action', 'assigned') // Hanya bisa proses yang masih 'assigned'
                ->first();

            if (!$log) {
                throw new \Exception('You are not authorized to process this request at the current level or it has been processed.');
            }

            $log->update([
                'action' => $action, // 'approved' atau 'rejected'
                'comment' => $note,
            ]);

            if ($action === 'rejected') {
                $approvalRequest->update(['status' => 'rejected']);
                $this->syncStatusToSource($approvalRequest);
                return $approvalRequest;
            }

            // Cek apakah masih ada approver lain yang belum approve di level ini
            $pendingCount = $approvalRequest->logs()
                ->where('level', $approvalRequest->current_level)
                ->where('action', 'assigned')
                ->count();

            // Jika semua sudah approve di level ini
            if ($pendingCount == 0) {
                $nextLevelNumber = $approvalRequest->current_level + 1;

                // ✅ DIPERBAIKI: Gunakan ID rule dari request yang sedang diproses
                $nextLevelData = ApprovalRuleLevel::where('approval_rules_id', $approvalRequest->approval_rules_id)
                    ->where('level', $nextLevelNumber)
                    ->first();

                if ($nextLevelData) {
                    // Buat log untuk level berikutnya
                    foreach ($nextLevelData->users as $nextUser) {
                        $approvalRequest->logs()->create([
                            'level'   => $nextLevelNumber,
                            'user_id' => $nextUser->user_id, // ✅ DIPERBAIKI
                            'action'  => 'assigned',
                        ]);
                    }
                    // update level di request utama
                    $approvalRequest->update(['current_level' => $nextLevelNumber]);
                } else {
                    // Jika tidak ada level berikutnya, berarti selesai -> approved
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
