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

            // ---TEMUKAN ATURAN (RULE) YANG AKTIF ---
            $activeRule = ApprovalRule::where('approval_types_id', $approvalTypesId)
                                      ->where('is_active', true)
                                      ->first();

            // Jika tidak ada aturan aktif sama sekali untuk tipe ini
            if (!$activeRule) {
                // Opsi 1: Gagal dan beri error
                // throw new \Exception("Tidak ada aturan approval aktif untuk tipe ini.");

                // Opsi 2: Langsung setujui (Auto-approve)
                return $this->createAutoApprovedRequest($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy);
            }

            // Cari level terendah di mana amount_limit >= amount yang diajukan.
            // Ini akan secara otomatis "melewati" level yang limitnya lebih rendah.
            $startingLevel = ApprovalRuleLevel::where('approval_rules_id', $activeRule->id)
                ->where('amount_limit', '>=', $amount)
                ->orderBy('level', 'asc')
                ->first();

            // Ini terjadi jika `amount` lebih rendah dari `amount_limit` terendah.
            if (!$startingLevel) {
                return $this->createAutoApprovedRequest($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy, $activeRule->id);
            }

            $approvalRequest = ApprovalRequest::create([
                'approval_types_id' => $approvalTypesId,
                'approval_rules_id' => $activeRule->id,
                'requestable_type'  => $modelClass,
                'requestable_id'    => $modelId,
                'created_by'        => $requestedBy,
                'amount'            => $amount,
                'status'            => 'pending',
                'current_level'     => $startingLevel->level,
            ]);

            // Temukan data 'requester' di tabel 'approval_rule_users' yang cocok dengan user pembuat
            $requesterRule = ApprovalRuleUser::where('approval_rule_levels_id', $startingLevel->id)
                                 ->where('role', 'requester')
                                 ->where('user_id', $requestedBy)
                                 ->first();

            // Jika pembuat (requester) tidak ditemukan di dalam aturan approval
            if (!$requesterRule) {
                // Batalkan transaksi dan lempar error, karena tidak ada alur approval yang cocok
                throw new \Exception("Alur approval tidak ditemukan untuk requester ini di level {$startingLevel->level}.");
            }

            // Ambil sequence dari requester yang ditemukan
            $targetSequence = $requesterRule->sequence;

            // Cari SEMUA approver di level yang sama DAN sequence yang sama
            $approvers = ApprovalRuleUser::where('approval_rule_levels_id', $startingLevel->id)
                                        ->where('role', 'approver')
                                        ->where('sequence', $targetSequence)
                                        ->get();

            // Jika tidak ada approver yang cocok di sequence ini
            if ($approvers->isEmpty()) {
                throw new \Exception("Tidak ada approver yang ditemukan untuk requester ini di sequence {$targetSequence}, level {$startingLevel->level}.");
            }

            // Buat log tugas untuk approver yang ditemukan
            foreach ($approvers as $approver) {
                $approvalRequest->logs()->create([
                    'level'   => $startingLevel->level,
                    'user_id' => $approver->user_id,
                    'action'  => 'assigned',
                ]);
            }

            return $approvalRequest;
        });
    }

    /**
     * Helper method untuk membuat request yang langsung disetujui.
     */
    private function createAutoApprovedRequest($modelClass, $modelId, $approvalTypesId, $amount, $requestedBy, $ruleId = null)
    {
        $approvalRequest = ApprovalRequest::create([
            'approval_types_id' => $approvalTypesId,
            'approval_rules_id' => $ruleId, // Bisa null jika tidak ada rule aktif
            'requestable_type'  => $modelClass,
            'requestable_id'    => $modelId,
            'created_by'        => $requestedBy,
            'amount'            => $amount,
            'status'            => 'approved', // Langsung approved
            'current_level'     => 0, // Level 0 menandakan tidak ada proses approval
        ]);
        
        // Langsung sinkronkan status 'Approved' ke model aslinya (MasterBudget)
        $this->syncStatusToSource($approvalRequest); 
        
        return $approvalRequest;
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
    // public function approve(ApprovalRequest $request, $user)
    // {
    //     $request->update(['status' => 'approved']);
    //     $this->syncStatusToSource($request);
    // }

    public function forceApprove(ApprovalRequest $request, int $userId, string $note = 'Force Approved by Admin')
    {
        // Catat log bahwa admin melakukan override
        $request->logs()->create([
            'level'       => $request->current_level ?? 0,
            'user_id'     => $userId,
            'action'      => 'approved',
            'comment'     => $note,
        ]);

        // Update status utama
        $request->update(['status' => 'approved']);
        $this->syncStatusToSource($request);
    }
}
