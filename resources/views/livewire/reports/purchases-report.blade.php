<div>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    <form wire:submit="generateReport">
                        <div class="form-row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input wire:model="start_date" type="date" class="form-control" name="start_date">
                                    @error('start_date')
                                        <span class="text-danger mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    <input wire:model="end_date" type="date" class="form-control" name="end_date">
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

                                    @if (auth()->user()->department_id == 0)
                                        <!-- Jika department_id user == 0, tampilkan semua departemen -->
                                        <select wire:model="department_id" class="form-control" name="department_id">
                                            <option value="">Select Departement</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->department_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <!-- Jika bukan 0, hanya tampilkan departemen user yang login -->
                                        <input type="text" class="form-control"
                                            value="{{ auth()->user()->department->department_name ?? 'N/A' }}" disabled>
                                        <!-- Hidden input agar Livewire tetap tahu department_id -->
                                        <input type="hidden" wire:model="department_id"
                                            value="{{ auth()->user()->department_id }}">
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select wire:model="purchase_status" class="form-control" name="purchase_status">
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approve</option>
                                        <option value="Rejected">Reject</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-primary">
                                    <span wire:target="generateReport" wire:loading
                                        class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span>
                                    <i wire:target="generateReport" wire:loading.remove class="bi bi-shuffle"></i>
                                    Filter Report
                                </button>
                            </div>
                            <div class="form-group mb-0">
                                {{-- Ganti type="submit" menjadi type="button" dan tambahkan wire:click --}}
                                <button type="button" wire:click="printReport" class="btn btn-secondary">
                                    <span wire:target="printReport" wire:loading
                                        class="spinner-border spinner-border-sm"></span>
                                    <i wire:target="printReport" wire:loading.remove class="bi bi-printer"></i>
                                    Print Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered rounded-3 table-striped text-center mb-0">
                            <div wire:loading.flex
                                class="col-12 position-absolute justify-content-center align-items-center"
                                style="top:0;right:0;left:0;bottom:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Departement</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Tipe</th>
                                    <th class="text-end">Over Budget</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $purchase)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d M, Y') }}</td>
                                        <td>{{ $purchase->reference }}</td>
                                        <td>{{ $purchase->department->department_name ?? 'N/A' }}</td>
                                        <td>
                                            @if ($purchase->status == 'pending')
                                                <span class="badge badge-warning">
                                                    {{ ucfirst($purchase->status) }}
                                                </span>
                                            @elseif ($purchase->status == 'approved')
                                                <span class="badge badge-success">
                                                    {{ ucfirst($purchase->status) }}
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    {{ ucfirst($purchase->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ format_currency($purchase->total_amount) }}</td>
                                        <td>{{ format_currency($purchase->paid_amount) }}</td>
                                        <td>{{ format_currency($purchase->due_amount) }}</td>
                                        <td>
                                            @if ($purchase->master_budget_remaining < 0)
                                                <span class="badge bg-danger">Over Budget</span>
                                            @else
                                                <span class="badge bg-info">Normal</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            @if ($purchase->master_budget_remaining < 0)
                                                {{ format_currency(abs($purchase->master_budget_remaining)) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <span class="text-danger">No Purchases Data Available!</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div @class(['mt-3' => $purchases->hasPages()])>
                        {{ $purchases->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
