<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Получаем заказы пользователя
$query = "SELECT o.*, 
          COUNT(oi.id) as items_count,
          SUM(oi.quantity) as total_items
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.user_id = $user_id";

if ($status_filter !== 'all') {
    $query .= " AND o.status = '$status_filter'";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $query);

// Получаем статистику по заказам
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(total_amount) as total_spent
    FROM orders 
    WHERE user_id = $user_id";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Обработка отмены заказа
if (isset($_GET['cancel'])) {
    $order_id = intval($_GET['cancel']);
    
    // Проверяем, что заказ принадлежит пользователю и имеет статус "новый"
    $check_query = "SELECT id FROM orders WHERE id = $order_id AND user_id = $user_id AND status = 'new'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Отменяем заказ
        $cancel_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";
        mysqli_query($conn, $cancel_query);
        
        // Возвращаем товары на склад
        $return_items = "SELECT oi.product_id, oi.quantity 
                        FROM order_items oi 
                        WHERE oi.order_id = $order_id";
        $return_result = mysqli_query($conn, $return_items);
        
        while ($item = mysqli_fetch_assoc($return_result)) {
            $update_stock = "UPDATE products 
                            SET quantity = quantity + {$item['quantity']} 
                            WHERE id = {$item['product_id']}";
            mysqli_query($conn, $update_stock);
        }
        
        $_SESSION['success'] = 'Заказ успешно отменен';
        header('Location: orders.php');
        exit();
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/orders.css">

<div class="orders-page">
    <h1 class="page-title">Мои заказы</h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="orders-container">
        <!-- Левая колонка - статистика и навигация -->
        <div class="orders-sidebar">
            <!-- Статистика -->
            <div class="stats-card">
                <h3>Статистика заказов</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                        <div class="stat-label">Всего заказов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo formatPrice($stats['total_spent']); ?></div>
                        <div class="stat-label">Всего потрачено</div>
                    </div>
                </div>
            </div>
            
            <!-- Навигация -->
            <div class="navigation-card">
                <a href="profile.php" class="nav-link">
                    <span class="nav-icon">👤</span>
                    <span>Профиль</span>
                </a>
                <a href="orders.php" class="nav-link active">
                    <span class="nav-icon">📦</span>
                    <span>Мои заказы</span>
                </a>
            </div>
        </div>
        
        <!-- Правая колонка - список заказов -->
        <div class="orders-content">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div class="orders-list">
                    <?php while($order = mysqli_fetch_assoc($result)): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3 class="order-number">Заказ #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                    <span class="order-date">
                                        <?php echo date('d.m.Y в H:i', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'new' => 'Новый',
                                            'confirmed' => 'Подтвержден',
                                            'cancelled' => 'Отменен'
                                        ];
                                        echo $status_text[$order['status']];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <div class="order-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Товаров:</span>
                                        <span class="detail-value"><?php echo $order['total_items'] ?? 0; ?> шт.</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Позиций:</span>
                                        <span class="detail-value"><?php echo $order['items_count'] ?? 0; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Сумма:</span>
                                        <span class="detail-value total-price"><?php echo formatPrice($order['total_amount']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Кнопки действий -->
                                <div class="order-actions">
                                    
                                    
                                    <?php if($order['status'] == 'new'): ?>
                                    <a href="?cancel=<?php echo $order['id']; ?>" 
                                       class="btn-danger"
                                       onclick="return confirm('Вы уверены, что хотите отменить заказ?')">
                                        Отменить заказ
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Пагинация (если нужно) -->
                <?php 
                // Получаем общее количество заказов
                $total_orders_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id";
                $total_orders_result = mysqli_query($conn, $total_orders_query);
                $total_orders = mysqli_fetch_assoc($total_orders_result)['total'];
                
                // Если заказов больше 10, показываем пагинацию
                if ($total_orders > 10): ?>
                <div class="pagination">
                    <span class="pagination-info">
                        Показано <?php echo min(10, $total_orders); ?> из <?php echo $total_orders; ?> заказов
                    </span>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-orders">
                    <div class="empty-icon">📦</div>
                    <h2>Заказов пока нет</h2>
                    <p>Сделайте свой первый заказ в нашем магазине!</p>
                    <div class="empty-actions">
                        <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
                        <a href="index.php" class="btn-secondary">На главную</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>