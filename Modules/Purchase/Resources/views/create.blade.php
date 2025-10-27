@extends('layouts.app')

@section('title', 'Create Purchase')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Create</li>
</ol>
@endsection

@php
    $departmentId = auth()->user()->department_id;
    $purchaseDateValue = old('date', now()->format('Y-m-d'));
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
                    <form id="purchase-form" action="{{ route('purchases.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            {{-- No. Permintaan --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="reference">No. Permintaan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="reference" required readonly value="{{ $prNumber }}">
                                </div>
                            </div>

                            {{-- Department --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="department_id">Department <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" 
                                        value="{{ optional(auth()->user()->department)->department_name ?? '-' }}" readonly>
                                    <input type="hidden" name="department_id" 
                                        value="{{ optional(auth()->user()->department)->id ?? '' }}">
                                </div>
                            </div>


                            {{-- Requester --}}
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="users_id">Requester <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                    <input type="hidden" name="users_id" value="{{ auth()->id() }}">
                                </div>
                            </div>

                            {{-- Date --}}
                            <div class="col-lg-4 mt-3">
                                <div class="form-group">
                                    <label for="date">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date" required value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group mt-3">
                            <label for="note">Note (If Needed)</label>
                            <textarea name="note" id="note" rows="5" class="form-control"></textarea>
                        </div>

                        {{-- Product Cart --}}
                        <livewire:product-cart :cartInstance="'purchase'" :departmentId="$departmentId" :purchaseDate="$purchaseDateValue" />

                            {{-- ====== Ringkasan Budget ====== --}}
                            <div class="card border-0 shadow-sm">
                                <div class="card-body table-responsive">
                                    <h5 class="fw-bold mb-3 text-secondary">Budget Summary</h5>
    
                                    <table class="table table-borderless">
                                        <tr>
                                            <th class="text-start text-muted">Grand Total</th>
                                            {{-- Biarkan JavaScript yang mengisi --}}
                                            <td class="text-end fw-bold" id="grand_total_display">Rp0</td>
                                        </tr>
                                        <tr>
                                            <th class="text-start text-muted">Budget {{ optional(auth()->user()->department)->department_name ?? '-' }}</th>
                                             {{-- Biarkan JavaScript yang mengisi --}}
                                            <td class="text-end fw-bold" id="budget_display">Rp0</td>
                                        </tr>
                                        <tr>
                                            <th class="text-start text-muted">Sisa Budget</th>
                                             {{-- Biarkan JavaScript yang mengisi, set kelas awal ke success --}}
                                            <td class="text-end fw-bold text-success" id="sisa_budget_display">Rp0</td>
                                        </tr>
                                    </table>
    
                                    {{-- Hidden input untuk Controller --}}
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                    <input type="hidden" name="master_budget_value" id="master_budget_value" value="0">
                                    <input type="hidden" name="master_budget_remaining" id="master_budget_remaining" value="0">
                                </div>

                                {{-- ====== Tombol Submit ====== --}}
                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save"></i> Submit Purchase
                                    </button>
                                </div>
                        </div>
                    </form>
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
        console.log('Data diterima dari Livewire:', payload);

        const formatRupiah = (angka) => {
            let num = Number(angka);
            if (isNaN(num)) num = 0;
            return num.toLocaleString('id-ID', { 
                style: 'currency', 
                currency: 'IDR', 
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        };

        const grandTotalEl = document.getElementById('grand_total_display');
        const budgetEl = document.getElementById('budget_display');
        const sisaEl = document.getElementById('sisa_budget_display');

        const total = Number(payload.total_amount);
        const budget = Number(payload.master_budget_value);
        const remaining = Number(payload.master_budget_remaining);

        grandTotalEl.innerText = formatRupiah(total);
        budgetEl.innerText = formatRupiah(budget);
        sisaEl.innerText = formatRupiah(remaining);

        // Warna merah jika negatif
        sisaEl.style.setProperty('color', remaining < 0 ? 'red' : 'black', 'important');

        document.getElementById('total_amount').value = total;
        document.getElementById('master_budget_value').value = budget;
        document.getElementById('master_budget_remaining').value = remaining;

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

// === Konfirmasi sebelum submit dengan pengecekan budget ===
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchase-form');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // cegah submit langsung

        const totalAmount = Number(document.getElementById('total_amount').value);
        const remaining = Number(document.getElementById('master_budget_remaining').value);

        // Cek Keranjang Kosong
        if (totalAmount <= 0) {
            Swal.fire({
                title: 'Keranjang Kosong!',
                text: 'Anda harus menambahkan setidaknya satu produk untuk membuat Purchase Request.',
                icon: 'error',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#d33',
            });
            return; // Hentikan proses
        }
        // Jika melebihi budget
        if (remaining < 0) {
            Swal.fire({
                title: 'Budget Melebihi Batas!',
                text: 'Total permintaan melebihi budget yang tersedia. Silakan kurangi item atau hubungi departemen terkait.',
                icon: 'error',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#d33',
            });
            // Tidak memanggil form.submit() berarti proses berhenti di sini
        }
        // Jika masih dalam budget
        else {
            Swal.fire({
                title: 'Buat Purchase Request?',
                text: 'Apakah kamu yakin ingin mengirim PR ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });
});
</script>
@endpush