<a href="{{ route('master_budget.show', $data->id) }}" class="btn btn-sm btn-info">Detail</a>
@if(strtolower($data->status) === 'pending')
<a href="{{ route('master_budget.edit', $data->id) }}" class="btn btn-sm btn-warning">Edit</a>
@endif

@if(strtolower($data->status) !== 'approved')
<form action="{{ route('master_budget.destroy', $data->id) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</button>
</form>
@endif

@if($data->approval_status === 'Pending' && Gate::allows('approve-budget', $data))
    <form action="{{ route('master_budget.approve', $data->id) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-sm btn-success">Approve</button>
    </form>
    <form action="{{ route('master_budget.reject', $data->id) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
    </form>
@endif