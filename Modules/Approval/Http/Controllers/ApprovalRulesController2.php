<?php

namespace Modules\Approval\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Approval\Entities\ApprovalRule;
use Modules\Approval\Entities\ApprovalType;
use Modules\Approval\Entities\ApprovalRuleUser;
use App\Models\User;
use Modules\Approval\DataTables\ApprovalRulesDataTable;

class ApprovalRulesController extends Controller
{
    public function index(ApprovalRulesDataTable $dataTable)
    {
        abort_if(Gate::denies('access_approval_rules'), 403);

        $approvalTypes = ApprovalType::all();
        $users = User::all();

        return $dataTable->render('approval::approval_rules.index', compact('approvalTypes', 'users'));
    }


    public function create()
    {
        abort_if(Gate::denies('access_approval_rules'), 403);

        $approvalTypes = ApprovalType::all();
        $users = User::all();

        return view('approval::approval_rules.create', compact('approvalTypes', 'users'));
    }

public function store(Request $request)
    {
        $request->validate([
            'approval_types_id' => 'required|exists:approval_types,id',
            'is_active' => 'nullable|boolean',
            'levels' => 'required|array',
        ]);

        // buat data rule per level
        foreach ($request->input('levels') as $levelIndex => $levelData) {
            $amount = $levelData['amount_limit'] ?? 0;
            // Simpan satu row di approval_rules untuk level ini
            $rule = ApprovalRule::create([
                'approval_types_id' => $request->approval_type_id,
                'level' => $levelIndex + 1,
                'amount_limit' => $amount,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]);

            // simpan groups (requesters + approvers)
            if (!empty($levelData['groups']) && is_array($levelData['groups'])) {
                foreach ($levelData['groups'] as $groupIndex => $group) {
                    // requesters
                    if (!empty($group['requesters']) && is_array($group['requesters'])) {
                        foreach ($group['requesters'] as $userId) {
                            ApprovalRuleUser::create([
                                'approval_rules_id' => $rule->id,
                                'user_id' => $userId,
                                'type' => 'requester',
                                'group_index' => $groupIndex,
                                'level' => $levelIndex + 1,
                            ]);
                        }
                    }
                    // approvers
                    if (!empty($group['approvers']) && is_array($group['approvers'])) {
                        foreach ($group['approvers'] as $userId) {
                            ApprovalRuleUser::create([
                                'approval_rules_id' => $rule->id,
                                'user_id' => $userId,
                                'type' => 'approver',
                                'group_index' => $groupIndex,
                                'level' => $levelIndex + 1,
                            ]);
                        }
                    }
                }
            }
        }

        session()->flash('success','Approval Rules saved');
        return redirect()->route('approval_rules.index');
    }


    public function edit($id)
    {
        abort_if(Gate::denies('access_approval_rules'), 403);

        $approvalRule  = ApprovalRule::with('users')->findOrFail($id);
        $approvalTypes = ApprovalType::all();
        $users         = User::all();

        return view('approval::approval_rules.edit', compact('approvalRule', 'approvalTypes', 'users'));
    }

    public function update(Request $request, $id)
    {
        abort_if(Gate::denies('access_approval_rules'), 403);

        $request->validate([
        'approval_types_id'        => 'required|exists:approval_types,id',
        'is_active'               => 'required|boolean',
        'levels'                  => 'required|array',
        'amount_limit'   => 'nullable|numeric|min:0',
        'requesters'     => 'required|array',
        'approvers'      => 'required|array',
        ]);

        $rule = ApprovalRule::findOrFail($id);
        $rule->update([
            'approval_types_id' => $request->approval_type_id,
            'is_active'        => $request->is_active,
        ]);

        // Hapus user lama
        $rule->users()->delete();

        // Tambah user baru sesuai level
        // Simpan user per level (requester & approver)
        foreach ($request->levels as $index => $levelData) {
            $level = $index + 1;
            $amountLimit = $levelData['amount_limit'] ?? 0;

            // simpan requester
            if (!empty($levelData['requesters'])) {
                foreach ($levelData['requesters'] as $userId) {
                    ApprovalRuleUser::create([
                        'approval_rules_id' => $rule->id,
                        'user_id'          => $userId,
                        'level'            => $level,
                        'amount_limit'     => $amountLimit,
                    ]);
                }
            }

            // simpan approver
            if (!empty($levelData['approvers'])) {
                foreach ($levelData['approvers'] as $userId) {
                    ApprovalRuleUser::create([
                        'approval_rules_id' => $rule->id,
                        'user_id'          => $userId,
                        'level'            => $level,
                        'amount_limit'     => $amountLimit,
                    ]);
                }
            }
        }


        session()->flash('info', 'Approval Rule Updated!');

        return redirect()->route('approval_rules.index');
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('access_approval_rules'), 403);

        $approvalRule = ApprovalRule::findOrFail($id);
        $approvalRule->users()->delete();
        $approvalRule->delete();

        session()->flash('warning', 'Approval Rule Deleted!');

        return redirect()->route('approval_rules.index');
    }
}
