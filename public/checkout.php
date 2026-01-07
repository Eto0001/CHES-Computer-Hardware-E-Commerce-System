<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();

if (!isLoggedIn()) {
    $_SESSION['after_login_redirect'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

/* ---------- CALCULATE TOTAL ---------- */
$total = 0.0;
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare(
    "SELECT id, price FROM products WHERE id IN ($placeholders)"
);
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach ($products as $p) {
    $qty = $_SESSION['cart'][$p['id']];
    $total += $p['price'] * $qty;
}

/* ---------- PLACE ORDER (POST ONLY) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $address = sanitize($_POST['address'] ?? '');
    $method  = sanitize($_POST['payment_method'] ?? 'COD');

    if ($address === '') {
        die("Shipping address required");
    }

    try {
        $pdo->beginTransaction();

        // Insert order
        $stmt = $pdo->prepare(
            "INSERT INTO orders (user_id, total, payment_method, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $total,
            $method
        ]);

        $orderId = $pdo->lastInsertId();

        // Insert items + reduce stock
        foreach ($_SESSION['cart'] as $productId => $qty) {

            // Lock stock
            $check = $pdo->prepare(
                "SELECT stock FROM products WHERE id=? FOR UPDATE"
            );
            $check->execute([$productId]);
            $stock = $check->fetchColumn();

            if ($stock < $qty) {
                throw new Exception("Insufficient stock for product ID $productId");
            }

            // Order items
            $stmt = $pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$orderId, $productId, $qty]);

            // Reduce stock
            $stmt = $pdo->prepare(
                "UPDATE products SET stock = stock - ? WHERE id = ?"
            );
            $stmt->execute([$qty, $productId]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);

        header("Location: order-success.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Order failed: " . $e->getMessage());
    }
}

include 'header.php';
?>
<link rel="stylesheet" href="../css/checkout.css">
<h2>Checkout</h2>

<form method="post" class="form">
    <label>
        Shipping Address
        <textarea name="address" required><?= htmlspecialchars($_SESSION['user_name']) ?></textarea>
    </label>

    <label>
        Payment Method
        <select name="payment_method">
            <option value="COD">Cash On Delivery</option>
            <option value="MockPay">Mock Payment</option>
        </select>
    </label>

    <p><strong>Total: Rs. <?= number_format($total,2) ?></strong></p>

    <button type="submit">Place Order</button>
</form>

<?php include 'footer.php'; ?>
