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
    <title>Daftar Master Budget</title>
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
            padding: 3px 6px; 
            border-radius: 4px; 
            color: #fff; 
            font-weight: bold; 
            font-size: 10px;
        }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-danger  { background-color: #dc3545; }

        /* Ruang dalam halaman agar isi tidak menempel di tepi */
        .margin-a4 { padding-top: 175px; padding-left:75px; padding-right:75px; padding-bottom:100px;}
    </style>
</head>

<body>
    <div class="margin-a4">
        <h2>Daftar Master Budget</h2>
        <table>
            <thead>
                <tr>
                    <th>No. Budgeting</th>
                    <th>Tgl. Penyusunan</th>
                    <th>Bulan</th>
                    <th>Departemen</th>
                    <th class="text-end">Total Budget</th>
                    <th class="text-end">Used</th>
                    <th class="text-end">Remaining</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $budget)
                    @php $status = strtolower($budget->status); @endphp
                    <tr>
                        <td>{{ $budget->no_budgeting }}</td>
                        <td>{{ \Carbon\Carbon::parse($budget->tgl_penyusunan)->format('d-m-Y') }}</td>
                        <td>{{ $budget->bulan_text }}</td>
                        <td>{{ $budget->department->department_name ?? '-' }}</td>
                        <td class="text-end">{{ $budget->grandtotal_formatted }}</td>
                        <td class="text-end">{{ $budget->used_amount_formatted }}</td>
                        <td class="text-end">{{ $budget->remaining_formatted }}</td>
                        <td class="text-center">
                            @if($status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data untuk ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
