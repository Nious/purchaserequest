{{-- resources/views/master_budgets/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Master Budget')

@section('content')
<div class="container">
    <h4>Detail Master Budget</h4>
    <div class="card">
        <div class="card-body">
            <h5>Informasi Budget</h5>
            <table class="table table-bordered">
                <tr>
                    <th>No. Budgeting PR</th>
                    <td>{{ $budget->no_budgeting }}</td>
                </tr>
                <tr>
                    <th>Tgl. Penyusunan</th>
                    <td>{{ $budget->tgl_penyusunan }}</td>
                </tr>
                <tr>
                    <th>Bulan</th>
                    <td>{{ $budget->bulan }}</td>
                </tr>
                <tr>
                    <th>Periode Awal</th>
                    <td>{{ $budget->periode_awal }}</td>
                </tr>
                <tr>
                    <th>Periode Akhir</th>
                    <td>{{ $budget->periode_akhir }}</td>
                </tr>
                <tr>
                    <th>Departemen</th>
                    <td>{{ $budget->department ? $budget->department->department_name : '-' }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $budget->description }}</td>
                </tr>
                <tr>
                    <th>Grandtotal</th>
                    <td>Rp {{ number_format($budget->grandtotal, 2) }}</td>
                </tr>
            </table>

            <h5>Detail Budget</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kode Kategori</th>
                        <th>Nama Kategori</th>
                        <th>Budget</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budget->details as $detail)
                    <tr>
                        <td>{{ $detail->kode_kategori }}</td>
                        <td>{{ $detail->nama_kategori }}</td>
                        <td>Rp {{ number_format($detail->budget, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('master_budget.index') }}" class="btn btn-secondary">Kembali</a>
        <a href="{{ route('master_budget.edit', $budget->id) }}" class="btn btn-warning">Edit</a>
    </div>
</div>
@endsection
