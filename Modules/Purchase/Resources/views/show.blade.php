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
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-secondary">Purchase Request Detail</h5>
            <div>
                <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-sm btn-warning me-2" id="edit-btn">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>

                {{-- Status Dropdown --}}
                <div class="btn-group">
                    @if ($purchase->status === 'pending')
                        <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                            <i class="bi bi-hourglass-split"></i> Pending
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
                    @elseif ($purchase->status === 'approved')
                        <button class="btn btn-sm btn-success" disabled>
                            <i class="bi bi-check2-circle"></i> Approved
                        </button>
                    @elseif ($purchase->status === 'rejected')
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

        <div class="card-body">
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

                    {{-- Gunakan variabel baru --}}
                    @php $remainingAfterThisPR = $sisaBudgetSetelahPRIni; @endphp 

                    <table class="table table-striped">
                        <tr>
                            <th class="text-start text-muted">Grand Total</th>
                            <td class="text-end fw-bold">{{ format_currency($purchase->total_amount) }}</td>
                        </tr>
                        <tr>
                            <th class="text-start text-muted">Budget {{ optional($purchase->department)->department_name ?? '-' }}</th>
                            <td class="text-end fw-bold">{{ format_currency($currentRemainingBudget) }}</td>
                        </tr>
                        <tr>
                            {{-- Ubah label agar lebih jelas --}}
                            <th class="text-start text-muted">Sisa Budget (Jika PR Ini Disetujui)</th> 
                            <td class="text-end fw-bold" style="color: {{ $remainingAfterThisPR < 0 ? 'red' : 'green' }}">
                                {{-- Tampilkan hasil perhitungan baru --}}
                                {{ format_currency($remainingAfterThisPR) }} 
                            </td>
                        </tr>
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
    const remaining = {{ $purchase->master_budget_remaining }};
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
                                if (!value) return 'Alasan tidak boleh kosong!';
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
    function submitApproval(status, reason = null) {
        fetch("{{ route('purchases.updateStatus', $purchase->id) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ status: status, reason: reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire(
                    'Berhasil!',
                    `Status berhasil diubah menjadi ${status}${reason ? ' dengan alasan: ' + reason : ''}.`,
                    'success'
                ).then(() => location.reload());
            } else {
                Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
            }
        });
    }
});
</script>
@endpush
