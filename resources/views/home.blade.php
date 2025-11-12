@extends('layouts.app')

@section('title', 'Home')

@section('breadcrumb')
<div class="d-flex justify-content-between align-items-center w-100 flex-wrap">
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item active">Home</li>
    </ol>

    {{-- 🔍 Filter Bulan & Tahun --}}
    <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2">
        <div>
            <select name="month" id="month" class="form-select form-select-sm">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="year" id="year" class="form-select form-select-sm">
                @foreach (range(date('Y') - 3, date('Y')) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="d-flex align-items-center h-100">
            <button class="btn btn-primary btn-sm align-items-center">Tampilkan</button>
        </div>
    </form>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @can('show_total_stats')
    <div class="row align-items-center mb-4">
        {{-- Approved Budget --}}
        <div class="col-md-4 col-lg-3">
            <div class="card border-0">
                <div class="card-body p-0 d-flex align-items-center shadow-sm">
                    <div class="bg-gradient-success p-4 mfe-3 rounded-left">
                        <i class="bi bi-cash-coin font-2xl"></i>
                    </div>
                    <div>
                        <div class="text-value text-success">{{ format_currency($approved_budget) }}</div>
                        <div class="text-muted text-uppercase font-weight-bold small">Approved Budget</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchases --}}
        <div class="col-md-4 col-lg-3">
            <div class="card border-0">
                <div class="card-body p-0 d-flex align-items-center shadow-sm">
                    <div class="bg-gradient-warning p-4 mfe-3 rounded-left">
                        <i class="bi bi-cart-check font-2xl"></i>
                    </div>
                    <div>
                        <div class="text-value text-warning">{{ format_currency($purchases) }}</div>
                        <div class="text-muted text-uppercase font-weight-bold small">Purchases</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('show_weekly_sales_purchases|show_month_overview')
    <div class="row mb-4">
        @can('show_weekly_sales_purchases')
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    Budget & Purchases of This Month
                </div>
                <div class="card-body">
                    <canvas id="budgetPurchasesChart"></canvas>
                </div>
            </div>
        </div>
        @endcan

        @can('show_month_overview')
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    Percentage of Approved Budget by Department, {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F, Y') }}
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <div class="chart-container" style="position: relative; height:420px; width:420px;">
                        <canvas id="budgetDepartmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
    @endcan
</div>
@endsection

@section('third_party_scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/chart.min.js"
        integrity="sha512-asxKqQghC1oBShyhiBwA+YgotaSYKxGP1rcSYTDrB0U6DxwlJjU59B67U8+5/++uFjcuVM8Hh5cokLjZlhm3Vg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection

@push('page_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@vite('resources/js/chart-config.js')

<script>
let budgetChart;

// Fungsi load chart berdasarkan bulan & tahun
function loadBudgetChart(month, year) {
    fetch(`{{ route('home.budgetByDepartmentChart') }}?month=${month}&year=${year}`)
        .then(res => res.json())
        .then(res => {
            const ctx = document.getElementById('budgetDepartmentChart').getContext('2d');
            if (budgetChart) budgetChart.destroy();

            budgetChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: res.labels,
                    datasets: [{
                        data: res.data,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                            '#FF9F40', '#C9CBCF', '#8BC34A', '#FF5722', '#607D8B'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // biar ukuran bisa fleksibel mengikuti container
                    layout: {
                        padding: {
                            right: 40 // tambahkan jarak antara chart dan legend (nama department)
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                padding: 20, // jarak antar nama department
                                font: {
                                    size: 13
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let total = context.dataset.data.reduce((a,b)=>a+b,0);
                                    let value = context.raw;
                                    let percentage = ((value / total) * 100).toFixed(2);
                                    return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
        });
}

// Saat pertama kali load halaman
document.addEventListener('DOMContentLoaded', function() {
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    loadBudgetChart(month, year);
});

// Event ketika user mengubah filter bulan / tahun
document.getElementById('month').addEventListener('change', function() {
    loadBudgetChart(this.value, document.getElementById('year').value);
});
document.getElementById('year').addEventListener('change', function() {
    loadBudgetChart(document.getElementById('month').value, this.value);
});
</script>
@endpush
 