<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

// Получаем все товары с пагинацией
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT SQL_CALC_FOUND_ROWS p.*, 
          c.name as category_name,
          co.name as country_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN countries co ON p.country_id = co.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

// Общее количество для пагинации
$total_result = mysqli_query($conn, "SELECT FOUND_ROWS()");
$total_row = mysqli_fetch_row($total_result);
$total_products = $total_row[0];
$total_pages = ceil($total_products / $limit);
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/admin.css">

<div class="admin-page">
    <h1 class="page-title">Управление товарами</h1>
    
    <div class="admin-actions">
        <a href="admin_product_add.php" class="btn-primary">Добавить новый товар</a>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Наименование</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($product = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php if(!empty($product['image'])): ?>
                            <img src="images/<?php echo $product['image']; ?>" 
                                 alt="<?php echo $product['name']; ?>" 
                                 class="product-thumbnail">
                            <?php else: ?>
                            <span class="no-image">Нет фото</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $product['name']; ?></strong><br>
                            <small><?php echo substr($product['description'], 0, 100); ?>...</small>
                        </td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span class="quantity-badge <?php echo $product['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $product['quantity']; ?> шт
                            </span>
                        </td>
                        <td>
                            <?php if($product['is_promotional']): ?>
                            <span class="status-badge status-promo">Акция</span>
                            <?php endif; ?>
                            <?php if($product['quantity'] <= 0): ?>
                            <span class="status-badge status-out">Нет в наличии</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                   target="_blank" class="btn-small btn-view">Просмотр</a>
                                <a href="admin_product_edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn-small btn-edit">Редактировать</a>
                                <a href="admin_product_delete.php?id=<?php echo $product['id']; ?>" 
                                   class="btn-small btn-delete"
                                   onclick="return confirm('Удалить товар?')">Удалить</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            Товары не найдены
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Пагинация -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Быстрое обновление количества
function quickUpdateQuantity(productId, action) {
    const quantityInput = document.getElementById('quantity_' + productId);
    let newQuantity = parseInt(quantityInput.value);
    
    if (action === 'increase') {
        newQuantity++;
    } else if (action === 'decrease' && newQuantity > 0) {
        newQuantity--;
    }
    
    quantityInput.value = newQuantity;
    
    // AJAX обновление
    fetch('ajax/update_product_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: newQuantity,
            admin_id: <?php echo $_SESSION['user_id']; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('qty_badge_' + productId).textContent = newQuantity + ' шт';
            document.getElementById('qty_badge_' + productId).className = 
                newQuantity > 0 ? 'quantity-badge in-stock' : 'quantity-badge out-of-stock';
        }
    });
}

// Быстрое изменение цены
function quickUpdatePrice(productId) {
    const priceInput = document.getElementById('price_' + productId);
    const newPrice = parseFloat(priceInput.value);
    
    fetch('ajax/update_product_price.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            price: newPrice
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Цена обновлена');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>