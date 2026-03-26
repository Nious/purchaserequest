@extends('layouts.app')

@section('title', 'Edit Target Sales')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            {{-- Tampilkan Error Validasi Global --}}
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

            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    {{-- <h5 class="mb-0">Edit Target Sales: <span class="text-primary">{{ $targetSale->reference }}</span></h5> --}}
                    <h5 class="mb-0">Edit Target Sales:</h5>
                    {{-- <span class="badge badge-warning">{{ $targetSale->status }}</span> --}}
                </div>
                <div class="card-body">
                    <form action="{{ route('target_sales.update', $targetSale->id) }}" method="POST">
                        @csrf
                        @method('PUT') {{-- Method PUT wajib untuk update --}}

                        <div class="form-row">
                            {{-- Input Bulan --}}
                            <div class="col-md-6 mb-3">
                                <label for="month">Bulan <span class="text-danger">*</span></label>
                                <select name="month" id="month" class="form-control" required>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ old('month', $targetSale->month) == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('month') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Input Tahun --}}
                            <div class="col-md-6 mb-3">
                                <label for="year">Tahun <span class="text-danger">*</span></label>
                                <select name="year" id="year" class="form-control" required>
                                    @foreach(range(now()->year - 1, now()->year + 5) as $y)
                                        <option value="{{ $y }}" {{ old('year', $targetSale->year) == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Input Nominal --}}
                        <div class="form-group">
                            <label for="target_amount">Nominal Target (Rp) <span class="text-danger">*</span></label>
                            <input type="text" name="target_amount" id="target_amount" 
                                   class="form-control form-control-lg font-weight-bold" 
                                   value="{{ old('target_amount', 'Rp. ' . number_format($targetSale->target_amount, 0, ',', '.')) }}" 
                                   placeholder="0" required>
                            @error('target_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- Input Deskripsi --}}
                        <div class="form-group">
                            <label for="description">Keterangan</label>
                            <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $targetSale->description) }}</textarea>
                        </div>

                        <div class="form-group mt-4 d-flex justify-content-between">
                            <a href="{{ route('target_sales.index') }}" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle"></i> Update Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('page_scripts')
<script>
    // Script Format Rupiah
    const inputAmount = document.getElementById('target_amount');
    
    // Format saat mengetik
    inputAmount.addEventListener('keyup', function(e) {
        this.value = formatRupiah(this.value, 'Rp. ');
    });

    // Fungsi helper format rupiah
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