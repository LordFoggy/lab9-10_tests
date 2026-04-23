<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Получаем ID товара из URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: catalog.php');
    exit();
}

// Получаем данные товара
$query = "SELECT p.*, 
          c.name as category_name,
          co.name as country_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN countries co ON p.country_id = co.id
          WHERE p.id = $product_id";

$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header('Location: catalog.php');
    exit();
}

// Получаем похожие товары из той же категории
$similar_query = "SELECT * FROM products 
                  WHERE category_id = {$product['category_id']} 
                  AND id != $product_id 
                  AND quantity > 0
                  ORDER BY RAND() 
                  LIMIT 4";
$similar_result = mysqli_query($conn, $similar_query);

// Увеличиваем счетчик просмотров (опционально)
// $views_query = "UPDATE products SET views = views + 1 WHERE id = $product_id";
// mysqli_query($conn, $views_query);
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/product.css">

<div class="product-page">
    <div class="breadcrumbs">
        <a href="index.php">Главная</a> &gt;
        <a href="catalog.php">Каталог</a> &gt;
        <span><?php echo $product['name']; ?></span>
    </div>

    <div class="product-container">
        <!-- Левая колонка - изображение -->
        <div class="product-gallery">
        <div class="main-image">
            <?php if(!empty($product['image'])): ?>
                <img src="images/<?php echo $product['image']; ?>" 
                     alt="<?php echo $product['name']; ?>" 
                     id="mainProductImage">
            <?php else: ?>
                <img src="images/no-image.jpg" alt="Нет изображения">
            <?php endif; ?>
        </div>

        <div class="specs-table">
            
            
            <?php if($product['country_name']): ?>
            <div class="spec-row">
                <span class="spec-name">Страна:</span>
                <span class="spec-value"><?php echo $product['country_name']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($product['production_year']): ?>
            <div class="spec-row">
                <span class="spec-name">Год:</span>
                <span class="spec-value"><?php echo $product['production_year']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($product['producer']): ?>
            <div class="spec-row">
                <span class="spec-name">Производитель:</span>
                <span class="spec-value"><?php echo $product['producer']; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="spec-row">
                <span class="spec-name">Доступно:</span>
                <span class="spec-value"><?php echo $product['quantity']; ?>шт</span>
            </div>
        </div>
    </div>

        <!-- Центральная колонка - информация -->
        <div class="product-info">
            <h1 class="product-title"><?php echo $product['name']; ?> <?php if($product['weight']): ?>
            <div class="spec-row">
                <span class="spec-value">(<?php echo $product['weight']; ?>)</span>
            </div>
            <?php endif; ?></h1>

            <div class="product-price-section">
                <?php if($product['old_price'] && $product['old_price'] > $product['price']): ?>
                <div class="old-price">
                    <?php echo formatPrice($product['old_price']); ?>
                </div>
                <?php endif; ?>
                
                <div class="current-price">
                    <?php echo formatPrice($product['price']); ?>
                </div>
            </div>

            <?php if($product['quantity'] > 0): ?>
            <div class="product-actions">
                <?php if(isLoggedIn()): ?>
                <div class="quantity-selector">
                    <button type="button" onclick="decreaseQuantity()">-</button>
                    <input type="number" id="productQuantity" value="1" min="1" 
                           max="<?php echo $product['quantity']; ?>">
                    <button type="button" onclick="increaseQuantity()">+</button>
                
                </div>
                
                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                    <span>Добавить в корзину</span>
                </button>
                
                <button class="buy-now-btn" onclick="buyNow(<?php echo $product['id']; ?>)">
                    <span>Купить сейчас</span>
                </button>
                <?php else: ?>
                <a href="login.php" class="add-to-cart-btn" style="width: 100%; text-align: center;">
                    Войдите, чтобы добавить в корзину
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="out-of-stock-message">
                <p>Товар временно отсутствует на складе</p>
            </div>
            <?php endif; ?>

            <div class="product-description">
                <h3>Описание</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'] ?: 'Описание отсутствует')); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
// Функции для управления количеством
function increaseQuantity() {
    const input = document.getElementById('productQuantity');
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('productQuantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

// Добавление в корзину
function addToCart(productId) {
    const quantity = parseInt(document.getElementById('productQuantity').value);
    
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            product_id: productId,
            quantity: quantity 
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showNotification('Товар добавлен в корзину!', 'success');
            
            // Обновляем счетчик корзины в шапке
            if(data.cart_count !== undefined) {
                updateCartCount(data.cart_count);
            }
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка при добавлении в корзину', 'error');
    });
}

// Купить сейчас
function buyNow(productId) {
    const quantity = parseInt(document.getElementById('productQuantity').value);
    
    // Сначала добавляем в корзину
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            product_id: productId,
            quantity: quantity 
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Перенаправляем в корзину
            window.location.href = 'cart.php';
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    });
}

// Уведомить о поступлении
function notifyWhenAvailable(productId) {
    const email = prompt('Введите ваш email для уведомления:');
    if (email && email.includes('@')) {
        fetch('ajax/notify_availability.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                product_id: productId,
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showNotification('Мы уведомим вас о поступлении товара!', 'success');
            } else {
                showNotification('Ошибка: ' + data.message, 'error');
            }
        });
    }
}

// Поделиться товаром
function shareProduct(productName) {
    if (navigator.share) {
        navigator.share({
            title: productName,
            text: 'Посмотрите этот товар в Зеленой лавке!',
            url: window.location.href
        });
    } else {
        // Копируем ссылку в буфер обмена для старых браузеров
        navigator.clipboard.writeText(window.location.href);
        showNotification('Ссылка скопирована в буфер обмена!', 'success');
    }
}

// Всплывающие уведомления
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Анимация появления
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Удаление через 3 секунды
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Обновление счетчика корзины
function updateCartCount(count) {
    const cartCounter = document.getElementById('cartCounter');
    if (cartCounter) {
        cartCounter.textContent = count;
    }
}
</script>

<?php include 'includes/footer.php'; ?>