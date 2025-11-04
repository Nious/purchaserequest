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
    <title>Laporan Pembelian</title>
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
            border-radius: 4px; 
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
            <h2>Purchase Request Report</h2>
            <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Referensi</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa</th>
                    <th>Tipe</th>
                    <th class="text-right">Over Budget</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d-m-Y') }}</td>
                        <td>{{ $purchase->reference }}</td>
                        <td>{{ $purchase->department->department_name ?? 'N/A' }}</td>
                        <td class="text-center">{{ ucfirst($purchase->status) }}</td>
                        <td class="text-right">{{ format_currency($purchase->total_amount) }}</td>
                        <td class="text-right">{{ format_currency($purchase->paid_amount) }}</td>
                        <td class="text-right">{{ format_currency($purchase->due_amount) }}</td>
                        <td class="text-center">
                            @if ($purchase->master_budget_remaining < 0)
                                <span class="badge bg-danger">Over Budget</span>
                            @else
                                <span class="badge bg-info">Normal</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($purchase->master_budget_remaining < 0)
                                {{ format_currency(abs($purchase->master_budget_remaining)) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>