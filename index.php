<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    if (getUserType() === 'supplier') {
        header('Location: supplier/dashboard.php');
    } else {
        header('Location: vendor/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KitchenKart - Emergency Raw Materials Supply</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils"></i> KitchenKart
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">Login</a>
                <a class="nav-link" href="register.php">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Emergency Raw Materials for Street Food Vendors</h1>
            <p class="lead mb-4">Connect suppliers and vendors for instant raw material delivery</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <a href="register.php?type=vendor" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-store"></i> Join as Vendor
                    </a>
                    <a href="register.php?type=supplier" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-truck"></i> Join as Supplier
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Key Features</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
                            <h5>Emergency Orders</h5>
                            <p>Get urgent supplies delivered within 15-30 minutes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                            <h5>Location Based</h5>
                            <p>Find suppliers near your location for faster delivery</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-star fa-3x text-success mb-3"></i>
                            <h5>Rating System</h5>
                            <p>Rate and review suppliers for quality assurance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
