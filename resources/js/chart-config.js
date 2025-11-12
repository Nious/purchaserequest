document.addEventListener('DOMContentLoaded', function() {
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('year');

    function updateDashboard(month, year) {
        // 1️⃣ Update Total Budget & Purchases
        fetch(`/home/total-budget-purchase?month=${month}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('approvedBudget').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data.approved_budget);
                document.getElementById('totalPurchase').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data.purchases);
            });

        // 2️⃣ Update Budget Purchases Chart
        fetch(`/budget-purchases/chart-data?month=${month}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('budgetPurchasesChart').getContext('2d');
                if(window.budgetPurchasesChart) window.budgetPurchasesChart.destroy();
                window.budgetPurchasesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Budget',
                                data: data.approvedBudgets,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Purchases',
                                data: data.approvedPurchases,
                                borderColor: '#ffc107',
                                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    }
                });
            });

        // 3️⃣ Update Doughnut Chart
        fetch(`/budget-by-department/chart-data?month=${month}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('budgetDepartmentChart').getContext('2d');
                if(window.budgetDepartmentChart) window.budgetDepartmentChart.destroy();

                const bgColors = (data.labels.length === 1 && data.labels[0] === 'No Data')
                    ? ['#E5E7EB']
                    : ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#8BC34A', '#FF5722', '#607D8B'];

                window.budgetDepartmentChart = new Chart(ctx, {
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
                            legend: { position: 'right', labels: { boxWidth: 15, padding: 20, font: { size: 13 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let total = context.dataset.data.reduce((a,b)=>a+b,0);
                                        let value = context.raw;
                                        let percentage = ((value / total) * 100).toFixed(2);
                                        return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
    }

    // Load awal
    updateDashboard(monthEl.value, yearEl.value);

    // Event filter bulan & tahun
    monthEl.addEventListener('change', () => updateDashboard(monthEl.value, yearEl.value));
    yearEl.addEventListener('change', () => updateDashboard(monthEl.value, yearEl.value));
});
