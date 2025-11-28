<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/orders.css">

<section class="page" id="orders">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>📋 Orders</h1>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="filter-section">
        <div class="filter-container">
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="orderSearch" placeholder="Search orders by Order Number, customer name, or email..." class="search-input">
                    <button class="search-clear" id="clearOrderSearch" onclick="clearOrderSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="orderStatusFilter">Status:</label>
                    <select id="orderStatusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Pending">⏳ Pending</option>
                        <option value="Confirmed">✅ Confirmed</option>
                        <option value="Processing">🔄 Processing</option>
                        <option value="Shipped">🚚 Shipped</option>
                        <option value="Delivered">📦 Delivered</option>
                        <option value="Cancelled">❌ Cancelled</option>
                    </select>
                </div>


                <div class="filter-group">
                    <label for="orderDateFilter">Date Range:</label>
                    <select id="orderDateFilter" class="filter-select">
                        <option value="">All Time</option>
                        <option value="today">📅 Today</option>
                        <option value="week">📅 This Week</option>
                        <option value="month">📅 This Month</option>
                        <option value="quarter">📅 This Quarter</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearOrderFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="ordersCount">Loading orders...</span>
                <span id="orderFilterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="content-panel">
        <div class="table-container">
            <div class="table-responsive">
                <table class="data-table orders-table">
                    <thead>
                        <tr>
                            <th class="order-id-column">
                                <span class="column-header sortable" onclick="sortOrders('order_id')">
                                    <span class="column-icon">🆔</span>
                                    Order Number
                                    <span class="sort-indicator" id="sort-order_id">↕️</span>
                                </span>
                            </th>
                            <th class="customer-column">
                                <span class="column-header sortable" onclick="sortOrders('customer_name')">
                                    <span class="column-icon">👤</span>
                                    Customer
                                    <span class="sort-indicator" id="sort-customer_name">↕️</span>
                                </span>
                            </th>
                            <th class="date-column">
                                <span class="column-header sortable" onclick="sortOrders('order_date')">
                                    <span class="column-icon">📅</span>
                                    Date
                                    <span class="sort-indicator" id="sort-order_date">↕️</span>
                                </span>
                            </th>
                            <th class="total-column">
                                <span class="column-header sortable" onclick="sortOrders('total_amount')">
                                    <span class="column-icon">💰</span>
                                    Total
                                    <span class="sort-indicator" id="sort-total_amount">↕️</span>
                                </span>
                            </th>
                            <th class="payment-column">
                                <span class="column-header sortable" onclick="sortOrders('payment_method')">
                                    <span class="column-icon">💳</span>
                                    Payment
                                    <span class="sort-indicator" id="sort-payment_method">↕️</span>
                                </span>
                            </th>
                            <th class="delivery-column">
                                <span class="column-header sortable" onclick="sortOrders('delivery_method')">
                                    <span class="column-icon">🚚</span>
                                    Delivery Method
                                    <span class="sort-indicator" id="sort-delivery_method">↕️</span>
                                </span>
                            </th>
                            <th class="status-column">
                                <span class="column-header sortable" onclick="sortOrders('status')">
                                    <span class="column-icon">📊</span>
                                    Status
                                    <span class="sort-indicator" id="sort-status">↕️</span>
                                </span>
                            </th>
                            <th class="actions-column">
                                <span class="column-header">
                                    <span class="column-icon">⚙️</span>
                                    Actions
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="orderRows">
                        <tr>
                            <td colspan="8" class="loading-row">
                                <div class="loading-spinner">
                                    <span class="spinner">⏳</span>
                                    Loading orders...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyOrderState" class="empty-state" style="display: none;">
                <div class="empty-icon">📋</div>
                <h3>No Orders Found</h3>
                <p>No orders match your current filters. Try adjusting your search criteria.</p>
            </div>
        </div>
    </div>
</section>

<!-- Order Details Modal -->
<div id="orderModal" class="modal" style="display: none;">
    <div class="modal-content order-modal-content">
        <div class="modal-header">
            <h2 id="orderModalTitle">Order Details</h2>
            <span class="close" onclick="closeOrderModal()">&times;</span>
        </div>
        
        <div id="orderDetailsContent" class="order-details-content">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<!-- Order Action Confirmation Modal -->
<div id="orderActionModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2 id="modalTitle">Confirm Action</h2>
            <span class="close" onclick="closeOrderActionModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <p id="modalMessage">Are you sure you want to perform this action?</p>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeOrderActionModal()">Cancel</button>
            <button type="button" id="modalConfirmBtn" class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>