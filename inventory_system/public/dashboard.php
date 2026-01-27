<?php
// public/dashboard.php
require_once '../config/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$sql = "SELECT p.*, s.name AS supplier_name 
        FROM products p 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        ORDER BY p.id DESC";
$result = $conn->query($sql);
?>

<h2>Admin Dashboard: Inventory Management</h2>

<div class="mb-20">
    <a href="add_product.php" class="btn-primary"> + Add New Product</a>
</div>

<table>
    <thead>
        <tr>
            <th>SKU</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Supplier</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo e($row['sku']); ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                    
                    <td class="<?php echo ($row['stock_quantity'] < 5) ? 'low-stock' : ''; ?>">
                        <?php echo e($row['stock_quantity']); ?>
                    </td>
                    
                    <td><?php echo e($row['supplier_name'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit</a>
                        
                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" 
                           class="btn-delete"
                           onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No products found. Click "Add New Product" to start.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>