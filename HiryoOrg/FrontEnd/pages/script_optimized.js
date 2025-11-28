/**
 * Hiryo Organization - Optimized Admin Panel JavaScript
 * 
 * Cleaned version with duplicates removed and functions consolidated
 * 
 * @author HiryoOrg Development Team
 * @version 2.0 - Optimized
 */

// ========================================
// 1. GLOBAL CONFIGURATION & STATE
// ========================================

const APP_CONFIG = {
    API_BASE_URL: '/HiryoOrg/FrontEnd/pages/api',
    TOAST_DURATION: 3000
};

const state = {
    currentPage: 'dashboard',
    dashboardStats: {},
    salesData: [],
    detailedData: {},
    products: [],
    users: [],
    orders: [],
    transactions: [],
    announcements: [],
    notifications: [],
    admins: [],
    allProducts: null,
    allUsers: null,
    allOrders: null,
    allTransactions: null,
    allAnnouncements: null,
    allNotifications: null,
    productSort: { column: '', direction: 'asc' },
    userSort: { column: '', direction: 'asc' },
    orderSort: { column: '', direction: 'asc' },
    transactionSort: { column: '', direction: 'asc' },
    announcementSort: { column: '', direction: 'asc' },
    notificationSort: { column: '', direction: 'asc' }
};

// ========================================
// 2. UTILITY FUNCTIONS (CONSOLIDATED)
// ========================================

// HTML escaping utility
function escapeHtml(text) {
    if (typeof text !== 'string' && typeof text !== 'number') return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Unified toast function
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        max-width: 300px;
        word-wrap: break-word;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        position: relative;
    `;
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-weight: bold;">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
            <span>${escapeHtml(message)}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                margin-left: auto;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            ">&times;</button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Unified date formatting function (consolidated from multiple duplicates)
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (error) {
        return 'Invalid Date';
    }
}

// Unified date-time formatting function
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'Invalid Date';
    }
}

// Unified status icon function (consolidated from multiple duplicates)
function getStatusIcon(status) {
    const icons = {
        'Active': '✅',
        'Inactive': '❌',
        'Pending': '⏳',
        'Processing': '🔄',
        'Delivered': '📦',
        'Completed': '✅',
        'Cancelled': '❌',
        'Failed': '❌',
        'Suspended': '🚫',
        'Read': '👁️',
        'Unread': '🔴'
    };
    return icons[status] || '❓';
}

// Unified clear functions (consolidated from multiple duplicates)
function clearSearchAndFilters(searchId, filterIds, filterFunction) {
    // Clear search
    const searchInput = document.getElementById(searchId);
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Clear filters
    filterIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.value = '';
        }
    });
    
    // Execute filter function
    if (typeof filterFunction === 'function') {
        filterFunction();
    }
}

// Generic refresh function
function refreshData(loadFunction, renderFunction) {
    if (typeof loadFunction === 'function' && typeof renderFunction === 'function') {
        loadFunction().then(renderFunction);
    }
}

// ========================================
// 3. API DATA LOADING (CONSOLIDATED)
// ========================================

async function loadApiData(endpoint, stateKey, dataKey) {
    try {
        console.log(`🔄 Loading API data from: ${APP_CONFIG.API_BASE_URL}/${endpoint}`);
        const fullUrl = `${APP_CONFIG.API_BASE_URL}/${endpoint}`;
        console.log(`🔄 Full URL: ${fullUrl}`);
        
        const response = await fetch(fullUrl, { 
            credentials: 'same-origin',
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        console.log(`🔄 API Response status: ${response.status}`);
        console.log(`🔄 Response headers:`, Object.fromEntries(response.headers.entries()));
        
        if (response.status === 401) {
            console.log('⚠️ Unauthorized, redirecting to dashboard');
            return PageLoader.loadPage('dashboard');
        }
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const responseText = await response.text();
        console.log(`🔄 Raw response text:`, responseText);
        
        const result = JSON.parse(responseText);
        console.log(`🔄 Parsed API Response for ${endpoint}:`, result);
        
        if (result.success) {
            const dataArray = result[dataKey] || [];
            state[stateKey] = dataArray;
            state[`all${stateKey.charAt(0).toUpperCase() + stateKey.slice(1)}`] = dataArray;
            console.log(`✅ Loaded ${dataArray.length} ${dataKey}`);
            console.log(`✅ First few items:`, dataArray.slice(0, 3));
            return true; // Return success
        } else {
            console.error(`❌ API Error for ${endpoint}:`, result.message);
            state[stateKey] = [];
            showToast(`Failed to load ${stateKey}.`, 'error');
            return false; // Return failure
        }
    } catch (error) {
        console.error(`❌ Failed to load ${endpoint}:`, error);
        console.error(`❌ Error details:`, error.message);
        state[stateKey] = [];
        showToast(`Failed to load data for ${stateKey}.`, 'error');
        return false; // Return failure
    }
}

// Dashboard data loading
async function loadDashboardData() {
    try {
        console.log('📊 Loading dashboard data...');
        
        // Add cache-busting timestamp to prevent stale data
        const timestamp = Date.now();
        
        // Use dashboard_api.php for summary data
        const summaryResponse = await fetch(`${APP_CONFIG.API_BASE_URL}/dashboard_api.php?action=get_dashboard_stats&_t=${timestamp}`, { 
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        if (summaryResponse.status === 401) return PageLoader.loadPage('dashboard');
        const summaryResult = await summaryResponse.json();
        
        // Use dashboard_api.php for sales data
        const salesResponse = await fetch(`${APP_CONFIG.API_BASE_URL}/dashboard_api.php?action=get_sales_data&_t=${timestamp}`, { 
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        const salesResult = await salesResponse.json();
        
        console.log('📊 Dashboard API responses:', { summaryResult, salesResult });
        
        if (summaryResult.success) {
            state.dashboardStats = summaryResult.data || {};
            console.log('📊 Dashboard stats loaded:', state.dashboardStats);
        } else {
            console.error('📊 Failed to load dashboard stats:', summaryResult.message);
            state.dashboardStats = {};
        }
        
        if (salesResult.success) {
            state.salesData = salesResult.data || [];
            console.log('📊 Sales data loaded:', state.salesData);
        } else {
            console.error('📊 Failed to load sales data:', salesResult.message);
            state.salesData = [];
        }
    } catch (error) {
        console.error('Dashboard API Error:', error);
        state.dashboardStats = {};
        state.salesData = [];
        state.detailedData = {};
        showToast('Failed to load dashboard data.', 'error');
    }
}

// API loading functions
const loadProducts = () => loadApiData('products_api.php?action=get_products_for_admin', 'products', 'products');
const loadUsers = () => loadApiData('users_api.php?action=get_all_users', 'users', 'users');
const loadOrders = async () => {
    try {
        // Show loading indicator
        const ordersTable = document.getElementById('ordersTable');
        if (ordersTable) {
            ordersTable.style.opacity = '0.7';
            ordersTable.style.pointerEvents = 'none';
        }
        
        await loadApiData('orders_api_v2.php?action=get_all_orders_for_admin', 'orders', 'orders');
        
        // Re-render the orders table with updated data
        renderOrders();
        console.log('Orders table re-rendered with updated data');
        
        // Hide loading indicator
        if (ordersTable) {
            ordersTable.style.opacity = '1';
            ordersTable.style.pointerEvents = 'auto';
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        // Hide loading indicator even on error
        const ordersTable = document.getElementById('ordersTable');
        if (ordersTable) {
            ordersTable.style.opacity = '1';
            ordersTable.style.pointerEvents = 'auto';
        }
    }
};
const loadTransactions = () => loadApiData('transactions_api_v5.php?action=get_all_transactions', 'transactions', 'transactions');
const loadAnnouncements = () => loadApiData('notifications_api.php?action=get_all_announcements', 'announcements', 'announcements');
const loadAdmins = () => loadApiData('admin_api.php?action=get_all_admins', 'admins', 'admins');
const loadNotifications = async () => {
    try {
        await loadApiData('notifications_api.php?action=get_all_notifications_for_admin', 'notifications', 'notifications');
        renderNotifications();
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
};

// ========================================
// 4. DASHBOARD FUNCTIONS
// ========================================

function renderDashboardKPIs() {
    console.log('📊 Rendering dashboard KPIs with data:', state.dashboardStats);
    
    // Update Total Users
    const usersElement = document.getElementById('kpiUsers');
    if (usersElement) {
        usersElement.textContent = state.dashboardStats.total_users || '0';
        console.log('📊 Updated users:', state.dashboardStats.total_users || '0');
    }
    
    // Update Total Orders  
    const ordersElement = document.getElementById('kpiOrders');
    if (ordersElement) {
        ordersElement.textContent = state.dashboardStats.total_orders || '0';
        console.log('📊 Updated orders:', state.dashboardStats.total_orders || '0');
    }
    
    // Update Total Revenue (this is mapped to kpiProducts in HTML)
    const revenueElement = document.getElementById('kpiProducts');
    if (revenueElement) {
        const revenue = parseFloat(state.dashboardStats.total_revenue || 0);
        revenueElement.textContent = `₱${revenue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        console.log('📊 Updated revenue:', revenue);
    }
}

// Manual refresh function for dashboard
async function refreshDashboard() {
    console.log('🔄 Manually refreshing dashboard data...');
    showToast('Refreshing dashboard data...', 'info');
    
    try {
        await loadDashboardData();
        renderDashboardKPIs();
        renderSalesChart();
        showToast('Dashboard refreshed successfully!', 'success');
    } catch (error) {
        console.error('Error refreshing dashboard:', error);
        showToast('Failed to refresh dashboard data', 'error');
    }
}

let salesChartInstance = null;
function renderSalesChart() {
    console.log('📈 Rendering sales chart with data:', state.salesData);
    const salesData = state.salesData || [];
    const ctx = document.getElementById('salesChart');
    if (!ctx || typeof Chart === 'undefined') {
        console.log('📈 Chart canvas or Chart.js not found');
        return;
    }
    if (salesChartInstance) { 
        salesChartInstance.destroy(); 
        console.log('📈 Destroyed previous chart instance');
    }
    
            // Handle empty data
            if (salesData.length === 0) {
                console.log('📈 No sales data available, showing empty chart');
                salesChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['No Data'],
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: [0],
                            borderColor: '#2e7d32',
                            backgroundColor: 'rgba(46, 125, 50, 0.2)',
                            borderWidth: 4,
                            pointRadius: 6,
                            pointBackgroundColor: '#2e7d32',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(0, 0, 0, 0.2)',
                                    lineWidth: 2
                                }
                            },
                            y: {
                                display: true,
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(46, 125, 50, 0.3)',
                                    lineWidth: 2
                                },
                                ticks: {
                                    callback: (value) => `₱${value.toLocaleString()}`
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'No sales data available yet',
                                color: '#666',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                });
                return;
            }
    
    // Determine if we're showing monthly or daily data based on data points
    const isMonthlyData = salesData.length > 0 && salesData.length <= 24;
    
    const labels = salesData.map(item => {
        const date = new Date(item.date);
        // For monthly data, show full month name; for daily data, show month and day
        if (isMonthlyData && salesData.length > 50) {
            return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        } else {
            // For daily data, show abbreviated month name
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    });
    
    const revenueData = salesData.map(item => parseFloat(item.daily_revenue || 0));
    const ordersData = salesData.map(item => parseInt(item.orders_count || 0));
    
    console.log('📈 Chart labels:', labels);
    console.log('📈 Revenue data:', revenueData);
    console.log('📈 Orders data:', ordersData);
    
    salesChartInstance = new Chart(ctx, { 
        type: 'line', 
        data: { 
            labels: labels, 
            datasets: [{ 
                label: 'Monthly Revenue (₱)', 
                data: revenueData, 
                borderColor: '#2e7d32',
                backgroundColor: 'rgba(46, 125, 50, 0.1)', 
                fill: true, 
                tension: 0.8,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#2e7d32',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#1b5e20',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
                yAxisID: 'y'
            }, {
                type: 'bar',
                label: 'Daily Orders',
                data: ordersData,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.6)',
                borderWidth: 2,
                borderRadius: 6,
                yAxisID: 'y1'
            }] 
        }, 
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            scales: { 
                x: {
                    display: true,
                    grid: {
                        display: true,
                        drawBorder: true,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: '#333',
                        padding: 8
                    },
                    title: {
                        display: true,
                        text: 'Month',
                        color: '#666',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: { 
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(46, 125, 50, 0.1)',
                        drawBorder: true
                    },
                    ticks: { 
                        callback: (value) => `₱${value.toLocaleString()}`,
                        font: {
                            size: 11
                        },
                        padding: 8
                    },
                    title: { 
                        display: true, 
                        text: 'Revenue (₱)',
                        color: '#2e7d32',
                        font: {
                            size: 13,
                            weight: 'bold'
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: { 
                        drawOnChartArea: false,
                        drawBorder: true,
                        color: 'rgba(33, 150, 243, 0.1)',
                        lineWidth: 1
                    },
                    ticks: { 
                        callback: (value) => Math.floor(value),
                        font: {
                            size: 11
                        },
                        padding: 8
                    },
                    title: { 
                        display: true, 
                        text: 'Orders',
                        color: '#2196F3',
                        font: {
                            size: 13,
                            weight: 'bold'
                        }
                    }
                }
            }, 
            plugins: { 
                legend: { 
                    display: true, 
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 13,
                            weight: '500'
                        },
                        color: '#333'
                    },
                    onClick: (e, legendItem, legend) => {
                        const index = legendItem.datasetIndex;
                        const ci = legend.chart;
                        const meta = ci.getDatasetMeta(index);
                        
                        meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
                        ci.update();
                    }
                },
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(tooltipItems) {
                            return tooltipItems[0].label || '';
                        },
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                // Revenue dataset
                                label += '₱' + context.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            } else {
                                // Orders dataset
                                label += context.parsed.y + ' orders';
                            }
                            return label;
                        },
                        footer: function(tooltipItems) {
                            if (tooltipItems.length > 1) {
                                const revenue = tooltipItems[0].parsed.y;
                                const orders = tooltipItems[1].parsed.y;
                                if (orders > 0) {
                                    const avgOrderValue = revenue / orders;
                                    return 'Avg Order: ₱' + avgOrderValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                            return '';
                        }
                    }
                },
                title: {
                    display: false
                }
            },
            interaction: { 
                mode: 'index', 
                intersect: false,
                axis: 'x'
            },
            hover: {
                mode: 'index',
                intersect: false
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const datasetIndex = activeElements[0].datasetIndex;
                    const index = activeElements[0].index;
                    const label = salesChartInstance.data.labels[index];
                    const value = salesChartInstance.data.datasets[datasetIndex].data[index];
                    const datasetLabel = salesChartInstance.data.datasets[datasetIndex].label;
                    
                    console.log('📊 Chart clicked:', {
                        date: label,
                        dataset: datasetLabel,
                        value: value
                    });
                    
                    // Optional: Show detailed view
                    showToast(`${label}: ${datasetLabel} = ${datasetIndex === 0 ? '₱' + value.toLocaleString() : value}`, 'info');
                }
            }
        } 
    });
}

// Dashboard period update function
async function updateChartPeriod() {
    const period = document.getElementById('chartPeriod')?.value || 'thismonth';
    console.log('📊 Updating chart period to:', period);
    showToast('Updating chart period...', 'info');
    
    try {
        const timestamp = Date.now();
        const salesResponse = await fetch(`${APP_CONFIG.API_BASE_URL}/dashboard_api.php?action=get_sales_data&period=${period}&_t=${timestamp}`, { 
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        const salesResult = await salesResponse.json();
        console.log('📊 Period-specific API Response:', salesResult);
        
        if (salesResult.success) {
            state.salesData = salesResult.data || [];
            console.log('📊 Updated sales data for period:', period, state.salesData);
            renderSalesChart();
            
            // Get readable period name
            const periodNames = {
                '7days': 'Last 7 Days',
                '30days': 'Last 30 Days',
                'thismonth': 'This Month',
                'thisquarter': 'This Quarter',
                'ytd': 'Year to Date',
                'custom': 'Custom Range'
            };
            const periodName = periodNames[period] || period;
            showToast(`Chart updated for ${periodName}`, 'success');
        } else {
            console.error('📊 Failed to load chart data:', salesResult.message);
            showToast('Failed to load chart data for selected period', 'error');
        }
    } catch (error) {
        console.error('Error updating chart period:', error);
        showToast('Error updating chart period', 'error');
    }
}

// Download chart data as CSV
function downloadChartData() {
    console.log('📥 Downloading chart data as CSV...');
    
    if (!state.salesData || state.salesData.length === 0) {
        showToast('No data available to download', 'error');
        return;
    }
    
    try {
        // Create CSV content
        let csv = 'Date,Revenue (₱),Orders\n';
        
        state.salesData.forEach(item => {
            const date = item.date || '';
            const revenue = item.daily_revenue || 0;
            const orders = item.orders_count || 0;
            csv += `${date},${revenue},${orders}\n`;
        });
        
        // Create blob and download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `hiryo-sales-data-${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Chart data downloaded successfully!', 'success');
        console.log('📥 CSV download complete');
    } catch (error) {
        console.error('Error downloading chart data:', error);
        showToast('Failed to download chart data', 'error');
    }
}

// Download chart as image
function downloadChartImage() {
    console.log('📸 Downloading chart as image...');
    
    if (!salesChartInstance) {
        showToast('No chart available to download', 'error');
        return;
    }
    
    try {
        // Get chart canvas and convert to image
        const canvas = document.getElementById('salesChart');
        const url = canvas.toDataURL('image/png');
        
        // Create download link
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `hiryo-sales-chart-${new Date().toISOString().split('T')[0]}.png`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Chart image downloaded successfully!', 'success');
        console.log('📸 Image download complete');
    } catch (error) {
        console.error('Error downloading chart image:', error);
        showToast('Failed to download chart image', 'error');
    }
}

// ========================================
// 5. UNIFIED RENDERING FUNCTIONS
// ========================================

// Generic table rendering function
function renderTable(data, containerId, rowRenderer, emptyMessage = 'No data available') {
    console.log(`renderTable called for ${containerId} with ${data?.length || 0} items`);
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container ${containerId} not found`);
        // Try to find it after a short delay
        setTimeout(() => {
            const delayedContainer = document.getElementById(containerId);
            if (delayedContainer) {
                console.log(`Container ${containerId} found after delay, rendering now`);
                renderTable(data, containerId, rowRenderer, emptyMessage);
            }
        }, 100);
        return;
    }
    
    if (!data || data.length === 0) {
        console.log(`No data for ${containerId}, showing empty message`);
        container.innerHTML = `<tr><td colspan="100%" class="text-center">${emptyMessage}</td></tr>`;
        return;
    }
    
    console.log(`Rendering ${data.length} rows for ${containerId}`);
    container.innerHTML = data.map(rowRenderer).join('');
}

// Generic modal functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Missing Modal Functions
function showAddProductModal() {
    console.log('showAddProductModal called');
    // Reset form for new product
    const form = document.getElementById('productForm');
    if (form) {
        form.reset();
        form.setAttribute('data-mode', 'add');
    }
    showModal('productModal');
}

function showEditProductModal(productId) {
    console.log('showEditProductModal called with productId:', productId);
    // Load product data for editing
    const product = state.products.find(p => p.product_id == productId);
    if (product) {
        // Populate form with product data
        const form = document.getElementById('productForm');
        if (form) {
            document.getElementById('productName')?.setAttribute('value', product.product_name || '');
            document.getElementById('productDescription')?.setAttribute('value', product.description || '');
            document.getElementById('productPrice')?.setAttribute('value', product.price || '');
            document.getElementById('productStock')?.setAttribute('value', product.stock_quantity || '');
            form.setAttribute('data-mode', 'edit');
            form.setAttribute('data-product-id', productId);
        }
        showModal('productModal');
    } else {
        showToast('Product not found', 'error');
    }
}

// Missing Filter Functions
function clearTransactionFilters() {
    console.log('clearTransactionFilters called');
    const searchInput = document.getElementById('transactionSearch');
    const statusFilter = document.getElementById('transactionStatusFilter');
    const dateFilter = document.getElementById('transactionDateFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    
    // Reload transactions data
    loadTransactions();
    showToast('Filters cleared', 'info');
}

function clearProductFilters() {
    console.log('clearProductFilters called');
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('productCategoryFilter');
    const statusFilter = document.getElementById('productStatusFilter');
    
    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    
    // Reload products data
    loadProducts();
    showToast('Filters cleared', 'info');
}

function clearUserFilters() {
    console.log('clearUserFilters called');
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('userRoleFilter');
    const statusFilter = document.getElementById('userStatusFilter');
    
    if (searchInput) searchInput.value = '';
    if (roleFilter) roleFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    
    // Reload users data
    loadUsers();
    showToast('Filters cleared', 'info');
}

function clearOrderFilters() {
    console.log('clearOrderFilters called');
    const searchInput = document.getElementById('orderSearch');
    const statusFilter = document.getElementById('orderStatusFilter');
    const dateFilter = document.getElementById('orderDateFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    
    // Reload orders data
    loadOrders();
    showToast('Filters cleared', 'info');
}

function clearNotificationFilters() {
    console.log('clearNotificationFilters called');
    const searchInput = document.getElementById('notificationSearch');
    const statusFilter = document.getElementById('notificationStatusFilter');
    const typeFilter = document.getElementById('notificationTypeFilter');
    const dateFilter = document.getElementById('notificationDateFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (typeFilter) typeFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    
    // Reload notifications data
    loadNotifications();
    showToast('Filters cleared', 'info');
}

function clearAnnouncementFilters() {
    console.log('clearAnnouncementFilters called');
    const searchInput = document.getElementById('announcementSearch');
    const typeFilter = document.getElementById('announcementTypeFilter');
    const dateFilter = document.getElementById('announcementDateFilter');
    
    if (searchInput) searchInput.value = '';
    if (typeFilter) typeFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    
    // Reload announcements data
    loadAnnouncements();
    showToast('Filters cleared', 'info');
}

// Generic filter function factory
function createFilterFunction(pageName, filterFields) {
    return function() {
        const searchTerm = document.getElementById(`${pageName}Search`)?.value.toLowerCase() || '';
        const filters = {};
        
        filterFields.forEach(field => {
            const element = document.getElementById(`${pageName}${field}Filter`);
            if (element) {
                filters[field] = element.value;
            }
        });
        
        let filteredData = state[`all${pageName.charAt(0).toUpperCase() + pageName.slice(1)}`] || state[pageName] || [];
        
        filteredData = filteredData.filter(item => {
            if (searchTerm) {
                const searchableFields = Object.keys(item).filter(key => 
                    typeof item[key] === 'string' && item[key].toLowerCase().includes(searchTerm)
                );
                if (searchableFields.length === 0) return false;
            }
            
            for (const [field, value] of Object.entries(filters)) {
                if (value && item[field] !== value) return false;
            }
            
            return true;
        });
        
        state[pageName] = filteredData;
        const renderFunction = window[`render${pageName.charAt(0).toUpperCase() + pageName.slice(1)}`];
        if (typeof renderFunction === 'function') {
            renderFunction();
        }
    };
}

// ========================================
// 6. PAGE-SPECIFIC RENDERING FUNCTIONS
// ========================================

function renderProducts() {
    console.log('🎨 renderProducts called with:', state.products.length, 'products');
    console.log('📋 Products data:', state.products);
    
    const rowRenderer = (product) => `
        <tr>
            <td class="image-column" data-label="Image">
                <img src="${getProductImageUrl(product.image_url)}" alt="${product.product_name}" class="product-image">
            </td>
            <td class="name-column" data-label="Name">${product.product_name}</td>
            <td class="sku-column" data-label="SKU">${product.product_sku || '—'}</td>
            <td class="category-column" data-label="Category">${getCategoryIcon(product.category)} ${product.category}</td>
            <td class="price-column" data-label="Price">₱${parseFloat(product.price).toFixed(2)}</td>
            <td class="stock-column" data-label="Stock">
                <span class="stock-indicator ${getStockClass(product.stock_quantity)}">
                    ${product.stock_quantity}
                </span>
            </td>
            <td class="status-column" data-label="Status">
                <span class="status ${product.status?.toLowerCase()}">
                    ${getStatusIcon(product.status)} ${product.status}
                </span>
            </td>
            <td class="actions-column" data-label="Actions">
                <button class="btn-icon edit-btn" onclick="showEditProductModal(${product.product_id})" title="Edit Product">✏️</button>
                <button class="btn-icon delete-btn" onclick="deleteProduct(${product.product_id})" title="Delete Product">🗑️</button>
            </td>
        </tr>
    `;
    
    renderTable(state.products, 'productRows', rowRenderer, 'No products found');
    console.log('✅ renderProducts completed - table should be updated');
}

function renderUsers() {
    console.log('renderUsers called with data:', state.users);
    const rowRenderer = (user) => {
        console.log('Rendering user:', user);
        return `
        <tr>
            <td class="id-column" data-label="ID">${user.user_id ?? '—'}</td>
            <td class="name-column" data-label="Name">${escapeHtml(user.name || 'Unknown')}</td>
            <td class="email-column" data-label="Email">${escapeHtml(user.email || '—')}</td>
            <td class="role-column" data-label="Role">${getRoleIcon(user.roles)} ${escapeHtml(user.roles || 'customer')}</td>
            <td class="status-column" data-label="Status">
                <span class="status ${user.status?.toLowerCase()}">
                    ${getStatusIcon(user.status)} ${escapeHtml(user.status || 'Unknown')}
                </span>
            </td>
            <td class="joined-column" data-label="Joined">${formatDate(user.created_at)}</td>
            <td class="actions-column" data-label="Actions">
                <button class="btn-icon edit-btn" onclick="showEditUserModal(${user.user_id})" title="Edit">✏️</button>
            </td>
        </tr>
    `;
    };
    
    renderTable(state.users, 'userRows', rowRenderer, 'No users found');
}

// ========================================
// 7. MODAL AND CRUD FUNCTIONS
// ========================================

// Product modal functions
function showProductModal(productId = null) {
    console.log('showProductModal called with productId:', productId);
    const modal = document.getElementById('productModal');
    const title = document.getElementById('modalTitle');
    
    if (!modal) {
        console.error('Product modal not found!');
        return;
    }
    
    console.log('Product modal found');
    
    if (title) {
        title.textContent = productId ? 'Edit Product' : 'Add New Product';
    }
    
    if (productId) {
        // Load product data for editing
        const product = state.products.find(p => p.product_id == productId);
        if (product) {
            document.getElementById('productName').value = product.product_name || '';
            document.getElementById('productCategory').value = product.category || '';
            document.getElementById('productPrice').value = product.price || '';
            document.getElementById('productStock').value = product.stock_quantity || '';
            document.getElementById('productSku').value = product.product_sku || '';
            document.getElementById('weightValue').value = product.weight_value || '';
            document.getElementById('weightUnit').value = product.weight_unit || '';
            document.getElementById('productDescription').value = product.description || '';
            
            // Handle product ID for updates
            document.getElementById('productId').value = product.product_id || '';
        }
    } else {
        // Clear form for new product
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = ''; // Clear product ID for new products
    }
    
    showModal('productModal');
}

// Separate function for adding new products
function showAddProductModal() {
    console.log('showAddProductModal called');
    const modal = document.getElementById('productModal');
    const title = document.getElementById('modalTitle');
    
    if (!modal) {
        console.error('Product modal not found!');
        return;
    }
    
    // Set title for adding new product
    if (title) {
        title.textContent = 'Add New Product';
    }
    
    // Clear form for new product
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = ''; // Clear product ID for new products
    
    // Hide any existing image preview
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    if (imagePreview) imagePreview.style.display = 'none';
    if (uploadPlaceholder) uploadPlaceholder.style.display = 'block';
    
    showModal('productModal');
}

// Separate function for editing existing products
function showEditProductModal(productId) {
    console.log('showEditProductModal called with productId:', productId);
    const modal = document.getElementById('productModal');
    const title = document.getElementById('modalTitle');
    
    if (!modal) {
        console.error('Product modal not found!');
        return;
    }
    
    // Set title for editing product
    if (title) {
        title.textContent = 'Edit Product';
    }
    
    // Load product data for editing
    const product = state.products.find(p => p.product_id == productId);
    if (product) {
        document.getElementById('productName').value = product.product_name || '';
        document.getElementById('productCategory').value = product.category || '';
        document.getElementById('productPrice').value = product.price || '';
        document.getElementById('productStock').value = product.stock_quantity || '';
        document.getElementById('productSku').value = product.product_sku || '';
        document.getElementById('weightValue').value = product.weight_value || '';
        document.getElementById('weightUnit').value = product.weight_unit || '';
        document.getElementById('productDescription').value = product.description || '';
        
        // Handle product ID for updates
        document.getElementById('productId').value = product.product_id || '';
        
        // Show existing product image if available
        if (product.image_url) {
            const previewImg = document.getElementById('previewImg');
            const imagePreview = document.getElementById('imagePreview');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            
            if (previewImg && imagePreview && uploadPlaceholder) {
                previewImg.src = product.image_url;
                imagePreview.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            }
        }
    }
    
    showModal('productModal');
}

function closeProductModal() {
    closeModal('productModal');
}

// User modal functions
function showEditUserModal(userId) {
    const user = state.users.find(u => u.user_id == userId);
    if (user) {
        document.getElementById('userId').value = user.user_id;
        document.getElementById('userName').value = user.name || 'Unknown';
        document.getElementById('userEmail').value = user.email || '';
        document.getElementById('userStatus').value = user.status || 'Active';
        showModal('userModal');
    }
}

function closeUserModal() {
    closeModal('userModal');
}

// User management functions
function sortUsers(column) {
    const currentSort = state.userSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.userSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.users.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderUsers();
}


function clearUserFilters() {
    clearSearchAndFilters('userSearch', ['userRoleFilter', 'userStatusFilter'], filterUsers);
    showToast('Filters cleared', 'info');
}

function clearUserSearch() {
    document.getElementById('userSearch').value = '';
    filterUsers();
}

function filterUsers() {
    const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('userRoleFilter')?.value || '';
    const statusFilter = document.getElementById('userStatusFilter')?.value || '';
    
    let filteredData = state.allUsers || state.users || [];
    
    filteredData = filteredData.filter(user => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [user.name, user.email, user.roles].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Role filter
        if (roleFilter && user.roles !== roleFilter) return false;
        
        // Status filter
        if (statusFilter && user.status !== statusFilter) return false;
        
        return true;
    });
    
    state.users = filteredData;
    renderUsers();
    
    // Update count display
    const countElement = document.getElementById('usersCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} users found`;
    }
}

// Initialize user filters
function initializeUserFilters() {
    console.log('User filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('userRoleFilter');
    const statusFilter = document.getElementById('userStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterUsers);
    }
    if (roleFilter) {
        roleFilter.addEventListener('change', filterUsers);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterUsers);
    }
    
    // Initial filter
    filterUsers();
}

// Admin modal functions
function showAddAdminModal() {
    showModal('addAdminModal');
}

function closeAdminModal() {
    closeModal('addAdminModal');
}

function showEditProfileModal() {
    showModal('editProfileModal');
}

function closeEditProfileModal() {
    closeModal('editProfileModal');
}

// Helper function to get proper image URL
function getProductImageUrl(imageUrl) {
    if (!imageUrl || imageUrl === '') {
        return '/HiryoOrg/public_images/placeholder.jpg';
    }
    
    // If it's already a full URL, return as is
    if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return imageUrl;
    }
    
    // If it's a relative path, make it absolute
    if (imageUrl.startsWith('public_images/')) {
        return '/HiryoOrg/' + imageUrl;
    }
    
    // Default fallback
    return '/HiryoOrg/public_images/placeholder.jpg';
}

// Image preview function
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('uploadPlaceholder');
            const img = document.getElementById('previewImg');
            
            if (preview && placeholder && img) {
                img.src = e.target.result;
                placeholder.style.display = 'none';
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('uploadPlaceholder');
    const input = document.getElementById('productImage');
    
    if (preview && placeholder && input) {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
        input.value = '';
    }
}

// Test function for debugging
window.testSaveProduct = async function() {
    console.log('Testing save product functionality...');
    
    const testData = new URLSearchParams();
    testData.append('action', 'add_product');
    testData.append('product_name', 'Test Product ' + Date.now());
    testData.append('category', 'Fertilizer');
    testData.append('price', '99.99');
    testData.append('stock_quantity', '25');
    testData.append('description', 'Test product description');
    testData.append('product_sku', 'TEST-' + Date.now());
    testData.append('weight_value', '2.5');
    testData.append('weight_unit', 'kg');
    
    console.log('Test data:', testData.toString());
    
    try {
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/products_api.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: testData
        });
        
        console.log('Test response status:', response.status);
        const result = await response.json();
        console.log('Test API Response:', result);
        
        if (result.success) {
            console.log('✅ Test save successful!');
            loadProducts().then(renderProducts);
        } else {
            console.log('❌ Test save failed:', result.message);
        }
    } catch (error) {
        console.error('❌ Test save error:', error);
    }
};

// CRUD operations
async function saveProduct() {
    console.log('⚡ saveProduct function called (optimized)');
    
    const formElement = document.getElementById('productForm');
    if (!formElement) {
        console.error('Product form not found');
        showToast('Product form not found', 'error');
        return;
    }
    
    // Show immediate feedback
    showToast('Saving product...', 'info');
    
    const formData = new FormData(formElement);
    const productId = formData.get('product_id');
    const action = productId ? 'update_product' : 'add_product';
    
    console.log('⚡ Action:', action);
    
    try {
        // OPTIMIZED: Check file upload faster
        const fileInput = formElement.querySelector('input[type="file"]');
        const hasFileUpload = fileInput && fileInput.files && fileInput.files[0];
        
        console.log('⚡ Has file upload:', hasFileUpload);
        
        let requestOptions;
        
        if (hasFileUpload) {
            // File upload - use FormData directly
            formData.append('action', action);
            requestOptions = {
                method: 'POST',
                body: formData
            };
        } else {
            // OPTIMIZED: Direct field access (faster than FormData iteration)
            const urlParams = new URLSearchParams();
            urlParams.append('action', action);
            
            // Add form fields directly with debugging
            const productName = formElement.querySelector('#productName')?.value || '';
            const category = formElement.querySelector('#productCategory')?.value || '';
            const price = formElement.querySelector('#productPrice')?.value || '';
            const stockQuantity = formElement.querySelector('#productStock')?.value || '';
            const productSku = formElement.querySelector('#productSku')?.value || '';
            const weightValue = formElement.querySelector('#weightValue')?.value || '';
            const weightUnit = formElement.querySelector('#weightUnit')?.value || '';
            const description = formElement.querySelector('#productDescription')?.value || '';
            
            console.log('⚡ Form field values:', {
                productName, category, price, stockQuantity, 
                productSku, weightValue, weightUnit, description
            });
            
            urlParams.append('product_name', productName);
            urlParams.append('category', category);
            urlParams.append('price', price);
            urlParams.append('stock_quantity', stockQuantity);
            urlParams.append('product_sku', productSku);
            urlParams.append('weight_value', weightValue);
            urlParams.append('weight_unit', weightUnit);
            urlParams.append('description', description);
            
            if (productId) {
                urlParams.append('product_id', productId);
            }
            
            requestOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: urlParams
            };
        }
        
        console.log('⚡ Sending request...');
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/products_api.php`, requestOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('⚡ API Response:', result);
        
        if (result.success) {
            showToast(result.message, 'success');
            closeProductModal();
            
            // DIRECT UPDATE: Update state immediately with returned product data
            console.log('⚡ Received product data:', result.product);
            console.log('⚡ Updating table directly with new data...');
            
            if (action === 'add_product' && result.product) {
                // Add new product to the beginning of the array
                const newProduct = {
                    product_id: result.product.product_id,
                    product_name: result.product.product_name,
                    product_sku: result.product.product_sku || '',
                    category: result.product.category,
                    price: result.product.price,
                    stock_quantity: result.product.stock_quantity,
                    description: result.product.description || '',
                    image_url: result.product.image_url || '',
                    status: result.product.status || 'Active',
                    weight_value: result.product.weight_value || '',
                    weight_unit: result.product.weight_unit || '',
                    created_at: result.product.created_at || new Date().toISOString(),
                    updated_at: result.product.updated_at || new Date().toISOString()
                };
                
                console.log('⚡ Adding new product to state:', newProduct);
                state.products.unshift(newProduct); // Add to beginning
                console.log('⚡ State now has', state.products.length, 'products');
                
            } else if (action === 'update_product' && result.product) {
                // Update existing product in state
                const index = state.products.findIndex(p => p.product_id == productId);
                console.log('⚡ Found product at index:', index);
                
                if (index !== -1) {
                    state.products[index] = result.product;
                    console.log('⚡ Updated product in state:', result.product);
                }
            }
            
            // Render the updated table
            console.log('⚡ Calling renderProducts with', state.products.length, 'products');
            renderProducts();
            console.log('⚡ Table updated instantly!');
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showToast('Failed to save product: ' + error.message, 'error');
    }
}

async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to permanently delete this product? This action cannot be undone.')) return;
    
    try {
        console.log('🗑️ Deleting product ID:', productId);
        
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/products_api.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_product&product_id=${productId}`
        });
        
        const result = await response.json();
        console.log('🗑️ Delete API response:', result);
        
        if (result.success) {
            showToast(result.message, 'success');
            
            // Remove product from state immediately (faster than reloading)
            const index = state.products.findIndex(p => p.product_id == productId);
            if (index !== -1) {
                const deletedProduct = state.products[index];
                state.products.splice(index, 1);
                console.log('🗑️ Removed product from state:', deletedProduct.product_name);
                console.log('🗑️ State now has', state.products.length, 'products');
                
                // Update allProducts array too
                if (state.allProducts) {
                    const allIndex = state.allProducts.findIndex(p => p.product_id == productId);
                    if (allIndex !== -1) {
                        state.allProducts.splice(allIndex, 1);
                    }
                }
            }
            
            // Re-render the table with updated state
            renderProducts();
            console.log('🗑️ Products table updated after deletion');
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error deleting product:', error);
        showToast('Failed to delete product', 'error');
    }
}

async function saveUser() {
    const formData = new FormData(document.getElementById('userForm'));
    formData.append('action', 'update_user_status');
    
    try {
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/users_api.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeUserModal();
            loadUsers().then(renderUsers);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showToast('Failed to save user', 'error');
    }
}

// ========================================
// 8. HELPER FUNCTIONS
// ========================================

function getCategoryIcon(category) {
    const icons = {
        'Fertilizer': '🌱',
        'Soil': '🌍',
        'Seeds': '🌰',
        'Tools': '🔧'
    };
    return icons[category] || '📦';
}

function getRoleIcon(role) {
    const icons = {
        'Administrator': '👑',
        'Moderator': '🛡️',
        'Editor': '✏️',
        'User': '👤',
        'customer': '👤',
        'admin': '👑',
        'super_admin': '👑'
    };
    return icons[role] || '👤';
}

function getStockClass(quantity) {
    if (quantity <= 0) return 'out';
    if (quantity <= 10) return 'low';
    if (quantity <= 50) return 'medium';
    return 'high';
}

// ========================================
// 8. INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initGlobalEventListeners();
    
    // Load dashboard by default
    PageLoader.loadPage('dashboard');
});

function initNavigation() {
    console.log('Navigation initialized');
    
    // Add click event listeners to navigation links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) {
                PageLoader.loadPage(page);
            }
        });
    });
    
    // Add event listeners for topbar buttons
    const notificationsBtn = document.getElementById('notificationsBtn');
    const profileBtn = document.getElementById('profileBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (notificationsBtn) {
        notificationsBtn.addEventListener('click', () => {
            PageLoader.loadPage('notifications');
        });
    }
    
    if (profileBtn) {
        profileBtn.addEventListener('click', () => {
            PageLoader.loadPage('admin_profile');
        });
    }
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to sign out?')) {
                window.location.href = 'logout.php';
            }
        });
    }
}

function initGlobalEventListeners() {
    console.log('Global event listeners initialized');
}

// ========================================
// 9. PAGE LOADER
// ========================================

const PageLoader = {
    async loadPage(page) {
        state.currentPage = page;
        console.log(`Loading page: ${page}`);
        
        // Update navigation active state
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-page="${page}"]`)?.classList.add('active');
        
        // Update page title
        const pageTitle = document.getElementById('currentPageTitle');
        if (pageTitle) {
            pageTitle.textContent = this.getPageTitle(page);
        }
        
        // Load page content
        try {
            const response = await fetch(`${page}.php`);
            if (response.ok) {
                const content = await response.text();
                const contentDiv = document.getElementById('content');
                if (contentDiv) {
                    contentDiv.innerHTML = content;
                    
                    // Initialize page-specific functionality
                    this.initializePage(page);
                }
            } else {
                throw new Error(`Failed to load ${page}.php`);
            }
        } catch (error) {
            console.error(`Error loading page ${page}:`, error);
            const contentDiv = document.getElementById('content');
            if (contentDiv) {
                contentDiv.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <h2>Page Loading Error</h2>
                        <p>Failed to load ${page} page. Please try again.</p>
                        <button onclick="PageLoader.loadPage('dashboard')" class="btn primary">Go to Dashboard</button>
                    </div>
                `;
            }
        }
    },
    
    getPageTitle(page) {
        const titles = {
            'dashboard': 'Dashboard',
            'products': 'Products',
            'users': 'Users',
            'orders': 'Orders',
            'transactions': 'Transactions',
            'announcements': 'Announcements',
            'notifications': 'Notifications',
            'admin_profile': 'Admin Profile'
        };
        return titles[page] || 'Admin Panel';
    },
    
    initializePage(page) {
        switch(page) {
            case 'dashboard':
                this.initializeDashboard();
                break;
            case 'products':
                this.initializeProducts();
                break;
            case 'users':
                this.initializeUsers();
                break;
            case 'orders':
                this.initializeOrders();
                break;
            case 'transactions':
                this.initializeTransactions();
                break;
            case 'announcements':
                this.initializeAnnouncements();
                break;
            case 'notifications':
                this.initializeNotifications();
                break;
            case 'admin_profile':
                this.initializeAdminProfile();
                break;
        }
    },
    
    async initializeDashboard() {
        try {
            await loadDashboardData();
            renderDashboardKPIs();
            renderSalesChart();
            
            // Initialize notification popup system
            this.initializeNotificationPopup();
            
            // Start auto-refresh for dashboard every 60 seconds
            this.startDashboardAutoRefresh();
        } catch (error) {
            console.error('Error initializing dashboard:', error);
        }
    },
    
    startDashboardAutoRefresh() {
        // Clear any existing interval
        if (this.dashboardRefreshInterval) {
            clearInterval(this.dashboardRefreshInterval);
        }
        
        // Refresh dashboard every 60 seconds
        this.dashboardRefreshInterval = setInterval(async () => {
            if (state.currentPage === 'dashboard') {
                console.log('Auto-refreshing dashboard...');
                try {
                    await loadDashboardData();
                    renderDashboardKPIs();
                    renderSalesChart();
                    console.log('Dashboard auto-refresh completed');
                } catch (error) {
                    console.error('Error auto-refreshing dashboard:', error);
                }
            }
        }, 60000); // Every 60 seconds
    },
    
    async initializeProducts() {
        try {
            console.log('🔄 initializeProducts called - starting product loading...');
            console.log('🔄 Current state.products before load:', state.products.length);
            
            const loadSuccess = await loadProducts();
            console.log('🔄 loadProducts result:', loadSuccess);
            console.log('🔄 state.products after load:', state.products.length);
            console.log('🔄 state.products data:', state.products);
            
            renderProducts();
            console.log('🔄 renderProducts completed');
            
            initializeProductFilters();
            console.log('🔄 Product filters initialized');
        } catch (error) {
            console.error('❌ Error initializing products:', error);
            console.error('❌ Error details:', error.message);
            console.error('❌ Error stack:', error.stack);
        }
    },
    
    async initializeUsers() {
        try {
            await loadUsers();
            renderUsers();
            initializeUserFilters();
        } catch (error) {
            console.error('Error initializing users:', error);
        }
    },
    
    async initializeOrders() {
        try {
            await loadOrders();
            renderOrders();
            initializeOrderFilters();
        } catch (error) {
            console.error('Error initializing orders:', error);
        }
    },
    
    async initializeTransactions() {
        try {
            await loadTransactions();
            renderTransactions();
            initializeTransactionFilters();
        } catch (error) {
            console.error('Error initializing transactions:', error);
        }
    },
    
    async initializeAnnouncements() {
        try {
            await loadAnnouncements();
            renderAnnouncements();
            initializeAnnouncementFilters();
            initializeAnnouncementForm();
        } catch (error) {
            console.error('Error initializing announcements:', error);
        }
    },
    
    async initializeNotifications() {
        try {
            await loadNotifications();
            renderNotifications();
            initializeNotificationFilters();
        } catch (error) {
            console.error('Error initializing notifications:', error);
        }
    },
    
    async initializeNotificationPopup() {
        try {
            initializeNotificationPopup();
            console.log('Notification popup system initialized');
        } catch (error) {
            console.error('Error initializing notification popup:', error);
        }
    },
    
    initializeAdminProfile() {
        // Admin profile initialization is handled in the separate DOMContentLoaded listener
        console.log('Admin profile initialized');
    }
};

// ========================================
// 10. UTILS OBJECT (for compatibility)
// ========================================

const Utils = {
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    formatCurrency(amount) {
        return `₱${parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
    },
    
    showToast: showToast,
    formatDate: formatDate,
    formatDateTime: formatDateTime
};

// ========================================
// ADMIN PROFILE FUNCTIONS
// ========================================

function closeAdminModal() {
    const modal = document.getElementById('addAdminModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function closeEditProfileModal() {
    const modal = document.getElementById('editProfileModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editProfile() {
    // Load current admin profile data and show edit modal
    loadCurrentAdminProfile().then(() => {
        showModal('editProfileModal');
    });
}

function loadCurrentAdminProfile() {
    // Get admin ID from the JavaScript variable set in the PHP page
    const adminId = window.currentAdminId || 1;
    
    return fetch(`api/admin_api.php?action=get_admin_profile&admin_id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.admin) {
                // Populate the edit form with current data (only if elements exist)
                const editAdminId = document.getElementById('editAdminId');
                const editFullName = document.getElementById('editFullName');
                const editEmail = document.getElementById('editEmail');
                const editRole = document.getElementById('editRole');
                
                if (editAdminId) editAdminId.value = data.admin.admin_id;
                if (editFullName) editFullName.value = data.admin.full_name || '';
                if (editEmail) editEmail.value = data.admin.email || '';
                if (editRole) editRole.value = data.admin.role || '';
                
                // Store current admin data for reference
                window.currentAdminData = data.admin;
                window.currentAdminId = data.admin.admin_id;
            } else {
                showToast('Failed to load profile data', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading admin profile:', error);
            showToast('Error loading profile data', 'error');
        });
}

async function handleAdminSubmit() {
    const fullName = document.getElementById('adminFullName').value.trim();
    const email = document.getElementById('adminEmail').value.trim();
    const password = document.getElementById('adminPassword').value.trim();
    const role = document.getElementById('adminRole').value;
    
    if (!fullName || !email || !password || !role) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('adminSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Adding Admin...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('api/admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_admin_to_users&full_name=${encodeURIComponent(fullName)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&role=${encodeURIComponent(role)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Admin user added successfully!', 'success');
            document.getElementById('adminForm').reset();
            closeAdminModal();
            // Refresh users list to show new admin
            if (state.currentPage === 'users') {
                loadUsers().then(renderUsers);
            }
        } else {
            showToast(result.message || 'Failed to add admin user', 'error');
        }
    } catch (error) {
        console.error('Error adding admin user:', error);
        showToast('An error occurred while adding the admin user', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function handleEditProfileSubmit() {
    const adminId = document.getElementById('editAdminId').value;
    const fullName = document.getElementById('editFullName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const password = document.getElementById('editPassword').value.trim();
    const role = document.getElementById('editRole').value;
    
    if (!adminId || !fullName || !email || !role) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('editProfileSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Updating...';
    submitBtn.disabled = true;
    
    try {
        let bodyData = `action=update_admin_profile&admin_id=${encodeURIComponent(adminId)}&full_name=${encodeURIComponent(fullName)}&email=${encodeURIComponent(email)}&role=${encodeURIComponent(role)}`;
        
        if (password) {
            bodyData += `&password=${encodeURIComponent(password)}`;
        }
        
        const response = await fetch('api/admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bodyData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Profile updated successfully!', 'success');
            closeEditProfileModal();
            // Update the profile display without full page reload
            updateProfileDisplay();
        } else {
            showToast(result.message || 'Failed to update profile', 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showToast('An error occurred while updating the profile', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function updateProfileDisplay() {
    // Reload current admin profile data and update the display
    loadCurrentAdminProfile().then(() => {
        if (window.currentAdminData) {
            // Update profile header
            const profileName = document.querySelector('.profile-info h1');
            const profileRole = document.querySelector('.profile-role');
            const profileStatus = document.querySelector('.profile-status');
            
            if (profileName) profileName.textContent = window.currentAdminData.full_name || window.currentAdminData.username || 'Admin';
            if (profileRole) profileRole.textContent = window.currentAdminData.role || 'Administrator';
            if (profileStatus) profileStatus.textContent = `${window.currentAdminData.status || 'Active'} Administrator`;
            
            // Update personal information card
            const fullNameSpan = document.querySelector('.personal-info-card .info-item:nth-child(1) .value-text');
            const emailSpan = document.querySelector('.personal-info-card .info-item:nth-child(2) .value-text');
            const contactSpan = document.querySelector('.personal-info-card .info-item:nth-child(3) .value-text');
            
            if (fullNameSpan) fullNameSpan.textContent = window.currentAdminData.full_name || window.currentAdminData.username || 'Admin';
            if (emailSpan) emailSpan.textContent = window.currentAdminData.email || 'admin@hiryo.com';
            
            // Update account information card
            const roleBadge = document.querySelector('.role-badge');
            const statusBadge = document.querySelector('.status-badge');
            
            if (roleBadge) roleBadge.textContent = window.currentAdminData.role || 'Administrator';
            if (statusBadge) {
                statusBadge.textContent = window.currentAdminData.status === 'Active' ? '✅ Active' : '❌ Suspended';
                statusBadge.className = `status-badge ${window.currentAdminData.status === 'Active' ? 'active' : 'suspended'}`;
            }
        }
    });
}

// Initialize admin profile functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle admin form submission
    const adminForm = document.getElementById('adminForm');
    if (adminForm) {
        adminForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAdminSubmit();
        });
    }
    
    // Handle edit profile form submission
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleEditProfileSubmit();
        });
    }
    
    // Handle user form submission
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveUser();
        });
    }
    
    // Handle product form submission - use event delegation
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'productForm') {
            console.log('Product form submit detected via event delegation');
            e.preventDefault();
            saveProduct();
        }
    });
    
    // Add direct click handler to save button as backup - use event delegation
    document.addEventListener('click', function(e) {
        if (e.target && e.target.type === 'submit' && e.target.closest('#productModal')) {
            console.log('Save button clicked via event delegation');
            e.preventDefault();
            e.stopPropagation();
            saveProduct();
        }
    });
    
    // Load current admin profile data on page load
    loadCurrentAdminProfile();
});

// ========================================
// PRODUCTS FUNCTIONS
// ========================================

// Product management functions
function sortProducts(column) {
    const currentSort = state.productSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.productSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.products.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'price' || column === 'stock_quantity') {
            aVal = parseFloat(aVal) || 0;
            bVal = parseFloat(bVal) || 0;
        } else if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderProducts();
}


function clearProductFilters() {
    clearSearchAndFilters('productSearch', ['productCategoryFilter', 'productStatusFilter'], filterProducts);
    showToast('Filters cleared', 'info');
}

function clearProductSearch() {
    document.getElementById('productSearch').value = '';
    filterProducts();
}

function filterProducts() {
    const searchTerm = document.getElementById('productSearch')?.value.toLowerCase() || '';
    const categoryFilter = document.getElementById('productCategoryFilter')?.value || '';
    const statusFilter = document.getElementById('productStatusFilter')?.value || '';
    
    let filteredData = state.allProducts || state.products || [];
    
    filteredData = filteredData.filter(product => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [product.product_name, product.product_sku, product.description, product.category].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Category filter
        if (categoryFilter && product.category !== categoryFilter) return false;
        
        // Status filter
        if (statusFilter && product.status !== statusFilter) return false;
        
        return true;
    });
    
    state.products = filteredData;
    renderProducts();
    
    // Update count display
    const countElement = document.getElementById('productsCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} products found`;
    }
}

function initializeProductFilters() {
    console.log('Product filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('productCategoryFilter');
    const statusFilter = document.getElementById('productStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterProducts);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterProducts);
    }
    
    // Initial filter
    filterProducts();
}

// ========================================
// ORDERS FUNCTIONS
// ========================================

// Order management functions
function sortOrders(column) {
    const currentSort = state.orderSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.orderSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.orders.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'total_amount') {
            aVal = parseFloat(aVal) || 0;
            bVal = parseFloat(bVal) || 0;
        } else if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderOrders();
}


function clearOrderFilters() {
    clearSearchAndFilters('orderSearch', ['orderStatusFilter'], filterOrders);
    showToast('Filters cleared', 'info');
}

function clearOrderSearch() {
    document.getElementById('orderSearch').value = '';
    filterOrders();
}

function filterOrders() {
    const searchTerm = document.getElementById('orderSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('orderStatusFilter')?.value || '';
    
    let filteredData = state.allOrders || state.orders || [];
    
    filteredData = filteredData.filter(order => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [order.customer_name, order.order_id, order.delivery_address].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Status filter
        if (statusFilter && order.status !== statusFilter) return false;
        
        return true;
    });
    
    state.orders = filteredData;
    renderOrders();
    
    // Update count display
    const countElement = document.getElementById('ordersCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} orders found`;
    }
}

function initializeOrderFilters() {
    console.log('Order filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('orderSearch');
    const statusFilter = document.getElementById('orderStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterOrders);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterOrders);
    }
    
    // Initial filter
    filterOrders();
}

// ========================================
// TRANSACTIONS FUNCTIONS
// ========================================

// Transaction management functions
function sortTransactions(column) {
    const currentSort = state.transactionSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.transactionSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.transactions.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'total_amount') {
            aVal = parseFloat(aVal) || 0;
            bVal = parseFloat(bVal) || 0;
        } else if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderTransactions();
}


function clearTransactionFilters() {
    clearSearchAndFilters('transactionSearch', ['transactionStatusFilter'], filterTransactions);
    showToast('Filters cleared', 'info');
}

function clearTransactionSearch() {
    document.getElementById('transactionSearch').value = '';
    filterTransactions();
}

function filterTransactions() {
    const searchTerm = document.getElementById('transactionSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('transactionStatusFilter')?.value || '';
    
    let filteredData = state.allTransactions || state.transactions || [];
    
    filteredData = filteredData.filter(transaction => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [transaction.customer_name, transaction.transaction_id].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Status filter
        if (statusFilter && transaction.status !== statusFilter) return false;
        
        return true;
    });
    
    state.transactions = filteredData;
    renderTransactions();
    
    // Update count display
    const countElement = document.getElementById('transactionsCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} transactions found`;
    }
}

function initializeTransactionFilters() {
    console.log('Transaction filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('transactionSearch');
    const statusFilter = document.getElementById('transactionStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTransactions);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterTransactions);
    }
    
    // Initial filter
    filterTransactions();
}

// ========================================
// ANNOUNCEMENTS FUNCTIONS
// ========================================

// Announcement management functions
function sortAnnouncements(column) {
    const currentSort = state.announcementSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.announcementSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.announcements.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderAnnouncements();
}


function clearAnnouncementFilters() {
    clearSearchAndFilters('announcementSearch', ['announcementStatusFilter'], filterAnnouncements);
    showToast('Filters cleared', 'info');
}

function clearAnnouncementForm() {
    // Clear announcement form fields
    const form = document.getElementById('announcementForm');
    if (form) {
        form.reset();
    }
    
    // Clear any modal if open
    closeModal('addAnnouncementModal');
    closeModal('editAnnouncementModal');
    
    showToast('Form cleared', 'info');
}

function clearAnnouncementSearch() {
    document.getElementById('announcementSearch').value = '';
    filterAnnouncements();
}

function filterAnnouncements() {
    const searchTerm = document.getElementById('announcementSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('announcementStatusFilter')?.value || '';
    
    let filteredData = state.allAnnouncements || state.announcements || [];
    
    filteredData = filteredData.filter(announcement => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [announcement.title, announcement.content].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Status filter
        if (statusFilter && announcement.status !== statusFilter) return false;
        
        return true;
    });
    
    state.announcements = filteredData;
    renderAnnouncements();
    
    // Update count display
    const countElement = document.getElementById('announcementsCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} announcements found`;
    }
}

function initializeAnnouncementFilters() {
    console.log('Announcement filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('announcementSearch');
    const statusFilter = document.getElementById('announcementStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterAnnouncements);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterAnnouncements);
    }
    
    // Initial filter
    filterAnnouncements();
}

// ========================================
// NOTIFICATIONS FUNCTIONS
// ========================================

// Notification management functions
function sortNotifications(column) {
    const currentSort = state.notificationSort;
    const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
    
    state.notificationSort = { column, direction };
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.textContent = '↕️';
    });
    
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = direction === 'asc' ? '↑' : '↓';
    }
    
    // Sort the data
    state.notifications.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle special cases
        if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    renderNotifications();
}


function clearNotificationFilters() {
    clearSearchAndFilters('notificationSearch', ['notificationTypeFilter', 'notificationStatusFilter'], filterNotifications);
    showToast('Filters cleared', 'info');
}

function clearNotificationSearch() {
    document.getElementById('notificationSearch').value = '';
    filterNotifications();
}

function filterNotifications() {
    const searchTerm = document.getElementById('notificationSearch')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('notificationTypeFilter')?.value || '';
    const statusFilter = document.getElementById('notificationStatusFilter')?.value || '';
    
    let filteredData = state.allNotifications || state.notifications || [];
    
    filteredData = filteredData.filter(notification => {
        // Search filter
        if (searchTerm) {
            const searchableFields = [notification.title, notification.message].filter(field => 
                typeof field === 'string' && field.toLowerCase().includes(searchTerm)
            );
            if (searchableFields.length === 0) return false;
        }
        
        // Type filter
        if (typeFilter && notification.type !== typeFilter) return false;
        
        // Status filter
        if (statusFilter && notification.status !== statusFilter) return false;
        
        return true;
    });
    
    state.notifications = filteredData;
    renderNotifications();
    
    // Update count display
    const countElement = document.getElementById('notificationsCount');
    if (countElement) {
        countElement.textContent = `${filteredData.length} notifications found`;
    }
}

function initializeNotificationFilters() {
    console.log('Notification filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('notificationSearch');
    const typeFilter = document.getElementById('notificationTypeFilter');
    const statusFilter = document.getElementById('notificationStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterNotifications);
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', filterNotifications);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterNotifications);
    }
    
    // Initial filter
    filterNotifications();
}

// ========================================
// DASHBOARD FUNCTIONS
// ========================================


function exportDashboardData() {
    // Export dashboard data as CSV
    const data = [
        ['Metric', 'Value'],
        ['Total Users', state.users?.length || 0],
        ['Total Orders', state.orders?.length || 0],
        ['Total Products', state.products?.length || 0],
        ['Total Transactions', state.transactions?.length || 0]
    ];
    
    const csvContent = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dashboard_data.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('Dashboard data exported', 'success');
}

// ========================================
// SIGNOUT FUNCTION
// ========================================

function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        // Clear any stored data
        localStorage.clear();
        sessionStorage.clear();
        
        // Redirect to login page
        window.location.href = 'login.php';
        
        showToast('Signed out successfully', 'success');
    }
}

// ========================================
// MISSING RENDER FUNCTIONS
// ========================================

function getOrderActionButtons(order) {
    const status = order.status?.toLowerCase();
    const deliveryMethod = order.delivery_method?.toLowerCase() || 'delivery';
    
    console.log(`getOrderActionButtons: orderId=${order.order_id}, status=${status}, deliveryMethod=${deliveryMethod}`);
    
    // Check if delivery method is pickup
    const isPickup = deliveryMethod === 'pickup' || deliveryMethod === 'pick up';
    
    switch (status) {
        case 'pending':
            // For both delivery and pickup: Show Confirm and Cancel buttons
            return `
                <button class="btn-text confirm-btn" onclick="showConfirmModal(${order.order_id}, '${isPickup ? 'Pickup' : 'Processing'}', '${isPickup ? 'Mark as Pickup' : 'Confirm Order'}')" title="${isPickup ? 'Mark as Pickup' : 'Confirm Order'}">Confirm</button>
                <button class="btn-text cancel-btn" onclick="showCancelModal(${order.order_id})" title="Cancel Order">Cancel</button>
            `;
            
        case 'processing':
            // For delivery: Show Shipped button (Cancel no longer shown after Confirm)
            return `
                <button class="btn-text shipped-btn" onclick="showConfirmModal(${order.order_id}, 'Shipped', 'Mark as Shipped')" title="Mark as Shipped">Shipped</button>
            `;
            
        case 'shipped':
            // For delivery: Show Delivered button
            return `
                <button class="btn-text delivered-btn" onclick="showConfirmModal(${order.order_id}, 'Delivered', 'Mark as Delivered')" title="Mark as Delivered">Delivered</button>
            `;
            
        case 'delivered':
            // For delivery: Show Completed button (final step)
            return `
                <button class="btn-text complete-btn" onclick="showConfirmModal(${order.order_id}, 'Completed', 'Mark as Complete')" title="Mark as Complete">Completed</button>
            `;
            
        case 'pickup':
            // For pickup: Show Completed button (Cancel no longer shown after Confirm)
            return `
                <button class="btn-text complete-btn" onclick="showConfirmModal(${order.order_id}, 'Completed', 'Mark as Complete')" title="Mark as Complete">Completed</button>
            `;
            
        case 'completed':
            // Final status: No longer clickable
            return `<span class="completed-text">✅ Complete</span>`;
            
        case 'cancelled':
            // Cancelled status: Show cancelled text
            return `<span class="cancelled-text">❌ Cancelled</span>`;
            
        default:
            // Default: Show Confirm and Cancel buttons
            return `
                <button class="btn-text confirm-btn" onclick="showConfirmModal(${order.order_id}, '${isPickup ? 'Pickup' : 'Processing'}', '${isPickup ? 'Mark as Pickup' : 'Confirm Order'}')" title="${isPickup ? 'Mark as Pickup' : 'Confirm Order'}">Confirm</button>
                <button class="btn-text cancel-btn" onclick="showCancelModal(${order.order_id})" title="Cancel Order">Cancel</button>
            `;
    }
}


// Modal functions for order actions
function showConfirmModal(orderId, newStatus, actionTitle) {
    console.log(`showConfirmModal called: orderId=${orderId}, newStatus=${newStatus}, actionTitle=${actionTitle}`);
    
    const modal = document.getElementById('orderActionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    console.log('Modal elements found:', {
        modal: !!modal,
        modalTitle: !!modalTitle,
        modalMessage: !!modalMessage,
        confirmBtn: !!confirmBtn
    });
    
    if (!modal || !modalTitle || !modalMessage || !confirmBtn) {
        console.error('Missing modal elements');
        return;
    }
    
    modalTitle.textContent = actionTitle;
    modalMessage.textContent = `Are you sure you want to ${actionTitle.toLowerCase()} for Order #${orderId}?`;
    confirmBtn.textContent = actionTitle;
    
    // Store the action data for when confirm is clicked
    confirmBtn.onclick = () => {
        console.log(`🔵 Confirm button clicked: orderId=${orderId}, newStatus=${newStatus}`);
        closeOrderActionModal();
        console.log(`🔵 Calling updateOrderStatus with orderId=${orderId}, newStatus=${newStatus}`);
        updateOrderStatus(orderId, newStatus);
    };
    
    modal.style.display = 'flex';
    console.log('Modal displayed');
}

function showCancelModal(orderId) {
    const modal = document.getElementById('orderActionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    modalTitle.textContent = 'Cancel Order';
    modalMessage.textContent = `Are you sure you want to cancel Order #${orderId}? This action cannot be undone.`;
    confirmBtn.textContent = 'Cancel Order';
    
    // Store the action data for when confirm is clicked
    confirmBtn.onclick = () => {
        closeOrderActionModal();
        updateOrderStatus(orderId, 'Cancelled');
    };
    
    modal.style.display = 'flex';
}

function showCancelModal(orderId) {
    console.log(`showCancelModal called: orderId=${orderId}`);
    
    const modal = document.getElementById('orderActionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    if (!modal || !modalTitle || !modalMessage || !confirmBtn) {
        console.error('Missing modal elements for cancel modal');
        return;
    }
    
    modalTitle.textContent = 'Cancel Order';
    modalMessage.textContent = `Are you sure you want to cancel Order #${orderId}? This action cannot be undone.`;
    confirmBtn.textContent = 'Cancel Order';
    
    // Store the action data for when confirm is clicked
    confirmBtn.onclick = () => {
        closeOrderActionModal();
        updateOrderStatus(orderId, 'Cancelled');
    };
    
    modal.style.display = 'flex';
}

function closeOrderActionModal() {
    console.log('closeOrderActionModal called');
    const modal = document.getElementById('orderActionModal');
    if (modal) {
        modal.style.display = 'none';
        console.log('Modal closed');
    } else {
        console.error('Modal element not found for closing');
    }
}

// Add event listeners for modal interactions
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderActionModal');
    if (modal) {
        // Close modal if clicking on the backdrop (not on modal content)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeOrderActionModal();
            }
        });
    }
    
    // Close modal when pressing ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('orderActionModal');
            if (modal && modal.style.display === 'flex') {
                closeOrderActionModal();
            }
        }
    });
});

/**
 * Show confirmation dialog before updating order status
 */
function confirmStatusUpdate(orderId, newStatus, actionTitle) {
    const confirmMessage = `Are you sure you want to ${actionTitle.toLowerCase()} for Order #${orderId}?`;
    
    // Use the existing order action modal for confirmation
    const modal = document.getElementById('orderActionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    if (!modal || !modalTitle || !modalMessage || !confirmBtn) {
        // Fallback to basic confirm dialog
        if (confirm(confirmMessage)) {
            console.log(`User confirmed: ${actionTitle} for Order #${orderId}`);
            updateOrderStatus(orderId, newStatus);
        } else {
            console.log(`User cancelled: ${actionTitle} for Order #${orderId}`);
        }
        return;
    }
    
    // Set modal content
    modalTitle.textContent = `Confirm ${actionTitle}`;
    modalMessage.textContent = confirmMessage;
    confirmBtn.textContent = actionTitle;
    
    // Store the action data for when confirm is clicked
    confirmBtn.onclick = () => {
        console.log(`User confirmed: ${actionTitle} for Order #${orderId}`);
        closeOrderActionModal();
        updateOrderStatus(orderId, newStatus);
    };
    
    // Add click outside modal to close functionality
    modal.onclick = (event) => {
        if (event.target === modal) {
            console.log(`User cancelled: ${actionTitle} for Order #${orderId}`);
            closeOrderActionModal();
        }
    };
    
    // Show the modal
    modal.style.display = 'flex';
    console.log('Confirmation modal displayed');
}

async function updateOrderStatus(orderId, newStatus) {
    try {
        console.log(`updateOrderStatus called: orderId=${orderId}, newStatus=${newStatus}`);
        
        // Show loading state
        showToast(`Updating order #${orderId}...`, 'info');
        
        // Add error handling for network issues
        if (!navigator.onLine) {
            throw new Error('No internet connection');
        }
        
        const response = await fetch('api/orders_api_v2.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_order_status',
                order_id: orderId,
                status: newStatus
            })
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`API Error: ${response.status} - ${errorText}`);
            throw new Error(`Server error: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Update order status response:', result);
        
        if (result.success) {
            showToast(`✅ Order #${orderId} status updated to ${newStatus}`, 'success');
            console.log('Order status updated successfully, refreshing orders list and modal...');
            
            // Note: No confirmation modal to close since we're bypassing it
            
            // Immediately refresh the modal if it's open
            const modal = document.getElementById('orderModal');
            if (modal && modal.style.display === 'block') {
                console.log('Order modal is open, refreshing order details immediately...');
                
                // If order is completed or cancelled, close modal and show completion message
                if (newStatus.toLowerCase() === 'completed' || newStatus.toLowerCase() === 'cancelled') {
                    console.log('Order completed/cancelled, closing modal and showing completion message');
                    closeOrderModal();
                    if (newStatus.toLowerCase() === 'completed') {
                        showToast(`✅ Order #${orderId} completed and moved to transactions!`, 'success');
                    } else {
                        showToast(`❌ Order #${orderId} cancelled and moved to transactions!`, 'warning');
                    }
                } else {
                    // Show immediate visual feedback and update buttons
                    console.log('Updating modal immediately...');
                    
                    // Update status element
                    const statusElement = document.querySelector('#orderDetailsContent .status');
                    if (statusElement) {
                        statusElement.innerHTML = `${getStatusIcon(newStatus)} ${newStatus}`;
                        statusElement.className = `status ${newStatus.toLowerCase()}`;
                        console.log('✅ Status updated immediately to:', newStatus);
                    }
                    
                    // Update buttons immediately
                    const statusUpdateSection = document.querySelector('#orderDetailsContent .status-update-section');
                    if (statusUpdateSection) {
                        const statusActions = statusUpdateSection.querySelector('.status-actions');
                        if (statusActions) {
                            // Create a temporary order object with updated status
                            const tempOrder = { order_id: orderId, status: newStatus, delivery_method: 'Delivery' };
                            statusActions.innerHTML = getOrderStatusUpdateButtons(tempOrder);
                            console.log('✅ Buttons updated immediately');
                        }
                    }
                    
                    // Also try to refresh from server for complete data
                    setTimeout(async () => {
                        try {
                            console.log('Refreshing modal content from server...');
                            await refreshOrderModal(orderId);
                            console.log('Modal refreshed successfully from server');
                        } catch (error) {
                            console.error('Error refreshing modal from server:', error);
                        }
                    }, 500);
                }
            }
            
            // Also refresh the orders list in the background
            setTimeout(async () => {
                console.log('Starting auto-refresh of orders list...');
                await loadOrders(); // Refresh the orders list
                console.log('Orders list auto-refresh completed');
                
                // Refresh dashboard when orders change
                if (state.currentPage === 'dashboard') {
                    console.log('Dashboard is active, refreshing dashboard data...');
                    await loadDashboardData();
                    renderDashboardKPIs();
                    renderSalesChart();
                    console.log('Dashboard refreshed successfully');
                }
                
                // If order was completed or cancelled, also refresh transactions table
                if (newStatus.toLowerCase() === 'completed' || newStatus.toLowerCase() === 'cancelled') {
                    console.log('Order completed/cancelled, refreshing transactions table...');
                    await loadTransactions(); // Refresh the transactions list
                    console.log('Transactions table refreshed');
                }
            }, 300);
        } else {
            showToast(`❌ Failed to update order status: ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Error updating order status:', error);
        showToast(`❌ Error: ${error.message}`, 'error');
    }
}

function renderOrders() {
    console.log(`renderOrders called with ${state.orders?.length || 0} orders`);
    console.log('Current orders state:', state.orders);
    
    const rowRenderer = (order) => `
        <tr>
            <td class="order-id-column" data-label="Order Number">#${order.order_id}</td>
            <td class="customer-column" data-label="Customer">${escapeHtml(order.customer_name || ((order.firstName || '') + ' ' + (order.lastName || '')).trim() || 'N/A')}</td>
            <td class="date-column" data-label="Date">${formatDate(order.order_date || order.created_at)}</td>
            <td class="total-column" data-label="Total">₱${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td class="payment-column" data-label="Payment">${escapeHtml(order.payment_method || 'COD')}</td>
            <td class="delivery-column" data-label="Delivery Method">${escapeHtml(order.delivery_method || 'Delivery')}</td>
            <td class="status-column" data-label="Status">
                <span class="status ${order.status?.toLowerCase()}">
                    ${getStatusIcon(order.status)} ${escapeHtml(order.status || 'Unknown')}
                </span>
            </td>
            <td class="actions-column" data-label="Actions">
                <button class="btn-text view-order-btn" onclick="viewOrderDetails(${order.order_id})" title="View Order Details">
                    👁️ View Order
                </button>
            </td>
        </tr>
    `;
    
    console.log('Calling renderTable for orders...');
    renderTable(state.orders, 'orderRows', rowRenderer, 'No orders found');
    console.log('renderOrders completed');
}

function renderTransactions() {
    const rowRenderer = (transaction) => `
        <tr>
            <td class="order-number-column" data-label="Order Number">#${transaction.order_id}</td>
            <td class="customer-column" data-label="Customer">${escapeHtml(transaction.user_name || 'N/A')}</td>
            <td class="amount-column" data-label="Amount">₱${parseFloat(transaction.amount || 0).toFixed(2)}</td>
            <td class="date-column" data-label="Date">${formatDate(transaction.created_at)}</td>
            <td class="status-column" data-label="Status">
                <span class="status ${transaction.order_status?.toLowerCase() || 'completed'}">
                    ${getStatusIcon(transaction.order_status || 'Completed')} ${escapeHtml(transaction.order_status || 'Completed')}
                </span>
            </td>
            <td class="actions-column" data-label="Actions">
                <button class="btn brand view-order-btn" onclick="viewCompletedOrderDetails(${transaction.order_id})" title="View Order Details">
                    👁️ View Details
                </button>
            </td>
        </tr>
    `;
    
    renderTable(state.transactions, 'transactionRows', rowRenderer, 'No transactions found');
}

// ========================================
// COMPLETED ORDER DETAILS MODAL FUNCTIONS
// ========================================

async function viewCompletedOrderDetails(orderId) {
    console.log('viewCompletedOrderDetails called for order ID:', orderId);
    
    try {
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/transactions_api_v5.php?action=get_completed_order_details&order_id=${orderId}&t=${Date.now()}`, {
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Completed order details response:', data);
        
        if (data.success && data.order) {
            displayCompletedOrderDetailsModal(data.order);
        } else {
            showToast(`Failed to load order details: ${data.message || 'Unknown error'}`, 'error');
        }
        
    } catch (error) {
        console.error('Error fetching completed order details:', error);
        showToast('Failed to load order details. Please try again.', 'error');
    }
}

function displayCompletedOrderDetailsModal(order) {
    console.log('displayCompletedOrderDetailsModal called with order:', order);
    console.log('Order data keys:', Object.keys(order));
    console.log('Order items:', order.items);
    console.log('Order customer_name:', order.customer_name);
    console.log('Order customer_email:', order.customer_email);
    console.log('Order total_amount:', order.total_amount);
    console.log('Order amount:', order.amount);
    
    const modal = document.getElementById('completedOrderModal');
    const modalTitle = document.getElementById('completedOrderModalTitle');
    const modalContent = document.getElementById('completedOrderDetailsContent');
    
    if (!modal || !modalTitle || !modalContent) {
        console.error('Modal elements not found');
        showToast('Modal elements not found', 'error');
        return;
    }
    
    // Get order status (check multiple fields for compatibility)
    const orderStatus = (order.status || order.order_status || 'Completed').toLowerCase();
    const isCancelled = orderStatus === 'cancelled';
    const statusDisplay = isCancelled ? 'Cancelled' : 'Completed';
    const statusIcon = isCancelled ? '❌' : '✅';
    const statusClass = isCancelled ? 'cancelled' : 'completed';
    const statusText = isCancelled ? 'Order Cancelled' : 'Completed Successfully';
    const dateLabel = isCancelled ? 'Cancelled on' : 'Completed on';
    
    // Update modal title
    modalTitle.innerHTML = `${statusDisplay} Order Details - Order Number: ${order.order_id}`;
    
    // Parse items if they exist
    let itemsHtml = '';
    let totalItems = 0;
    let totalQuantity = 0;
    
    if (order.items && Array.isArray(order.items) && order.items.length > 0) {
        totalItems = order.items.length;
        itemsHtml = order.items.map((item, index) => {
            const quantity = parseInt(item.quantity) || 1;
            const price = parseFloat(item.price || item.price_at_purchase || item.item_price) || 0;
            const itemTotal = price * quantity;
            totalQuantity += quantity;
            
            return `
                <div class="order-item-card">
                    <div class="item-number">${index + 1}</div>
                    <div class="item-details">
                        <div class="item-name">${escapeHtml(item.name || item.product_name || 'Unknown Product')}</div>
                        <div class="item-meta">
                            <span class="item-quantity">Quantity: ${quantity}</span>
                            <span class="item-price">₱${price.toFixed(2)} each</span>
                        </div>
                    </div>
                    <div class="item-total">
                        <span class="total-label">Total</span>
                        <span class="total-amount">₱${itemTotal.toFixed(2)}</span>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        itemsHtml = `
            <div class="no-items-card">
                <div class="no-items-icon">📦</div>
                <div class="no-items-text">No items found for this order</div>
            </div>
        `;
    }
    
            // Calculate totals - calculate subtotal from items if not provided
            let subtotal = parseFloat(order.subtotal) || 0;
            const deliveryFee = parseFloat(order.delivery_fee) || 0;
            let totalAmount = parseFloat(order.total_amount || order.amount) || 0;
            
            // If subtotal is 0 but we have items, calculate it from items
            if (subtotal === 0 && order.items && Array.isArray(order.items) && order.items.length > 0) {
                subtotal = order.items.reduce((sum, item) => {
                    const quantity = parseInt(item.quantity) || 1;
                    const price = parseFloat(item.price || item.price_at_purchase || item.item_price) || 0;
                    return sum + (price * quantity);
                }, 0);
            }
            
            // Calculate total amount if missing
            if (totalAmount === 0) {
                totalAmount = subtotal + deliveryFee;
            }
    
    // Create enhanced modal content
    modalContent.innerHTML = `
        <div class="completed-order-container">
            <!-- Order Header -->
            <div class="order-header-card">
                <div class="order-header-icon">${statusIcon}</div>
                <div class="order-header-info">
                    <h3 class="order-title">Order #${order.order_id}</h3>
                    <p class="order-status ${statusClass}">${statusText}</p>
                    <p class="order-date">${dateLabel} ${formatDate(order.completion_date || order.created_at)}</p>
                </div>
            </div>
            
            <!-- Order Summary Cards -->
            <div class="summary-cards-grid">
                <div class="summary-card">
                    <div class="summary-icon">🛒</div>
                    <div class="summary-info">
                        <span class="summary-label">Items Ordered</span>
                        <span class="summary-value">${totalItems} items (${totalQuantity} units)</span>
                    </div>
                </div>
                         <div class="summary-card">
                             <div class="summary-icon">💰</div>
                             <div class="summary-info">
                                 <span class="summary-label">Subtotal</span>
                                 <span class="summary-value">₱${subtotal.toFixed(2)}</span>
                             </div>
                         </div>
                         <div class="summary-card">
                             <div class="summary-icon">🚚</div>
                             <div class="summary-info">
                                 <span class="summary-label">Delivery Fee</span>
                                 <span class="summary-value">₱${deliveryFee.toFixed(2)}</span>
                             </div>
                         </div>
                         <div class="summary-card">
                             <div class="summary-icon">${order.delivery_method && order.delivery_method.toLowerCase() === 'pickup' ? '🏪' : '🚚'}</div>
                             <div class="summary-info">
                                 <span class="summary-label">Delivery Method</span>
                                 <span class="summary-value">${escapeHtml(order.delivery_method || 'Delivery')}</span>
                             </div>
                         </div>
                         <div class="summary-card">
                             <div class="summary-icon">💳</div>
                             <div class="summary-info">
                                 <span class="summary-label">Payment Method</span>
                                 <span class="summary-value">${escapeHtml(order.payment_method || 'COD')}</span>
                             </div>
                         </div>
            </div>
            
            <!-- Customer Information -->
            <div class="info-section">
                <h4 class="section-title">
                    <span class="section-icon">👤</span>
                    Customer Information
                </h4>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Customer Name</span>
                        <span class="info-value">${escapeHtml(order.customer_name || order.user_name || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email Address</span>
                        <span class="info-value">${escapeHtml(order.customer_email || order.user_email || 'N/A')}</span>
                    </div>
                    ${order.customer_contact ? `
                    <div class="info-item">
                        <span class="info-label">Contact Number</span>
                        <span class="info-value">${escapeHtml(order.customer_contact)}</span>
                    </div>
                    ` : ''}
                    ${order.delivery_method && order.delivery_method.toLowerCase() !== 'pickup' && order.shipping_address ? `
                    <div class="info-item full-width">
                        <span class="info-label">Delivery Address</span>
                        <span class="info-value address-text">${escapeHtml(order.shipping_address)}</span>
                    </div>
                    ` : ''}
                    ${parseFloat(order.delivery_fee || 0) > 0 ? `
                    <div class="info-item">
                        <span class="info-label">Delivery Fee:</span>
                        <span class="info-value">₱${parseFloat(order.delivery_fee || 0).toFixed(2)}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="info-section">
                <h4 class="section-title">
                    <span class="section-icon">🛒</span>
                    Order Items (${totalItems} items)
                </h4>
                <div class="items-container">
                    ${itemsHtml}
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="info-section">
                <h4 class="section-title">
                    <span class="section-icon">💰</span>
                    Order Summary
                </h4>
                <div class="order-summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal (${totalItems} items)</span>
                        <span class="summary-value">₱${subtotal.toFixed(2)}</span>
                    </div>
                             <div class="summary-row">
                                 <span class="summary-label">Delivery Fee</span>
                                 <span class="summary-value">₱${deliveryFee.toFixed(2)}</span>
                             </div>
                    <div class="summary-row total-row">
                        <span class="summary-label">Total Amount</span>
                        <span class="summary-value">₱${totalAmount.toFixed(2)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button class="btn secondary" onclick="closeCompletedOrderModal()">
                    <span class="btn-icon">✕</span>
                    Close
                </button>
            </div>
        </div>
    `;
    
    // Show modal
    modal.style.display = 'flex';
    console.log('Enhanced completed order modal displayed');
}

function closeCompletedOrderModal() {
    const modal = document.getElementById('completedOrderModal');
    if (modal) {
        modal.style.display = 'none';
        console.log('Completed order modal closed');
    }
}

function renderAnnouncements() {
    const rowRenderer = (announcement) => `
        <tr>
            <td class="title-column" data-label="Title">${escapeHtml(announcement.title || 'Untitled')}</td>
            <td class="message-column" data-label="Message">${escapeHtml(announcement.message || 'No message')}</td>
            <td class="date-column" data-label="Date">${formatDate(announcement.created_at)}</td>
            <td class="actions-column" data-label="Actions">
                <button class="btn-icon view-btn" onclick="viewAnnouncement(${announcement.announcement_id})" title="View">👁️</button>
            </td>
        </tr>
    `;
    
    renderTable(state.announcements, 'announcementRows', rowRenderer, 'No announcements found');
}

function viewAnnouncement(announcementId) {
    const announcement = state.announcements.find(a => a.announcement_id === announcementId);
    if (!announcement) {
        showToast('Announcement not found', 'error');
        return;
    }
    
    // Find or create modal
    let modal = document.getElementById('announcementViewModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'announcementViewModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>📢 Announcement Details</h2>
                    <button class="close-modal" onclick="closeAnnouncementViewModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="announcement-view-details">
                        <div class="detail-group">
                            <label>Title</label>
                            <div class="detail-value" id="modalAnnouncementTitle"></div>
                        </div>
                        <div class="detail-group">
                            <label>Message</label>
                            <div class="detail-value" id="modalAnnouncementMessage"></div>
                        </div>
                        <div class="detail-group">
                            <label>Date Created</label>
                            <div class="detail-value" id="modalAnnouncementDate"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn secondary" onclick="closeAnnouncementViewModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Populate modal with announcement data
    document.getElementById('modalAnnouncementTitle').textContent = announcement.title;
    document.getElementById('modalAnnouncementMessage').textContent = announcement.message;
    document.getElementById('modalAnnouncementDate').textContent = formatDate(announcement.created_at);
    
    // Show modal
    modal.style.display = 'flex';
}

function closeAnnouncementViewModal() {
    const modal = document.getElementById('announcementViewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function renderNotifications() {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    if (!state.notifications || state.notifications.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">🔔</div><h3>No Notifications Found</h3><p>No notifications available at the moment.</p></div>';
        return;
    }
    
    container.innerHTML = state.notifications.map(notification => `
        <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" onclick="viewNotification(${notification.notification_id})">
            <div class="notification-icon">
                ${notification.is_read ? '📖' : '🔔'}
            </div>
            <div class="notification-content">
                <div class="notification-header">
                    <h4 class="notification-title">${escapeHtml(notification.message || 'Notification')}</h4>
                    <span class="notification-date">${formatDate(notification.created_at)}</span>
                </div>
                <div class="notification-meta">
                    <span class="notification-user">${escapeHtml(notification.firstName + ' ' + notification.lastName || 'System')}</span>
                    <span class="notification-status ${notification.is_read ? 'read' : 'unread'}">
                        ${notification.is_read ? 'Read' : 'Unread'}
                    </span>
                </div>
            </div>
        </div>
    `).join('');
}

// ========================================
// FILTER INITIALIZATION FUNCTIONS
// ========================================

function initializeProductFilters() {
    console.log('Product filters initialized');
}

function initializeUserFilters() {
    console.log('User filters initialized');
}

function initializeOrderFilters() {
    console.log('Order filters initialized');
}

// ========================================
// NOTIFICATION FUNCTIONS
// ========================================

// Global variables for notification popup
let notificationPopup = null;
let notificationCheckInterval = null;
let lastNotificationCount = 0;

// Initialize notification popup system
function initializeNotificationPopup() {
    createNotificationPopup();
    startNotificationPolling();
}

// Create notification popup element
function createNotificationPopup() {
    // Remove existing popup if any
    if (notificationPopup) {
        notificationPopup.remove();
    }
    
    notificationPopup = document.createElement('div');
    notificationPopup.id = 'notificationPopup';
    notificationPopup.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #FF5722 0%, #D84315 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(255, 87, 34, 0.3);
        z-index: 10000;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        cursor: pointer;
        display: none;
    `;
    
    document.body.appendChild(notificationPopup);
    
    // Add click handler to open notifications page
    notificationPopup.addEventListener('click', () => {
        hideNotificationPopup();
        PageLoader.loadPage('notifications');
    });
}

// Show notification popup
function showNotificationPopup(title, message, type = 'info') {
    if (!notificationPopup) {
        createNotificationPopup();
    }
    
    const icon = type === 'order' ? '🛒' : type === 'announcement' ? '📢' : '🔔';
    
    notificationPopup.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="font-size: 24px;">${icon}</div>
            <div>
                <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;">${title}</div>
                <div style="font-size: 14px; opacity: 0.9;">${message}</div>
            </div>
            <button onclick="hideNotificationPopup()" style="
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                margin-left: auto;
            ">×</button>
        </div>
    `;
    
    notificationPopup.style.display = 'block';
    
    // Animate in
    setTimeout(() => {
        notificationPopup.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        hideNotificationPopup();
    }, 5000);
}

// Hide notification popup
function hideNotificationPopup() {
    if (notificationPopup) {
        notificationPopup.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notificationPopup) {
                notificationPopup.style.display = 'none';
            }
        }, 300);
    }
}

// Start polling for new notifications
function startNotificationPolling() {
    // Check for new notifications every 30 seconds
    notificationCheckInterval = setInterval(async () => {
        try {
            const timestamp = Date.now();
            const response = await fetch(`api/notifications_api.php?action=get_all_notifications_for_admin&_t=${timestamp}`, {
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.notifications) {
                    const currentCount = result.notifications.length;
                    const unreadCount = result.notifications.filter(n => !n.is_read).length;
                    
                    // Check if there are new unread notifications
                    if (unreadCount > lastNotificationCount && lastNotificationCount > 0) {
                        const newNotifications = result.notifications
                            .filter(n => !n.is_read)
                            .slice(0, unreadCount - lastNotificationCount);
                        
                        newNotifications.forEach(notification => {
                            showNotificationPopup(
                                notification.title || 'New Notification',
                                notification.message || 'You have a new notification',
                                notification.type || 'info'
                            );
                        });
                        
                        // If dashboard is active, refresh it when new order notifications arrive
                        if (state.currentPage === 'dashboard') {
                            console.log('New order notification detected, refreshing dashboard...');
                            loadDashboardData().then(() => {
                                renderDashboardKPIs();
                                renderSalesChart();
                                console.log('Dashboard refreshed due to new order notification');
                            });
                        }
                        
                        // Also refresh orders page if active
                        if (state.currentPage === 'orders') {
                            console.log('New order notification detected, refreshing orders...');
                            loadOrders();
                        }
                    }
                    
                    lastNotificationCount = unreadCount;
                    
                    // Update notification badge if exists
                    updateNotificationBadge(unreadCount);
                }
            }
        } catch (error) {
            console.error('Error checking for new notifications:', error);
        }
    }, 30000); // Check every 30 seconds
}

// Update notification badge in navigation
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Render notifications list
function renderNotifications() {
    console.log(`renderNotifications called with ${state.notifications?.length || 0} notifications`);
    
    const container = document.getElementById('notificationsList');
    if (!container) {
        console.error('Notifications container not found');
        return;
    }
    
    if (!state.notifications || state.notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet.</p>
            </div>
        `;
        return;
    }
    
    const notificationsHtml = state.notifications.map(notification => `
        <div class="notification-card ${notification.is_read ? 'read' : 'unread'}" 
             onclick="showNotificationDetails(${notification.notification_id})">
            ${!notification.is_read ? '<div class="unread-indicator"></div>' : ''}
            
            <div class="notification-header">
                <h4>${escapeHtml(notification.title || 'Notification')}</h4>
                <span class="notification-type ${notification.type || 'info'}">
                    ${getNotificationTypeIcon(notification.type)} ${notification.type || 'General'}
                </span>
            </div>
            
            <p>${escapeHtml(notification.message || 'No message available')}</p>
            
            <small>${formatDate(notification.created_at)}</small>
            
            <div class="notification-meta">
                <div class="notification-actions" onclick="event.stopPropagation()">
                    ${!notification.is_read ? 
                        `<button class="notification-action-btn mark-read-btn" onclick="markNotificationAsRead(${notification.notification_id})">
                            Mark as Read
                        </button>` : 
                        `<button class="notification-action-btn mark-unread-btn" onclick="markNotificationAsUnread(${notification.notification_id})">
                            Mark as Unread
                        </button>`
                    }
                    <button class="notification-action-btn delete-notification-btn" onclick="deleteNotification(${notification.notification_id})">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = notificationsHtml;
    
    // Update count display
    updateNotificationCount();
}

// Get notification type icon
function getNotificationTypeIcon(type) {
    switch (type) {
        case 'order': return '🛒';
        case 'announcement': return '📢';
        case 'system': return '⚙️';
        default: return '🔔';
    }
}

// Show notification details modal
function showNotificationDetails(notificationId) {
    const notification = state.notifications.find(n => n.notification_id == notificationId);
    if (!notification) return;
    
    const modal = document.getElementById('notificationModal');
    const title = document.getElementById('notificationModalTitle');
    const content = document.getElementById('notificationDetailsContent');
    
    if (modal && title && content) {
        title.textContent = notification.title || 'Notification Details';
        
        content.innerHTML = `
            <div class="notification-details-section">
                <h3>📝 Message</h3>
                <div class="notification-message-content">
                    ${escapeHtml(notification.message || 'No message available')}
                </div>
            </div>
            
            <div class="notification-details-section">
                <h3>ℹ️ Details</h3>
                <div class="detail-item">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">${getNotificationTypeIcon(notification.type)} ${notification.type || 'General'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">${notification.is_read ? '✅ Read' : '📩 Unread'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${formatDate(notification.created_at)}</span>
                </div>
                ${notification.order_id ? `
                <div class="detail-item">
                    <span class="detail-label">Related Order:</span>
                    <span class="detail-value">#${notification.order_id}</span>
                </div>
                ` : ''}
            </div>
        `;
        
        modal.style.display = 'flex';
        
        // Mark as read when viewing
        if (!notification.is_read) {
            markNotificationAsRead(notificationId);
        }
    }
}

// Close notification modal
function closeNotificationModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Mark notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const response = await fetch('api/notifications_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mark_as_read',
                notification_id: notificationId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Update local state
            const notification = state.notifications.find(n => n.notification_id == notificationId);
            if (notification) {
                notification.is_read = true;
                renderNotifications();
            }
            showToast('Notification marked as read', 'success');
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        showToast('Failed to mark notification as read', 'error');
    }
}

// Mark notification as unread
async function markNotificationAsUnread(notificationId) {
    try {
        const response = await fetch('api/notifications_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mark_as_unread',
                notification_id: notificationId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Update local state
            const notification = state.notifications.find(n => n.notification_id == notificationId);
            if (notification) {
                notification.is_read = false;
                renderNotifications();
            }
            showToast('Notification marked as unread', 'success');
        }
    } catch (error) {
        console.error('Error marking notification as unread:', error);
        showToast('Failed to mark notification as unread', 'error');
    }
}

// Delete notification
async function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }
    
    try {
        const response = await fetch('api/notifications_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'delete_notification',
                notification_id: notificationId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Remove from local state
            state.notifications = state.notifications.filter(n => n.notification_id != notificationId);
            renderNotifications();
            showToast('Notification deleted', 'success');
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
        showToast('Failed to delete notification', 'error');
    }
}

// Update notification count display
function updateNotificationCount() {
    const countElement = document.getElementById('notificationsCount');
    if (countElement && state.notifications) {
        const totalCount = state.notifications.length;
        const unreadCount = state.notifications.filter(n => !n.is_read).length;
        
        countElement.textContent = `${totalCount} notifications found${unreadCount > 0 ? ` (${unreadCount} unread)` : ''}`;
    }
}

// Mark all notifications as read
async function markAllAsRead() {
    try {
        const response = await fetch('api/notifications_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mark_all_as_read'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Update local state
            state.notifications.forEach(notification => {
                notification.is_read = true;
            });
            renderNotifications();
            showToast('All notifications marked as read', 'success');
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
        showToast('Failed to mark all notifications as read', 'error');
    }
}

// Clear notification search
function clearNotificationSearch() {
    const searchInput = document.getElementById('notificationSearch');
    if (searchInput) {
        searchInput.value = '';
        filterNotifications();
    }
}

// Clear notification filters
function clearNotificationFilters() {
    const searchInput = document.getElementById('notificationSearch');
    const statusFilter = document.getElementById('notificationStatusFilter');
    const typeFilter = document.getElementById('notificationTypeFilter');
    const dateFilter = document.getElementById('notificationDateFilter');
    const sortFilter = document.getElementById('notificationSort');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (typeFilter) typeFilter.value = '';
    if (dateFilter) dateFilter.value = '';
    if (sortFilter) sortFilter.value = 'date-desc';
    
    filterNotifications();
}

// Filter notifications
function filterNotifications() {
    const searchTerm = document.getElementById('notificationSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('notificationStatusFilter')?.value || '';
    const typeFilter = document.getElementById('notificationTypeFilter')?.value || '';
    const dateFilter = document.getElementById('notificationDateFilter')?.value || '';
    const sortFilter = document.getElementById('notificationSort')?.value || 'date-desc';
    
    if (!state.notifications) return;
    
    let filteredNotifications = state.notifications.filter(notification => {
        // Search filter
        if (searchTerm && !notification.title?.toLowerCase().includes(searchTerm) && 
            !notification.message?.toLowerCase().includes(searchTerm)) {
            return false;
        }
        
        // Status filter
        if (statusFilter) {
            const isRead = notification.is_read;
            if (statusFilter === 'read' && !isRead) return false;
            if (statusFilter === 'unread' && isRead) return false;
        }
        
        // Type filter
        if (typeFilter && notification.type !== typeFilter) {
            return false;
        }
        
        // Date filter (implement as needed)
        // if (dateFilter) {
        //     // Add date filtering logic
        // }
        
        return true;
    });
    
    // Sort notifications
    filteredNotifications.sort((a, b) => {
        switch (sortFilter) {
            case 'date-asc':
                return new Date(a.created_at) - new Date(b.created_at);
            case 'date-desc':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'title-asc':
                return (a.title || '').localeCompare(b.title || '');
            case 'title-desc':
                return (b.title || '').localeCompare(a.title || '');
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });
    
    // Temporarily update state for rendering
    const originalNotifications = state.notifications;
    state.notifications = filteredNotifications;
    renderNotifications();
    state.notifications = originalNotifications; // Restore original state
    
    // Update filter status
    const filterStatus = document.getElementById('notificationFilterStatus');
    if (filterStatus) {
        const activeFilters = [];
        if (searchTerm) activeFilters.push(`Search: "${searchTerm}"`);
        if (statusFilter) activeFilters.push(`Status: ${statusFilter}`);
        if (typeFilter) activeFilters.push(`Type: ${typeFilter}`);
        
        filterStatus.textContent = activeFilters.length > 0 ? 
            `Filters applied: ${activeFilters.join(', ')}` : '';
    }
}

// Initialize notification filters
function initializeNotificationFilters() {
    console.log('Notification filters initialized');
    
    // Add event listeners
    const searchInput = document.getElementById('notificationSearch');
    const statusFilter = document.getElementById('notificationStatusFilter');
    const typeFilter = document.getElementById('notificationTypeFilter');
    const dateFilter = document.getElementById('notificationDateFilter');
    const sortFilter = document.getElementById('notificationSort');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterNotifications);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterNotifications);
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', filterNotifications);
    }
    if (dateFilter) {
        dateFilter.addEventListener('change', filterNotifications);
    }
    if (sortFilter) {
        sortFilter.addEventListener('change', filterNotifications);
    }
    
    // Initial filter
    filterNotifications();
}

function initializeTransactionFilters() {
    console.log('Transaction filters initialized');
}

function initializeAnnouncementFilters() {
    console.log('Announcement filters initialized');
}

// Initialize announcement form submission
function initializeAnnouncementForm() {
    console.log('📢 Initializing announcement form...');
    
    const form = document.getElementById('announcementForm');
    if (form) {
        form.addEventListener('submit', handleAnnouncementSubmit);
        console.log('📢 Announcement form submit handler added');
    } else {
        console.error('📢 Announcement form not found');
    }
}

// Handle announcement form submission
async function handleAnnouncementSubmit(event) {
    event.preventDefault();
    console.log('📢 Announcement form submitted');
    
    const form = event.target;
    const title = form.querySelector('#announcementTitle').value.trim();
    const message = form.querySelector('#announcementMessage').value.trim();
    
    if (!title || !message) {
        showToast('Please fill in both title and message', 'error');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = form.querySelector('#sendAnnouncementBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Sending...';
    submitBtn.disabled = true;
    
    try {
        console.log('📢 Sending announcement:', { title, message });
        
        const formData = new FormData();
        formData.append('action', 'create_announcement');
        formData.append('title', title);
        formData.append('message', message);
        
        console.log('📢 Sending announcement data:', { title, message });
        
        console.log('📢 API URL:', `${APP_CONFIG.API_BASE_URL}/notifications_api.php`);
        
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/notifications_api.php`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        console.log('📢 Response status:', response.status);
        console.log('📢 Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
        }
        
        const responseText = await response.text();
        console.log('📢 Raw response text:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('📢 JSON parse error:', parseError);
            throw new Error('Invalid JSON response from server: ' + responseText);
        }
        
        console.log('📢 Parsed API response:', result);
        
        if (result.success) {
            showToast(result.message, 'success');
            
            // Clear the form
            form.reset();
            
            // Reload announcements to show the new one in the table
            await loadAnnouncements();
            renderAnnouncements();
            
            console.log('📢 Announcement created and sent successfully');
        } else {
            showToast(result.message || 'Failed to send announcement', 'error');
        }
        
    } catch (error) {
        console.error('📢 Error sending announcement:', error);
        showToast('Failed to send announcement: ' + error.message, 'error');
    } finally {
        // Re-enable submit button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function initializeNotificationFilters() {
    console.log('Notification filters initialized');
}

// View Order Details Function
async function viewOrderDetails(orderId) {
    console.log('👁️ Viewing order details for order ID:', orderId);
    
    try {
        showToast('Loading order details...', 'info');
        
        // Fetch order details from API with cache busting
        const response = await fetch(`${APP_CONFIG.API_BASE_URL}/orders_api_v2.php?action=get_order_details&order_id=${orderId}&t=${Date.now()}`, {
            credentials: 'same-origin',
            cache: 'no-cache' // Prevent caching
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('👁️ Order details response:', result);
        
        if (result.success && result.order) {
            console.log('👁️ Displaying order details modal with fresh data:', result.order);
            displayOrderDetailsModal(result.order);
        } else {
            showToast(result.message || 'Failed to load order details', 'error');
        }
        
    } catch (error) {
        console.error('👁️ Error fetching order details:', error);
        showToast('Failed to load order details: ' + error.message, 'error');
    }
}

// Display Order Details in Modal
function displayOrderDetailsModal(order) {
    console.log('👁️ Displaying order details modal with fresh data at:', new Date().toISOString(), order);
    
    const modal = document.getElementById('orderModal');
    const modalTitle = document.getElementById('orderModalTitle');
    const modalContent = document.getElementById('orderDetailsContent');
    
    if (!modal || !modalTitle || !modalContent) {
        console.error('👁️ Modal elements not found');
        return;
    }
    
    // Set modal title
    modalTitle.innerHTML = `Order Details - Order Number: ${order.order_id}`;
    
    // Build modal content
    const orderItems = order.items || [];
    const customerName = order.customer_name || `${order.firstName || ''} ${order.lastName || ''}`.trim() || 'N/A';
    const customerEmail = order.customer_email || order.email || 'N/A';
    const customerContact = order.contact_number || order.customer_contact || 'N/A';
    const shippingAddress = order.shipping_address || 'N/A';
    
    modalContent.innerHTML = `
        <div class="order-details-container">
            <!-- Customer Information Section -->
            <div class="order-section customer-section">
                <h3 class="section-title">👤 Customer Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value">${escapeHtml(customerName)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">${escapeHtml(customerEmail)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Contact Number:</span>
                        <span class="info-value">${escapeHtml(customerContact)}</span>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-label">Shipping Address:</span>
                        <span class="info-value">${escapeHtml(shippingAddress)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Order Information Section -->
            <div class="order-section order-info-section">
                <h3 class="section-title">📋 Order Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value">#${order.order_id}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value">${formatDate(order.order_date || order.created_at)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">${escapeHtml(order.payment_method || 'COD')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Delivery Method:</span>
                        <span class="info-value">${escapeHtml(order.delivery_method || 'Standard')}</span>
                    </div>
                    ${order.delivery_method && order.delivery_method.toLowerCase() !== 'pickup' && order.shipping_address ? `
                    <div class="info-item">
                        <span class="info-label">Delivery Address:</span>
                        <span class="info-value">${escapeHtml(order.shipping_address)}</span>
                    </div>
                    ` : ''}
                    ${parseFloat(order.delivery_fee || 0) > 0 ? `
                    <div class="info-item">
                        <span class="info-label">Delivery Fee:</span>
                        <span class="info-value">₱${parseFloat(order.delivery_fee || 0).toFixed(2)}</span>
                    </div>
                    ` : ''}
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status ${order.status?.toLowerCase()}">
                                ${getStatusIcon(order.status)} ${escapeHtml(order.status || 'Unknown')}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value total-amount">₱${parseFloat(order.total_amount || 0).toFixed(2)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="order-section products-section">
                <h3 class="section-title">📦 Order Items</h3>
                <div class="products-list">
                    ${orderItems.length > 0 ? orderItems.map(item => `
                        <div class="product-item">
                            <div class="product-details">
                                <h4 class="product-name">${escapeHtml(item.product_name || item.productName || 'Unknown Product')}</h4>
                                <p class="product-quantity">Quantity: ${item.quantity || 0}</p>
                                <p class="product-price">Price: ₱${parseFloat(item.price || item.price_at_purchase || 0).toFixed(2)}</p>
                            </div>
                            <div class="product-total">
                                <span class="item-total">₱${(parseFloat(item.price || item.price_at_purchase || 0) * parseInt(item.quantity || 0)).toFixed(2)}</span>
                            </div>
                        </div>
                    `).join('') : '<p class="no-items">No items found</p>'}
                </div>
            </div>
            
            <!-- Update Status Section -->
            <div class="order-section status-update-section">
                <h3 class="section-title">⚙️ Update Order Status</h3>
                <div class="status-actions">
                    ${getOrderStatusUpdateButtons(order)}
                </div>
            </div>
        </div>
    `;
    
    // Show modal
    modal.style.display = 'block';
    
    // Add event listener for clicking outside modal
    window.onclick = function(event) {
        if (event.target == modal) {
            closeOrderModal();
        }
    };
}

// Get Order Status Update Buttons for Modal
function getOrderStatusUpdateButtons(order) {
    const status = order.status?.toLowerCase();
    const deliveryMethod = order.delivery_method?.toLowerCase() || 'delivery';
    const isPickup = deliveryMethod === 'pickup' || deliveryMethod === 'pick up';
    
    console.log(`getOrderStatusUpdateButtons: orderId=${order.order_id}, status=${status}, deliveryMethod=${deliveryMethod}, isPickup=${isPickup}`);
    console.log(`Flow type: ${isPickup ? 'PICKUP (Pending → Pickup → Completed)' : 'DELIVERY (Pending → Processing → Shipped → Delivered → Completed)'}`);
    
    switch (status) {
        case 'pending':
            if (isPickup) {
                return `
                    <button class="btn brand confirm-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Pickup', 'Confirm Order')" title="Confirm Order">
                        ✅ Confirm Order
                    </button>
                    <button class="btn secondary cancel-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Cancelled', 'Cancel Order')" title="Cancel Order">
                        ❌ Cancel Order
                    </button>
                `;
            } else {
                return `
                    <button class="btn brand confirm-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Processing', 'Confirm Order')" title="Confirm Order">
                        ✅ Confirm Order
                    </button>
                    <button class="btn secondary cancel-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Cancelled', 'Cancel Order')" title="Cancel Order">
                        ❌ Cancel Order
                    </button>
                `;
            }
            
        case 'processing':
            // Only show "Mark as Shipped" for delivery orders, not pickup orders
            if (!isPickup) {
                return `
                    <button class="btn brand shipped-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Shipped', 'Mark as Shipped')" title="Mark as Shipped">
                        🚚 Mark as Shipped
                    </button>
                `;
            } else {
                // For pickup orders in processing state, this shouldn't happen, but handle it gracefully
                return `
                    <button class="btn brand complete-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Completed', 'Mark as Completed')" title="Mark as Completed">
                        ✅ Mark as Completed
                    </button>
                `;
            }
            
        case 'pickup':
            return `
                <button class="btn brand complete-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Completed', 'Mark as Completed')" title="Mark as Completed">
                    ✅ Mark as Completed
                </button>
            `;
            
        case 'shipped':
            // Only for delivery orders
            if (!isPickup) {
                return `
                    <button class="btn brand delivered-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Delivered', 'Mark as Delivered')" title="Mark as Delivered">
                        📦 Mark as Delivered
                    </button>
                `;
            } else {
                // This shouldn't happen for pickup orders, but handle gracefully
                return `
                    <button class="btn brand complete-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Completed', 'Mark as Completed')" title="Mark as Completed">
                        ✅ Mark as Completed
                    </button>
                `;
            }
            
        case 'delivered':
            // Only for delivery orders
            if (!isPickup) {
                return `
                    <button class="btn brand complete-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Completed', 'Mark as Completed')" title="Mark as Complete">
                        ✅ Mark as Completed
                    </button>
                `;
            } else {
                // This shouldn't happen for pickup orders, but handle gracefully
                return `<p class="status-message completed-message">✅ This order is completed</p>`;
            }
            
        case 'pickup':
            return `
                <button class="btn brand complete-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Completed', 'Mark as Completed')" title="Mark as Complete">
                    ✅ Mark as Completed
                </button>
            `;
            
        case 'completed':
            return `<p class="status-message completed-message">✅ This order is completed</p>`;
            
        case 'cancelled':
            return `<p class="status-message cancelled-message">❌ This order was cancelled</p>`;
            
        default:
            if (isPickup) {
                return `
                    <button class="btn brand confirm-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Pickup', 'Confirm Order')" title="Confirm Order">
                        ✅ Confirm Order
                    </button>
                    <button class="btn secondary cancel-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Cancelled', 'Cancel Order')" title="Cancel Order">
                        ❌ Cancel Order
                    </button>
                `;
            } else {
                return `
                    <button class="btn brand confirm-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Processing', 'Confirm Order')" title="Confirm Order">
                        ✅ Confirm Order
                    </button>
                    <button class="btn secondary cancel-order-btn" onclick="confirmStatusUpdate(${order.order_id}, 'Cancelled', 'Cancel Order')" title="Cancel Order">
                        ❌ Cancel Order
                    </button>
                `;
            }
    }
}

// Close Order Modal
function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    if (modal) {
        modal.style.display = 'none';
        // Clear any cached data when closing modal
        console.log('Modal closed - clearing cached data');
    }
}

/**
 * Efficiently refreshes the order modal content without closing it
 */
async function refreshOrderModal(orderId) {
    try {
        console.log('🔄 Refreshing modal content for order:', orderId);
        
        // Show loading indicator on status actions
        const statusActions = document.querySelector('.status-actions');
        if (statusActions) {
            statusActions.innerHTML = '<div class="loading-spinner">🔄 Updating...</div>';
        }
        
        // Fetch updated order details using the correct API with cache busting
        const apiUrl = `${APP_CONFIG.API_BASE_URL}/orders_api_v2.php?action=get_order_details&order_id=${orderId}&t=${Date.now()}`;
        console.log('🔄 Refresh API URL being called:', apiUrl);
        
        const response = await fetch(apiUrl, {
            credentials: 'same-origin',
            cache: 'no-cache' // Prevent caching
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('🔄 Modal refresh response:', result);
        
        if (result.success && result.order) {
            // Update the modal content with new data
            updateModalContent(result.order);
            console.log('✅ Modal content refreshed successfully');
        } else {
            throw new Error(result.message || 'Failed to load order details');
        }
    } catch (error) {
        console.error('❌ Error refreshing modal:', error);
        throw error;
    }
}

/**
 * Updates the modal content with new order data
 */
function updateModalContent(order) {
    const modalContent = document.getElementById('orderDetailsContent');
    const modalTitle = document.getElementById('orderModalTitle');
    
    if (!modalContent || !modalTitle) {
        console.error('Modal elements not found');
        return;
    }
    
    console.log('🔄 Updating modal content for order:', order.order_id, 'with status:', order.status);
    
    // Update modal title
    modalTitle.innerHTML = `Order Details - #${order.order_id}`;
    
    // Update order status in the order info section
    const statusElement = modalContent.querySelector('.status');
    if (statusElement) {
        statusElement.className = `status ${order.status?.toLowerCase()}`;
        statusElement.innerHTML = `${getStatusIcon(order.status)} ${escapeHtml(order.status || 'Unknown')}`;
        console.log('✅ Status element updated to:', order.status, 'at:', new Date().toISOString());
    }
    
    // Update status update section with new buttons
    const statusUpdateSection = modalContent.querySelector('.status-update-section');
    if (statusUpdateSection) {
        const statusActions = statusUpdateSection.querySelector('.status-actions');
        if (statusActions) {
            const newButtons = getOrderStatusUpdateButtons(order);
            statusActions.innerHTML = newButtons;
            console.log('✅ Status buttons updated');
        }
    }
    
    console.log('✅ Modal content updated for order:', order.order_id, 'status:', order.status);
}



function getStatusIcon(status) {
    switch (status?.toLowerCase()) {
        case 'pending': return '⏳';
        case 'processing': return '🔄';
        case 'shipped': return '🚚';
        case 'delivered': return '📦';
        case 'pickup': return '📋';
        case 'completed': return '✅';
        case 'cancelled': return '❌';
        default: return '📋';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log('Hiryo Organization Admin Panel - Optimized JavaScript Loaded');

