<?php
require_once '../config/database.php';
requireVendor();

$database = new Database();
$db = $database->getConnection();

$selected_product = $_GET['product'] ?? '';
$comparisons = [];

if ($selected_product) {
    $comparison_query = "SELECT p.*, u.full_name as supplier_name, u.rating as supplier_rating, u.phone as supplier_phone
                        FROM products p
                        JOIN users u ON p.supplier_id = u.id
                        WHERE p.name LIKE ? AND p.status = 'active' AND u.status = 'active'
                        ORDER BY p.price ASC";
    $comparison_stmt = $db->prepare($comparison_query);
    $comparison_stmt->execute(["%$selected_product%"]);
    $comparisons = $comparison_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get unique product names for dropdown
$products_query = "SELECT DISTINCT name FROM products WHERE status = 'active' ORDER BY name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$product_names = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Comparison - KitchenKart</title>
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
                            <a class="nav-link" href="marketplace.php">
                                <i class="fas fa-store"></i> Browse Marketplace
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="price-comparison.php">
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
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Price Comparison</h1>
                </div>

                <!-- Product Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="product" class="form-label">Select Product to Compare</label>
                                    <select class="form-control" id="product" name="product" required>
                                        <option value="">Choose a product...</option>
                                        <?php foreach ($product_names as $product): ?>
                                            <option value="<?php echo $product['name']; ?>" <?php echo $selected_product === $product['name'] ? 'selected' : ''; ?>>
                                                <?php echo $product['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">Compare Prices</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Comparison Results -->
                <?php if ($selected_product && !empty($comparisons)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Price Comparison for "<?php echo htmlspecialchars($selected_product); ?>"</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Price</th>
                                            <th>Unit</th>
                                            <th>Available Quantity</th>
                                            <th>Rating</th>
                                            <th>Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comparisons as $index => $item): ?>
                                            <tr class="<?php echo $index === 0 ? 'table-success' : ''; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['supplier_name']); ?></strong>
                                                    <?php if ($index === 0): ?>
                                                        <span class="badge bg-success ms-2">Best Price</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <h5 class="text-primary">₹<?php echo number_format($item['price'], 2); ?></h5>
                                                </td>
                                                <td><?php echo $item['unit']; ?></td>
                                                <td><?php echo $item['quantity_available']; ?> <?php echo $item['unit']; ?></td>
                                                <td>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $item['supplier_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <small>(<?php echo number_format($item['supplier_rating'], 1); ?>)</small>
                                                </td>
                                                <td>
                                                    <a href="tel:<?php echo $item['supplier_phone']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-phone"></i> Call
                                                    </a>
                                                </td>
                                                <td>
                                                    <form method="POST" action="marketplace.php" style="display: inline;">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $item['min_order_quantity']; ?>">
                                                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-success">
                                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selected_product && empty($comparisons)): ?>
                    <div class="alert alert-warning">
                        <h4>No suppliers found</h4>
                        <p>No suppliers are currently offering "<?php echo htmlspecialchars($selected_product); ?>". Try searching for a different product.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>