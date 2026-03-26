@extends('layouts.app')

@section('title', 'Input Actual Purchase')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Input Actual</li>
</ol>
@endsection

@php
    $departmentId = $purchase->department_id ?? '';
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

                    <form id="purchase-form"
                          action="{{ route('purchases.updateActual', $purchase) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>No. Permintaan</label>
                                    <input type="text" class="form-control" readonly value="{{ $purchase->reference }}">
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" class="form-control" readonly
                                           value="{{ optional($purchase->department)->department_name ?? '-' }}">
                                    <input type="hidden" name="department_id" value="{{ $purchase->department_id }}">
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Requester</label>
                                    <input type="text" class="form-control" readonly
                                           value="{{ optional($purchase->user)->name ?? '-' }}">
                                    <input type="hidden" name="users_id" value="{{ $purchase->users_id }}">
                                </div>
                            </div>

                            <div class="col-lg-4 mt-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="date"
                                           value="{{ $purchaseDateValue }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group mt-3">
                            <label>Note</label>
                            <textarea name="note" rows="4" class="form-control">{{ $purchase->note }}</textarea>
                        </div>

                        {{-- Product Cart --}}
                        <livewire:product-cart
                            :cartInstance="'purchase'"
                            :data="$purchase"
                            :departmentId="$departmentId"
                            :purchaseDate="$purchaseDateValue" />

                        {{-- Budget Summary --}}
                        <div class="card shadow-sm mt-4">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Budget Summary</h5>

                                <table class="table table-borderless">
                                    <tr>
                                        <th>Actual Grand Total</th>
                                        <td class="text-end fw-bold" id="grand_total_display">
                                            Rp{{ number_format($purchase->total_amount ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Budget (Snapshot Saat Approved)</th>
                                        <td class="text-end fw-bold" id="budget_display">
                                            Rp{{ number_format($purchase->master_budget_value ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Sisa Budget PR</th>
                                        <td class="text-end fw-bold" id="sisa_budget_display">
                                            Rp{{ number_format($purchase->master_budget_remaining ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>                                    
                                </table>

                                {{-- Hidden --}}
                                <input type="hidden" name="total_amount" id="total_amount"
                                       value="{{ $purchase->total_amount ?? 0 }}">
                                       <input type="hidden" name="master_budget_value"
                                       id="master_budget_value"
                                       value="{{ $purchase->master_budget_value }}">                                
                                <input type="hidden" name="master_budget_remaining"
                                       id="master_budget_remaining"
                                       value="{{ $purchase->master_budget_remaining }}">
                                
                            </div>
                        </div>

                        <div class="text-end mt-4">
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
const formatRupiah = v =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
    }).format(Number(v) || 0);

document.addEventListener('livewire:init', () => {
    Livewire.on('update-budget-fields', data => {
        const p = Array.isArray(data) ? data[0] : data;

        document.getElementById('grand_total_display').innerText = formatRupiah(p.total_amount);
        document.getElementById('budget_display').innerText = formatRupiah(p.master_budget_value);
        document.getElementById('sisa_budget_display').innerText = formatRupiah(p.master_budget_remaining);

        document.getElementById('total_amount').value = p.total_amount;
        document.getElementById('master_budget_value').value = p.master_budget_value;
        document.getElementById('master_budget_remaining').value = p.master_budget_remaining;
    });
});
</script>
@endpush
