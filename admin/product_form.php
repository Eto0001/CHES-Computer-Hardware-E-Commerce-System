<?php
require_once 'header.php';

$id = intval($_GET['id'] ?? 0);
$product = ['name'=>'','price'=>'','description'=>'','stock'=>0,'category_id'=>null];
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $stmt->execute([$id]);
  $product = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = sanitize($_POST['name']);
  $price = floatval($_POST['price']);
  $desc = sanitize($_POST['description']);
  $stock = intval($_POST['stock']);
  $cat = intval($_POST['category_id']);
  $image = $product['image'] ?? null;

  // Upload
  if (!empty($_FILES['image']['name'])) {
    $fname = time().'_'.basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/$fname");
    $image = $fname;
  }

  if ($id) {
    $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, description=?, stock=?, category_id=?, image=? WHERE id=?");
    $stmt->execute([$name, $price, $desc, $stock, $cat, $image, $id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO products (name, price, description, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $desc, $stock, $cat, $image]);
  }

  header('Location: products.php');
  exit;
}

// categories
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<link rel="stylesheet" href="../css/form.css">
<link rel="stylesheet" href="../css/product-form.css">
<h2><?php echo $id ? 'Edit' : 'Add'; ?> Product</h2>
<form method="post" enctype="multipart/form-data" class="form">
  <label>Name <input name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></label>
  <label>Price <input name="price" type="number" step="0.01" value="<?php echo $product['price']; ?>" required></label>
  <label>Stock <input name="stock" type="number" value="<?php echo $product['stock']; ?>" required></label>
  <label>Category
    <select name="category_id">
      <option value="">-- Select --</option>
      <?php foreach($cats as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php if($c['id']==$product['category_id']) echo 'selected'; ?>>
          <?php echo htmlspecialchars($c['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Description <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea></label>
  <label>Image <input type="file" name="image"></label>
  <?php if(!empty($product['image'])): ?>
    <p><img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" width="120"></p>
  <?php endif; ?>
  <button type="submit">Save</button>
</form>
<?php require 'footer.php'; ?>

