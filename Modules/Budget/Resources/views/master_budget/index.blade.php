@extends('layouts.app')

@section('title', 'Master Budget PR')

@section('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Master Budget</li>
</ol>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('master_budget.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Budget
                    </a>
                    <hr>
                    <div class="card">
                        <div class="card-body table-responsive">
                            {!! $dataTable->table(['class' => 'table table-bordered table-striped']) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="rejectForm">
      @csrf
      <input type="hidden" name="id" id="reject_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Alasan Reject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <textarea name="notes" id="reject_notes" class="form-control" placeholder="Masukkan alasan reject" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Submit</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(function () {
    // ===== Format Rupiah =====
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

    $('form').on('submit', function () {
        $('.budget-input').each(function () {
            $(this).val(parseRupiah($(this).val()));
        });
        $('#grandtotal').val(parseRupiah($('#grandtotal').val()));
    });

    // ===== AJAX Approve =====
    $(document).on('click', '.approve-btn', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        $.ajax({
            url: '/master-budget/approve/' + id,
            method: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(res) {
                $('#master_budget-table').DataTable().ajax.reload();
                alert('Status berhasil diubah menjadi Approved');
            }
        });
    });

    // ===== AJAX Reject (tampilkan modal) =====
    $(document).on('click', '.reject-btn', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        $('#reject_id').val(id);
        $('#reject_notes').val('');
        $('#rejectModal').modal('show');
    });

    // ===== Submit Reject =====
    $('#rejectForm').submit(function(e) {
        e.preventDefault();
        let id = $('#reject_id').val();
        let notes = $('#reject_notes').val();

        $.ajax({
            url: '/master-budget/reject/' + id,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                notes: notes
            },
            success: function(res) {
                $('#rejectModal').modal('hide');
                $('#master_budget-table').DataTable().ajax.reload();
                alert('Status berhasil diubah menjadi Rejected');
            }
        });
    });
});
</script>
@endpush