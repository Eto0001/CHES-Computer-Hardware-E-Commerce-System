<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header("Location: index.php");
    exit;
}

// Clear existing cart (optional but recommended for instant buy)
$_SESSION['cart'] = [];

// Add product with quantity = 1
$_SESSION['cart'][$product_id] = 1;

// Redirect to checkout
header("Location: checkout.php");
exit;
