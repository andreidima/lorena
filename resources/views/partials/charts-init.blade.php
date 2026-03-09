<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Chart === 'undefined') {
        return;
    }

    var palette = [
        '#ff4d6d',
        '#ff9f1c',
        '#2ec4b6',
        '#3a86ff',
        '#8338ec',
        '#fb5607',
        '#06d6a0',
        '#118ab2',
        '#ef476f',
        '#8ecae6',
        '#ffbe0b',
        '#43aa8b',
    ];

    var charts = document.querySelectorAll('canvas[data-chart]');

    charts.forEach(function (canvas, chartIndex) {
        var raw = canvas.getAttribute('data-chart');
        if (!raw) {
            return;
        }

        var payload = null;
        try {
            payload = JSON.parse(raw);
        } catch (error) {
            return;
        }

        if (!payload || !Array.isArray(payload.labels) || !Array.isArray(payload.datasets) || payload.labels.length === 0) {
            return;
        }

        var type = payload.type || 'bar';
        var datasets = payload.datasets.map(function (dataset, index) {
            var item = Object.assign({}, dataset);
            var values = Array.isArray(item.data) ? item.data : [];
            var colors = values.map(function (_, valueIndex) {
                return palette[(chartIndex + valueIndex + index) % palette.length];
            });

            if (type === 'line') {
                item.borderColor = item.borderColor || colors[0] || '#3a86ff';
                item.backgroundColor = item.backgroundColor || 'rgba(58, 134, 255, 0.18)';
                item.fill = true;
                item.tension = 0.35;
                item.borderWidth = 3;
                item.pointRadius = 3;
                item.pointHoverRadius = 5;
            } else {
                item.backgroundColor = item.backgroundColor || colors;
                item.borderColor = item.borderColor || '#ffffff';
                item.borderWidth = item.borderWidth || 1.2;
            }

            return item;
        });

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        useBorderRadius: true,
                        borderRadius: 4,
                    },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            interaction: {
                mode: 'nearest',
                intersect: false,
            },
        };

        if (['bar', 'line'].includes(type)) {
            options.scales = {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(120, 120, 120, 0.12)',
                    },
                },
            };
        }

        new Chart(canvas.getContext('2d'), {
            type: type,
            data: {
                labels: payload.labels,
                datasets: datasets,
            },
            options: options,
        });
    });
});
</script>
