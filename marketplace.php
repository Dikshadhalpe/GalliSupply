<?php
require_once '../config/database.php';
requireVendor();

$database = new Database();
$db = $database->getConnection();

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter parameters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$where_conditions = ["p.status = 'active'", "u.status = 'active'"];
$params = [];

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($min_price) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "p.price ASC";
        break;
    case 'price_high':
        $order_clause .= "p.price DESC";
        break;
    case 'rating':
        $order_clause .= "u.rating DESC";
        break;
    default:
        $order_clause .= "p.name ASC";
}

$products_query = "SELECT p.*, c.name as category_name, u.full_name as supplier_name, u.rating as supplier_rating
                  FROM products p
                  JOIN categories c ON p.category_id = c.id
                  JOIN users u ON p.supplier_id = u.id
                  WHERE $where_clause
                  $order_clause";

$products_stmt = $db->prepare($products_query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - KitchenKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils"></i> KitchenKart - Vendor
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="marketplace.php">
                                <i class="fas fa-store"></i> Browse Marketplace
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="price-comparison.php">
                                <i class="fas fa-chart-line"></i> Price Comparison
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="emergency.php">
                                <i class="fas fa-exclamation-triangle"></i> Emergency Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reviews.php">
                                <i class="fas fa-star"></i> Rate Suppliers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="expense-tracker.php">
                                <i class="fas fa-chart-pie"></i> Expense Tracker
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Browse Marketplace</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="fas fa-shopping-cart"></i> Cart (<span id="cart-count">0</span>)
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                            </div>
                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="min_price" class="form-label">Min Price</label>
                                <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="₹0">
                            </div>
                            <div class="col-md-2">
                                <label for="max_price" class="form-label">Max Price</label>
                                <input type="number" class="form-control" id="max_price" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="₹1000">
                            </div>
                            <div class="col-md-2">
                                <label for="sort" class="form-label">Sort By</label>
                                <select class="form-control" id="sort" name="sort">
                                    <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                                    <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Supplier Rating</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <h4>No products found</h4>
                                <p>Try adjusting your search criteria or check back later for new products.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small><br>
                                            <?php echo htmlspecialchars($product['description']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h5 text-primary">₹<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></span>
                                            <span class="badge bg-success"><?php echo $product['quantity_available']; ?> available</span>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Supplier: <?php echo htmlspecialchars($product['supplier_name']); ?></small><br>
                                            <div class="d-flex align-items-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $product['supplier_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-1">(<?php echo number_format($product['supplier_rating'], 1); ?>)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="input-group" style="width: 120px;">
                                                <input type="number" class="form-control form-control-sm" id="qty-<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo $product['quantity_available']; ?>">
                                            </div>
                                            <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo htmlspecialchars($product['unit']); ?>')">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shopping Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cart-items"></div>
                    <div class="text-end mt-3">
                        <h5>Total: ₹<span id="cart-total">0.00</span></h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                    <button type="button" class="btn btn-primary" onclick="checkout()">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];

        function addToCart(productId, productName, price, unit) {
            const quantity = parseInt(document.getElementById('qty-' + productId).value);
            
            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: price,
                    unit: unit,
                    quantity: quantity
                });
            }
            
            updateCartDisplay();
            showAlert('Product added to cart!', 'success');
        }

        function updateCartDisplay() {
            const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = cartCount;
            
            const cartItems = document.getElementById('cart-items');
            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-center">Your cart is empty</p>';
                document.getElementById('cart-total').textContent = '0.00';
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                html += `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <h6 class="mb-0">${item.name}</h6>
                            <small class="text-muted">₹${item.price}/${item.unit}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${index}, -1)">-</button>
                            <span class="mx-2">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${index}, 1)">+</button>
                            <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${index})">×</button>
                        </div>
                        <div class="text-end">
                            <strong>₹${itemTotal.toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            document.getElementById('cart-total').textContent = total.toFixed(2);
        }

        function updateQuantity(index, change) {
            cart[index].quantity += change;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            updateCartDisplay();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function checkout() {
            if (cart.length === 0) {
                showAlert('Your cart is empty!', 'warning');
                return;
            }
            
            // Store cart in session storage and redirect to checkout
            sessionStorage.setItem('cart', JSON.stringify(cart));
            window.location.href = 'checkout.php';
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>