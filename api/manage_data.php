<?php
session_start();

// Admin Session Check (Exact match with login.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$message = '';

// -------------------------
// 1. DELETE ACTION
// -------------------------
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $type = $_GET['type'] ?? '';
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        if ($type == 'category') {
            $conn->query("DELETE FROM categories WHERE id=$id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Category deleted successfully!</div>";
        } elseif ($type == 'subcategory') {
            $conn->query("DELETE FROM subcategories WHERE id=$id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Subcategory deleted successfully!</div>";
        } elseif ($type == 'item') {
            $conn->query("DELETE FROM items WHERE id=$id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Data item deleted successfully!</div>";
        }
    }
}

// -------------------------
// 2. ADD / EDIT ACTIONS
// -------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    // -- Category Form --
    if ($form_type == 'category') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        $cat_name = $conn->real_escape_string($_POST['category_name']);
        $status = isset($_POST['status']) ? 'Active' : 'Inactive';

        if ($cat_id > 0) {
            $conn->query("UPDATE categories SET category_name='$cat_name', status='$status' WHERE id=$cat_id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Category updated!</div>";
        } else {
            $conn->query("INSERT INTO categories (category_name, status) VALUES ('$cat_name', '$status')");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Category added!</div>";
        }
    }

    // -- Subcategory Form --
    if ($form_type == 'subcategory') {
        $subcat_id = (int)($_POST['subcat_id'] ?? 0);
        $cat_id = (int)$_POST['category_id'];
        $subcat_name = $conn->real_escape_string($_POST['subcategory_name']);
        $status = isset($_POST['status']) ? 'Active' : 'Inactive';

        if ($subcat_id > 0) {
            $conn->query("UPDATE subcategories SET category_id=$cat_id, subcategory_name='$subcat_name', status='$status' WHERE id=$subcat_id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Subcategory updated!</div>";
        } else {
            $conn->query("INSERT INTO subcategories (category_id, subcategory_name, status) VALUES ($cat_id, '$subcat_name', '$status')");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Subcategory added!</div>";
        }
    }

    // -- Item / Data Form --
    if ($form_type == 'item') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $cat_id = (int)$_POST['category_id'];
        $subcat_id = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : "NULL";
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $status = isset($_POST['status']) ? 'Active' : 'Inactive';

        // Image Handling
        $imagePath = '';
        if (!empty($_FILES['image']['name'])) {
            $imgName = time() . '_' . basename($_FILES['image']['name']);
            $targetDir = 'uploads/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $targetFile = $targetDir . $imgName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        if ($item_id > 0) {
            $imgSql = $imagePath ? ", image='$imagePath'" : "";
            $conn->query("UPDATE items SET category_id=$cat_id, subcategory_id=$subcat_id, title='$title', description='$description', status='$status' $imgSql WHERE id=$item_id");
            $message = "<div class='alert alert-success border-0 shadow-sm'>Item updated!</div>";
        } else {
            $conn->query("INSERT INTO items (category_id, subcategory_id, title, description, image, status) VALUES ($cat_id, $subcat_id, '$title', '$description', '$imagePath', '$status')");
            $message = "<div class='alert alert-success border-0 shadow-sm'>New Item added!</div>";
        }
    }
}

// Fetch categories for dropdowns
$all_categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    body {
      background-color: #f4f6f9;
      font-family: 'Inter', sans-serif;
      color: #333;
    }
    .main-header {
      background: #ffffff;
      padding: 20px 0;
      border-bottom: 1px solid #e9ecef;
      margin-bottom: 30px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .main-header h2 {
      font-weight: 700;
      color: #1e293b;
      margin: 0;
      font-size: 1.5rem;
    }
    .custom-card {
      background: #ffffff;
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
      padding: 24px;
      margin-bottom: 25px;
    }
    .custom-card h5 {
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f1f5f9;
    }
    .nav-tabs {
      border-bottom: 2px solid #e2e8f0;
      gap: 10px;
    }
    .nav-tabs .nav-link {
      border: none;
      color: #64748b;
      font-weight: 600;
      padding: 12px 20px;
      border-radius: 8px 8px 0 0;
      transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
      color: #2563eb;
      background: #f8fafc;
    }
    .nav-tabs .nav-link.active {
      color: #2563eb;
      background: #ffffff;
      border-bottom: 3px solid #2563eb;
    }
    .tab-content {
      background: #ffffff;
      border-radius: 0 0 12px 12px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      padding: 25px;
    }
    .form-label, label {
      font-weight: 500;
      color: #475569;
      font-size: 0.9rem;
      margin-bottom: 6px;
    }
    .form-control, .form-select {
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      padding: 10px 14px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .btn {
      border-radius: 8px;
      padding: 8px 18px;
      font-weight: 500;
      transition: all 0.2s;
    }
    .btn-primary {
      background-color: #2563eb;
      border-color: #2563eb;
    }
    .btn-primary:hover {
      background-color: #1d4ed8;
    }
    .btn-success {
      background-color: #10b981;
      border-color: #10b981;
    }
    .btn-success:hover {
      background-color: #059669;
    }
    .table {
      vertical-align: middle;
    }
    .table thead th {
      background-color: #f8fafc;
      color: #475569;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #e2e8f0;
      padding: 12px 16px;
    }
    .table tbody td {
      padding: 14px 16px;
      color: #334155;
    }
    .img-thumbnail-custom {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    }
    .badge-status-active {
      background-color: #dcfce7;
      color: #15803d;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .badge-status-inactive {
      background-color: #f1f5f9;
      color: #64748b;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
  </style>
</head>
<body>

<div class="main-header">
  <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
    <h2>Admin Panel - Content & Category Management</h2>
    <a href="logout.php" class="btn btn-outline-danger btn-sm fw-semibold">Logout</a>
  </div>
</div>

<div class="container-fluid px-4 mb-5">
  <?= $message ?>

  <!-- Tabs Nav -->
  <ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button">Manage Data / Items</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button">Manage Categories</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="subcategories-tab" data-bs-toggle="tab" data-bs-target="#subcategories" type="button">Manage Subcategories</button>
    </li>
  </ul>

  <div class="tab-content" id="adminTabContent">
    
    <!-- ==================== TAB 1: ITEMS DATA ==================== -->
    <div class="tab-pane fade show active" id="items" role="tabpanel">
      <div class="custom-card mb-4">
        <h5>Add / Edit Data Item</h5>
        <form method="POST" enctype="multipart/form-data" id="itemForm">
          <input type="hidden" name="form_type" value="item">
          <input type="hidden" name="item_id" id="item_id">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Category</label>
              <select name="category_id" id="item_cat_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php 
                $all_categories->data_seek(0);
                while($c = $all_categories->fetch_assoc()): 
                ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label>Subcategory (Optional)</label>
              <select name="subcategory_id" id="item_subcat_id" class="form-select">
                <option value="">Select Category First</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" id="item_title" class="form-control" placeholder="Enter title" required>
          </div>

          <div class="mb-3">
            <label>Description</label>
            <textarea name="description" id="item_desc" class="form-control" rows="3" placeholder="Enter description..."></textarea>
          </div>

          <div class="mb-3">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
          </div>

          <div class="form-check mb-3">
            <input type="checkbox" name="status" id="item_status" class="form-check-input" checked>
            <label class="form-check-label" for="item_status">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Save Item</button>
          <button type="button" class="btn btn-light border" onclick="document.getElementById('itemForm').reset();">Clear</button>
        </form>
      </div>

      <!-- Items Table -->
      <h5 class="fw-bold mb-3">All Data Items</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Subcategory</th>
              <th>Title</th>
              <th>Image</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $items_res = $conn->query("
              SELECT i.*, c.category_name, s.subcategory_name 
              FROM items i 
              LEFT JOIN categories c ON i.category_id = c.id 
              LEFT JOIN subcategories s ON i.subcategory_id = s.id 
              ORDER BY i.id DESC
            ");
            while($row = $items_res->fetch_assoc()):
            ?>
            <tr>
              <td><strong>#<?= $row['id'] ?></strong></td>
              <td><?= htmlspecialchars($row['category_name']) ?></td>
              <td><?= htmlspecialchars($row['subcategory_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td>
                <?php if($row['image'] && file_exists($row['image'])): ?>
                  <img src="<?= $row['image'] ?>" class="img-thumbnail-custom">
                <?php else: ?>
                  <span class="text-muted small">No Image</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="<?= $row['status'] == 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                  <?= $row['status'] ?>
                </span>
              </td>
              <td>
                <a href="manage_data.php?action=delete&type=item&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ==================== TAB 2: CATEGORIES ==================== -->
    <div class="tab-pane fade" id="categories" role="tabpanel">
      <div class="custom-card mb-4">
        <h5>Add Category</h5>
        <form method="POST">
          <input type="hidden" name="form_type" value="category">
          <div class="mb-3">
            <label>Category Name</label>
            <input type="text" name="category_name" class="form-control" placeholder="e.g. Electronics, Clothing" required>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="status" class="form-check-input" id="cat_status" checked>
            <label class="form-check-label" for="cat_status">Active</label>
          </div>
          <button type="submit" class="btn btn-success">Add Category</button>
        </form>
      </div>

      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $all_categories->data_seek(0);
          while($cat = $all_categories->fetch_assoc()): 
          ?>
          <tr>
            <td><strong>#<?= $cat['id'] ?></strong></td>
            <td><?= htmlspecialchars($cat['category_name']) ?></td>
            <td>
              <span class="<?= $cat['status'] == 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                <?= $cat['status'] ?>
              </span>
            </td>
            <td>
              <a href="manage_data.php?action=delete&type=category&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deleting category will also delete its subcategories and items!')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- ==================== TAB 3: SUBCATEGORIES ==================== -->
    <div class="tab-pane fade" id="subcategories" role="tabpanel">
      <div class="custom-card mb-4">
        <h5>Add Subcategory</h5>
        <form method="POST">
          <input type="hidden" name="form_type" value="subcategory">
          <div class="mb-3">
            <label>Select Parent Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select Category</option>
              <?php 
              $all_categories->data_seek(0);
              while($c = $all_categories->fetch_assoc()): 
              ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Subcategory Name</label>
            <input type="text" name="subcategory_name" class="form-control" placeholder="e.g. Mobile Phones, Laptops" required>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="status" class="form-check-input" id="subcat_status" checked>
            <label class="form-check-label" for="subcat_status">Active</label>
          </div>
          <button type="submit" class="btn btn-success">Add Subcategory</button>
        </form>
      </div>

      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Parent Category</th>
            <th>Subcategory Name</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sub_res = $conn->query("SELECT s.*, c.category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY s.id DESC");
          while($sub = $sub_res->fetch_assoc()): 
          ?>
          <tr>
            <td><strong>#<?= $sub['id'] ?></strong></td>
            <td><?= htmlspecialchars($sub['category_name']) ?></td>
            <td><?= htmlspecialchars($sub['subcategory_name']) ?></td>
            <td>
              <span class="<?= $sub['status'] == 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                <?= $sub['status'] ?>
              </span>
            </td>
            <td>
              <a href="manage_data.php?action=delete&type=subcategory&id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete subcategory?')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dynamic Subcategory Loading via AJAX -->
<script>
document.getElementById('item_cat_id').addEventListener('change', function() {
    let catId = this.value;
    let subCatSelect = document.getElementById('item_subcat_id');
    
    subCatSelect.innerHTML = '<option value="">Loading...</option>';

    if(catId) {
        fetch('get_subcategories.php?category_id=' + catId)
            .then(response => response.json())
            .then(data => {
                subCatSelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                data.forEach(item => {
                    subCatSelect.innerHTML += `<option value="${item.id}">${item.subcategory_name}</option>`;
                });
            });
    } else {
        subCatSelect.innerHTML = '<option value="">Select Category First</option>';
    }
});
</script>

</body>
</html>