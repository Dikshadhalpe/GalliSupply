<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle location update
if ($_POST && isset($_POST['update_location'])) {
    $user_id = $_SESSION['user_id'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $delivery_radius = $_POST['delivery_radius'];
    
    $query = "UPDATE users SET address = :address, city = :city, state = :state, 
              pincode = :pincode, latitude = :latitude, longitude = :longitude, 
              delivery_radius = :delivery_radius WHERE id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':delivery_radius', $delivery_radius);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Location updated successfully!";
    } else {
        $error_message = "Error updating location.";
    }
}

// Get current location data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Settings - KitchenKart Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .nav-link {
            color: white !important;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .map-container {
            height: 300px;
            background-color: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h4 class="text-white mb-4"><i class="fas fa-store"></i> Supplier Panel</h4>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i> Products
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="emergency.php">
                            <i class="fas fa-exclamation-triangle me-2"></i> Emergency Requests
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="location.php">
                            <i class="fas fa-map-marker-alt me-2"></i> Location Settings
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-map-marker-alt text-primary"></i> Location Settings</h2>
                    <span class="badge bg-success">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-edit"></i> Update Location Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="pincode" class="form-label">Pincode</label>
                                            <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo htmlspecialchars($user_data['pincode'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_radius" class="form-label">Delivery Radius (km)</label>
                                            <input type="number" class="form-control" id="delivery_radius" name="delivery_radius" value="<?php echo htmlspecialchars($user_data['delivery_radius'] ?? '10'); ?>" min="1" max="50" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="latitude" class="form-label">Latitude (Optional)</label>
                                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($user_data['latitude'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="longitude" class="form-label">Longitude (Optional)</label>
                                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($user_data['longitude'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="update_location" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Location
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="getCurrentLocation()">
                                            <i class="fas fa-crosshairs"></i> Get Current Location
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-map"></i> Location Preview</h5>
                            </div>
                            <div class="card-body">
                                <div class="map-container">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                                        <p>Map integration can be added here<br>(Google Maps, OpenStreetMap, etc.)</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h6>Current Location:</h6>
                                    <p class="text-muted small">
                                        <?php if ($user_data['address']): ?>
                                            <?php echo htmlspecialchars($user_data['address'] . ', ' . $user_data['city'] . ', ' . $user_data['state'] . ' - ' . $user_data['pincode']); ?>
                                        <?php else: ?>
                                            No location set
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-muted small">
                                        <strong>Delivery Radius:</strong> <?php echo htmlspecialchars($user_data['delivery_radius'] ?? '10'); ?> km
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Location Tips</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success"></i> Accurate location helps vendors find you easily</li>
                                    <li><i class="fas fa-check text-success"></i> Set appropriate delivery radius</li>
                                    <li><i class="fas fa-check text-success"></i> Update location if you move</li>
                                    <li><i class="fas fa-check text-success"></i> GPS coordinates improve accuracy</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    alert('Location coordinates updated! Please fill in the address details and save.');
                }, function(error) {
                    alert('Error getting location: ' + error.message);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
    </script>
</body>
</html>