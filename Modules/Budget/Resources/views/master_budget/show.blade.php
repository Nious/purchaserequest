{{-- resources/views/master_budgets/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Master Budget')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('master_budget.index') }}">Master Budget</a></li>
    <li class="breadcrumb-item active">Detail</li>
</ol>
@endsection

@section('content')
<div class="container-fluid mb-4">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-secondary">Detail Master Budget</h5>
            <div>
                {{-- Tombol Edit --}}
                <a href="{{ route('master_budget.edit', $budget->id) }}" class="btn btn-sm btn-warning me-2" id="edit-btn">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>

                {{-- Status Dropdown --}}
                @if (strtolower($budget->status) === 'pending')
                    <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                        <i class="bi bi-hourglass-split"></i> Pending
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a href="#" class="dropdown-item approval-action" data-status="approved">✅ Approve</a></li>
                        <li><a href="#" class="dropdown-item approval-action" data-status="rejected">❌ Reject</a></li>
                    </ul>
                @elseif (strtolower($budget->status) === 'approved')
                    <button class="btn btn-sm btn-success" disabled>Approved</button>
                @elseif (strtolower($budget->status) === 'rejected')
                    <button class="btn btn-sm btn-danger" disabled>Rejected</button>
                @else
                    <button class="btn btn-sm btn-secondary" disabled>Unknown</button>
                @endif

            </div>
        </div>
        <div class="card-header">
            <h5 class="mb-2 fw-bold text-secondary">Log Approval</h5>
            
            {{-- Jika tidak ada log sama sekali --}}
            @if($approvalLogs->isEmpty())
                <h6 class="text-muted my-0">Belum ada riwayat approval.</h6>
            @endif
        
            {{-- Loop untuk log yang SUDAH DISETUJUI --}}
            @foreach($approvalLogs->where('action', 'approved') as $log)
                <div class_content="d-flex justify-content-between align-items-center">
                    <h6 class="text-success my-0">
                        <i class="bi bi-check-circle-fill"></i>
                        Disetujui oleh: <strong>{{ $log->approver->name ?? 'User tidak dikenal' }}</strong> (Level {{ $log->level }})
                    </h6>
                    @if($log->comment)
                        <h6 class="my-0 fst-italic">Note: "{{ $log->comment }}"</h6>
                    @endif
                </div>
            @endforeach
        
            {{-- Loop untuk log yang MASIH MENUNGGU --}}
            @foreach($approvalLogs->where('action', 'assigned') as $log)
                 <h6 class="text-warning my-0">
                    <i class="bi bi-hourglass-split"></i>
                    Menunggu approval dari: <strong>{{ $log->approver->name ?? 'User tidak dikenal' }}</strong> (Level {{ $log->level }})
                 </h6>
            @endforeach
        
            {{-- Loop untuk log yang DITOLAK --}}
            @foreach($approvalLogs->where('action', 'rejected') as $log)
                 <div class_content="d-flex justify-content-between align-items-center">
                     <h6 class="text-danger my-0">
                        <i class="bi bi-x-circle-fill"></i>
                        Ditolak oleh: <strong>{{ $log->approver->name ?? 'User tidak dikenal' }}</strong> (Level {{ $log->level }})
                     </h6>
                     @if($log->comment)
                        <h6 class="my-0 fst-italic">Note: "{{ $log->comment }}"</h6>
                     @endif
                 </div>
            @endforeach
        </div>

        <div class="card-body">
            {{-- Informasi Budget --}}
            <h5 class="fw-bold mb-3 text-secondary">Informasi Budget</h5>
            <table class="table table-bordered">
                <tr>
                    <th>No. Budgeting PR</th>
                    <td>{{ $budget->no_budgeting }}</td>
                </tr>
                <tr>
                    <th>Tgl. Penyusunan</th>
                    <td>{{ $budget->tgl_penyusunan }}</td>
                </tr>
                <tr>
                    <th>Bulan</th>
                    <td>{{ $budget->bulan }}</td>
                </tr>
                <tr>
                    <th>Periode Awal</th>
                    <td>{{ $budget->periode_awal }}</td>
                </tr>
                <tr>
                    <th>Periode Akhir</th>
                    <td>{{ $budget->periode_akhir }}</td>
                </tr>
                <tr>
                    <th>Departemen</th>
                    <td>{{ $budget->department ? $budget->department->department_name : '-' }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $budget->description }}</td>
                </tr>
                <tr>
                    <th>Grandtotal</th>
                    <td>Rp {{ number_format($budget->grandtotal, 0) }}</td>
                </tr>
            </table>

            {{-- Detail Budget --}}
            <h5 class="fw-bold mb-3 text-secondary mt-4">Detail Budget</h5>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Kode Kategori</th>
                        <th>Nama Kategori</th>
                        <th>Budget</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budget->details as $detail)
                    <tr>
                        <td>{{ $detail->category_id }}</td>
                        <td>{{ $detail->category_name }}</td>
                        <td>Rp {{ number_format($detail->budget, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Jika Rejected tampilkan alasan --}}
            @if(strtolower($budget->status) === 'rejected' && $budget->notes)
                <div class="form-group mt-4">
                    <label class="text-danger fw-bold">Alasan Penolakan</label>
                    <textarea class="form-control bg-light" rows="3" readonly>{{ $budget->notes }}</textarea>
                </div>
            @endif

            <div class="mt-3">
                <a href="{{ route('master_budget.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.approval-action').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const newStatus = this.dataset.status;

            Swal.fire({
                title: `Ubah status menjadi "${newStatus.toUpperCase()}"?`,
                text: `Apakah kamu yakin ingin ${newStatus === 'approved' ? 'menyetujui' : 'menolak'} Master Budget ini?`,
                icon: newStatus === 'approved' ? 'success' : 'error',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    if (newStatus === 'rejected') {
                        Swal.fire({
                            title: 'Masukkan Alasan Penolakan',
                            input: 'textarea',
                            inputPlaceholder: 'Tuliskan alasan kenapa Master Budget ini ditolak...',
                            inputAttributes: { 'aria-label': 'Alasan penolakan' },
                            showCancelButton: true,
                            confirmButtonText: 'Kirim',
                            cancelButtonText: 'Batal',
                            inputValidator: (value) => {
                                if (value.trim().length < 5) {
                                    return 'Alasan harus berisi minimal 5 karakter.';
                                }
                                if (!value || value.trim().length === 0) {
                                    return 'Alasan tidak boleh kosong!';
                                }
                                return null;
                            }
                        }).then((reasonResult) => {
                            if (reasonResult.isConfirmed) {
                                submitApproval(newStatus, reasonResult.value);
                            }
                        });
                    } else {
                        submitApproval(newStatus);
                    }
                }
            });
        });
    });   
});

function submitApproval(status, reason = null) {
    let url = "";
    let data = {
        _token: "{{ csrf_token() }}", // Token keamanan Laravel
        status: status
    };

    // 1. Tentukan URL Controller mana yang akan dituju
    if (status === 'approved') {
        url = "{{ route('master_budget.approve', $budget->id) }}";
    } else if (status === 'rejected') {
        url = "{{ route('master_budget.reject', $budget->id) }}";
        // Tambahkan 'notes' ke data jika statusnya 'rejected'
        data.notes = reason; 
    }

    if (url === "") return; // Jangan lakukan apa-apa jika status tidak dikenal

    // 2. Kirim data ke server menggunakan Fetch (AJAX)
    fetch(url, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(data) // Ubah data JavaScript menjadi string JSON
    })
    .then(response => {
        // Cek jika server merespon dengan error (seperti 403, 404, 500)
        if (!response.ok) {
            // Ambil pesan error dari JSON server jika ada
            return response.json().then(err => { throw new Error(err.error || 'Terjadi kesalahan.') });
        }
        return response.json(); // Ubah respons server menjadi JSON
    })
    .then(data => {
        // 3. Tampilkan pesan berdasarkan respons dari Controller
        if (data.success) {
            // Jika Controller mengembalikan { success: true, message: '...' }
            Swal.fire({
                title: 'Berhasil!',
                text: data.message, // Tampilkan pesan sukses dari server
                icon: 'success'
            }).then(() => {
                location.reload(); // Muat ulang halaman untuk melihat status baru
            });
        } else {
            // Jika Controller mengembalikan { success: false, error: '...' }
            Swal.fire('Gagal!', data.error || 'Terjadi kesalahan saat memproses.', 'error');
        }
    })
    .catch(error => {
        // 4. Tampilkan pesan jika koneksi gagal
        console.error('Fetch Error:', error);
        Swal.fire('Error!', error.message || 'Tidak dapat terhubung ke server.', 'error');
    });
}
</script>
@endpush
