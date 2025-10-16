@php
    $department_max_id = \Modules\Department\Entities\Departments::max('id') + 1;
    $department_code = "DE_" . str_pad($department_max_id, 2, '0', STR_PAD_LEFT);
@endphp
<div class="modal fade" id="departmentCreateModal" tabindex="-1" role="dialog" aria-labelledby="departmentCreateModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentCreateModalLabel">Create Department</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('departments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="department_code">Department Code <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="department_code" required value="{{ $department_code }}">
                    </div>
                    <div class="form-group">
                        <label for="department_name">Department Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="department_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create <i class="bi bi-check"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>