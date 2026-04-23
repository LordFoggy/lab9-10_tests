<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'new';

// Получаем заказы
$query = "SELECT o.*, u.first_name, u.last_name, 
          COUNT(oi.id) as items_count,
          SUM(oi.quantity) as total_items
          FROM orders o
          JOIN users u ON o.user_id = u.id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND o.status = '$status_filter'";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/admin.css">

<div class="admin-page">
    <h1 class="page-title">Панель администратора</h1>
    
    <div class="admin-filters">
        <a href="?status=new" class="<?php echo $status_filter == 'new' ? 'active' : ''; ?>">Новые</a>
        <a href="?status=confirmed" class="<?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Подтвержденные</a>
        <a href="?status=cancelled" class="<?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Отмененные</a>
        <a href="?status=all" class="<?php echo $status_filter == 'all' ? 'active' : ''; ?>">Все</a>
    </div>
    
    <div class="orders-list">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Товаров</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    <td><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></td>
                    <td><?php echo $order['total_items']; ?></td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                    <td>
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
                    </td>
                    <td>
                        <?php if($order['status'] == 'new'): ?>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')" 
                                class="btn-small btn-confirm">Подтвердить</button>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')"
                                class="btn-small btn-cancel">Отменить</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateOrderStatus(orderId, status) {
    if(confirm('Изменить статус заказа?')) {
        fetch('ajax/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>