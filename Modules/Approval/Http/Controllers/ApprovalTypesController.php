<?php

namespace Modules\Approval\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Approval\Entities\ApprovalType;
use Modules\Approval\DataTables\ApprovalTypesDataTable;

class ApprovalTypesController extends Controller
{
    public function index(ApprovalTypesDataTable $dataTable)
    {
        abort_if(Gate::denies('access_approval_types'), 403);

        // Sesuaikan dengan direktori view (approval_types/index.blade.php)
        return $dataTable->render('approval::approval_types.index');
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('access_approval_types'), 403);

        $request->validate([
            'approval_code' => 'required|unique:approval_types,approval_code',
            'approval_name' => 'required',
        ]);

        ApprovalType::create([
            'approval_code' => $request->approval_code,
            'approval_name' => $request->approval_name,
        ]);

        session()->flash('success', 'Approval Type Created!');

        return redirect()->back();
    }

    public function edit($id)
    {
        abort_if(Gate::denies('access_approval_types'), 403);

        $approvalType = ApprovalType::findOrFail($id);

        return view('approval::approval_types.edit', compact('approvalType'));
    }

    public function update(Request $request, $id)
    {
        abort_if(Gate::denies('access_approval_types'), 403);

        $request->validate([
            'approval_code' => 'required|unique:approval_types,approval_code,' . $id,
            'approval_name' => 'required',
        ]);

        ApprovalType::findOrFail($id)->update([
            'approval_code' => $request->approval_code,
            'approval_name' => $request->approval_name,
        ]);

        session()->flash('info', 'Approval Type Updated!');

        return redirect()->route('approval_types.index');
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('access_approval_types'), 403);

        $approvalType = ApprovalType::findOrFail($id);
        $approvalType->delete();

        session()->flash('warning', 'Approval Type Deleted!');

        return redirect()->route('approval_types.index');
    }
}
