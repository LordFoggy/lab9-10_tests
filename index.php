<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Получаем акционные товары
$query = "SELECT p.*, c.name as country_name 
          FROM products p 
          LEFT JOIN countries c ON p.country_id = c.id 
          WHERE p.is_promotional = 1 AND p.quantity > 0 
          ORDER BY p.created_at DESC 
          LIMIT 4";
$promo_result = mysqli_query($conn, $query);
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero-section">
    <img src="images/Rectangle 7.png" alt="Зелёная лавка" class="hero-image">
    <div class="hero-text-container">
        <div class="hero-text">
            Выращено с заботой - упаковано с любовью! Ваш идеальный сад начинается здесь
        </div>
    </div>
</div>

<div class="promotional-section">
    <h2 class="section-title">Акционные товары</h2>
    
    <div class="products-grid">
        <?php while($product = mysqli_fetch_assoc($promo_result)): ?>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-link">
                <div class="product-card">
                    <img src="images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $product['name']; ?></h3>
                        <div class="product-weight">
                            <?php if($product['weight']): ?>
                            <span>(<?php echo $product['weight']; ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-price">
                            <?php if($product['old_price']): ?>
                            <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                            <?php endif; ?>
                            <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <?php if(isLoggedIn()): ?>
                        <button class="add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                            Добавить в корзину
                        </button>
                        <?php else: ?>
                        <a href="login.php" class="add-to-cart" style="display: block; text-align: center; text-decoration: none;">
                            Войдите для покупки
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
    
    
</div>
<div class="catalog-link-container">
        <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
    </div>
<?php include 'includes/footer.php'; ?>