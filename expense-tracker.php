<?php
require_once '../config/database.php';
requireVendor();

$database = new Database();
$db = $database->getConnection();

// Get expense data for charts
$current_month = date('Y-m');
$current_year = date('Y');

// Monthly expenses for current year
$monthly_query = "SELECT 
    MONTH(created_at) as month,
    SUM(total_amount) as total
    FROM orders 
    WHERE vendor_id = ? AND YEAR(created_at) = ? AND status = 'delivered'
    GROUP BY MONTH(created_at)
    ORDER BY month";
$monthly_stmt = $db->prepare($monthly_query);
$monthly_stmt->execute([$_SESSION['user_id'], $current_year]);
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

// Category-wise expenses
$category_query = "SELECT 
    c.name as category,
    SUM(oi.total_price) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.vendor_id = ? AND o.status = 'delivered' AND MONTH(o.created_at) = MONTH(CURDATE())
    GROUP BY c.id
    ORDER BY total DESC";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute([$_SESSION['user_id']]);
$category_data = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent transactions
$transactions_query = "SELECT o.*, u.full_name as supplier_name
    FROM orders o
    JOIN users u ON o.supplier_id = u.id
    WHERE o.vendor_id = ? AND o.status = 'delivered'
    ORDER BY o.created_at DESC
    LIMIT 10";
$transactions_stmt = $db->prepare($transactions_query);
$transactions_stmt->execute([$_SESSION['user_id']]);
$transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$summary_query = "SELECT 
    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN total_amount ELSE 0 END) as this_month,
    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) - 1 THEN total_amount ELSE 0 END) as last_month,
    SUM(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) THEN total_amount ELSE 0 END) as this_week,
    SUM(total_amount) as total_spent
    FROM orders 
    WHERE vendor_id = ? AND status = 'delivered' AND YEAR(created_at) = ?";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->execute([$_SESSION['user_id'], $current_year]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - KitchenKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                            <a class="nav-link" href="reviews.php">
                                <i class="fas fa-star"></i> Rate Suppliers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="location.php">
                                <i class="fas fa-map-marker-alt"></i> Update Location
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="expense-tracker.php">
                                <i class="fas fa-chart-pie"></i> Expense Tracker
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Expense Tracker</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5>This Week</h5>
                                <h3>₹<?php echo number_format($summary['this_week'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5>This Month</h5>
                                <h3>₹<?php echo number_format($summary['this_month'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5>Last Month</h5>
                                <h3>₹<?php echo number_format($summary['last_month'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5>Total Spent</h5>
                                <h3>₹<?php echo number_format($summary['total_spent'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Trend Chart -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Monthly Spending Trend (<?php echo $current_year; ?>)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Category Breakdown (This Month)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Amount</th>
                                        <th>Emergency</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['supplier_name']); ?></td>
                                            <td>₹<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                            <td>
                                                <?php if ($transaction['is_emergency']): ?>
                                                    <span class="badge bg-danger">Emergency</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Regular</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Delivered</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyLabels = [];
        const monthlyValues = [];
        
        for (let i = 1; i <= 12; i++) {
            monthlyLabels.push(monthNames[i-1]);
            const found = monthlyData.find(item => item.month == i);
            monthlyValues.push(found ? parseFloat(found.total) : 0);
        }
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Spending',
                    data: monthlyValues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_data); ?>;
        
        const categoryLabels = categoryData.map(item => item.category);
        const categoryValues = categoryData.map(item => parseFloat(item.total));
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>