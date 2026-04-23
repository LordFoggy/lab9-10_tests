<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);
$admin_id = intval($data['admin_id']);

if ($quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Количество не может быть отрицательным']);
    exit();
}

// Получаем текущее количество
$current_query = "SELECT quantity FROM products WHERE id = $product_id";
$current_result = mysqli_query($conn, $current_query);
$current = mysqli_fetch_assoc($current_result);

if (!$current) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit();
}

$old_quantity = $current['quantity'];
$quantity_change = $quantity - $old_quantity;

// Обновляем количество
$update_query = "UPDATE products SET quantity = $quantity WHERE id = $product_id";

if (mysqli_query($conn, $update_query)) {
    // Записываем в историю
    if ($quantity_change != 0) {
        $action = $quantity_change > 0 ? 'add' : 'remove';
        $history_query = "INSERT INTO stock_history (product_id, action, quantity_change, 
                         old_quantity, new_quantity, reason, admin_id) 
                         VALUES ($product_id, '$action', $quantity_change, 
                         $old_quantity, $quantity, 'Быстрая корректировка', $admin_id)";
        mysqli_query($conn, $history_query);
    }
    
    echo json_encode(['success' => true, 'new_quantity' => $quantity]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>