<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'];

// Проверяем пароль
$query = "SELECT password FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Неверный пароль']);
    exit();
}

// Получаем товары из корзины
$cart_query = "SELECT c.*, p.price, p.name, p.quantity as available_qty 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_query);

if (mysqli_num_rows($cart_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
    exit();
}

// Проверяем наличие товаров и считаем сумму
$total_amount = 0;
$order_items = [];
$errors = [];

while ($item = mysqli_fetch_assoc($cart_result)) {
    if ($item['quantity'] > $item['available_qty']) {
        $errors[] = "Товар '{$item['name']}' отсутствует в нужном количестве";
    }
    
    $subtotal = $item['price'] * $item['quantity'];
    $total_amount += $subtotal;
    
    $order_items[] = [
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'subtotal' => $subtotal
    ];
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit();
}

// Начинаем транзакцию
mysqli_begin_transaction($conn);

try {
    // Создаем заказ
    $order_query = "INSERT INTO orders (user_id, total_amount) VALUES ($user_id, $total_amount)";
    if (!mysqli_query($conn, $order_query)) {
        throw new Exception('Ошибка при создании заказа');
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Добавляем товары в заказ
    foreach ($order_items as $item) {
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                      VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})";
        
        if (!mysqli_query($conn, $item_query)) {
            throw new Exception('Ошибка при добавлении товаров в заказ');
        }
        
        // Уменьшаем количество товара на складе
        $update_query = "UPDATE products 
                        SET quantity = quantity - {$item['quantity']} 
                        WHERE id = {$item['product_id']}";
        
        if (!mysqli_query($conn, $update_query)) {
            throw new Exception('Ошибка при обновлении количества товара');
        }
    }
    
    // Очищаем корзину
    $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
    if (!mysqli_query($conn, $clear_cart)) {
        throw new Exception('Ошибка при очистке корзины');
    }
    
    // Фиксируем транзакцию
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно оформлен! Номер заказа: #' . $order_id,
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>