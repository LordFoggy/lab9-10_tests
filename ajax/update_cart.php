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
$action = $data['action']; // 'increase' или 'decrease'
$user_id = $_SESSION['user_id'];

// Проверяем, что товар принадлежит текущему пользователю
$check_query = "SELECT c.*, p.quantity as available_qty 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = $cart_id AND c.user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден в корзине']);
    exit();
}

$cart_item = mysqli_fetch_assoc($check_result);
$new_quantity = $cart_item['quantity'];

if ($action == 'increase') {
    if ($new_quantity >= $cart_item['available_qty']) {
        echo json_encode(['success' => false, 'message' => 'Достигнут лимит товара в наличии']);
        exit();
    }
    $new_quantity++;
} elseif ($action == 'decrease') {
    if ($new_quantity <= 1) {
        // Если количество становится 0, удаляем товар из корзины
        $delete_query = "DELETE FROM cart WHERE id = $cart_id";
        if (mysqli_query($conn, $delete_query)) {
            echo json_encode(['success' => true, 'quantity' => 0]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при удалении товара']);
            exit();
        }
    }
    $new_quantity--;
}

// Обновляем количество
$update_query = "UPDATE cart SET quantity = $new_quantity WHERE id = $cart_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true, 'quantity' => $new_quantity]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении корзины']);
}
?>