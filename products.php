<?php
require_once '../config/database.php';
requireSupplier();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $category_id = $_POST['category_id'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $unit = $_POST['unit'];
                $quantity = $_POST['quantity_available'];
                $min_order = $_POST['min_order_quantity'];
                
                $query = "INSERT INTO products (supplier_id, category_id, name, description, price, unit, quantity_available, min_order_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_SESSION['user_id'], $category_id, $name, $description, $price, $unit, $quantity, $min_order])) {
                    $message = 'Product added successfully!';
                } else {
                    $error = 'Failed to add product.';
                }
                break;
                
            case 'update':
                $product_id = $_POST['product_id'];
                $name = $_POST['name'];
                $category_id = $_POST['category_id'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $unit = $_POST['unit'];
                $quantity = $_POST['quantity_available'];
                $min_order = $_POST['min_order_quantity'];
                $status = $_POST['status'];
                
                $query = "UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, unit = ?, quantity_available = ?, min_order_quantity = ?, status = ? WHERE id = ? AND supplier_id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$category_id, $name, $description, $price, $unit, $quantity, $min_order, $status, $product_id, $_SESSION['user_id']])) {
                    $message = 'Product updated successfully!';
                } else {
                    $error = 'Failed to update product.';
                }
                break;
                
            case 'delete':
                $product_id = $_POST['product_id'];
                $query = "DELETE FROM products WHERE id = ? AND supplier_id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$product_id, $_SESSION['user_id']])) {
                    $message = 'Product deleted successfully!';
                } else {
                    $error = 'Failed to delete product.';
                }
                break;
        }
    }
}

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products
$products_query = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.supplier_id = ? ORDER BY p.created_at DESC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$_SESSION['user_id']]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - KitchenKart</title>
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
                            <a class="nav-link active" href="products.php">
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
                    <h1 class="h2">Product Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
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

                <!-- Products Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>Your Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Unit</th>
                                        <th>Available Qty</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                            <td><?php echo $product['quantity_available']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="unit" name="unit" placeholder="kg, liter, piece" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity_available" class="form-label">Available Quantity</label>
                                    <input type="number" class="form-control" id="quantity_available" name="quantity_available" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_order_quantity" class="form-label">Min Order Quantity</label>
                                    <input type="number" class="form-control" id="min_order_quantity" name="min_order_quantity" value="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProductForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_id" class="form-label">Category</label>
                            <select class="form-control" id="edit_category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_unit" class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="edit_unit" name="unit" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_quantity_available" class="form-label">Available Quantity</label>
                                    <input type="number" class="form-control" id="edit_quantity_available" name="quantity_available" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_min_order_quantity" class="form-label">Min Order Quantity</label>
                                    <input type="number" class="form-control" id="edit_min_order_quantity" name="min_order_quantity" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_unit').value = product.unit;
            document.getElementById('edit_quantity_available').value = product.quantity_available;
            document.getElementById('edit_min_order_quantity').value = product.min_order_quantity;
            document.getElementById('edit_status').value = product.status;
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>