<?php

namespace Modules\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateApprovalRulesRequest extends FormRequest
{
    public function rules()
    {
        return [
            'approval_type_id' => ['required', 'integer', 'exists:approval_types,id'],
            'level' => ['required', 'integer', 'min:1'],
            'amount_limit' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function authorize()
    {
        return Gate::allows('edit_approval_rules');
    }
}
