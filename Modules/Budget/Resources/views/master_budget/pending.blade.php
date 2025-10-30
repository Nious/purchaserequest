@extends('layouts.app')

@section('title', 'Pending Master Budgets')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('master_budget.index') }}">Master Budget</a></li>
    <li class="breadcrumb-item active">Pending Approvals</li>
</ol>
@endsection

@section('content')
<style>
    .status-filter a {
        text-decoration: none;
        color: #333;
        transition: background-color 0.2s ease;
    }
    /* Style untuk tombol yang tidak aktif saat di-hover */
    .status-filter a:not(.active):hover {
        background-color: #f0f0f0;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h3 class="mb-1 fw-semibold">Daftar Master Budget</h3>
            <div class="d-flex mt-2">
                {{-- Tambahkan class 'status-filter' untuk target style hover --}}
                <div class="d-flex bg-white rounded-3 shadow status-filter">
                    
                    {{-- Tombol Pending --}}
                    <a href="{{ route('master_budget.pending', ['status' => 'pending']) }}" 
                    class="p-2 px-4 rounded-3 {{ $activeStatus == 'pending' ? 'bg-info text-white active' : '' }}">
                    Pending
                    </a>
                    
                    {{-- Tombol Approved --}}
                    <a href="{{ route('master_budget.pending', ['status' => 'approved']) }}" 
                    class="p-2 px-4 rounded-3 {{ $activeStatus == 'approved' ? 'bg-info text-white active' : '' }}">
                    Approved
                    </a>
                    
                    {{-- Tombol Rejected --}}
                    <a href="{{ route('master_budget.pending', ['status' => 'rejected']) }}" 
                    class="p-2 px-4 rounded-3 {{ $activeStatus == 'rejected' ? 'bg-info text-white active' : '' }}">
                    Rejected
                    </a>
                    
                    {{-- Tombol Semua Data --}}
                    <a href="{{ route('master_budget.pending', ['status' => 'all']) }}" 
                    class="p-2 px-4 rounded-3 {{ $activeStatus == 'all' ? 'bg-info text-white active' : '' }}">
                    Semua Data
                    </a>
                </div>
            </div>

            @if ($pendingBudgets->count() !== 0)
            <h6 class="text-muted my-3">
                Berhasil Menampilkan <span class="fw-semibold text-black">{{ $pendingBudgets->count() }} Data</span> Master Budget
                @if($activeStatus != 'all')
                    dengan status "{{ ucfirst($activeStatus) }}"
                @endif
                .
            </h6>
            @endif

            @forelse ($pendingBudgets as $budget)
                {{-- === KODE BARU ANDA MULAI DARI SINI === --}}
                <div class="rounded-4 p-4 card shadow-sm mb-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2 align-items-center">
                            <h4 class="fw-bold mb-0">{{ $budget->no_budgeting }}</h4>
                            
                            {{-- 1. Badge Status Dinamis --}}
                            @php
                                $status = strtolower($budget->status);
                                $statusClass = 'bg-secondary'; // Default
                                if ($status == 'pending') {
                                    $statusClass = 'bg-warning';
                                } elseif ($status == 'approved') {
                                    $statusClass = 'bg-success';
                                } elseif ($status == 'rejected') {
                                    $statusClass = 'bg-danger';
                                }
                            @endphp
                            <div class="p-1 px-4 {{ $statusClass }} text-white bg-opacity-50 rounded-2" style="font-size: 0.9em;">
                                {{ ucfirst($status) }}
                            </div>
                        
                            {{-- 2. Badge Tipe Budget Dinamis --}}
                            @if(!$budget->department_id) {{-- Ini akan menangkap 0 atau null --}}
                                <div class="p-1 px-4 bg-danger text-white bg-opacity-50 rounded-2" style="font-size: 0.9em;">
                                    Over Budget
                                </div>
                            @else
                                <div class="p-1 px-4 bg-info text-white bg-opacity-50 rounded-2" style="font-size: 0.9em;">
                                    Master Budget
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('master_budget.show', $budget->id) }}" class="p-2 px-4 btn btn-primary text-white rounded-2">
                            Lihat Detail & Approve
                        </a>
                    </div>
                    
                    <h6 class="mt-1 mb-1">
                        <span class="fw-semibold">{{ optional($budget->department)->department_name ?? 'All Departemen' }}</span>
                        - {{ \Carbon\Carbon::create(null, $budget->bulan, 1)->format('F Y') }}
                    </h6>
                    
                    <div class="border-bottom my-2"></div>
                    
                    <div class="d-flex mt-2 justify-content-between align-items-center">
                        <div>
                            <h6><span class="fw-bold">Tanggal Penyusunan:</span> {{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d M Y') }}</h6>
                            <h6 class="mb-0"><span class="fw-bold">Deskripsi:</span> {{ $budget->description ?? '-' }}</h6>
                        </div>
                        <h5><span class="fw-bold">Grand Total:</span> <span class="text-danger">{{ format_currency($budget->grandtotal) }}</span></h5>
                    </div>
                    
                    <div class="border-bottom my-2"></div>

                    <div class="mt-2">
                        <h6><span class="fw-bold">Log Approval:</span></h6>
                        
                        {{-- Logika untuk menampilkan siapa yang sedang ditunggu --}}
                        @if($budget->approvalRequest && $budget->approvalRequest->logs->where('action', 'assigned')->count() > 0)
                            @php
                                $waitingFor = $budget->approvalRequest->logs
                                    ->where('action', 'assigned')
                                    ->map(function($log) {
                                        return $log->approver->name ?? 'User';
                                    })->implode(', ');
                            @endphp
                            <h6 class="text-warning my-0">
                                <i class="bi bi-hourglass-split"></i>
                                Menunggu approval dari: <strong>{{ $waitingFor }}</strong>
                            </h6>
                        @else
                             <h6 class="text-muted my-0">Tidak ada log approval yang menunggu.</h6>
                        @endif
                    </div>
                </div>

                @empty
                    {{-- Ganti alert-success menjadi alert-secondary agar lebih netral --}}
                    <div class="alert alert-secondary text-center mt-3 rounded-3">
                        <i class="bi bi-info-circle-fill"></i>
                        
                        {{-- Tampilkan pesan yang berbeda berdasarkan filter yang aktif --}}
                        @if($activeStatus === 'pending')
                            Tidak ada Master Budget yang menunggu approval saat ini.
                        @elseif($activeStatus === 'approved')
                            Tidak ada Master Budget yang berstatus "Approved".
                        @elseif($activeStatus === 'rejected')
                            Tidak ada Master Budget yang berstatus "Rejected".
                        @else {{-- Ini untuk 'all' --}}
                            Tidak ada data Master Budget yang ditemukan.
                        @endif
                    </div>
                @endforelse
            
        </div>
    </div>
</div>
@endsection