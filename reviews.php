<?php
require_once '../config/database.php';
requireVendor();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle review submission
if ($_POST && isset($_POST['submit_review'])) {
    $order_id = $_POST['order_id'];
    $supplier_id = $_POST['supplier_id'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    
    // Check if review already exists
    $check_query = "SELECT id FROM reviews WHERE order_id = ? AND vendor_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if ($check_stmt->fetch()) {
        $error_message = "You have already reviewed this order.";
    } else {
        $insert_query = "INSERT INTO reviews (order_id, vendor_id, supplier_id, rating, review_text) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$order_id, $_SESSION['user_id'], $supplier_id, $rating, $review_text])) {
            // Update supplier's average rating
            $update_rating_query = "UPDATE users SET 
                                   rating = (SELECT AVG(rating) FROM reviews WHERE supplier_id = ?),
                                   total_reviews = (SELECT COUNT(*) FROM reviews WHERE supplier_id = ?)
                                   WHERE id = ?";
            $update_stmt = $db->prepare($update_rating_query);
            $update_stmt->execute([$supplier_id, $supplier_id, $supplier_id]);
            
            $success_message = "Review submitted successfully!";
        } else {
            $error_message = "Failed to submit review. Please try again.";
        }
    }
}

// Get orders that can be reviewed (delivered orders without reviews)
$reviewable_orders_query = "SELECT o.*, u.full_name as supplier_name
                           FROM orders o
                           JOIN users u ON o.supplier_id = u.id
                           LEFT JOIN reviews r ON o.id = r.order_id AND r.vendor_id = ?
                           WHERE o.vendor_id = ? AND o.status = 'delivered' AND r.id IS NULL
                           ORDER BY o.created_at DESC";
$reviewable_stmt = $db->prepare($reviewable_orders_query);
$reviewable_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$reviewable_orders = $reviewable_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get submitted reviews
$reviews_query = "SELECT r.*, o.id as order_id, o.total_amount, u.full_name as supplier_name
                 FROM reviews r
                 JOIN orders o ON r.order_id = o.id
                 JOIN users u ON r.supplier_id = u.id
                 WHERE r.vendor_id = ?
                 ORDER BY r.created_at DESC";
$reviews_stmt = $db->prepare($reviews_query);
$reviews_stmt->execute([$_SESSION['user_id']]);
$submitted_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews & Ratings - KitchenKart</title>
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
                            <a class="nav-link active" href="reviews.php">
                                <i class="fas fa-star"></i> Rate Suppliers
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-star"></i> Reviews & Ratings</h1