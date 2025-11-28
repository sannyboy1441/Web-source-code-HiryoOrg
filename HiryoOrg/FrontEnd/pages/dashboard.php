<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<link rel="stylesheet" href="../styles/dashboard.css">

<section class="content" id="dashboard">
    <!-- Enhanced Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-title">
                <h1>📊 Dashboard</h1>
                <p>Welcome back! Here's what's happening with your Hiryo Organics business.</p>
            </div>
            <div class="header-actions">
                <button onclick="refreshDashboard()" class="btn btn-primary">
                    <span class="icon">🔄</span> Refresh Data
                </button>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Dashboard KPI Cards -->
    <div class="dashboard-cards">
        <div class="kpi-card users-card">
            <div class="card-icon">
                <span class="icon">👥</span>
            </div>
            <div class="card-content">
                <h3>Total Users</h3>
                <div class="metric">
                    <strong id="kpiUsers">0</strong>
                    <span class="metric-label">Registered Users</span>
                </div>
                <div class="card-footer">
                    <span class="trend-indicator">📈 Active users growing</span>
                </div>
            </div>
        </div>
        
        <div class="kpi-card orders-card">
            <div class="card-icon">
                <span class="icon">🛒</span>
            </div>
            <div class="card-content">
                <h3>Total Orders</h3>
                <div class="metric">
                    <strong id="kpiOrders">0</strong>
                    <span class="metric-label">This Month</span>
                </div>
                <div class="card-footer">
                    <span class="trend-indicator">📊 Order tracking</span>
                </div>
            </div>
        </div>
        
        <div class="kpi-card revenue-card">
            <div class="card-icon">
                <span class="icon">💰</span>
            </div>
            <div class="card-content">
                <h3>Total Revenue</h3>
                <div class="metric">
                    <strong id="kpiProducts">₱0</strong>
                    <span class="metric-label">This Month</span>
                </div>
                <div class="card-footer">
                    <span class="trend-indicator">💹 Revenue growth</span>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Enhanced Sales Chart Section -->
    <div class="chart-section">
        <div class="section-header">
            <div class="header-content">
                <h2>📈 Sales Overview</h2>
                <p>Monthly sales performance and growth trends</p>
            </div>
            <div class="chart-controls">
                <div class="time-filter">
                    <label for="chartPeriod">Period:</label>
                    <select id="chartPeriod" onchange="updateChartPeriod()">
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="thismonth" selected>This Month</option>
                        <option value="thisquarter">This Quarter</option>
                        <option value="ytd">Year to Date (YTD)</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="chart-actions">
                    <button onclick="downloadChartData()" class="btn btn-secondary btn-sm" title="Download chart data as CSV">
                        <span class="icon">📥</span> Export CSV
                    </button>
                    <button onclick="downloadChartImage()" class="btn btn-secondary btn-sm" title="Download chart as image">
                        <span class="icon">📸</span> Save Image
                    </button>
                </div>
                <span class="chart-badge">📊 Interactive Chart</span>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="salesChart" 
                    style="width:100%; max-height:400px; margin-top:15px;">
            </canvas>
        </div>
    </div>
    
</section>
