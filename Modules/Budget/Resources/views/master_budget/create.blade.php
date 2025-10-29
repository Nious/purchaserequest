@extends('layouts.app')

@section('title', 'Create Master Budget')

@section('content')
<div class="container">
    <h4 class="mb-4">Master Budget PR</h4>

    <form action="{{ route('master_budget.store') }}" method="POST">
        @csrf

        @php
            // default selected month (1 = January). Ganti 1 ke now()->month kalau mau bulan sekarang
            $selectedMonth = old('bulan', 1);
            $year = now()->year; // 2025 (sesuaikan server)
            $defaultStart = \Carbon\Carbon::create($year, $selectedMonth, 1)->format('Y-m-d');
            $defaultEnd   = \Carbon\Carbon::create($year, $selectedMonth, 1)->endOfMonth()->format('Y-m-d');
        @endphp

        <div class="row mb-3">
            <div class="col-md-3">
                <label>No. Budgeting PR</label>
                <input type="text" class="form-control" value="{{ $noBudgeting }}" readonly>
            </div>
            <div class="col-md-3">
                <label>Tgl. Penyusunan Budget</label>
                <input type="date" name="tgl_penyusunan" value="{{ old('tgl_penyusunan', now()->format('Y-m-d')) }}" class="form-control">
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
                    {{-- Opsi default --}}
                    <option value="0" {{ old('department_id') == 0 ? 'selected' : '' }}>All Department</option>
            
                    {{-- Opsi departemen dari database --}}
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label>Untuk Periode Budget Awal</label>
                <input type="date" id="periode_awal" name="periode_awal" value="{{ old('periode_awal', $defaultStart) }}" class="form-control" readonly>
            </div>
            <div class="col-md-3">
                <label>Sampai Dengan</label>
                <input type="date" id="periode_akhir" name="periode_akhir" value="{{ old('periode_akhir', $defaultEnd) }}" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
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
                <tr>
                    <td>
                        <select name="items[0][category_id]" class="form-control category-select">
                            <option value="">- Pilih Item -</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" data-name="{{ $cat->category_name }}">
                                    {{ $cat->category_code }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" name="items[0][category_name]" class="form-control category-name" readonly></td>
                    <td>
                        <input type="text" name="items[0][budget]" 
                            class="form-control budget-input text-end" value="Rp 0">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <a href="javascript:void(0)" id="add-row" class="text-primary">+ Tambah Item Baru</a>

        {{-- Grandtotal --}}
        <div class="row mt-3">
            <div class="col-12">
                <label for="grandtotal" class="form-label">Grandtotal</label>
                <input type="text" id="grandtotal" name="grandtotal"
                    class="form-control text-end" value="Rp 0" readonly>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">Simpan</button>
                <a href="{{ route('master_budget.index') }}" class="btn btn-secondary">Batalkan</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    let rowIndex = 1;

    // Helper Tanggal
    function formatLocalDate(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    // Helper update periode
    function updatePeriodeFromMonth(monthNumber) {
        const year = new Date().getFullYear();
        const firstDay = new Date(year, monthNumber - 1, 1);
        const lastDay  = new Date(year, monthNumber, 0);
        $('#periode_awal').val(formatLocalDate(firstDay));
        $('#periode_akhir').val(formatLocalDate(lastDay));
    }
    
    // Helper Rupiah
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });
    function formatRupiah(value) {
        return formatter.format(value || 0);
    }
    function parseRupiah(value) {
        return parseInt(String(value).replace(/[^0-9]/g, '')) || 0;
    }

    // Helper Kalkulasi Total
    function calculateGrandtotal() {
        let total = 0;
        $('.budget-input').each(function () {
            total += parseRupiah($(this).val());
        });
        $('#grandtotal').val(formatRupiah(total));
    }

    // Set periode awal saat load
    const initialMonth = parseInt($('#bulan').val()) || 1;
    updatePeriodeFromMonth(initialMonth);
    
    // Hitung total awal saat load
    calculateGrandtotal();

    // Event change bulan
    $('#bulan').on('change', function () {
        const m = parseInt($(this).val()) || 1;
        updatePeriodeFromMonth(m);
    });

    // Tambah row baru
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
            </tr>`;
        $('#budget-table tbody').append(row);
        rowIndex++;
    });

    // Auto isi nama kategori
    $(document).on('change', '.category-select', function () {
        const name = $(this).find(':selected').data('name') || '';
        $(this).closest('tr').find('.category-name').val(name);
    });

    // Hapus row
    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
        calculateGrandtotal();
    });

    // Saat input budget -> format ke Rp dan hitung ulang total
    $(document).on('input', '.budget-input', function () {
        let raw = parseRupiah($(this).val());
        $(this).val(formatRupiah(raw));
        calculateGrandtotal();
    });

    // --- VALIDASI DAN SUBMIT FORM ---
    $('form').on('submit', function (e) {
        let itemCount = 0;
        let itemValid = true;
        $('.category-select').each(function() {
            itemCount++;
            if ($(this).val() === "") {
                itemValid = false;
            }
        });

        if (itemCount === 0 || !itemValid) {
            e.preventDefault();
            Swal.fire({
                title: 'Item Belum Lengkap!',
                text: 'Anda harus menambahkan setidaknya satu item kategori dan mengisinya dengan benar.',
                icon: 'error',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#d33',
            });
            return;
        }

        $('.budget-input').each(function () {
            $(this).val(parseRupiah($(this).val()));
        });
        $('#grandtotal').val(parseRupiah($('#grandtotal').val()));

    });

    @if ($errors->has('error'))
        Swal.fire({
            title: 'Gagal Menyimpan!',
            text: '{{ $errors->first("error") }}',
            icon: 'error',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#d33',
        });
    @endif

});
</script>
@endpush
