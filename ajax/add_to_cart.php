<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id']);
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
$user_id = $_SESSION['user_id'];

// Проверяем наличие товара
$check_query = "SELECT quantity FROM products WHERE id = $product_id";
$check_result = mysqli_query($conn, $check_query);
$product = mysqli_fetch_assoc($check_result);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit();
}

if ($product['quantity'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Товар отсутствует на складе']);
    exit();
}

if ($quantity > $product['quantity']) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно товара на складе']);
    exit();
}

// Проверяем, есть ли уже товар в корзине
$cart_query = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id";
$cart_result = mysqli_query($conn, $cart_query);

if (mysqli_num_rows($cart_result) > 0) {
    // Обновляем количество
    $cart_item = mysqli_fetch_assoc($cart_result);
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    if ($new_quantity <= $product['quantity']) {
        $update_query = "UPDATE cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}";
        mysqli_query($conn, $update_query);
    } else {
        echo json_encode(['success' => false, 'message' => 'Превышен лимит товара в наличии']);
        exit();
    }
} else {
    // Добавляем новый товар
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)";
    mysqli_query($conn, $insert_query);
}

// Получаем общее количество товаров в корзине для обновления счетчика
$count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_query);
$count = mysqli_fetch_assoc($count_result);

echo json_encode([
    'success' => true,
    'cart_count' => $count['total'] ?: 0,
    'message' => 'Товар добавлен в корзину'
]);
?>