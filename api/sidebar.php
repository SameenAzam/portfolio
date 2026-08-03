<?php
include_once ('db.php');

// Agar index.php ne pehle se $cat_id set nahi ki, sirf tab hi URL se get karein
if (!isset($cat_id)) {
    $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
}
if (!isset($subcat_id)) {
    $subcat_id = isset($_GET['subcat_id']) ? (int)$_GET['subcat_id'] : 0;
}

// Order by c.id ASC se pehli category sabse upar rahegi
$categories_res = $conn->query("
    SELECT c.id AS cat_id, c.category_name, s.id AS subcat_id, s.subcategory_name 
    FROM categories c 
    LEFT JOIN subcategories s ON c.id = s.category_id AND s.status = 'Active'
    WHERE c.status = 'Active' 
    ORDER BY c.id ASC, s.id ASC
");

$sidebar_menu = [];
if ($categories_res) {
    while ($row = $categories_res->fetch_assoc()) {
        $c_id = $row['cat_id'];
        if (!isset($sidebar_menu[$c_id])) {
            $sidebar_menu[$c_id] = [
                'name' => $row['category_name'],
                'subcategories' => []
            ];
        }
        if ($row['subcat_id']) {
            $sidebar_menu[$c_id]['subcategories'][] = [
                'id' => $row['subcat_id'],
                'name' => $row['subcategory_name']
            ];
        }
    }
}

// Function to get icon according to Category Name
function getCategoryIcon($name) {
    $name = strtolower(trim($name));
    if (strpos($name, 'intro') !== false || strpos($name, 'about') !== false) return 'fa-regular fa-user';
    if (strpos($name, 'educat') !== false || strpos($name, 'academic') !== false) return 'fa-solid fa-graduation-cap';
    if (strpos($name, 'experi') !== false || strpos($name, 'work') !== false) return 'fa-solid fa-briefcase';
    if (strpos($name, 'research') !== false || strpos($name, 'publi') !== false) return 'fa-solid fa-book-bookmark';
    if (strpos($name, 'skill') !== false || strpos($name, 'tech') !== false) return 'fa-solid fa-code';
    if (strpos($name, 'project') !== false || strpos($name, 'portfoli') !== false) return 'fa-solid fa-folder-open';
    if (strpos($name, 'award') !== false || strpos($name, 'achiev') !== false) return 'fa-solid fa-trophy';
    if (strpos($name, 'contact') !== false) return 'fa-regular fa-envelope';
    
    return 'fa-solid fa-layer-group'; // Default fallback icon
}
?>

<style>
  :root {
    --sidebar-width: 290px;
    --primary-color: #2563eb;
  }

  .sidebar {
    width: var(--sidebar-width);
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
  }

  .sidebar-profile {
    padding: 30px 24px 20px 24px;
    border-bottom: 1px solid #f1f5f9;
  }

  .profile-name {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
    line-height: 1.2;
  }

  .profile-designation {
    font-size: 0.88rem;
    color: #64748b;
    font-weight: 500;
    margin: 0;
  }

  /* Menu scrollable container */
  .sidebar-menu-wrapper {
    flex: 1;
    overflow-y: auto;
  }

  .sidebar-menu {
    list-style: none;
    padding: 20px 14px;
    margin: 0;
  }

  .sidebar-item { margin-bottom: 6px; }

  .sidebar-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    color: #475569;
    font-weight: 600;
    font-size: 0.92rem;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
  }

  .sidebar-link-content {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .sidebar-icon {
    font-size: 1rem;
    width: 20px;
    text-align: center;
    color: #64748b;
    transition: color 0.2s ease;
  }

  .sidebar-link:hover, .sidebar-link.active {
    color: var(--primary-color);
    background-color: #eff6ff;
  }

  .sidebar-link:hover .sidebar-icon, 
  .sidebar-link.active .sidebar-icon {
    color: var(--primary-color);
  }

  .subcategory-list {
    list-style: none;
    padding-left: 28px;
    margin-top: 4px;
    display: none;
  }

  .sidebar-item.open .subcategory-list { display: block; }

  .subcategory-link {
    display: block;
    padding: 7px 14px;
    font-size: 0.86rem;
    color: #64748b;
    text-decoration: none;
    border-left: 2px solid #e2e8f0;
    transition: all 0.2s;
  }

  .subcategory-link:hover, .subcategory-link.active {
    color: var(--primary-color);
    border-left-color: var(--primary-color);
    font-weight: 600;
    background-color: #f8fafc;
  }

  .chevron-icon {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
  }

  .sidebar-item.open .chevron-icon { transform: rotate(90deg); }

  /* Admin Login Footer */
  .sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
  }

  .admin-login-link {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
  }

  .admin-login-link:hover {
    color: #0f172a;
    background-color: #f8fafc;
  }

  .admin-login-link i {
    font-size: 0.9rem;
  }
</style>

<!-- Sidebar HTML Markup -->
<aside class="sidebar">
  <div class="sidebar-profile">
    <h1 class="profile-name">Soomaiya Hamid</h1>
    <p class="profile-designation">Lecturer</p>
  </div>

  <div class="sidebar-menu-wrapper">
    <ul class="sidebar-menu">
      <?php foreach ($sidebar_menu as $c_id => $category): 
        $is_cat_active = ($cat_id == $c_id);
        $has_subcats = !empty($category['subcategories']);
        $cat_icon = getCategoryIcon($category['name']);
      ?>
        <li class="sidebar-item <?= $is_cat_active ? 'open' : '' ?>">
          <a href="index.php?cat_id=<?= $c_id ?>" class="sidebar-link <?= ($is_cat_active && $subcat_id == 0) ? 'active' : '' ?>">
            <div class="sidebar-link-content">
              <i class="<?= $cat_icon ?> sidebar-icon"></i>
              <span><?= htmlspecialchars($category['name']) ?></span>
            </div>
            <?php if ($has_subcats): ?>
              <i class="fa-solid fa-chevron-right chevron-icon"></i>
            <?php endif; ?>
          </a>

          <?php if ($has_subcats): ?>
            <ul class="subcategory-list">
              <?php foreach ($category['subcategories'] as $sub): 
                $is_sub_active = ($subcat_id == $sub['id']);
              ?>
                <li>
                  <a href="index.php?cat_id=<?= $c_id ?>&subcat_id=<?= $sub['id'] ?>" class="subcategory-link <?= $is_sub_active ? 'active' : '' ?>">
                    <?= htmlspecialchars($sub['name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Admin Login Footer Link -->
  <div class="sidebar-footer">
    <a href="login.php" class="admin-login-link" title="Admin Panel Login">
      <i class="fa-solid fa-lock"></i>
      <span>Admin Login</span>
    </a>
  </div>
</aside>

<script>
document.querySelectorAll('.sidebar-item').forEach(item => {
  const link = item.querySelector('.sidebar-link');
  const chevron = item.querySelector('.chevron-icon');
  
  if (chevron) {
    chevron.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      item.classList.toggle('open');
    });

    item.addEventListener('mouseenter', function() {
      this.classList.add('open');
    });
    item.addEventListener('mouseleave', function() {
      const hasActiveChild = item.querySelector('.active');
      if (!hasActiveChild) {
        item.classList.remove('open');
      }
    });
  }
});
</script>