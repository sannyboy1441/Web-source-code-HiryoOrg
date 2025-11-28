<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/transactions.css">

<section class="page" id="transactions">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>💳 Transactions</h1>
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
                    <input type="text" id="transactionSearch" placeholder="Search transactions by Order Number, customer name, or amount..." class="search-input">
                    <button class="search-clear" id="clearTransactionSearch" onclick="clearTransactionSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="transactionStatusFilter">Status:</label>
                    <select id="transactionStatusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Completed">✅ Completed</option>
                        <option value="Pending">⏳ Pending</option>
                        <option value="Failed">❌ Failed</option>
                        <option value="Cancelled">🚫 Cancelled</option>
                    </select>
                </div>


                <div class="filter-group">
                    <label for="transactionDateFilter">Date Range:</label>
                    <select id="transactionDateFilter" class="filter-select">
                        <option value="">All Time</option>
                        <option value="today">📅 Today</option>
                        <option value="week">📅 This Week</option>
                        <option value="month">📅 This Month</option>
                        <option value="quarter">📅 This Quarter</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearTransactionFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="transactionsCount">Loading transactions...</span>
                <span id="transactionFilterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="content-panel">
        <div class="table-container">
            <div class="table-responsive">
                <table class="data-table transactions-table">
                    <thead>
                        <tr>
                            <th class="order-number-column">
                                <span class="column-header sortable" onclick="sortTransactions('order_id')">
                                    <span class="column-icon">🆔</span>
                                    Order Number
                                    <span class="sort-indicator" id="sort-order_id">↕️</span>
                                </span>
                            </th>
                            <th class="customer-column">
                                <span class="column-header sortable" onclick="sortTransactions('user_name')">
                                    <span class="column-icon">👤</span>
                                    Customer
                                    <span class="sort-indicator" id="sort-user_name">↕️</span>
                                </span>
                            </th>
                            <th class="amount-column">
                                <span class="column-header sortable" onclick="sortTransactions('amount')">
                                    <span class="column-icon">💰</span>
                                    Amount
                                    <span class="sort-indicator" id="sort-amount">↕️</span>
                                </span>
                            </th>
                            <th class="date-column">
                                <span class="column-header sortable" onclick="sortTransactions('created')">
                                    <span class="column-icon">📅</span>
                                    Date
                                    <span class="sort-indicator" id="sort-created">↕️</span>
                                </span>
                            </th>
                            <th class="status-column">
                                <span class="column-header sortable" onclick="sortTransactions('status')">
                                    <span class="column-icon">📊</span>
                                    Status
                                    <span class="sort-indicator" id="sort-status">↕️</span>
                                </span>
                            </th>
                            <th class="actions-column">
                                <span class="column-header">
                                    <span class="column-icon">⚡</span>
                                    Actions
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="transactionRows">
                        <tr>
                            <td colspan="6" class="loading-row">
                                <div class="loading-spinner">
                                    <span class="spinner">⏳</span>
                                    Loading transactions...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyTransactionState" class="empty-state" style="display: none;">
                <div class="empty-icon">💳</div>
                <h3>No Transactions Found</h3>
                <p>No transactions match your current filters. Try adjusting your search criteria.</p>
            </div>
        </div>
    </div>
</section>

<!-- Completed Order Details Modal -->
<div id="completedOrderModal" class="modal" style="display: none;">
    <div class="modal-content order-modal-content">
        <div class="modal-header">
            <h2 id="completedOrderModalTitle">Completed Order Details</h2>
            <span class="close" onclick="closeCompletedOrderModal()">&times;</span>
        </div>
        
        <div id="completedOrderDetailsContent" class="order-details-content">
            <!-- Completed order details will be loaded here -->
        </div>
    </div>
</div>