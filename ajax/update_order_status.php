<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id']);
$status = mysqli_real_escape_string($conn, $data['status']);

// Проверяем существование заказа
$check_query = "SELECT id FROM orders WHERE id = $order_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
    exit();
}

// Обновляем статус
$update_query = "UPDATE orders SET status = '$status' WHERE id = $order_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении статуса']);
}
?>