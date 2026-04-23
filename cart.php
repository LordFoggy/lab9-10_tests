<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем товары в корзине
$query = "SELECT c.*, p.name, p.price, p.image, p.quantity as available_qty 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = $user_id 
          ORDER BY c.added_at DESC";
$result = mysqli_query($conn, $query);

$total = 0;
$cart_items = [];
while($item = mysqli_fetch_assoc($result)) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
    $cart_items[] = $item;
}

// Обработка удаления товара из корзины
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $delete_query = "DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id";
    mysqli_query($conn, $delete_query);
    header('Location: cart.php');
    exit();
}

// Обработка изменения количества
if (isset($_GET['update'])) {
    $cart_id = intval($_GET['update']);
    $quantity = intval($_GET['quantity']);
    
    if ($quantity > 0) {
        // Проверяем наличие товара
        $check_query = "SELECT p.quantity as available_qty 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.id = $cart_id AND c.user_id = $user_id";
        $check_result = mysqli_query($conn, $check_query);
        $check = mysqli_fetch_assoc($check_result);
        
        if ($quantity <= $check['available_qty']) {
            $update_query = "UPDATE cart SET quantity = $quantity WHERE id = $cart_id";
            mysqli_query($conn, $update_query);
        }
    }
    header('Location: cart.php');
    exit();
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/cart.css">

<div class="cart-page">
    <h1 class="page-title">Корзина</h1>
    
    <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-icon">🛒</div>
            <h2>Ваша корзина пуста</h2>
            <p>Добавьте товары из каталога, чтобы сделать заказ</p>
            <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <!-- Список товаров -->
            <div class="cart-items">
                <div class="cart-header">
                    <div class="header-product">Товар</div>
                    <div class="header-price">Цена</div>
                    <div class="header-quantity">Количество</div>
                    <div class="header-subtotal">Сумма</div>
                    <div class="header-actions">Действия</div>
                </div>
                
                <?php foreach($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-product">
                        <img src="images/<?php echo $item['image']; ?>" 
                             alt="<?php echo $item['name']; ?>" 
                             class="item-image">
                        <div class="item-info">
                            <h3 class="item-name"><?php echo $item['name']; ?></h3>
                            <?php if($item['quantity'] > $item['available_qty']): ?>
                            <p class="item-warning">В наличии: <?php echo $item['available_qty']; ?> шт.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="item-price">
                        <?php echo formatPrice($item['price']); ?>
                    </div>
                    
                    <div class="item-quantity">
                        <form method="GET" class="quantity-form">
                            <input type="hidden" name="update" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="quantity" 
                                    value="<?php echo max(1, $item['quantity'] - 1); ?>"
                                    class="quantity-btn minus">-</button>
                            
                            <input type="number" name="quantity" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['available_qty']; ?>"
                                   class="quantity-input"
                                   onchange="this.form.submit()">
                            
                            <button type="submit" name="quantity" 
                                    value="<?php echo min($item['available_qty'], $item['quantity'] + 1); ?>"
                                    class="quantity-btn plus"
                                    <?php echo $item['quantity'] >= $item['available_qty'] ? 'disabled' : ''; ?>>+</button>
                        </form>
                    </div>
                    
                    <div class="item-subtotal">
                        <?php echo formatPrice($item['subtotal']); ?>
                    </div>
                    
                    <div class="item-actions">
                        <a href="?remove=<?php echo $item['id']; ?>" 
                           class="remove-btn"
                           onclick="return confirm('Удалить товар из корзины?')">×</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Итого и оформление заказа -->
            <div class="cart-summary">
                <div class="summary-card">
                    <h3>Итого</h3>
                    
                    <div class="summary-total">
                        <span>К оплате</span>
                        <span class="total-amount"><?php echo formatPrice($total); ?></span>
                    </div>
                    
                    <form method="POST" action="process_order.php" id="orderForm" class="order-form">
                        <div class="form-group">
                            <label for="password" class="form-label">Подтвердите пароль для оформления заказа:</label>
                            <input type="password" id="password" name="password" required 
                                   class="form-input" placeholder="Введите ваш пароль">
                        </div>
                        
                        <button type="submit" class="btn-primary checkout-btn">
                            Оформить заказ
                        </button>
                    </form>
                    
                    <a href="catalog.php" class="continue-shopping">
                        ← Продолжить покупки
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// AJAX оформление заказа (альтернатива стандартной форме)
document.getElementById('orderForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const password = document.getElementById('password').value;
    
    if (!password) {
        alert('Пожалуйста, введите пароль');
        return;
    }
    
    // Показываем индикатор загрузки
    const submitBtn = this.querySelector('.checkout-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Оформляем заказ...';
    submitBtn.disabled = true;
    
    fetch('ajax/create_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ошибка сети');
        }
        return response.json();
    })
    .then(data => {
        if(data.success) {
            // Показываем успешное сообщение
            alert(data.message);
            // Перенаправляем на страницу заказов
            window.location.href = 'orders.php';
        } else {
            // Показываем ошибку
            alert('Ошибка: ' + data.message);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при оформлении заказа');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Проверка доступности товаров перед оформлением заказа
function validateCart() {
    let warnings = [];
    <?php foreach($cart_items as $item): ?>
        <?php if($item['quantity'] > $item['available_qty']): ?>
            warnings.push('Товар "<?php echo $item['name']; ?>" доступен в количестве <?php echo $item['available_qty']; ?> шт.');
        <?php endif; ?>
    <?php endforeach; ?>
    
    if (warnings.length > 0) {
        alert('Внимание:\n\n' + warnings.join('\n'));
        return false;
    }
    return true;
}

// При отправке формы проверяем товары
document.getElementById('orderForm')?.addEventListener('submit', function(e) {
    if (!validateCart()) {
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>