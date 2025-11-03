@php
    $imagePath = public_path('images/cover-surat.png'); 
    $type = pathinfo($imagePath, PATHINFO_EXTENSION);
    $data = file_get_contents($imagePath);
    $backgroundImage = 'data:image/' . $type . ';base64,' . base64_encode($data);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Master Budget</title>
    <style>
        @page { 
            margin: 0cm !important; 
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body { 
            font-family: "Poppins", sans-serif; 
            font-size: 11px; 
            color: #333; 
            background-image: url("{{ $backgroundImage }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover; 
        }

        h2 { 
            text-align: center; 
            margin-bottom: 10px; 
            font-size: 16px;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }

        th, td { 
            border: 1px solid #777; 
            padding: 6px; 
            text-align: left; 
        }

        th { 
            background-color: rgba(240, 240, 240, 0.5); 
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .mt-2 { margin-top: 10px; }

        .badge { 
            padding: 1px 6px; 
            border-radius: 3px; 
            color: #fff; 
            font-weight: bold; 
            font-size: 8px;
        }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-danger  { background-color: #dc3545; }
        .bg-info { background-color: #17a2b8; }

        /* Ruang dalam halaman agar isi tidak menempel di tepi */
        .margin-a4 { padding-top: 175px; padding-left:75px; padding-right:75px; padding-bottom:100px;}
    </style>
</head>
<body>
    <div class="margin-a4">
        <div class="header">
            <h2>Laporan Master Budget</h2>
            <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tgl. Susun</th>
                    <th>No. Budgeting</th>
                    <th>Departemen</th>
                    <th>Tipe</th>
                    <th>Bulan</th>
                    <th>Status</th>
                    <th class="text-right">Nilai Budget</th>
                    <th class="text-right">Realisasi</th>
                    <th class="text-right">Sisa</th>
                    <th class="text-right">Realisasi OB</th>
                </tr>
            </thead>
            <tbody>
                @forelse($masterBudgets as $budget)
                    @php
                        $grand = $budget->grandtotal ?? 0;
                        $used  = $budget->used_amount ?? 0;
                        $remain = $budget->remaining; // Gunakan accessor 'remaining'
    
                        // Kalkulasi Realisasi Over Budget
                        $over_budget_total = 0;
                        if ($budget->department_id == 0 && $budget->relationLoaded('purchases')) {
                            $over_budget_total = $budget->purchases
                                ->where('status', 'Approved')
                                ->where('master_budget_remaining', '<', 0)
                                ->sum(function($pr) {
                                    return abs($pr->master_budget_remaining);
                                });
                        }
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d-m-Y') }}</td>
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
                        <td class="text-right">{{ $budget->department_id == 0 ? format_currency($over_budget_total) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">Tidak ada data untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>