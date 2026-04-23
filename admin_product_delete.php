<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: admin_products.php');
    exit();
}

// Проверяем, есть ли у товара заказы
$check_orders = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id = $product_id";
$result = mysqli_query($conn, $check_orders);
$row = mysqli_fetch_assoc($result);

if ($row['order_count'] > 0) {
    // Не удаляем, а скрываем товар
    $update_query = "UPDATE products SET quantity = 0, is_promotional = 0 WHERE id = $product_id";
    mysqli_query($conn, $update_query);
    
    // Запись в историю
    $admin_id = $_SESSION['user_id'];
    $history_query = "INSERT INTO stock_history (product_id, action, quantity_change, 
                     reason, admin_id) 
                     VALUES ($product_id, 'remove', 0, 
                     'Товар скрыт из-за наличия в заказах', $admin_id)";
    mysqli_query($conn, $history_query);
    
    $message = 'Товар скрыт (есть связанные заказы)';
} else {
    // Получаем информацию о товаре для удаления изображения
    $product_query = "SELECT image FROM products WHERE id = $product_id";
    $product_result = mysqli_query($conn, $product_query);
    $product = mysqli_fetch_assoc($product_result);
    
    // Удаляем изображение
    if (!empty($product['image']) && file_exists('images/' . $product['image'])) {
        unlink('images/' . $product['image']);
    }
    
    // Удаляем из корзин
    $delete_cart = "DELETE FROM cart WHERE product_id = $product_id";
    mysqli_query($conn, $delete_cart);
    
    // Удаляем историю
    $delete_history = "DELETE FROM stock_history WHERE product_id = $product_id";
    mysqli_query($conn, $delete_history);
    
    // Удаляем товар
    $delete_query = "DELETE FROM products WHERE id = $product_id";
    mysqli_query($conn, $delete_query);
    
    $message = 'Товар успешно удален';
}

header('Location: admin_products.php?message=' . urlencode($message));
exit();