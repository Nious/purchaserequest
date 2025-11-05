<div>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    {{-- FORM FILTER TETAP SAMA --}}
                    <form wire:submit.prevent="generateReport">
                        <div class="form-row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input wire:model="start_date" type="date" class="form-control">
                                    @error('start_date') <span class="text-danger mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    <input wire:model="end_date" type="date" class="form-control">
                                    @error('end_date') <span class="text-danger mt-1">{{ $message }}</span> @enderror
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
                                            <option value="0">All Departemen (Over Budget)</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control" value="{{ auth()->user()->department->department_name ?? 'N/A' }}" disabled>
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

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <span wire:target="generateReport" wire:loading class="spinner-border spinner-border-sm"></span>
                                <i wire:target="generateReport" wire:loading.remove class="bi bi-shuffle"></i>
                                Filter Report
                            </button>
                            <button type="button" wire:click="printReport" class="btn btn-secondary">
                                <span wire:target="printReport" wire:loading class="spinner-border spinner-border-sm"></span>
                                <i wire:target="printReport" wire:loading.remove class="bi bi-printer"></i>
                                Print Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- =============================================== --}}
    {{-- BAGIAN HASIL LAPORAN (LOGIKA BARU) --}}
    {{-- =============================================== --}}
    <div wire:loading.flex class="justify-content-center mt-4">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div wire:loading.remove>
        {{-- 1. LOOPING UNTUK SETIAP DEPARTEMEN --}}
        @foreach($departmentBudgets as $deptId => $budgets)
            @if($budgets->isNotEmpty())
                <div class="row">
                    <div class="col-12">
                        {{-- Judul Departemen --}}
                        <h4 class="mb-3 fw-semibold">{{ $budgets->first()->department->department_name }} - <span class="px-4 py-1 badge bg-info">Budget Utama</span></h4>
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                @include('livewire.reports.partials.budget-table', [
                                    'budgets' => $budgets, 
                                    'isOverBudgetTable' => false
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        {{-- 2. TABEL KHUSUS UNTUK OVER BUDGET --}}
        @if($overBudgets && $overBudgets->isNotEmpty())
            <div class="row">
                <div class="col-12">
                    {{-- Judul Over Budget --}}
                    <h4 class="mb-3 fw-semibold">All Departemen - <span class="px-4 py-1 badge bg-danger">Budget Lain-Lain</span></h4>
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            @include('livewire.reports.partials.budget-table', [
                                'budgets' => $overBudgets,
                                'isOverBudgetTable' => true
                            ])
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3. PESAN JIKA TIDAK ADA DATA SAMA SEKALI --}}
        @if($departmentBudgets->isEmpty() && (!$overBudgets || $overBudgets->isEmpty()))
            <div class="row rounded-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <span class="text-danger">No Master Budget Data Available for the selected filters!</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Script untuk membuka tab baru (jika belum ada di layout utama) --}}
@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-new-tab', (url) => {
            window.open(url, '_blank');
        });
    });
</script>
@endpush