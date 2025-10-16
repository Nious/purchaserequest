@extends('layouts.app')

@section('title', 'Create Purchase')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>
@endsection

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
                        <livewire:product-cart :cartInstance="'purchase'" />

                        {{-- Submit Button --}}
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                Create Purchase <i class="bi bi-check"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page_scripts')
<script src="{{ asset('js/jquery-mask-money.js') }}"></script>
<script>
$(document).ready(function () {
    // MaskMoney untuk paid_amount (jika ada)
    $('#paid_amount').maskMoney({
        prefix: '{{ settings()->currency->symbol }} ',
        thousands: '{{ settings()->currency->thousand_separator }}',
        decimal: '{{ settings()->currency->decimal_separator }}',
        precision: 0,
        allowZero: true,
    });

    // Ambil total tombol
    $('#getTotalAmount').click(function () {
        let cartTotal = {{ Cart::instance('purchase')->total(0, '', '') }};
        $('#paid_amount').val(cartTotal);
        $('#paid_amount').maskMoney('mask');
    });

    // Submit form
    $('#purchase-form').submit(function () {
        var rawValue = $('#paid_amount').maskMoney('unmasked')[0];
        if (isNaN(rawValue)) rawValue = 0;
        $('#paid_amount').val(rawValue); 
        return true;
    });
});
</script>
@endpush
