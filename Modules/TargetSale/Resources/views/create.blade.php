@extends('layouts.app')

@section('title', 'Create Target Sales')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            {{-- 1. TAMBAHKAN BLOK INI UNTUK MELIHAT ERROR --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            {{-- BATAS BLOK ERROR --}}

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Buat Target Sales Baru</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('target_sales.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="col-md-6 mb-3">
                                <label for="month">Bulan <span class="text-danger">*</span></label>
                                <select name="month" id="month" class="form-control" required>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="year">Tahun <span class="text-danger">*</span></label>
                                <select name="year" id="year" class="form-control" required>
                                    @foreach(range(now()->year, now()->year + 5) as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="target_amount">Nominal Target (Rp) <span class="text-danger">*</span></label>
                            <input type="text" name="target_amount" id="target_amount" class="form-control form-control-lg font-weight-bold" placeholder="0" required>
                            @error('target_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- 2. TAMBAHKAN INPUT DESKRIPSI (Agar controller tidak error) --}}
                        <div class="form-group">
                            <label for="description">Deskripsi / Catatan</label>
                            <textarea name="description" id="description" rows="3" class="form-control" placeholder="Contoh: Target sales Q1 2025"></textarea>
                        </div>

                        <div class="form-group mt-4 text-right">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script Rupiah tetap sama --}}
@push('page_scripts')
<script>
    const inputAmount = document.getElementById('target_amount');
    inputAmount.addEventListener('keyup', function(e) {
        this.value = formatRupiah(this.value, 'Rp. ');
    });
    function formatRupiah(angka, prefix) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split   = number_string.split(','),
            sisa    = split[0].length % 3,
            rupiah  = split[0].substr(0, sisa),
            ribuan  = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
    }
</script>
@endpush
@endsection