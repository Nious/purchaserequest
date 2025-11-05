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
    <title>Daftar Purchase Request</title>
    <style>
        @page { margin: 0px; }
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body { 
        font-family: "Poppins", sans-serif; 
        font-size: 12px; 
        color: #333; 
        
        background-image: url("{{ $backgroundImage }}");
        background-repeat: no-repeat;
        background-position: center center;
        background-size: cover; 
    }
        h2 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #777; padding: 6px; text-align: left; }
        th { background-color: #f0f0f000; }
        .no-border { border: none !important; }
        .text-right { text-align: right; }
        .mt-2 { margin-top: 10px; }
        .margin-a4 { padding-top: 175px; padding-left:75px; padding-right:75px; padding-bottom:100px;}
    </style>
</head>

<body>
    <div class="margin-a4">
        <h2>Daftar Purchase Request</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Referensi</th>
                <th>Requester</th>
                <th>Departemen</th>
                <th class="text-right">Total</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d-m-Y') }}</td>
                    <td>{{ $purchase->reference }}</td>
                    <td>{{ $purchase->user->name ?? '-' }}</td>
                    <td>{{ $purchase->department->department_name ?? '-' }}</td>
                    <td class="text-right">{{ format_currency($purchase->total_amount) }}</td>
                    <td class="text-center">{{ ucfirst($purchase->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</body>
</html>