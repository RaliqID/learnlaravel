import Chart from 'chart.js/auto';

let chartInstance = null;
let currentDays = 7;

export async function renderCategoryChart(container, fetchFn) {
    if (!container) return;

    // Add filter button handlers
    const buttons = document.querySelectorAll('#chart-filter button');
    buttons.forEach(btn => {
        btn.addEventListener('click', async () => {
            buttons.forEach(b => b.classList.remove('bg-primary', 'text-on-primary', 'border-primary'));
            btn.classList.add('bg-primary', 'text-on-primary', 'border-primary');
            const days = parseInt(btn.dataset.days, 10);
            currentDays = days;
            await loadChartData(container, fetchFn, days);
        });
    });

    // Set initial active state
    const defaultBtn = document.querySelector('#chart-filter button[data-days="7"]');
    if (defaultBtn) defaultBtn.classList.add('bg-primary', 'text-on-primary', 'border-primary');

    await loadChartData(container, fetchFn, currentDays);
}

async function loadChartData(container, fetchFn, days) {
    try {
        const payload = await fetchFn({ days });
        const data = payload.data;
        if (!data || !data.labels || !data.datasets || data.datasets.length === 0) {
            container.innerHTML = '<p class="text-on-surface-variant text-sm">No category data available.</p>';
            return;
        }
        // Destroy existing chart
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        // Build chart
        const canvas = document.createElement('canvas');
        container.innerHTML = '';
        container.appendChild(canvas);
        const ctx = canvas.getContext('2d');

        // Generate vibrant colors for datasets
        const colors = [
            'rgba(239, 68, 68, 0.8)',   // red
            'rgba(59, 130, 246, 0.8)',  // blue
            'rgba(34, 197, 94, 0.8)',   // green
            'rgba(234, 179, 8, 0.8)',   // yellow
            'rgba(168, 85, 247, 0.8)',  // purple
            'rgba(236, 72, 153, 0.8)',  // pink
            'rgba(20, 184, 166, 0.8)',  // teal
            'rgba(249, 115, 22, 0.8)',  // orange
            'rgba(99, 102, 241, 0.8)',  // indigo
            'rgba(132, 204, 22, 0.8)',  // lime
        ];
        const borderColors = colors.map(c => c.replace('0.8', '1'));

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets.map((ds, idx) => {
                    const color = colors[idx % colors.length];
                    const borderColor = borderColors[idx % borderColors.length];
                    return {
                        label: ds.label,
                        data: ds.data,
                        backgroundColor: color,
                        borderColor: borderColor,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: borderColor,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#e0e0e0',
                            font: { size: 11 },
                            boxWidth: 12,
                            padding: 10,
                        },
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' articles';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#a0a0a0' },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#a0a0a0', stepSize: 1 },
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
    } catch (e) {
        container.innerHTML = `<p class="text-on-surface-variant text-sm">Failed to load chart: ${e.message}</p>`;
    }
}