<?php
require 'db.php';

// Filter parameters
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$subcat_id = isset($_GET['subcat_id']) ? (int)$_GET['subcat_id'] : 0;

// Subcategory check for parent category mapping
if ($subcat_id > 0 && $cat_id == 0) {
    $sub_check = $conn->query("SELECT category_id FROM subcategories WHERE id = $subcat_id");
    if ($sub_check && $sub_row = $sub_check->fetch_assoc()) {
        $cat_id = (int)$sub_row['category_id'];
    }
}

// DEFAULT CATEGORY SETTING:
// Agar koi category selected nahi hai toh pehli active category (Introduction) auto select karein
if ($cat_id == 0 && $subcat_id == 0) {
    $default_cat_req = $conn->query("SELECT id FROM categories WHERE status = 'Active' ORDER BY id ASC LIMIT 1");
    if ($default_cat_req && $default_row = $default_cat_req->fetch_assoc()) {
        $cat_id = (int)$default_row['id'];
    }
}

// Data fetch query
$items_res = false;
if ($cat_id > 0 || $subcat_id > 0) {
    $where_clauses = ["i.status = 'Active'"];
    if ($subcat_id > 0) {
        $where_clauses[] = "i.subcategory_id = $subcat_id";
    } elseif ($cat_id > 0) {
        $where_clauses[] = "i.category_id = $cat_id";
    }

    $where_sql = implode(' AND ', $where_clauses);
    $items_query = "SELECT i.* FROM items i WHERE $where_sql ORDER BY i.id DESC";
    $items_res = $conn->query($items_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Portfolio</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  
  <style>
    :root {
      --sidebar-width: 290px;
      --primary-color: #2563eb;
      --bg-color: #f8fafc;
    }

    body {
      background-color: var(--bg-color);
      font-family: 'Inter', sans-serif;
      color: #334155;
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
    }

    .main-wrapper {
      margin-left: var(--sidebar-width);
      flex: 1;
      padding: 40px 50px;
      max-width: 1100px;
    }

    .portfolio-item {
      background: #ffffff;
      border-radius: 12px;
      padding: 28px;
      margin-bottom: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .item-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 12px;
    }

    .item-description {
      color: #475569;
      font-size: 0.98rem;
      line-height: 1.65;
      margin: 0;
      white-space: pre-line;
    }

    .item-img-right {
      width: 100%;
      max-height: 240px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #f1f5f9;
    }
  </style>
</head>
<body>

<!-- Include Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Portfolio Data Area -->
<main class="main-wrapper">

  <?php if ($items_res && $items_res->num_rows > 0): ?>
    <?php while ($item = $items_res->fetch_assoc()): 
      $has_image = (!empty($item['image']) && file_exists($item['image']));
    ?>
      <article class="portfolio-item">
        <div class="row g-4 align-items-center">
          <div class="<?= $has_image ? 'col-md-8' : 'col-12' ?>">
            <h3 class="item-title"><?= htmlspecialchars($item['title']) ?></h3>
            <p class="item-description"><?= htmlspecialchars($item['description'] ?? '') ?></p>
          </div>

          <?php if ($has_image): ?>
            <div class="col-md-4">
              <img src="<?= htmlspecialchars($item['image']) ?>" class="item-img-right" alt="Project Image">
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="py-5 text-center text-muted">
      <p class="fs-5 mb-0">No entries found in this section.</p>
    </div>
  <?php endif; ?>

</main>

</body>
</html>
