@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">List</li>
</ol>
@endsection

@section('content')
<style>
    /* Style untuk filter tab */
    .status-filter a {
        text-decoration: none;
        color: #333;
        transition: background-color 0.2s ease;
    }
    .status-filter a:not(.active):hover {
        background-color: #f0f0f0;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h3 class="mb-1 fw-semibold">Daftar Purchase Request</h3>
            
            {{-- === 1. TAMBAHKAN TOMBOL FILTER DI SINI === --}}
            <div class="d-flex mt-2">
                <div class="d-flex bg-white rounded-3 shadow status-filter">
                    
                    {{-- Tombol Pending --}}
                    <a href="{{ route('purchases.pending', ['status' => 'pending']) }}" 
                       class="p-2 px-4 rounded-3 {{ $activeStatus == 'pending' ? 'bg-info text-white active' : '' }}">
                       Pending
                    </a>
                    
                    {{-- Tombol Approved --}}
                    <a href="{{ route('purchases.pending', ['status' => 'approved']) }}" 
                       class="p-2 px-4 rounded-3 {{ $activeStatus == 'approved' ? 'bg-info text-white active' : '' }}">
                       Approved
                    </a>
                    
                    {{-- Tombol Rejected --}}
                    <a href="{{ route('purchases.pending', ['status' => 'rejected']) }}" 
                       class="p-2 px-4 rounded-3 {{ $activeStatus == 'rejected' ? 'bg-info text-white active' : '' }}">
                       Rejected
                    </a>
                    
                    {{-- Tombol Semua Data --}}
                    <a href="{{ route('purchases.pending', ['status' => 'all']) }}" 
                       class="p-2 px-4 rounded-3 {{ $activeStatus == 'all' ? 'bg-info text-white active' : '' }}">
                       Semua Data
                    </a>
                </div>
            </div>
            
            @if ($pendingPurchases->count() !== 0)
                <h6 class="text-muted my-3">
                    Berhasil Menampilkan <span class="fw-semibold text-black">{{ $pendingPurchases->count() }} Data</span> Purchase Request
                    @if($activeStatus != 'all')
                        dengan status "{{ ucfirst($activeStatus) }}"
                    @endif
                    .
                </h6>
            @endif
            {{-- === BATAS TOMBOL FILTER === --}}


            @forelse ($pendingPurchases as $purchase)
                <div class="rounded-4 p-4 card shadow-sm mb-2 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2 align-items-center">
                            <h4 class="fw-bold mb-0">{{ $purchase->reference }}</h4>
                            
                            {{-- === 2. PERBAIKAN BADGE STATUS DINAMIS === --}}
                            @php
                                $status = strtolower($purchase->status);
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
                            {{-- === BATAS PERBAIKAN === --}}

                            @php
                                $isOverBudget = isset($purchase->approvalRequest) && $purchase->approvalRequest->requestable_type === 'Over Budget';
                            @endphp

                            @if($isOverBudget)
                                <div class="p-1 px-4 bg-danger text-white bg-opacity-50 rounded-2" style="font-size: 0.9em;">
                                    Over Budget
                                </div>
                            @else
                                <div class="p-1 px-4 bg-info text-white bg-opacity-50 rounded-2" style="font-size: 0.9em;">
                                    Purchase Request
                                </div>
                            @endif
                        </div>
                        
                        <a href="{{ route('purchases.show', $purchase->id) }}" class="p-2 px-4 btn btn-primary text-white rounded-2">
                            Lihat Detail
                        </a>
                    </div>
                    
                    <h6 class="mt-1 mb-1">
                        <span class="fw-semibold">{{ optional($purchase->department)->department_name ?? 'All Departemen' }}</span> 
                        - {{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}
                    </h6>

                    <div class="border-bottom my-2"></div>
                    
                    <div class="d-flex mt-2 justify-content-between align-items-center">
                        <div>
                            <h6><span class="fw-bold">Requester:</span> {{ optional($purchase->user)->name ?? 'N/A' }}</h6>
                            <h6 class="mb-0"><span class="fw-bold">Note:</span> {{ Str::limit($purchase->note ?? '-', 50) }}</h6>
                        </div>
                        <h5><span class="fw-bold">Grand Total:</span> <span class="text-danger">{{ format_currency($purchase->total_amount) }}</span></h5>
                    </div>
                    
                    <div class="border-bottom my-2"></div>

                    <div class="mt-2">
                        <h6><span class="fw-bold">Log Approval:</span></h6>
                        
                        @if($status == 'pending' && $purchase->approvalRequest && $purchase->approvalRequest->logs->where('action', 'assigned')->count() > 0)
                            @php
                                $waitingFor = $purchase->approvalRequest->logs
                                    ->where('action', 'assigned')
                                    ->map(function($log) {
                                        return $log->approver->name ?? 'User';
                                    })->implode(', ');
                            @endphp
                            <h6 class="text-warning my-0">
                                <i class="bi bi-hourglass-split"></i>
                                Menunggu approval dari: <strong>{{ $waitingFor }}</strong>
                            </h6>
                        @elseif($status == 'approved')
                             <h6 class="text-success my-0">
                                <i class="bi bi-check-circle-fill"></i>
                                Purchase Request telah disetujui.
                            </h6>
                        @elseif($status == 'rejected')
                             <h6 class="text-danger my-0">
                                <i class="bi bi-x-circle-fill"></i>
                                Purchase Request ditolak.
                            </h6>
                        @else
                             <h6 class="text-muted my-0">Tidak ada log approval yang menunggu.</h6>
                        @endif
                    </div>
                </div>

            @empty
                {{-- === 3. PERBAIKAN PESAN @empty === --}}
                <div class="alert alert-secondary text-center mt-3 rounded-3">
                    <i class="bi bi-info-circle-fill"></i>
                    
                    @if($activeStatus === 'pending')
                        Tidak ada Purchase Request yang menunggu approval saat ini.
                    @elseif($activeStatus === 'approved')
                        Tidak ada Purchase Request yang berstatus "Approved".
                    @elseif($activeStatus === 'rejected')
                        Tidak ada Purchase Request yang berstatus "Rejected".
                    @else {{-- Ini untuk 'all' --}}
                        Tidak ada data Purchase Request yang ditemukan.
                    @endif
                </div>
            @endforelse
            
        </div>
    </div>
</div>
@endsection