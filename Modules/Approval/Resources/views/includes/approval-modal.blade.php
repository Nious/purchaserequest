@php
    $approval_max_id = \Modules\Approval\Entities\ApprovalType::max('id') + 1;
    $approval_code = "AP_" . str_pad($approval_max_id, 2, '0', STR_PAD_LEFT);
@endphp

<div class="modal fade" id="ApprovalTypeCreateModal" tabindex="-1" role="dialog" aria-labelledby="ApprovalTypeCreateModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalTypeCreateModalLabel">Create Approval Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('approval_types.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="approval_code">Approval Code <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="approval_code" required value="{{ $approval_code }}">
                    </div>

                    <div class="form-group">
                        <label for="approval_name">Approval Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="approval_name" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create <i class="bi bi-check"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
