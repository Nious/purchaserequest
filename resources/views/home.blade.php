@extends('layouts.app')

@section('title', 'Home')

@section('breadcrumb')
    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap">
        <ol class="breadcrumb border-0 m-0">
            <li class="breadcrumb-item active">Home</li>
        </ol>

        {{-- Filter Bulan & Tahun Global --}}
        <div class="d-flex align-items-center gap-2">
            <div>
                <select name="month" id="month" class="form-select form-select-sm">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $m == ($month ?? date('n')) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="year" id="year" class="form-select form-select-sm">
                    @foreach (range(date('Y') - 3, date('Y')) as $y)
                        <option value="{{ $y }}" {{ $y == ($year ?? date('Y')) ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    
    {{-- 1. INFO CARDS (STATISTIK UTAMA) --}}
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
                        {{-- Nilai awal diisi dari controller, nanti diupdate JS --}}
                        <div class="text-value text-success" id="approvedBudget">{{ format_currency($approved_budget ?? 0) }}</div>
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
                        <div class="text-value text-warning" id="totalPurchase">{{ format_currency($purchases ?? 0) }}</div>
                        <div class="text-muted text-uppercase font-weight-bold small">Purchases</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('show_weekly_sales_purchases|show_month_overview')
    <div class="row mb-4">
        
        {{-- 2. CHART BARU: MONITORING TAHUNAN (TARGET VS PURCHASE) --}}
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold">
                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Monitoring Purchase vs Target Sales (0.65%) - Tahun <span id="yearLabel">{{ $year ?? date('Y') }}</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="targetVsPurchaseChart"></canvas>
                    </div>
                    <small class="text-muted mt-2 d-block text-center">
                        *Garis merah putus-putus menandakan batas aman (0.65% dari Target Sales). Batang biru adalah realisasi Purchase Request.
                    </small>
                </div>
            </div>
        </div>

        {{-- 3. CHART HARIAN: BUDGET VS PURCHASE (BULAN INI) --}}
        @can('show_weekly_sales_purchases')
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    Budget & Purchases Daily Trend (<span id="monthLabel">{{ \Carbon\Carbon::create()->month($month ?? date('n'))->translatedFormat('F') }}</span>)
                </div>
                <div class="card-body">
                    {{-- ✅ PERBAIKAN: Tambahkan wrapper dengan tinggi fix (misal 300px) --}}
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="budgetPurchasesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- 4. CHART DOUGHNUT: SHARE PER DEPARTMENT --}}
        @can('show_month_overview')
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    Budget Share by Department
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    {{-- ✅ PERBAIKAN: Kurangi tinggi container dari 420px menjadi 300px --}}
                    <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
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

@push('page_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('year');
    const yearLabel = document.getElementById('yearLabel');
    const monthLabel = document.getElementById('monthLabel');

    // Instance Variables untuk Chart.js agar bisa di-destroy saat update
    let budgetPurchasesChartInstance = null;
    let budgetDepartmentChartInstance = null;
    let targetVsPurchaseChartInstance = null;

    // Helper: Format Nama Bulan (untuk update label)
    const getMonthName = (monthIndex) => {
        const date = new Date();
        date.setMonth(monthIndex - 1);
        return date.toLocaleString('id-ID', { month: 'long' });
    };

    // Helper: Format Rupiah Singkat (cth: 1.5M, 500jt)
    const formatCompactNumber = (number) => {
        if (number >= 1000000000) {
            return (number / 1000000000).toFixed(1) + 'M';
        } else if (number >= 1000000) {
            return (number / 1000000).toFixed(0) + 'jt';
        }
        return number;
    };

    // Fungsi Utama Update Dashboard
    function updateDashboard(month, year) {
        // Update label text di UI
        if(yearLabel) yearLabel.innerText = year;
        if(monthLabel) monthLabel.innerText = getMonthName(month);

        // 1️⃣ API: Total Statistik (Cards)
        fetch("{{ route('home.totalBudgetPurchase') }}?month=" + month + "&year=" + year)
            .then(res => res.json())
            .then(data => {
                const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
                const budgetEl = document.getElementById('approvedBudget');
                const purchaseEl = document.getElementById('totalPurchase');
                
                if(budgetEl) budgetEl.innerText = formatter.format(data.approved_budget);
                if(purchaseEl) purchaseEl.innerText = formatter.format(data.purchases);
            })
            .catch(error => console.error('Error fetching totals:', error));

        // 2️⃣ API: Chart Baru (Target vs Purchase - Tahunan)
        fetch("{{ route('home.targetVsPurchaseChart') }}?year=" + year)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('targetVsPurchaseChart').getContext('2d');
                
                if (targetVsPurchaseChartInstance) {
                    targetVsPurchaseChartInstance.destroy();
                }

                targetVsPurchaseChartInstance = new Chart(ctx, {
                    type: 'bar', 
                    data: {
                        labels: data.labels, // Jan, Feb, ...
                        datasets: [
                            {
                                type: 'line',
                                label: 'Batas (0.65% Target Sales)',
                                data: data.targetLimits,
                                borderColor: '#FF6384', // Merah
                                borderWidth: 2,
                                borderDash: [5, 5], // Garis putus-putus
                                fill: false,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                tension: 0.1,
                                order: 0 
                            },
                            {
                                type: 'bar',
                                label: 'Realisasi Purchase Request',
                                data: data.actualPurchases,
                                backgroundColor: 'rgba(54, 162, 235, 0.7)', // Biru
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatCompactNumber(value);
                                    }
                                },
                                grid: { color: '#f0f0f0' }
                            },
                            x: { grid: { display: false } }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching mixed chart:', error));

        // 3️⃣ API: Chart Harian (Budget vs Purchase - Bulanan)
        fetch("{{ route('budget.purchases.chart') }}?month=" + month + "&year=" + year)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('budgetPurchasesChart').getContext('2d');
                if (budgetPurchasesChartInstance) budgetPurchasesChartInstance.destroy();

                budgetPurchasesChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Sisa Budget',
                                data: data.approvedBudgets,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Total Pembelian',
                                data: data.approvedPurchases,
                                borderColor: '#ffc107',
                                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: function(value) { return formatCompactNumber(value); } }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching daily chart:', error));

        // 4️⃣ API: Chart Departemen (Doughnut - Bulanan)
        fetch("{{ route('home.budgetByDepartmentChart') }}?month=" + month + "&year=" + year)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('budgetDepartmentChart').getContext('2d');
                if (budgetDepartmentChartInstance) budgetDepartmentChartInstance.destroy();

                const bgColors = (data.labels.length === 1 && data.labels[0] === 'No Data')
                    ? ['#E5E7EB'] 
                    : ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#8BC34A', '#FF5722', '#607D8B'];

                budgetDepartmentChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: bgColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if(context.label === 'No Data') return 'No Data';
                                        let total = context.dataset.data.reduce((a,b)=>a+b,0);
                                        let value = context.raw;
                                        let percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                        let formattedValue = new Intl.NumberFormat('id-ID').format(value);
                                        return `${context.label}: ${formattedValue} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching doughnut chart:', error));
    }

    // Inisialisasi Awal
    if (monthEl && yearEl) {
        updateDashboard(monthEl.value, yearEl.value);

        // Event Listener saat filter berubah
        monthEl.addEventListener('change', () => updateDashboard(monthEl.value, yearEl.value));
        yearEl.addEventListener('change', () => updateDashboard(monthEl.value, yearEl.value));
    }
});
</script>
@endpush