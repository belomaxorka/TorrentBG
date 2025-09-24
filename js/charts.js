// js/charts.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('languagesChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
             {
                labels: <?= json_encode(array_keys($stats['languages'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['languages'])) ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                }]
            }
        });
    }
});