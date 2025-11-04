<div class="table-responsive rounded-3">
    <table class="table table-bordered table-striped text-center mb-0">
        <thead>
            <tr>
                <th>Tgl. Penyusunan</th>
                <th>No. Budgeting</th>
                <th>Bulan</th>
                <th>Status</th>
                <th class="text-end">Nilai Budgeting</th>
                <th>Realisasi (%)</th>
                <th class="text-end">Nilai Realisasi</th>
                <th class="text-end">Sisa Budget</th>
                <th>Sisa (%)</th>
                
                {{-- ✅ PERBAIKAN: Tampilkan kolom ini HANYA jika BUKAN tabel Over Budget --}}
                @if (isset($isOverBudgetTable) && !$isOverBudgetTable)
                    <th class="text-end">Realisasi Over Budget</th>
                @endif
                </tr>
            </thead>
        <tbody>
            @foreach ($budgets as $budget)
                @php
                    $grand = $budget->grandtotal ?? 0;
                    $used = $budget->used_amount ?? 0;
                    $remain = $budget->remaining;
                    $used_percent = $grand > 0 ? round(($used / $grand) * 100, 2) : 0;
                    $remain_percent = $grand > 0 ? round(($remain / $grand) * 100, 2) : 0;

                    // ✅ PERBAIKAN: Hanya hitung ini jika BUKAN tabel Over Budget
                    $over_budget_total = 0;
                    if ((!isset($isOverBudgetTable) || !$isOverBudgetTable) && $budget->relationLoaded('purchases')) {
                        $over_budget_total = $budget->purchases
                            ->where('status', 'Approved')
                            ->where('master_budget_remaining', '<', 0)
                            ->sum(fn($pr) => abs($pr->master_budget_remaining));
                    }
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d M, Y') }}</td>
                    <td>{{ $budget->no_budgeting }}</td>
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

                    {{-- ✅ PERBAIKAN: Tampilkan kolom ini HANYA jika BUKAN tabel Over Budget --}}
                    @if (isset($isOverBudgetTable) && !$isOverBudgetTable)
                        <td class="text-end text-danger fw-bold">
                            {{-- Logika ini sekarang benar, karena $budget->department_id TIDAK akan 0 di sini --}}
                            
                            {{ $over_budget_total > 0 ? format_currency($over_budget_total) : '-' }}
                            </td>
                    @endif
                    </tr>
            @endforeach
            </tbody>
        </table>
</div>
