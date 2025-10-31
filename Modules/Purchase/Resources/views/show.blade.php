@extends('layouts.app')

@section('title', 'Purchase Request Detail')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Detail</li>
</ol>
@endsection

@section('content')
<div class="container-fluid mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="mb-1 fw-semibold">Detail Purchase Request 
            @if(isset($approvalRequest) && $approvalRequest->requestable_type === 'Over Budget')
                <span class="badge bg-danger ms-2">Over Budget</span>
            @endif
        </h3>
        <div>
            @if ($purchase->status === 'pending')
                <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-sm btn-warning me-2" id="edit-btn">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            @endif

            {{-- Status Dropdown --}}
            <div class="btn-group">

                {{-- 1. Definisikan variabel pengecekan --}}
                @php
                    $status = strtolower($purchase->status);
                    
                    // Cek apakah user yg login adalah approver di level ini
                    $isCurrentUserApprover = $approvalLogs
                                                ->where('action', 'assigned') // Cari yang masih menunggu
                                                ->where('user_id', Auth::id()) // Cocokkan dengan user yg login
                                                ->isNotEmpty(); // true jika user ditemukan
                @endphp
            
                {{-- 2. Tampilkan dropdown HANYA jika status pending DAN user adalah approver --}}
                @if ($status === 'pending' && $isCurrentUserApprover)
                    <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                        <i class="bi bi-person-check"></i> Menunggu Aksi Anda
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="#" class="dropdown-item approval-action" data-status="approved">
                                ✅ Approve
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-item approval-action" data-status="rejected">
                                ❌ Reject
                            </a>
                        </li>
                    </ul>
            
                {{-- 3. Tampilkan status 'Pending' (non-aktif) jika user BUKAN approver --}}
                @elseif ($status === 'pending')
                    <button class="btn btn-sm btn-warning" disabled>
                        <i class="bi bi-hourglass-split"></i> Pending
                    </button>
            
                {{-- 4. Tampilkan status final (Approved / Rejected) --}}
                @elseif ($status === 'approved')
                    <button class="btn btn-sm btn-success" disabled>
                        <i class="bi bi-check2-circle"></i> Approved
                    </button>
                @elseif ($status === 'rejected')
                    <button class="btn btn-sm btn-danger" disabled>
                        <i class="bi bi-x-circle"></i> Rejected
                    </button>
                @else
                    <button class="btn btn-sm btn-secondary" disabled>
                        <i class="bi bi-question-circle"></i> Unknown
                    </button>
                @endif
            </div>

            <a target="_blank" href="{{ route('purchases.print', $purchase->id) }}" class="btn btn-sm btn-secondary ms-2">
                <i class="bi bi-printer"></i> Print
            </a>
        </div>
    </div>
    <div class="card-header rounded-3 mt-3">
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
    <div class="card shadow-sm border-0 rounded-3 mt-3">
        <div class="card-body">
            <h5 class="mb-4 fw-bold text-secondary">Informasi Purchase Request:</h5>
            {{-- ==== Informasi Utama ==== --}}
            <div class="row mb-3">
                <div class="col-lg-4">
                    <div class="form-group">
                        <label class="text-muted">No. Permintaan</label>
                        <input type="text" class="form-control" value="{{ $purchase->reference }}" readonly>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-group">
                        <label class="text-muted">Department</label>
                        <input type="text" class="form-control" 
                            value="{{ optional($purchase->department)->department_name ?? '-' }}" readonly>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-group">
                        <label class="text-muted">Requester</label>
                        <input type="text" class="form-control" 
                            value="{{ optional($purchase->user)->name ?? '-' }}" readonly>
                    </div>
                </div>

                <div class="col-lg-4 mt-3">
                    <div class="form-group">
                        <label class="text-muted">Tanggal</label>
                        <input type="text" class="form-control" 
                            value="{{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}" readonly>
                    </div>
                </div>
            </div>

            {{-- ==== Catatan ==== --}}
            <div class="form-group mt-3">
                <label class="text-muted">Note</label>
                <textarea class="form-control" rows="3" readonly>{{ $purchase->note }}</textarea>
            </div>

            {{-- ==== Jika Rejected, tampilkan alasan ==== --}}
            @if($purchase->status === 'rejected' && $purchase->rejection_reason)
                <div class="form-group mt-3">
                    <label class="text-danger fw-bold">Rejection Reason</label>
                    <textarea class="form-control bg-light" rows="3" readonly>{{ $purchase->rejection_reason }}</textarea>
                </div>
            @endif

            {{-- ==== Tabel Produk ==== --}}
            <div class="mt-4">
                <h5 class="fw-bold mb-2 text-dark">Product List</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Product</th>
                                <th>Code</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">UOM</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->purchaseDetails as $detail)
                                <tr>
                                    <td>{{ $detail->product_name }}</td>
                                    <td>{{ $detail->product_code }}</td>
                                    <td class="text-center">{{ $detail->quantity }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $detail->product->product_unit ?? '-' }}</span>
                                    </td>
                                    <td class="text-right">{{ format_currency($detail->unit_price) }}</td>
                                    <td class="text-right">{{ format_currency($detail->sub_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ==== Ringkasan Budget ==== --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body table-responsive">
                    <h5 class="fw-bold mb-3 text-dark">Budget Summary</h5>

                    @php 
                        $status = strtolower($purchase->status); 
                        // Cek apakah request ini TIPE-nya Over Budget
                        $isOverBudget = isset($approvalRequest) && $approvalRequest->requestable_type === 'Over Budget';
                    @endphp

                    <table class="table table-striped">
                        {{-- Tampilkan Grand Total (selalu sama) --}}
                        <tr>
                            <th class="text-start text-muted">Grand Total PR Ini</th>
                            <td class="text-end fw-bold">{{ format_currency($purchase->total_amount) }}</td>
                        </tr>

                        @if ($isOverBudget)
                            {{-- =================================== --}}
                            {{-- == TAMPILAN KHUSUS UNTUK OVER BUDGET == --}}
                            {{-- =================================== --}}
                            
                            @php
                                // Ambil snapshot budget departemen (misal: 1.800.000)
                                $budgetDeptTersedia = $purchase->master_budget_value ?? 0; 
                                
                                // Ambil nilai over budget (misal: -250.000)
                                $overageAmount = $purchase->master_budget_remaining ?? 0;
                                $sisaMB = $saldoOverBudget - abs($overageAmount);
                                
                                // Hitung sisa budget departemen (misal: 0)
                                // Rumus: 1.800.000 - (2.050.000 - 250.000) = 0
                                $sisaBudgetDept = $budgetDeptTersedia - ($purchase->total_amount - abs($overageAmount));
                            @endphp

                            <tr>
                                <th class="text-start text-muted">Budget {{ optional($purchase->department)->department_name ?? '-' }} (Tersedia)</th>
                                <td class="text-end fw-bold">
                                    {{ format_currency($budgetDeptTersedia) }}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-start text-muted">Sisa Budget {{ optional($purchase->department)->department_name ?? '-' }}</th>
                                <td class="text-end fw-bold">
                                    {{ format_currency($sisaBudgetDept) }} {{-- Harusnya 0 --}}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-start text-muted">Over Budget (Diajukan)</th>
                                <td class="text-end fw-bold text-danger">
                                    {{ format_currency(abs($overageAmount)) }}
                                </td>
                            </tr>
                            @if ($purchase->status === 'pending')
                            <tr>
                                <th class="text-start text-muted">Saldo Over Budget (Saat Ini)</th>
                                <td class="text-end fw-bold" style="color: {{ $saldoOverBudget < 0 ? 'red' : 'green' }}">
                                    {{ format_currency($saldoOverBudget) }}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-start text-muted">Sisa Over Budget</th>
                                <td class="text-end fw-bold">
                                    {{ format_currency($sisaMB) }}
                                </td>
                            </tr>
                            @endif

                        @elseif ($status === 'pending')
                            {{-- =================================== --}}
                            {{-- == TAMPILAN UNTUK PENDING (NORMAL) == --}}
                            {{-- =================================== --}}
                            
                            @php $remainingAfterThisPR = $sisaBudgetSetelahPRIni; @endphp 

                            <tr>
                                <th class="text-start text-muted">Budget Tersedia (Saat Ini)</th>
                                <td class="text-end fw-bold">{{ format_currency($currentRemainingBudget) }}</td>
                            </tr>
                            <tr>
                                <th class="text-start text-muted">Sisa Budget (Jika Disetujui)</th> 
                                <td class="text-end fw-bold" style="color: {{ $remainingAfterThisPR < 0 ? 'red' : 'green' }}">
                                    {{ format_currency($remainingAfterThisPR) }} 
                                </td>
                            </tr>

                        @else
                            {{-- ============================================ --}}
                            {{-- == TAMPILAN FINAL (APPROVED/REJECTED NORMAL) == --}}
                            {{-- ============================================ --}}
                            <tr>
                                <th class="text-start text-muted">Budget Tersedia (Saat Diproses)</th>
                                <td class="text-end fw-bold">
                                    {{ format_currency($purchase->master_budget_value ?? 0) }}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-start text-muted">Sisa Budget (Saat Diproses)</th>
                                <td class="text-end fw-bold {{ ($purchase->master_budget_remaining ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ format_currency($purchase->master_budget_remaining ?? 0) }}
                                </td>
                            </tr>
                        @endif

                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const remaining = {{ $sisaBudgetSetelahPRIni }};
    const status = '{{ $purchase->status }}';

    // === Notif jika budget minus ===
    if (remaining < 0) {
        Swal.fire({
            title: 'Budget Melebihi Batas!',
            text: 'Total permintaan melebihi budget yang tersedia.',
            icon: 'warning',
            confirmButtonColor: '#e74c3c'
        });
    }

    // === Tombol edit ===
    document.getElementById('edit-btn').addEventListener('click', function (e) {
        if (status === 'pending') {
            e.preventDefault();
            Swal.fire({
                title: 'Menunggu Approval',
                text: 'PR ini masih menunggu approval. Perubahan hanya diperbolehkan sebelum disetujui.',
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = this.href;
            });
        }
    });

    // === Approval Action ===
    document.querySelectorAll('.approval-action').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const newStatus = this.dataset.status;

            Swal.fire({
                title: `Ubah status menjadi "${newStatus.toUpperCase()}"?`,
                text: `Apakah kamu yakin ingin ${newStatus === 'approved' ? 'menyetujui' : 'menolak'} PR ini?`,
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
                            inputPlaceholder: 'Tuliskan alasan kenapa PR ini ditolak...',
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

    // === Submit ke server ===
    // function submitApproval(status, reason = null) {
    //     fetch("{{ route('purchases.updateStatus', $purchase->id) }}", {
    //         method: "POST",
    //         headers: {
    //             "X-CSRF-TOKEN": "{{ csrf_token() }}",
    //             "Content-Type": "application/json"
    //         },
    //         body: JSON.stringify({ status: status, reason: reason })
    //     })
    //     .then(res => res.json())
    //     .then(data => {
    //         if (data.success) {
    //             Swal.fire(
    //                 'Berhasil!',
    //                 `Status berhasil diubah menjadi ${status}${reason ? ' dengan alasan: ' + reason : ''}.`,
    //                 'success'
    //             ).then(() => location.reload());
    //         } else {
    //             Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
    //         }
    //     });
    // }
    function submitApproval(status, reason = null) {
        let url = "";
        let data = {
            _token: "{{ csrf_token() }}", // Token keamanan Laravel
            status: status
            // 'reason' tidak lagi dikirim, kita ganti dengan 'notes' agar konsisten
        };

        // 1. Tentukan URL Controller mana yang akan dituju
        if (status === 'approved') {
            // Ganti ke rute 'approve' untuk purchase
            url = "{{ route('purchases.approve', $purchase->id) }}"; 
        } else if (status === 'rejected') {
            // Ganti ke rute 'reject' untuk purchase
            url = "{{ route('purchases.reject', $purchase->id) }}"; 
            data.notes = reason; // Tambahkan 'notes' (sesuai standar MasterBudget)
        }

        if (url === "") return; 

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
                return response.json().then(err => { throw new Error(err.error || 'Terjadi kesalahan.') });
            }
            return response.json(); // Ubah respons server menjadi JSON
        })
        .then(data => {
            // 3. Tampilkan pesan berdasarkan respons dari Controller
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message, // Tampilkan pesan sukses dari server
                    icon: 'success'
                }).then(() => {
                    location.reload(); // Muat ulang halaman untuk melihat status baru
                });
            } else {
                Swal.fire('Gagal!', data.error || 'Terjadi kesalahan saat memproses.', 'error');
            }
        })
        .catch(error => {
            // 4. Tampilkan pesan jika koneksi gagal
            console.error('Fetch Error:', error);
            Swal.fire('Error!', error.message || 'Tidak dapat terhubung ke server.', 'error');
        });
    }
});
</script>
@endpush
