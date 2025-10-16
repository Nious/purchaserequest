<?php

namespace Modules\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApprovalRuleUsersRequest extends FormRequest
{
    public function rules()
    {
        return [
            'approval_rules_id' => ['required', 'integer', 'exists:approval_rules,id'],
            'user_id'          => ['required', 'integer', 'exists:users,id'],
            'roles_id'            => ['required', 'string', 'max:255'],
        ];
    }

    public function authorize()
    {
        return Gate::allows('create_approval_rule_users');
    }
}
