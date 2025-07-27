<?php
require_once '../config/database.php';
requireSupplier();

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM orders WHERE supplier_id = ? AND status != 'cancelled') as total_orders,
    (SELECT COUNT(*) FROM products WHERE supplier_id = ? AND status = 'active') as active_products,
    (SELECT COUNT(*) FROM emergency_responses er 
     JOIN emergency_requests req ON er.emergency_request_id = req.id 
     WHERE er.supplier_id = ? AND req.status = 'active') as emergency_requests,
    (SELECT AVG(rating) FROM reviews WHERE supplier_id = ?) as avg_rating";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$orders_query = "SELECT o.*, u.full_name as vendor_name, u.phone as vendor_phone 
                FROM orders o 
                JOIN users u ON o.vendor_id = u.id 
                WHERE o.supplier_id = ? 
                ORDER BY o.created_at DESC LIMIT 5";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$_SESSION['user_id']]);
$recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - KitchenKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils"></i> KitchenKart - Supplier
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i> Product Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Order Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="emergency.php">
                                <i class="fas fa-exclamation-triangle"></i> Emergency Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reviews.php">
                                <i class="fas fa-star"></i> Reviews & Ratings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="location.php">
                                <i class="fas fa-map-marker-alt"></i> Location Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_orders'] ?? 0; ?></h4>
                                        <p class="card-text">Total Orders</p>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['active_products'] ?? 0; ?></h4>
                                        <p class="card-text">Active Products</p>
                                    </div>
                                    <i class="fas fa-box fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['emergency_requests'] ?? 0; ?></h4>
                                        <p class="card-text">Emergency Requests</p>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?> <i class="fas fa-star"></i></h4>
                                        <p class="card-text">Average Rating</p>
                                    </div>
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p>No recent orders found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Vendor</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Emergency</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['vendor_name']); ?></td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $order['status'] === 'pending' ? 'warning' : 
                                                            ($order['status'] === 'delivered' ? 'success' : 'primary'); 
                                                    ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($order['is_emergency']): ?>
                                                        <span class="badge bg-danger">Emergency</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Regular</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <a href="orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>