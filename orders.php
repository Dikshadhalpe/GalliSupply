<?php
require_once '../config/database.php';
requireSupplier();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle order status updates
if ($_POST && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['accepted', 'processing', 'delivered', 'rejected'];
    if (in_array($new_status, $allowed_statuses)) {
        $query = "UPDATE orders SET status = ? WHERE id = ? AND supplier_id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$new_status, $order_id, $_SESSION['user_id']])) {
            $message = 'Order status updated successfully!';
        } else {
            $error = 'Failed to update order status.';
        }
    }
}

// Get orders with filters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';

$where_conditions = ['o.supplier_id = ?'];
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($date_filter === 'today') {
    $where_conditions[] = 'DATE(o.created_at) = CURDATE()';
} elseif ($date_filter === 'week') {
    $where_conditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($date_filter === 'month') {
    $where_conditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

$orders_query = "SELECT o.*, u.full_name as vendor_name, u.phone as vendor_phone, u.address as vendor_address
    FROM orders o
    JOIN users u ON o.vendor_id = u.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY o.is_emergency DESC, o.created_at DESC";

$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order items for each order
foreach ($orders as &$order) {
    $items_query = "SELECT oi.*, p.name as product_name, p.unit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - KitchenKart</title>
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i> Product Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="orders.php">
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
                    <h1 class="h2">Order Management</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label">Date Range</label>
                                <select class="form-control" id="date" name="date">
                                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary