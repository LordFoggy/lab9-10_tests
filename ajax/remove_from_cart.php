<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = intval($data['cart_id']);
$user_id = $_SESSION['user_id'];

// Проверяем, что товар принадлежит текущему пользователю
$check_query = "SELECT id FROM cart WHERE id = $cart_id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден в корзине']);
    exit();
}

// Удаляем товар
$delete_query = "DELETE FROM cart WHERE id = $cart_id";

if (mysqli_query($conn, $delete_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении товара']);
}
?>