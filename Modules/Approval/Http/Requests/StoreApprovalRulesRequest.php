<?php

namespace Modules\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApprovalRulesRequest extends FormRequest
{
    public function rules()
    {
        return [
            'approval_type_id' => ['required', 'integer', 'exists:approval_types,id'],
            'levels' => ['required', 'array'],
            'levels.*.amount_limit' => ['nullable', 'numeric', 'min:0'],
            'levels.*.requesters' => ['required', 'array'],
            'levels.*.approvers' => ['required', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function authorize()
    {
        return Gate::allows('create_approval_rules');
    }
}
