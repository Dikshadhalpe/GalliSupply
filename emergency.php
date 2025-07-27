<?php
require_once '../config/database.php';
requireSupplier();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle emergency response
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'respond') {
    $emergency_id = $_POST['emergency_id'];
    $available_quantity = $_POST['available_quantity'];
    $price_per_unit = $_POST['price_per_unit'];
    $delivery_time = $_POST['delivery_time'];
    $message_text = $_POST['message'];
    
    // Check if already responded
    $check_query = "SELECT id FROM emergency_responses WHERE emergency_request_id = ? AND supplier_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$emergency_id, $_SESSION['user_id']]);
    
    if ($check_stmt->fetch()) {
        $error = 'You have already responded to this emergency request.';
    } else {
        $query = "INSERT INTO emergency_responses (emergency_request_id, supplier_id, available_quantity, price_per_unit, estimated_delivery_time, message) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$emergency_id, $_SESSION['user_id'], $available_quantity, $price_per_unit, $delivery_time, $message_text])) {
            $message = 'Emergency response sent successfully!';
        } else {
            $error = 'Failed to send response.';
        }
    }
}

// Get current location of supplier
$location_query = "SELECT latitude, longitude FROM users WHERE id = ?";
$location_stmt = $db->prepare($location_query);
$location_stmt->execute([$_SESSION['user_id']]);
$supplier_location = $location_stmt->fetch(PDO::FETCH_ASSOC);

// Get emergency requests (within 10km radius if location is available)
$emergency_query = "SELECT er.*, u.full_name as vendor_name, u.phone as vendor_phone,
    (CASE WHEN er.latitude IS NOT NULL AND er.longitude IS NOT NULL AND ? IS NOT NULL AND ? IS NOT NULL
     THEN (6371 * acos(cos(radians(?)) * cos(radians(er.latitude)) * cos(radians(er.longitude) - radians(?)) + sin(radians(?)) * sin(radians(er.latitude))))
     ELSE 0 END) as distance
    FROM emergency_requests er
    JOIN users u ON er.vendor_id = u.id
    WHERE er.status = 'active'
    HAVING distance <= 10 OR distance = 0
    ORDER BY er.urgency_level = 'high' DESC, er.created_at DESC";

$emergency_stmt = $db->prepare($emergency_query);
$emergency_stmt->execute([
    $supplier_location['latitude'],
    $supplier_location['longitude'],
    $supplier_location['latitude'],
    $supplier_location['longitude'],
    $supplier_location['latitude']
]);
$emergency_requests = $emergency_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my responses
$responses_query = "SELECT er.*, u.full_name as vendor_name, resp.available_quantity, resp.price_per_unit, resp.estimated_delivery_time, resp.message, resp.status as response_status, resp.created_at as response_date
    FROM emergency_responses resp
    JOIN emergency_requests er ON resp.emergency_request_id = er.id
    JOIN users u ON er.vendor_id = u.id
    WHERE resp.supplier_id = ?
    ORDER BY resp.created_at DESC";
$responses_stmt = $db->prepare($responses_query);
$responses_stmt->execute([$_SESSION['user_id']]);
$my_responses = $responses_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Requests - KitchenKart</title>
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
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Order Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="emergency.php">
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
                    <h1 class="h2">Emergency Requests</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="badge bg-danger me-2"><?php echo count($emergency_requests); ?> Active Requests</span>
                    </div>
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

                <!-- Emergency Requests -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Active Emergency Requests</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($emergency_requests)): ?>
                                    <p>No emergency requests in your area.</p>
                                <?php else: ?>
                                    <?php foreach ($emergency_requests as $request): ?>
                                        <div class="card mb-3 border-<?php echo $request['urgency_level'] === 'high' ? 'danger' : ($request['urgency_level'] === 'medium' ? 'warning' : 'info'); ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title">
                                                            <?php echo htmlspecialchars($request['product_name']); ?>
                                                            <span class="badge bg-<?php echo $request['urgency_level'] === 'high' ? 'danger' : ($request['urgency_level'] === 'medium' ? 'warning' : 'info'); ?>">
                                                                <?php echo ucfirst($request['urgency_level']); ?> Priority
                                                            </span>
                                                        </h6>
                                                        <p class="card-text">
                                                            <strong>Vendor:</strong> <?php echo htmlspecialchars($request['vendor_name']); ?><br>
                                                            <strong>Quantity Needed:</strong> <?php echo $request['quantity_needed']; ?><br>
                                                            <strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?><br>
                                                            <strong>Distance:</strong> <?php echo number_format($request['distance'], 1); ?> km<br>
                                                            <strong>Posted:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                    <button class="btn btn-primary btn-sm" onclick="respondToEmergency(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-reply"></i> Respond
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- My Responses -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>My Responses</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($my_responses)): ?>
                                    <p>No responses yet.</p>
                                <?php else: ?>
                                    <?php foreach ($my_responses as $response): ?>
                                        <div class="card mb-2">
                                            <div class="card-body p-2">
                                                <h6 class="card-title"><?php echo htmlspecialchars($response['product_name']); ?></h6>
                                                <small class="text-muted">
                                                    Vendor: <?php echo htmlspecialchars($response['vendor_name']); ?><br>
                                                    Qty: <?php echo $response['available_quantity']; ?> @ ₹<?php echo $response['price_per_unit']; ?><br>
                                                    Delivery: <?php echo $response['estimated_delivery_time']; ?> min<br>
                                                    Status: <span class="badge bg-<?php echo $response['response_status'] === 'accepted' ? 'success' : ($response['response_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($response['response_status']); ?>
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Emergency Response Modal -->
    <div class="modal fade" id="emergencyResponseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Respond to Emergency Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="respond">
                    <input type="hidden" name="emergency_id" id="emergency_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="available_quantity" class="form-label">Available Quantity</label>
                            <input type="number" class="form-control" id="available_quantity" name="available_quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="price_per_unit" class="form-label">Price per Unit (₹)</label>
                            <input type="number" step="0.01" class="form-control" id="price_per_unit" name="price_per_unit" required>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_time" class="form-label">Estimated Delivery Time (minutes)</label>
                            <input type="number" class="form-control" id="delivery_time" name="delivery_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message (Optional)</label>
                            <textarea class="form-control" id="message" name="message" rows="3" placeholder="Additional information for the vendor"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function respondToEmergency(emergencyId) {
            document.getElementById('emergency_id').value = emergencyId;
            new bootstrap.Modal(document.getElementById('emergencyResponseModal')).show();
        }
        
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>