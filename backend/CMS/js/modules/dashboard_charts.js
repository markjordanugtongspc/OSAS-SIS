/**
 * Dashboard Charts Module (ApexCharts Implementation)
 * Styled to match standard dashboard theme
 */

let categoryChartInstance = null;
let statusChartInstance = null;

/**
 * Configure and render the Category Distribution Chart (Bar)
 */
function renderCategoryChart(categoryData) {
    const chartEl = document.getElementById("chart-distribution");
    if (!chartEl) return;

    // Use window.ApexCharts to ensure global scope access
    if (typeof window.ApexCharts === 'undefined') {
        console.error("ApexCharts library not loaded via CDN");
        return;
    }

    // Clear loading state if any
    chartEl.innerHTML = "";

    // Parse data
    const labels = categoryData ? Object.keys(categoryData) : [];
    const seriesData = categoryData ? Object.values(categoryData) : [];

    // Fallback if empty or all zeros
    const total = seriesData.reduce((a, b) => a + b, 0);
    const hasData = labels.length > 0 && total > 0;

    let finalSeries = seriesData;
    let finalLabels = labels;
    let colors = ['#800020', '#be123c', '#fb7185', '#f43f5e', '#e11d48', '#9f1239'];

    if (!hasData) {
        finalLabels = ['No Items'];
        finalSeries = [0];
        // We still render an empty bar chart so the user sees the grid
    }

    const options = {
        series: [{ name: 'Items', data: finalSeries }],
        chart: {
            type: 'bar',
            height: 320,
            width: '100%',
            toolbar: { show: false },
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            parentHeightOffset: 0
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '45%',
                distributed: true,
            }
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        xaxis: {
            categories: finalLabels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 },
                rotate: -45
            }
        },
        yaxis: {
            labels: { style: { colors: '#64748b', fontWeight: 600 } }
        },
        grid: {
            strokeDashArray: 4,
            borderColor: '#f1f5f9',
            padding: { top: 0, right: 0, bottom: 0, left: 10 }
        },
        colors: colors,
        tooltip: {
            theme: 'light',
            y: { formatter: function (val) { return val + " items" } }
        }
    };

    if (categoryChartInstance) {
        categoryChartInstance.destroy();
    }
    categoryChartInstance = new window.ApexCharts(chartEl, options);
    categoryChartInstance.render();
}

/**
 * Configure and render the Status Chart (Donut)
 */
function renderStatusChart(statusData) {
    const chartEl = document.getElementById("chart-status");
    if (!chartEl) return;

    if (typeof window.ApexCharts === 'undefined') {
        console.error("ApexCharts library not loaded via CDN");
        return;
    }

    // Clear container
    chartEl.innerHTML = "";

    const series = [];
    const labels = [];
    const colors = [];

    if (statusData) {
        // Order matters for color mapping
        if (statusData.available > 0) {
            series.push(statusData.available);
            labels.push('Available');
            colors.push('#10b981'); // Emerald
        }
        if (statusData.borrowed > 0) {
            series.push(statusData.borrowed);
            labels.push('Borrowed');
            colors.push('#f59e0b'); // Amber
        }
        if (statusData.archived > 0) {
            series.push(statusData.archived);
            labels.push('Archived');
            colors.push('#94a3b8'); // Gray
        }
    }

    // Zero fallback
    const total = series.reduce((a, b) => a + b, 0);
    const hasData = total > 0;

    let finalSeries = series;
    let finalLabels = labels;
    let finalColors = colors;

    if (!hasData) {
        finalSeries = [1];
        finalLabels = ['No Data'];
        finalColors = ['#e2e8f0'];
    }

    const options = {
        series: finalSeries,
        labels: finalLabels,
        chart: {
            type: 'donut',
            height: 320,
            width: '100%',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        colors: finalColors,
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: hasData ? 'Total Files' : 'No Data',
                            fontWeight: 800,
                            color: '#0f172a',
                            formatter: function (w) {
                                return hasData ? w.globals.seriesTotals.reduce((a, b) => a + b, 0) : 0;
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        stroke: { width: 4, colors: ['#ffffff'] },
        legend: {
            position: 'bottom',
            fontSize: '12px',
            fontWeight: 600,
            markers: { radius: 12 },
            itemMargin: { horizontal: 10, vertical: 8 }
        },
        tooltip: { theme: 'light' }
    };

    if (statusChartInstance) {
        statusChartInstance.destroy();
    }
    statusChartInstance = new window.ApexCharts(chartEl, options);
    statusChartInstance.render();
}


/**
 * Update all dashboard stats
 * @param {Object} stats - Stats object from API
 */
export function updateDashboardStats(stats) {
    // Update stat cards text
    const totalDocumentsEl = document.getElementById('totalDocuments');
    const totalCabinetsEl = document.getElementById('totalCabinets');
    const pendingCabinetsEl = document.getElementById('pendingCabinets');
    const archivedFilesEl = document.getElementById('archivedFiles');

    if (totalDocumentsEl) totalDocumentsEl.textContent = (stats.total_files || 0).toLocaleString();
    if (totalCabinetsEl) totalCabinetsEl.textContent = (stats.total_cabinets || 0).toLocaleString();
    if (pendingCabinetsEl) pendingCabinetsEl.textContent = (stats.pending_cabinets || 0).toLocaleString();
    if (archivedFilesEl) archivedFilesEl.textContent = (stats.archived_files || 0).toLocaleString();

    // Render Charts with slight delay to ensure DOM is ready and layout is stable
    setTimeout(() => {
        if (stats.files_by_category) {
            renderCategoryChart(stats.files_by_category);
        } else {
            renderCategoryChart({}); // Render empty
        }

        if (stats.files_by_status) {
            renderStatusChart(stats.files_by_status);
        } else {
            renderStatusChart({}); // Render empty
        }
    }, 100);
}
