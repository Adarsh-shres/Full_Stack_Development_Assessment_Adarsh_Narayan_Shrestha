<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$suppliers = $conn->query("SELECT id, name FROM suppliers");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $supplier_id = $_POST['supplier_id'];
    $description = $_POST['description'];

    if (empty($name) || empty($sku) || empty($price)) {
        $error = "Name, SKU, and Price are required.";
    } else {
        $sql = "INSERT INTO products (name, sku, price, stock_quantity, supplier_id, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ssdiis", $name, $sku, $price, $stock, $supplier_id, $description);

        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            if ($conn->errno === 1062) {
                $error = "Error: That SKU already exists. Please use a unique SKU.";
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<h2>Add New Product</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?> 
        <a href="dashboard.php">Return to Dashboard</a>
    </div>
<?php endif; ?>

<form action="add_product.php" method="POST">
    
    <div class="form-group">
        <label>Product Name:</label>
        <input type="text" name="name" required>
    </div>

    <div class="form-group">
        <label>SKU (Stock Keeping Unit):</label>
        <input type="text" name="sku" required placeholder="e.g., IPHONE-13-BLK">
    </div>

    <div class="form-row form-group">
        <div class="form-col">
            <label>Price ($):</label>
            <input type="number" step="0.01" name="price" required>
        </div>
        <div class="form-col">
            <label>Stock Quantity:</label>
            <input type="number" name="stock" required value="0">
        </div>
    </div>

    <div class="form-group">
        <label>Supplier:</label>
        <select name="supplier_id">
            <option value="">-- Select Supplier --</option>
            <?php while($row = $suppliers->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>"><?php echo e($row['name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Description:</label>
        <textarea name="description" rows="4"></textarea>
    </div>

    <div class="form-group">
        <button type="submit" class="btn-primary">Save Product</button>
        <a href="dashboard.php" class="btn-secondary">Cancel</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>