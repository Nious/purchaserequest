<div class="btn-group">
    {{-- Tombol Edit (Hanya jika pending) --}}
    @if(strtolower($data->status) == 'pending')
        <a href="{{ route('target_sales.edit', $data->id) }}" class="btn btn-sm btn-warning mr-1" title="Edit">
            <i class="bi bi-pencil"></i>
        </a>
    @endif

    {{-- Tombol Hapus --}}
    <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Apakah Anda yakin ingin menghapus data ini?')){ document.getElementById('delete-form-{{ $data->id }}').submit(); }">
        <i class="bi bi-trash"></i>
    </button>
    <form id="delete-form-{{ $data->id }}" action="{{ route('target_sales.destroy', $data->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
</div>