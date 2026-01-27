<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand">InventorySys</div>
    <div class="nav-links">
        <a href="index.php">Home</a>

        <?php
        if (isset($_SESSION['user_id'])) {
            
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="dashboard.php">Admin Dashboard</a>';
                echo '<a href="add_product.php">Add Product</a>';
            } else {
                echo '<a href="shop.php">Shop</a>';
                echo '<a href="orders.php">My Orders</a>';
            }
            
            echo '<a href="logout.php" class="logout-btn">Logout (' . htmlspecialchars($_SESSION['username']) . ')</a>';
            
        } else {
            echo '<a href="login.php">Login</a>';
            echo '<a href="register.php">Register</a>';
        }
        ?>
        
        <button id="theme-toggle">THE MOON HAUNTS YOU</button>
    </div>
</nav>

<div class="container">