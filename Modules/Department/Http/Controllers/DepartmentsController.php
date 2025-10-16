<?php

namespace Modules\Department\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Department\Entities\Departments;
use Modules\Department\DataTables\DepartmentsDataTable;

class DepartmentsController extends Controller
{
    public function index(DepartmentsDataTable $dataTable)
    {
        abort_if(Gate::denies('access_departments'), 403);

        // Pastikan view path sesuai folder
        return $dataTable->render('department::department.index');
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('access_departments'), 403);

        $request->validate([
            'department_code' => 'required|unique:departments,department_code',
            'department_name' => 'required'
        ]);

        Departments::create([
            'department_code' => $request->department_code,
            'department_name' => $request->department_name,
        ]);

        session()->flash('success', 'Department Created!');

        return redirect()->back();
    }

    public function edit($id)
    {
        abort_if(Gate::denies('access_departments'), 403);

        $department = Departments::findOrFail($id);

        // Pastikan view path sesuai folder
        return view('department::department.edit', compact('department'));
    }

    public function update(Request $request, $id)
    {
        abort_if(Gate::denies('access_departments'), 403);

        $request->validate([
            'department_code' => 'required|unique:departments,department_code,' . $id,
            'department_name' => 'required'
        ]);

        Departments::findOrFail($id)->update([
            'department_code' => $request->department_code,
            'department_name' => $request->department_name,
        ]);

        session()->flash('info', 'Department Updated!');

        return redirect()->route('departments.index');
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('access_departments'), 403);

        $department = Departments::findOrFail($id);
        $department->delete();

        session()->flash('warning', 'Department Deleted!');

        return redirect()->route('departments.index');
    }
}
