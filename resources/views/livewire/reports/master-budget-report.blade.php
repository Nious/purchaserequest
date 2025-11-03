<div>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    <form wire:submit.prevent="generateReport">
                        <div class="form-row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input wire:model="start_date" type="date" class="form-control">
                                    @error('start_date')
                                        <span class="text-danger mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    <input wire:model="end_date" type="date" class="form-control">
                                    @error('end_date')
                                        <span class="text-danger mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Departemen</label>
                                    @if(auth()->user()->department_id == 0)
                                        <select wire:model="department_id" class="form-control">
                                            <option value="">Select Departement</option>
                                            <option value="0">All Departemen</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" 
                                               class="form-control" 
                                               value="{{ auth()->user()->department->department_name ?? 'N/A' }}" 
                                               disabled>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select wire:model="status" class="form-control">
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <span wire:target="generateReport" wire:loading class="spinner-border spinner-border-sm"></span>
                                <i wire:target="generateReport" wire:loading.remove class="bi bi-shuffle"></i>
                                Filter Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body position-relative">
                    <div wire:loading.flex class="position-absolute w-100 h-100 justify-content-center align-items-center" style="top:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped table-responsive text-center mb-0">
                        <thead>
                            <tr>
                                <th>Tgl. Penyusunan</th>
                                <th>No. Budgeting</th>
                                <th>Departemen</th>
                                <th>Tipe Budget</th>
                                <th>Bulan</th>
                                <th>Status</th>
                                <th class="text-end">Nilai Budgeting</th>
                                <th>Realisasi (%)</th>
                                <th class="text-end">Nilai Realisasi</th>
                                <th class="text-end">Sisa Budget</th>
                                <th>Sisa (%)</th>
                                <th class="text-end">Nilai Realisasi Over Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($masterBudgets as $budget)
                                @php
                                    $grand = $budget->grandtotal ?? 0;
                                    $used  = $budget->used_amount ?? 0;
                                    $remain = $budget->remaining ?? ($grand - $used);
                                    $used_percent = $grand > 0 ? round(($used / $grand) * 100, 2) : 0;
                                    $remain_percent = $grand > 0 ? round(($remain / $grand) * 100, 2) : 0;

                                    // Hitung over budget dari relasi purchases
                                    $over_budget_total = $budget->purchases()
                                        ->where('status', 'approved')
                                        ->where('master_budget_remaining', '<', 0)
                                        ->sum('master_budget_remaining');

                                    // Nilai minus dijadikan positif untuk tampilan
                                    $over_budget_total = abs($over_budget_total);
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d M, Y') }}</td>
                                    <td>{{ $budget->no_budgeting }}</td>
                                    <td>{{ $budget->department->department_name ?? ($budget->department_id == 0 ? 'All Departemen' : 'N/A') }}</td>
                                    <td>
                                        @if($budget->department_id == 0)
                                            <span class="badge bg-danger">Over Budget</span>
                                        @else
                                            <span class="badge bg-info">Budget Utama</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::create()->month($budget->bulan)->format('F') }}</td>
                                    <td>
                                        @php $status = strtolower($budget->status); @endphp
                                        @if ($status == 'pending')
                                            <span class="badge bg-warning text-dark">{{ ucfirst($budget->status) }}</span>
                                        @elseif ($status == 'approved')
                                            <span class="badge bg-success">{{ ucfirst($budget->status) }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($budget->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ format_currency($grand) }}</td>
                                    <td>{{ $used_percent }}%</td>
                                    <td class="text-end">{{ format_currency($used) }}</td>
                                    <td class="text-end">{{ format_currency($remain) }}</td>
                                    <td>{{ $remain_percent }}%</td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ $over_budget_total > 0 ? format_currency($over_budget_total) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">
                                        <span class="text-danger">No Master Budget Data Available!</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div @class(['mt-3' => $masterBudgets->hasPages()])>
                        {{ $masterBudgets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
