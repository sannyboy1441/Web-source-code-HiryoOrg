<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/products.css">

<section class="page" id="products">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>Admin Panel</h1>
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
                    <input type="text" id="productSearch" placeholder="Search products by name, SKU, category, or description..." class="search-input">
                    <button class="search-clear" id="clearSearch" onclick="clearProductSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="productCategoryFilter">Category:</label>
                    <select id="productCategoryFilter" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="Fertilizer">🌱 Fertilizer</option>
                        <option value="Soil">🌍 Soil</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="productStatusFilter">Status:</label>
                    <select id="productStatusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Active">✅ Active</option>
                        <option value="Low Stock">⚠️ Low Stock</option>
                        <option value="Out of Stock">❌ Out of Stock</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="productStockFilter">Stock Level:</label>
                    <select id="productStockFilter" class="filter-select">
                        <option value="">All Stock Levels</option>
                        <option value="high">📈 High Stock (50+)</option>
                        <option value="medium">📊 Medium Stock (10-49)</option>
                        <option value="low">📉 Low Stock (1-9)</option>
                        <option value="out">❌ Out of Stock (0)</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearProductFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                    <button class="btn brand" onclick="showAddProductModal()">
                        <span class="btn-icon">➕</span>
                        Add Product
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="productsCount">Loading products...</span>
                <span id="filterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="content-panel">
        <div class="table-container">
            <div class="table-responsive">
                <table class="data-table products-table">
                    <thead>
                        <tr>
                            <th class="image-column">
                                <span class="column-header">
                                    <span class="column-icon">🖼️</span>
                                    Image
                                </span>
                            </th>
                            <th class="name-column">
                                <span class="column-header sortable" onclick="sortProducts('product_name')">
                                    <span class="column-icon">📦</span>
                                    Product Name
                                    <span class="sort-indicator" id="sort-product_name">↕️</span>
                                </span>
                            </th>
                            <th class="sku-column">
                                <span class="column-header sortable" onclick="sortProducts('product_sku')">
                                    <span class="column-icon">🏷️</span>
                                    SKU
                                    <span class="sort-indicator" id="sort-product_sku">↕️</span>
                                </span>
                            </th>
                            <th class="category-column">
                                <span class="column-header sortable" onclick="sortProducts('category')">
                                    <span class="column-icon">🏷️</span>
                                    Category
                                    <span class="sort-indicator" id="sort-category">↕️</span>
                                </span>
                            </th>
                            <th class="price-column">
                                <span class="column-header sortable" onclick="sortProducts('price')">
                                    <span class="column-icon">💰</span>
                                    Price
                                    <span class="sort-indicator" id="sort-price">↕️</span>
                                </span>
                            </th>
                            <th class="stock-column">
                                <span class="column-header sortable" onclick="sortProducts('stock_quantity')">
                                    <span class="column-icon">📊</span>
                                    Stock
                                    <span class="sort-indicator" id="sort-stock_quantity">↕️</span>
                                </span>
                            </th>
                            <th class="status-column">
                                <span class="column-header sortable" onclick="sortProducts('status')">
                                    <span class="column-icon">📈</span>
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
                    <tbody id="productRows">
                        <tr>
                            <td colspan="7" class="loading-row">
                                <div class="loading-spinner">
                                    <span class="spinner">⏳</span>
                                    Loading products...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">📦</div>
                <h3>No Products Found</h3>
                <p>No products match your current filters. Try adjusting your search criteria or add a new product.</p>
                <button class="btn brand" onclick="showAddProductModal()">
                    <span class="btn-icon">➕</span>
                    Add Your First Product
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Add/Edit Product Modal -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Product</h2>
            <span class="close" onclick="closeProductModal()">&times;</span>
        </div>
        
        <form id="productForm" enctype="multipart/form-data">
            <input type="hidden" id="productId" name="product_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="product_name" required 
                           placeholder="Enter product name">
                </div>
                
                <div class="form-group">
                    <label for="productCategory">Category *</label>
                    <select id="productCategory" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Fertilizer">🌱 Fertilizer</option>
                        <option value="Soil">🌍 Soil</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="productPrice">Price (₱) *</label>
                    <input type="number" id="productPrice" name="price" step="0.01" min="0" required 
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="productStock">Stock Quantity *</label>
                    <input type="number" id="productStock" name="stock_quantity" min="0" required 
                           placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="productSku">Product SKU</label>
                    <input type="text" id="productSku" name="product_sku" 
                           placeholder="Enter product SKU (optional)">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="weightValue">Weight Value</label>
                    <input type="number" id="weightValue" name="weight_value" step="0.01" min="0" 
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="weightUnit">Weight Unit</label>
                    <select id="weightUnit" name="weight_unit">
                        <option value="">Select Unit</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="g">Gram (g)</option>
                        <option value="lb">Pound (lb)</option>
                        <option value="oz">Ounce (oz)</option>
                        <option value="L">Liter (L)</option>
                        <option value="mL">Milliliter (mL)</option>
                        <option value="piece">Piece</option>
                        <option value="pack">Pack</option>
                        <option value="bag">Bag</option>
                        <option value="box">Box</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="productDescription">Description</label>
                <textarea id="productDescription" name="description" rows="4" 
                          placeholder="Enter product description..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="productImage">Product Image</label>
                <div class="file-upload-container">
                    <input type="file" id="productImage" name="product_image" accept="image/*" 
                           onchange="previewImage(this)">
                    <div class="file-upload-display">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <span class="upload-icon">📷</span>
                            <span class="upload-text">Click to upload image or drag and drop</span>
                            <span class="upload-subtext">PNG, JPG, JPEG up to 5MB</span>
                        </div>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage()">✕</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeProductModal()">
                    Cancel
                </button>
                <button type="submit" class="btn brand" id="submitBtn">
                    <span class="btn-icon">💾</span>
                    Save Product
                </button>
            </div>
        </form>
    </div>
</div>
