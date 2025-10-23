<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Request - {{ $purchase->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #777; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .no-border { border: none !important; }
        .text-right { text-align: right; }
        .mt-2 { margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Purchase Request</h2>

    <table>
        <tr><th>No. Permintaan</th><td>{{ $purchase->reference }}</td></tr>
        <tr><th>Tanggal</th><td>{{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}</td></tr>
        <tr><th>Requester</th><td>{{ $purchase->user->name ?? '-' }}</td></tr>
        <tr><th>Department</th><td>{{ $purchase->department->department_name ?? '-' }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($purchase->status) }}</td></tr>
        @if($purchase->status === 'rejected' && $purchase->rejection_reason)
            <tr><th>Rejection Reason</th><td>{{ $purchase->rejection_reason }}</td></tr>
        @endif
    </table>

    <h4 class="mt-2">Daftar Produk</h4>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Code</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>UOM</th>
                <th class="text-right">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->purchaseDetails as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->product_code }}</td>
                <td>{{ format_currency($item->unit_price) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ optional($item->product)->unit ?? '' }}</td>
                <td class="text-right">{{ format_currency($item->sub_total) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Grand Total</th>
                <th class="text-right">{{ format_currency($purchase->total_amount) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="mt-2">
        <p><strong>Catatan:</strong></p>
        <p>{{ $purchase->note ?? '-' }}</p>
    </div>
</body>
</html>
