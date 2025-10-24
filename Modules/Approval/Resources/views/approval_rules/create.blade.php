@extends('layouts.app')
@section('title','Create Approval Rule')
@section('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('approval_rules.index') }}">Approval Rules</a></li>
    <li class="breadcrumb-item active">Create</li>
</ol>
@endsection

@section('content')
<div class="container-fluid">
    @include('utils.alerts')
    <form method="POST" action="{{ route('approval_rules.store') }}" id="rule-form">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-row mb-3">
                    <div class="col-md-4">
                        <label>Approval Type</label>
                        <select name="approval_types_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}">{{ $t->approval_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Rule Name</label>
                        <input name="rule_name" class="form-control" required />
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked> Active
                    </div>
                </div>

                <div>
                    <h5>Levels</h5>
                    <div id="levels-container"></div>
                    <button type="button" id="add-level" class="btn btn-sm btn-primary mt-2">+ Tambah Level Baru</button>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-success">Simpan</button>
                <a href="{{ route('approval_rules.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('page_scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let levelIndex = 0;

/* -------------------- FORMAT RUPIAH -------------------- */
function formatRupiah(angka, prefix = 'Rp. ') {
    if (!angka) return prefix + '0';
    let number_string = angka.replace(/[^,\d]/g, '').toString(),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    return prefix + rupiah;
}

// Fokus input → kosongkan jika 0
$(document).on('focus', '.amount-limit', function () {
    let val = $(this).val().replace(/[^0-9]/g, '');
    if (val === '' || val === '0') $(this).val('');
});

// Saat mengetik → ubah ke format rupiah
$(document).on('input', '.amount-limit', function () {
    let digits = $(this).val().replace(/[^0-9]/g, '').replace(/^0+/, '');
    $(this).val(digits === '' ? 'Rp. 0' : formatRupiah(digits));
});

// Blur → jika kosong, tampilkan Rp. 0
$(document).on('blur', '.amount-limit', function () {
    let digits = $(this).val().replace(/[^0-9]/g, '');
    $(this).val(digits === '' ? 'Rp. 0' : formatRupiah(digits));
});

// Submit → ubah ke angka murni
$('#rule-form').on('submit', function () {
    $('.amount-limit').each(function () {
        let numeric = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(numeric === '' ? '0' : numeric);
    });
});
/* ------------------------------------------------------ */

function userPairRowHtml(levelIdx, pairIdx) {
    return `
    <div class="row align-items-end mb-2 user-pair" data-pair="${pairIdx}">
        <div class="col-md-5">
            <label>Requester</label>
            <select name="levels[${levelIdx}][pairs][${pairIdx}][requester][]" 
                class="form-control select2-user requester" multiple required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label>Approver</label>
            <select name="levels[${levelIdx}][pairs][${pairIdx}][approver][]" 
                class="form-control select2-user approver" multiple required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 text-center">
            <button type="button" class="btn btn-danger btn-sm remove-pair">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>`;
}

function levelHtml(idx) {
    return `
    <div class="level-card border rounded-3 p-3 mb-3" data-idx="${idx}">
        <div class="row mb-3">
            <div class="col-md-2">
                <label>Level</label>
                <input type="number" name="levels[${idx}][level]" class="form-control" value="${idx + 1}" readonly>
            </div>
            <div class="col-md-3">
                <label>Amount Limit</label>
                <input type="text" name="levels[${idx}][amount_limit]" class="form-control amount-limit" value="Rp. 0" required>
            </div>
            <div class="col-md-3 text-end">
                <label>&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-sm remove-level">
                    <i class="bi bi-trash"></i> Hapus Level
                </button>
            </div>
        </div>
        <div class="user-pair-container" id="pair-container-${idx}">
            ${userPairRowHtml(idx, 0)}
        </div>
        <button type="button" class="btn btn-sm btn-primary mt-2 add-pair" data-level="${idx}">
            + Tambah Requester & Approver
        </button>
    </div>`;
}

function initSelect2(container) {
    container.find('.select2-user').select2({
        placeholder: "Cari dan pilih user...",
        allowClear: true
    });
}

$(function() {
    const container = $('#levels-container');

    // Tambah level pertama
    container.append(levelHtml(levelIndex));
    initSelect2(container);

    // Tambah level baru
    $('#add-level').click(function() {
        levelIndex++;
        container.append(levelHtml(levelIndex));
        initSelect2(container);
    });

    // Hapus level
    $(document).on('click', '.remove-level', function() {
        $(this).closest('.level-card').remove();
        $('#levels-container .level-card').each(function(i, el) {
            $(el).attr('data-idx', i);
            $(el).find('[name]').each(function() {
                let name = $(this).attr('name');
                name = name.replace(/levels\[\d+\]/, `levels[${i}]`);
                $(this).attr('name', name);
            });
        });
        levelIndex = $('#levels-container .level-card').length - 1;
    });

    // Tambah pair requester/approver
    $(document).on('click', '.add-pair', function() {
        const levelIdx = $(this).data('level');
        const pairContainer = $(`#pair-container-${levelIdx}`);
        const pairIdx = pairContainer.find('.user-pair').length;
        pairContainer.append(userPairRowHtml(levelIdx, pairIdx));
        initSelect2(pairContainer);
    });

    // Hapus pair
    $(document).on('click', '.remove-pair', function() {
        $(this).closest('.user-pair').remove();
    });
});
</script>
@endpush
