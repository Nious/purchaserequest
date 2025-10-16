@extends('layouts.app')
@section('title','Create Approval Rule')
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
                        <select name="approval_type_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}">{{ $t->approval_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Rule Name</label>
                        <input name="rule_name" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <input type="checkbox" name="is_active" checked> Active
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
const userOptions = `@foreach($users as $u)<option value="{{ $u->id }}">{{ addslashes($u->name) }}</option>@endforeach`;

// --- TEMPLATE BARIS USER (Requester & Approver) ---
function userRowHtml(levelIdx, userIdx) {
    return `
    <div class="row align-items-end mb-2 user-row" data-user-idx="${userIdx}">
        <div class="col-md-5">
            <label>Requester</label>
            <select name="levels[${levelIdx}][users][${userIdx}][requester]" class="form-control select2 requester" required>
                <option value="">-- pilih requester --</option>
                ${userOptions}
            </select>
        </div>
        <div class="col-md-5">
            <label>Approver</label>
            <select name="levels[${levelIdx}][users][${userIdx}][approver]" class="form-control select2 approver" required>
                <option value="">-- pilih approver --</option>
                ${userOptions}
            </select>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-danger btn-sm remove-user-row mt-4">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>`;
}

// --- TEMPLATE LEVEL ---
function levelHtml(idx){
    return `
    <div class="level-card border p-3 mb-3" data-idx="${idx}">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Level <span class="level-number">${idx+1}</span></strong>
            <button type="button" class="btn btn-danger btn-sm remove-level">Delete Level</button>
        </div>

        <div class="row align-items-end mb-3">
            <div class="col-md-2">
                <label>Level #</label>
                <input type="number" name="levels[${idx}][level]" class="form-control" value="${idx+1}" required>
            </div>
            <div class="col-md-3">
                <label>Amount Limit</label>
                <input type="number" step="0.01" name="levels[${idx}][amount_limit]" class="form-control" placeholder="Masukkan batas nominal" required>
            </div>
            <div class="col-md-7">
                <div class="user-container" id="user-container-${idx}">
                    ${userRowHtml(idx, 0)}
                </div>
                <button type="button" class="btn btn-sm btn-primary mt-2 add-user-row" data-level="${idx}">
                    + Tambah User Request
                </button>
            </div>
        </div>
    </div>`;
}

function initSelect2(container){
    container.find('.select2').select2({ width:'100%' });
}

$(function(){
    const container = $('#levels-container');

    // tampilkan level pertama langsung
    container.append(levelHtml(levelIndex));
    initSelect2(container);

    // tambah level baru
    $('#add-level').click(function(){
        levelIndex++;
        container.append(levelHtml(levelIndex));
        initSelect2(container);
    });

    // hapus level
    $(document).on('click','.remove-level', function(){
        $(this).closest('.level-card').remove();
        $('#levels-container .level-card').each(function(i,el){
            $(el).attr('data-idx', i);
            $(el).find('.level-number').text(i+1);
            $(el).find('[name]').each(function(){
                let name = $(this).attr('name');
                name = name.replace(/levels\[\d+\]/, `levels[${i}]`);
                $(this).attr('name', name);
            });
        });
        levelIndex = $('#levels-container .level-card').length - 1;
    });

    // tambah baris requester–approver
    $(document).on('click','.add-user-row', function(){
        const levelIdx = $(this).data('level');
        const userContainer = $(`#user-container-${levelIdx}`);
        const userIdx = userContainer.find('.user-row').length;
        userContainer.append(userRowHtml(levelIdx, userIdx));
        initSelect2(userContainer);
    });

    // hapus baris requester–approver
    $(document).on('click','.remove-user-row', function(){
        $(this).closest('.user-row').remove();
    });
});
</script>
@endpush
