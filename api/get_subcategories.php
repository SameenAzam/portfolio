<?php
require 'db.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$subcategories = [];
if ($category_id > 0) {
    $res = $conn->query("SELECT id, subcategory_name FROM subcategories WHERE category_id = $category_id AND status = 'Active'");
    while ($row = $res->fetch_assoc()) {
        $subcategories[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($subcategories);
?>