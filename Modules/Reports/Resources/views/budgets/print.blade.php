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
            margin-bottom: -10px; 
            font-size: 16px;
        }

        /* h3 { margin-bottom: 5px; margin-top: 25px; border-bottom: 1px solid #555; padding-bottom: 5px; } */

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 4px; 
            border-radius: 4px;
        }

        th, td { 
            border: 1px solid #777; 
            padding: 6px; 
            text-align: left; 
        }

        th { 
            background-color: rgba(240, 240, 240, 0.5); 
        }

        .header { text-align: center; margin-bottom: 10px; }
        .page-break { page-break-after: always; }
        .d-flex { display: flex; }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-right { text-align: right; }
        .text-white { color: #fff }
        .mt-2 { margin-top: 10px; }

        .badge { 
            padding: 1px 6px; 
            border-radius: 2px; 
            color: #fff; 
            font-weight: bold; 
            font-size: 10px;
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
    
        {{-- 1. LOOP UNTUK BUDGET DEPARTEMEN --}}
        @foreach($departmentBudgets as $deptId => $budgets)
            @if($budgets->isNotEmpty())
                <h3>{{ $budgets->first()->department->department_name }} - <Span class="badge bg-info">Budget Utama</Span></h3>
                {{-- Kirim 'isOverBudgetTable' => false --}}
                @include('reports::budgets.partials.print-table', [
                    'budgets' => $budgets, 
                    'isOverBudgetTable' => false
                ])
            @endif
        @endforeach
    
        {{-- 2. TABEL UNTUK OVER BUDGET --}}
        @if($overBudgets && $overBudgets->isNotEmpty())
            <h3>All Departemen - <Span class="badge bg-danger">Budget Lain-Lain</Span></h3>
            {{-- Kirim 'isOverBudgetTable' => true --}}
            @include('reports::budgets.partials.print-table', [
                'budgets' => $overBudgets,
                'isOverBudgetTable' => true
            ])
        @endif
    
        {{-- 3. JIKA SEMUA KOSONG --}}
        @if($departmentBudgets->isEmpty() && (!$overBudgets || $overBudgets->isEmpty()))
            <table class="table table-bordered">
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data untuk periode ini.</td>
                </tr>
            </table>
        @endif
    </div>
    
</body>
</html>