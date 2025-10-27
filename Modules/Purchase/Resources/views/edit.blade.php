@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Edit</li>
</ol>
@endsection

@php
    $departmentId = $purchase->department_id ?? '';
    $purchaseDateValue = old('date', $purchase->date);
@endphp

@section('content')
<div class="container-fluid mb-4">
    {{-- Search Product --}}
    <div class="row">
        <div class="col-12">
            <livewire:search-product/>
        </div>
    </div>

    {{-- Purchase Form --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @include('utils.alerts')

                    {{-- === FORM YANG BENAR === --}}
                    <form id="purchase-form" action="{{ route('purchases.update', $purchase->id) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-row">
                            {{-- No. Permintaan --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="reference">No. Permintaan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="reference" readonly value="{{ $purchase->reference }}">
                                </div>
                            </div>

                            {{-- Department --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="department_id">Department <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" 
                                           value="{{ optional($purchase->department)->department_name ?? '-' }}" readonly>
                                    <input type="hidden" name="department_id" 
                                           value="{{ $purchase->department_id ?? '' }}">
                                </div>
                            </div>

                            {{-- Requester --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="users_id">Requester <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" 
                                           value="{{ optional($purchase->user)->name ?? '-' }}" readonly> 
                                    <input type="hidden" name="users_id" 
                                           value="{{ $purchase->users_id ?? '' }}">
                                </div>
                            </div>

                            {{-- Date --}}
                            <div class="col-lg-4 mt-3">
                                <div class="form-group">
                                    <label for="date">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date" required value="{{ $purchase->date }}">
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group mt-3">
                            <label for="note">Note (If Needed)</label>
                            <textarea name="note" id="note" rows="5" class="form-control">{{ $purchase->note }}</textarea>
                        </div>

                        {{-- Product Cart --}}
                        <livewire:product-cart :cartInstance="'purchase'" :data="$purchase" :departmentId="$departmentId" :purchaseDate="$purchaseDateValue" />

                        {{-- ====== Ringkasan Budget ====== --}}
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-body table-responsive">
                                <h5 class="fw-bold mb-3 text-secondary">Budget Summary</h5>

                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-start text-muted">Grand Total</th>
                                        <td class="text-end fw-bold" id="grand_total_display">
                                            Rp{{ number_format($purchase->total_amount ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-start text-muted">Budget {{ optional($purchase->department)->department_name ?? '-' }}</th>
                                        <td class="text-end fw-bold" id="budget_display">
                                            Rp{{ number_format($purchase->master_budget_value ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-start text-muted">Sisa Budget</th>
                                        <td class="text-end fw-bold {{ ($purchase->master_budget_remaining ?? 0) < 0 ? 'text-danger' : 'text-success' }}" id="sisa_budget_display">
                                            Rp{{ number_format($purchase->master_budget_remaining ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </table>

                                {{-- Hidden input untuk Controller --}}
                                <input type="hidden" name="total_amount" id="total_amount" value="{{ $purchase->total_amount ?? 0 }}">
                                <input type="hidden" name="master_budget_value" id="master_budget_value" value="{{ $purchase->master_budget_value ?? 0 }}">
                                <input type="hidden" name="master_budget_remaining" id="master_budget_remaining" value="{{ $purchase->master_budget_remaining ?? 0 }}">
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save"></i> Update Purchase
                            </button>
                        </div>
                    </form> {{-- Tutup form dengan benar --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('page_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('update-budget-fields', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        console.log('Data diterima dari Livewire (edit):', payload);

        const formatRupiah = (angka) => {
            const num = Number(angka) || 0;
            return 'Rp' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        };

        // Update tampilan
        document.getElementById('grand_total_display').innerText = formatRupiah(payload.total_amount);
        document.getElementById('budget_display').innerText = formatRupiah(payload.master_budget_value);
        const sisa = document.getElementById('sisa_budget_display');
        sisa.innerText = formatRupiah(payload.master_budget_remaining);

        // Warna merah jika minus
        if (payload.master_budget_remaining < 0) {
            sisa.classList.add('text-danger', 'fw-bold');
            sisa.classList.remove('text-success');
        } else {
            sisa.classList.remove('text-danger', 'fw-bold');
            sisa.classList.add('text-success');
        }

        // Hidden input update
        document.getElementById('total_amount').value = payload.total_amount;
        document.getElementById('master_budget_value').value = payload.master_budget_value;
        document.getElementById('master_budget_remaining').value = payload.master_budget_remaining;
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const newDate = this.value;
            console.log('Tanggal berubah:', newDate);
            Livewire.dispatch('dateChanged', { date: newDate });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchase-form');
    const status = '{{ $purchase->status }}'; // Ambil status dari server

    // === 🔔 Munculkan notifikasi otomatis jika status pending ===
    if (status === 'pending') {
        Swal.fire({
            title: 'Menunggu Approval',
            text: 'Purchase Request ini masih menunggu persetujuan. Anda masih bisa mengedit sebelum disetujui.',
            icon: 'info',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#3085d6'
        });
    }

    // === Konfirmasi sebelum submit ===
    form.addEventListener('submit', function (e) {
        e.preventDefault(); // cegah submit langsung

        Swal.fire({
            title: 'PR sedang menunggu approval',
            text: 'Apakah kamu yakin mau mengedit data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, lanjut edit',
            cancelButtonText: 'Tidak',
            reverseButtons: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // lanjutkan submit jika user klik Yes
            }
        });
    });
});
</script>
@endpush
