<table class="rounded-3">
    <thead>
        <tr>
            <th>Tgl. Susun</th>
            <th>No. Budgeting</th>
            <th>Bulan</th>
            <th class="text-center">Status</th>
            <th class="text-right">Nilai Budget</th>
            <th class="text-right">Realisasi</th>
            <th class="text-right">Sisa</th>

            {{-- 1. Sembunyikan kolom header jika INI BUKAN tabel over budget --}}
            @if(isset($isOverBudgetTable) && !$isOverBudgetTable)
                <th class="text-right">Nilai Budget Lainnya</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($budgets as $budget)
            @php
                $grand = $budget->grandtotal ?? 0;
                $used  = $budget->used_amount ?? 0;
                $remain = $budget->remaining;

                // Kalkulasi Realisasi Over Budget (hanya jika ini BUKAN tabel OB)
                $over_budget_total = 0;
                if (!isset($isOverBudgetTable) || !$isOverBudgetTable) {
                    if ($budget->relationLoaded('purchases')) {
                        $over_budget_total = $budget->purchases
                            ->where('status', 'Approved')
                            ->where('master_budget_remaining', '<', 0)
                            ->sum(fn($pr) => abs($pr->master_budget_remaining));
                    }
                }
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d-m-Y') }}</td>
                <td>{{ $budget->no_budgeting }}</td>
                <td>{{ \Carbon\Carbon::create()->month($budget->bulan)->format('F') }}</td>
                <td class="text-center">
                    @php $status = strtolower($budget->status); @endphp
                    @if ($status == 'pending')
                        <span class="badge bg-warning">{{ ucfirst($budget->status) }}</span>
                    @elseif ($status == 'approved')
                        <span class="badge bg-success">{{ ucfirst($budget->status) }}</span>
                    @else
                        <span class="badge bg-danger">{{ ucfirst($budget->status) }}</span>
                    @endif
                </td>
                <td class="text-right">{{ format_currency($grand) }}</td>
                <td class="text-right">{{ format_currency($used) }}</td>
                <td class="text-right">{{ format_currency($remain) }}</td>
                
                {{-- 2. Sembunyikan kolom data jika INI BUKAN tabel over budget --}}
                @if(isset($isOverBudgetTable) && !$isOverBudgetTable)
                    <td class="text-right">{{ $over_budget_total > 0 ? format_currency($over_budget_total) : '-' }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>