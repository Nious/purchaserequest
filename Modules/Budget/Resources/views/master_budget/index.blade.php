@extends('layouts.app')

@section('title', 'Master Budget PR')

@section('content')
<div class="container">
    <h4 class="mb-3">Master Budget PR</h4>
    <a href="{{ route('master_budget.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Tambah Budget
    </a>
    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped']) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
<script>
    $(function () {
    let formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    function formatToRupiah(value) {
        return formatter.format(value);
    }

    function parseRupiah(value) {
        return parseInt(value.replace(/[^0-9]/g, '')) || 0;
    }

    // Saat user ketik budget
    $(document).on('input', '.budget-input', function () {
        let raw = parseRupiah($(this).val());
        $(this).val(formatToRupiah(raw));
        calculateGrandtotal();
    });

    function calculateGrandtotal() {
        let total = 0;
        $('.budget-input').each(function () {
            total += parseRupiah($(this).val());
        });
        $('#grandtotal').val(formatToRupiah(total));
    }

    // Saat submit form → ubah kembali ke angka murni
    $('form').on('submit', function () {
        $('.budget-input').each(function () {
            $(this).val(parseRupiah($(this).val()));
        });
        $('#grandtotal').val(parseRupiah($('#grandtotal').val()));
    });
});
</script>
@endpush
