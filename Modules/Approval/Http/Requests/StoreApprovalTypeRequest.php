<?php

namespace Modules\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApprovalTypeRequest extends FormRequest 
{
    public function rules()
    {
        return [
            'approval_code' => ['required', 'string', 'max:50', 'unique:approval_types,approval_code'],
            'approval_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function authorize()
    {
        return Gate::allows('create_approval_types');
    }
}
