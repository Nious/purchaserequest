@if ($data->status == 'pending')
    <span class="badge badge-warning">Pending</span>
@elseif ($data->status == 'approved')
    <span class="badge badge-success">Approved</span>
@elseif ($data->status == 'rejected')
    <span class="badge badge-danger">Rejected</span>
@elseif ($data->status == 'completed') {{-- Tambahkan status lain jika ada --}}
    <span class="badge badge-info">Completed</span>
@else
    <span class="badge badge-secondary">{{ ucfirst($data->status) }}</span> {{-- Tampilkan status apa adanya jika tidak cocok --}}
@endif