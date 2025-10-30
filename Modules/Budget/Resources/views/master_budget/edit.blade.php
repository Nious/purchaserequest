@extends('layouts.app')

@section('title', 'Edit Master Budget')

@section('content')
<div class="container">
    <h4 class="mb-4">Edit Master Budget PR</h4>

    <form action="{{ route('master_budget.update', $masterBudget->id) }}" method="POST">
        @csrf
        @method('PUT')

        @php
            $isLocked = strtolower($masterBudget->status) !== 'pending';
        @endphp

        @if($isLocked)
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Tidak dapat diedit:</strong> Master Budget ini sudah <strong>{{ $masterBudget->status }}</strong>.
                Perubahan tidak akan disimpan.
            </div>
        @endif

        @php
            $selectedMonth = old('bulan', $masterBudget->bulan);
            $year = now()->year;
            $defaultStart = \Carbon\Carbon::create($year, $selectedMonth, 1)->format('Y-m-d');
            $defaultEnd   = \Carbon\Carbon::create($year, $selectedMonth, 1)->endOfMonth()->format('Y-m-d');
        @endphp

        <fieldset {{ $isLocked ? 'disabled' : '' }}>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>No. Budgeting PR</label>
                    <input type="text" name="no_budgeting"
                           value="{{ old('no_budgeting', $masterBudget->no_budgeting) }}"
                           class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label>Tgl. Penyusunan Budget</label>
                    <input type="date" name="tgl_penyusunan"
                           value="{{ old('tgl_penyusunan', \Carbon\Carbon::parse($masterBudget->tgl_penyusunan)->format('Y-m-d')) }}"
                           class="form-control">
                </div>
                <div class="col-md-3">
                    <label>Pilih Bulan</label>
                    <select name="bulan" id="bulan" class="form-control">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create($year, $m, 1)->format('F Y') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Departemen</label>
                    <select name="department_id" class="form-control">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ old('department_id', $masterBudget->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
    
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Untuk Periode Budget Awal</label>
                    <input type="date" id="periode_awal" name="periode_awal"
                           value="{{ old('periode_awal', \Carbon\Carbon::parse($masterBudget->periode_awal)->format('Y-m-d')) }}"
                           class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label>Sampai Dengan</label>
                    <input type="date" id="periode_akhir" name="periode_akhir"
                           value="{{ old('periode_akhir', \Carbon\Carbon::parse($masterBudget->periode_akhir)->format('Y-m-d')) }}"
                           class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label>Description</label>
                    <textarea name="description" class="form-control">{{ old('description', $masterBudget->description) }}</textarea>
                </div>
            </div>
    
            <h5>Detail Budget</h5>
            <table class="table table-bordered" id="budget-table">
                <thead>
                    <tr>
                        <th>Kode Kategori Barang</th>
                        <th>Nama Kategori Barang</th>
                        <th>Budget</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(old('items', $masterBudget->details->toArray() ?? []) as $i => $item)
    
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][category_id]" class="form-control category-select">
                                <option value="">- Pilih Item -</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-name="{{ $cat->category_name }}"
                                        {{ ($item['category_id'] ?? $item->category_id ?? null) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_code }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="items[{{ $i }}][category_name]"
                                   class="form-control category-name"
                                   value="{{ $item['category_name'] ?? $item->category_name ?? '' }}" readonly>
                        </td>
                        <td>
                            <input type="text" name="items[{{ $i }}][budget]"
                                   class="form-control budget-input text-end"
                                   value="{{ 'Rp ' . number_format($item['budget'] ?? $item->budget ?? 0, 0, ',', '.') }}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-row">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
    
            @if(!$isLocked)
                <a href="javascript:void(0)" id="add-row" class="text-primary">+ Tambah Item Baru</a>
            @endif
    
            {{-- Grandtotal --}}
            <div class="row mt-3">
                <div class="col-12">
                    <label for="grandtotal" class="form-label">Grandtotal</label>
                    <input type="text" id="grandtotal" name="grandtotal"
                           class="form-control text-end"
                           value="{{ 'Rp ' . number_format($masterBudget->grandtotal ?? 0, 0, ',', '.') }}" readonly>
                </div>
            </div>
        </fieldset>

        {{-- Tombol Aksi --}}
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-end">
                {{-- === PERBAIKI BAGIAN INI === --}}
                @if(!$isLocked)
                    <button type="submit" class="btn btn-primary me-2">Simpan</button>
                @endif
                <a href="{{ route('master_budget.index') }}" class="btn btn-secondary">
                    {{ $isLocked ? 'Kembali' : 'Batalkan' }}
                </a>
                {{-- === BATAS PERBAIKAN === --}}
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let rowIndex = {{ count(old('items', $masterBudget->items ?? [])) }};

    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    function formatRupiah(value) {
        return formatter.format(value || 0);
    }

    function parseRupiah(value) {
        return parseInt(value.replace(/[^0-9]/g, '')) || 0;
    }

    function calculateGrandtotal() {
        let total = 0;
        $('.budget-input').each(function () {
            total += parseRupiah($(this).val());
        });
        $('#grandtotal').val(formatRupiah(total));
    }

    $(document).on('input', '.budget-input', function () {
        let raw = parseRupiah($(this).val());
        $(this).val(formatRupiah(raw));
        calculateGrandtotal();
    });

    $('#add-row').on('click', function () {
        let row = `
            <tr>
                <td>
                    <select name="items[${rowIndex}][category_id]" class="form-control category-select">
                        <option value="">- Pilih Item -</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-name="{{ $cat->category_name }}">
                                {{ $cat->category_code }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="items[${rowIndex}][category_name]" class="form-control category-name" readonly></td>
                <td><input type="text" name="items[${rowIndex}][budget]" class="form-control budget-input text-end" value="Rp 0"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#budget-table tbody').append(row);
        rowIndex++;
    });

    $(document).on('change', '.category-select', function () {
        const name = $(this).find(':selected').data('name') || '';
        $(this).closest('tr').find('.category-name').val(name);
    });

    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
        calculateGrandtotal();
    });

    $('form').on('submit', function () {
        $('.budget-input').each(function () {
            $(this).val(parseRupiah($(this).val()));
        });
        $('#grandtotal').val(parseRupiah($('#grandtotal').val()));
    });

    calculateGrandtotal();
});
</script>
@endpush
