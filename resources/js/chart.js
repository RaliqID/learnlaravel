import Chart from 'chart.js/auto';

export function renderActivityChart(container, data) {
    if (!container) return;
    if (!data || !data.labels || !data.values || data.values.length === 0) {
        container.innerHTML = `<p class="text-on-surface-variant text-sm">No activity data yet.</p>`;
        return;
    }
    container.innerHTML = '<canvas id="activity-chart-canvas" height="150"></canvas>';
    const canvas = document.getElementById('activity-chart-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Articles',
                data: data.values,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' articles';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}