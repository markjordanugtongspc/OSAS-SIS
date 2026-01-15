import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const borrowStatusCanvas = document.getElementById('borrowStatusChart');
    const inventoryStatusCanvas = document.getElementById('inventoryStatusChart');
    const categoryBreakdownCanvas = document.getElementById('categoryBreakdownChart');

    if (!borrowStatusCanvas && !inventoryStatusCanvas && !categoryBreakdownCanvas) {
        return;
    }

    const borrowStatusScript = document.getElementById('dashboard-borrow-status-data');
    const inventoryStatusScript = document.getElementById('dashboard-inventory-status-data');
    const categoryBreakdownScript = document.getElementById('dashboard-category-breakdown-data');

    const parseData = (scriptEl) => {
        if (!scriptEl) return null;
        try {
            const text = scriptEl.textContent || scriptEl.innerText || '';
            if (!text.trim()) return null;
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    };

    const baseFont = {
        family: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif",
        size: 11,
        weight: '500',
    };

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1100,
            easing: 'easeOutQuart',
        },
        transitions: {
            active: {
                animation: {
                    duration: 250,
                },
            },
        },
        interaction: {
            mode: 'nearest',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 10,
                    boxHeight: 10,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 14,
                    font: baseFont,
                    color: '#334155',
                },
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                cornerRadius: 10,
                displayColors: true,
                bodyFont: baseFont,
                titleFont: { ...baseFont, weight: '600' },
                borderColor: 'rgba(148, 163, 184, 0.25)',
                borderWidth: 1,
            },
        },
    };

    const borrowStatusData = parseData(borrowStatusScript);
    const inventoryStatusData = parseData(inventoryStatusScript);
    const categoryBreakdownData = parseData(categoryBreakdownScript);

    if (borrowStatusCanvas && borrowStatusData && Array.isArray(borrowStatusData.labels)) {
        const ctx = borrowStatusCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: borrowStatusData.labels,
                datasets: [
                    {
                        data: borrowStatusData.data,
                        backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 10,
                    },
                ],
            },
            options: {
                ...commonOptions,
                cutout: '70%',
                layout: {
                    padding: 6,
                },
            },
        });
    }

    if (inventoryStatusCanvas && inventoryStatusData && Array.isArray(inventoryStatusData.labels)) {
        const ctx = inventoryStatusCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: inventoryStatusData.labels,
                datasets: [
                    {
                        data: inventoryStatusData.data,
                        backgroundColor: ['#10b981', '#f97316', '#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 10,
                    },
                ],
            },
            options: {
                ...commonOptions,
                layout: {
                    padding: 6,
                },
            },
        });
    }

    if (categoryBreakdownCanvas && categoryBreakdownData && Array.isArray(categoryBreakdownData.labels)) {
        const ctx = categoryBreakdownCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categoryBreakdownData.labels,
                datasets: [
                    {
                        data: categoryBreakdownData.data,
                        backgroundColor: 'rgba(128, 0, 32, 0.9)',
                        hoverBackgroundColor: 'rgba(92, 0, 22, 0.95)',
                        borderRadius: 10,
                        maxBarThickness: 38,
                    },
                ],
            },
            options: {
                ...commonOptions,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart',
                    delay: (context) => {
                        if (context.type !== 'data' || context.mode !== 'default') return 0;
                        return context.dataIndex * 70;
                    },
                },
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            font: baseFont,
                            color: '#475569',
                        },
                        grid: {
                            display: false,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: baseFont,
                            color: '#475569',
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.35)',
                        },
                    },
                },
            },
        });
    }
});

