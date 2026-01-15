/**
 * Dashboard Charts Module
 * Handles updating pie chart and bar charts with real data
 */

/**
 * Update pie chart with document category data
 * @param {Object} categoryData - Category counts object
 */
export function updatePieChart(categoryData) {
    const documentsCount = categoryData.Documents || 0;
    const othersCount = (categoryData.Sports || 0) + (categoryData.Objects || 0);
    const total = documentsCount + othersCount;
    
    // Update center total
    const totalEl = document.getElementById('pieChartTotal');
    if (totalEl) {
        totalEl.textContent = total.toLocaleString();
    }
    
    // Calculate percentages
    const documentsPercent = total > 0 ? Math.round((documentsCount / total) * 100) : 0;
    const othersPercent = total > 0 ? Math.round((othersCount / total) * 100) : 0;
    
    // Update legend
    const documentsLabel = document.getElementById('pieChartDocumentsLabel');
    const documentsPercentEl = document.getElementById('pieChartDocumentsPercent');
    const othersLabel = document.getElementById('pieChartOthersLabel');
    const othersPercentEl = document.getElementById('pieChartOthersPercent');
    
    if (documentsLabel) documentsLabel.textContent = 'Documents';
    if (documentsPercentEl) documentsPercentEl.textContent = documentsPercent;
    if (othersLabel) othersLabel.textContent = 'Others';
    if (othersPercentEl) othersPercentEl.textContent = othersPercent;
    
    // Update SVG circles
    const circumference = 2 * Math.PI * 40; // radius is 40
    const documentsCircle = document.getElementById('pieChartDocumentsCircle');
    const othersCircle = document.getElementById('pieChartOthersCircle');
    
    if (documentsCircle && total > 0) {
        const documentsDash = (documentsCount / total) * circumference;
        const documentsGap = circumference - documentsDash;
        documentsCircle.setAttribute('stroke-dasharray', `${documentsDash} ${documentsGap}`);
        documentsCircle.setAttribute('stroke-dashoffset', '0');
    }
    
    if (othersCircle && total > 0) {
        const documentsDash = (documentsCount / total) * circumference;
        const othersDash = (othersCount / total) * circumference;
        othersCircle.setAttribute('stroke-dasharray', `${othersDash} ${circumference}`);
        othersCircle.setAttribute('stroke-dashoffset', `-${documentsDash}`);
    }
}

/**
 * Update bar chart with status data
 * @param {Object} statusData - Status counts object
 */
export function updateBarChart(statusData) {
    const available = statusData.available || 0;
    const borrowed = statusData.borrowed || 0;
    const archived = statusData.archived || 0;
    const max = Math.max(available, borrowed, archived, 1); // Avoid division by zero
    
    // Update counts
    const availableCountEl = document.getElementById('barChartAvailableCount');
    const borrowedCountEl = document.getElementById('barChartBorrowedCount');
    const archivedCountEl = document.getElementById('barChartArchivedCount');
    
    if (availableCountEl) availableCountEl.textContent = available.toLocaleString();
    if (borrowedCountEl) borrowedCountEl.textContent = borrowed.toLocaleString();
    if (archivedCountEl) archivedCountEl.textContent = archived.toLocaleString();
    
    // Update bar widths
    const availableBar = document.getElementById('barChartAvailableBar');
    const borrowedBar = document.getElementById('barChartBorrowedBar');
    const archivedBar = document.getElementById('barChartArchivedBar');
    
    if (availableBar) {
        const width = Math.round((available / max) * 100);
        availableBar.style.width = `${width}%`;
    }
    
    if (borrowedBar) {
        const width = Math.round((borrowed / max) * 100);
        borrowedBar.style.width = `${width}%`;
    }
    
    if (archivedBar) {
        const width = Math.round((archived / max) * 100);
        archivedBar.style.width = `${width}%`;
    }
}

/**
 * Update all dashboard stats
 * @param {Object} stats - Stats object from API
 */
export function updateDashboardStats(stats) {
    // Update stat cards
    const totalDocumentsEl = document.getElementById('totalDocuments');
    const totalCabinetsEl = document.getElementById('totalCabinets');
    const pendingCabinetsEl = document.getElementById('pendingCabinets');
    const archivedFilesEl = document.getElementById('archivedFiles');
    
    if (totalDocumentsEl) totalDocumentsEl.textContent = (stats.total_files || 0).toLocaleString();
    if (totalCabinetsEl) totalCabinetsEl.textContent = (stats.total_cabinets || 0).toLocaleString();
    if (pendingCabinetsEl) pendingCabinetsEl.textContent = (stats.pending_cabinets || 0).toLocaleString();
    if (archivedFilesEl) archivedFilesEl.textContent = (stats.archived_files || 0).toLocaleString();
    
    // Update charts
    if (stats.files_by_category) {
        updatePieChart(stats.files_by_category);
    }
    
    if (stats.files_by_status) {
        updateBarChart(stats.files_by_status);
    }
}
