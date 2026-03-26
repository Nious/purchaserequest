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
    // Format tanggal untuk input date HTML5
    $purchaseDateValue = old('date', \Carbon\Carbon::parse($purchase->date)->format('Y-m-d'));
@endphp

@section('content')
<div class="container-fluid mb-4">
    {{-- Search Product --}}
    <div class="row">
        <div class="col-12">
            <livewire:search-product :departmentId="$departmentId" :date="$purchaseDateValue" />
        </div>
    </div>

    {{-- Purchase Form --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @include('utils.alerts')

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
                                    <input type="date" class="form-control" name="date" required value="{{ $purchaseDateValue }}">
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group mt-3">
                            <label for="note">Note (If Needed)</label>
                            <textarea name="note" id="note" rows="5" class="form-control">{{ $purchase->note }}</textarea>
                        </div>

                        {{-- <div class="row">
                            <div class="">
                                <div class="card shadow-sm border-1">
                                    <div class="card-header bg-dark text-white fw-bold">
                                        <i class="bi bi-box-seam me-2"></i>Products Master Data
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Code</th>
                                                        <th>Price</th>
                                                        <th>UOM</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($products as $product)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $product->product_name }}</td>
                                                            <td>
                                                                <span class="badge bg-secondary text-black">{{ $product->product_code ?? '-' }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="text-success fw-bold">
                                                                    Rp{{ number_format($product->product_price, 0, ',', '.') }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info text-white">{{ $product->product_unit ?? '-' }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            Data produk ini hanya sebagai referensi. Tidak dapat diubah di sini. List Produk Akan Terupdate Jika PR Sudah Disimpan (Tidak Realtime)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Product Cart --}}
                        <livewire:product-cart :cartInstance="'purchase'" :data="$purchase" :departmentId="$departmentId" :purchaseDate="$purchaseDateValue" />

                        {{-- ====== Ringkasan Budget ====== --}}
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-body table-responsive">
                                <h5 class="fw-bold mb-3 text-secondary">Budget Summary</h5>

                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-start text-muted">Estimate Grand Total</th>
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
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Fungsi Helper Format Rupiah
const formatRupiah = (angka) => {
    let num = Number(angka);
    if (isNaN(num)) num = 0;
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR', 
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
};

// Variabel Global untuk menyimpan data budget luar departemen (Over Budget)
let globalNonDeptBudgetRemaining = 0;

document.addEventListener('livewire:init', () => {
    Livewire.on('update-budget-fields', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        console.log('Data diterima dari Livewire (edit):', payload);

        const grandTotalEl = document.getElementById('grand_total_display');
        const budgetEl = document.getElementById('budget_display');
        const sisaEl = document.getElementById('sisa_budget_display');

        const total = Number(payload.total_amount);
        const budget = Number(payload.master_budget_value);
        const remaining = Number(payload.master_budget_remaining);

        // Update Tampilan
        grandTotalEl.innerText = formatRupiah(total);
        budgetEl.innerText = formatRupiah(budget);
        sisaEl.innerText = formatRupiah(remaining);

        // Update Warna Sisa Budget
        if (remaining < 0) {
            sisaEl.classList.add('text-danger', 'fw-bold');
            sisaEl.classList.remove('text-success');
        } else {
            sisaEl.classList.remove('text-danger', 'fw-bold');
            sisaEl.classList.add('text-success');
        }

        // Update Input Hidden
        document.getElementById('total_amount').value = total;
        document.getElementById('master_budget_value').value = budget;
        document.getElementById('master_budget_remaining').value = remaining;

        // Simpan data Over Budget
        globalNonDeptBudgetRemaining = Number(payload.non_dept_budget_remaining) || 0;
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Listener perubahan tanggal untuk Livewire
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const newDate = this.value;
            console.log('Tanggal berubah:', newDate);
            Livewire.dispatch('dateChanged', { date: newDate });
        });
    }

    const form = document.getElementById('purchase-form');
    const status = '{{ strtolower($purchase->status) }}';

    // Disable form jika status sudah final
    if (status === 'rejected') {
        form.querySelectorAll('input, textarea, select').forEach(element => {
            element.disabled = true;
        });

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.style.display = 'none';
        }

        // Sembunyikan elemen pencarian livewire
        const searchContainer = document.querySelector('.row > .col-12');
        if(searchContainer && searchContainer.querySelector('input[wire\\:model]')) {
             searchContainer.style.display = 'none';
        }

        Swal.fire({
            title: `Status: ${status.charAt(0).toUpperCase() + status.slice(1)}`,
            text: 'Purchase Request ini tidak dapat diedit lagi karena sudah diproses.',
            icon: 'info',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#3085d6'
        });
    }

    // === LOGIKA SUBMIT (SAMA DENGAN CREATE) ===
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Cek status lagi untuk keamanan
        if (status === 'rejected') {
             Swal.fire('Gagal', 'Data ini tidak dapat diubah lagi.', 'error');
             return;
        }

        const totalAmount = Number(document.getElementById('total_amount').value);
        const remaining = Number(document.getElementById('master_budget_remaining').value);

        // 1. Cek Keranjang Kosong
        if (totalAmount <= 0) {
            Swal.fire({
                title: 'Keranjang Kosong!',
                text: 'Anda harus menambahkan setidaknya satu produk.',
                icon: 'error',
                confirmButtonColor: '#d33',
            });
            return;
        }

        // 2. Cek apakah Melebihi Budget Departemen?
        if (remaining < 0) {
            const defisit = Math.abs(remaining); // Kekurangan dana
            
            // Hitung: Sisa Budget Dept (Minus) + Sisa Saldo Over Budget (Positif)
            const kalkulasiAkhir = remaining + globalNonDeptBudgetRemaining;
            
            const defisitRp = formatRupiah(defisit);
            const saldoOverBudgetRp = formatRupiah(globalNonDeptBudgetRemaining);

            // A. Jika Dana Over Budget JUGA TIDAK CUKUP
            if (kalkulasiAkhir < 0) {
                Swal.fire({
                    title: 'Dana Tidak Mencukupi!',
                    html: `
                        Budget Departemen Kurang: <b class="text-danger">${defisitRp}</b><br>
                        Saldo Over Budget Tersedia: <b>${saldoOverBudgetRp}</b><br><br>
                        Total dana (Dept + Over Budget) masih kurang <b>${formatRupiah(Math.abs(kalkulasiAkhir))}</b>. <br>Anda tidak dapat memperbarui data ini.
                    `,
                    icon: 'error',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#d33',
                });
                return; // Stop
            } 
            
            // B. Jika Dana Over Budget CUKUP
            else {
                Swal.fire({
                    title: 'Budget Departemen Habis!',
                    html: `
                        Total PR melebihi budget departemen sebesar <b class="text-danger">${defisitRp}</b>.<br>
                        Akan menggunakan dana <b>Over Budget</b> (Tersedia: ${saldoOverBudgetRp}).<br><br>
                        Apakah Anda yakin ingin memperbarui data ini?
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Gunakan Over Budget',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tambahkan penanda over budget
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'confirmed_over_budget';
                        input.value = '1';
                        form.appendChild(input);

                        form.submit(); // Lanjut Submit
                    }
                });
            }
        } 
        // 3. Jika Budget Cukup (Normal)
        else {
            Swal.fire({
                title: 'Konfirmasi Perubahan',
                text: 'Apakah kamu yakin ingin menyimpan perubahan pada data ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
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