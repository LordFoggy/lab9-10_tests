<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Получаем 5 последних добавленных товаров
$latest_products_query = "SELECT p.*, c.name as country_name 
                          FROM products p 
                          LEFT JOIN countries c ON p.country_id = c.id 
                          WHERE p.quantity > 0 
                          ORDER BY p.created_at DESC 
                          LIMIT 5";
$latest_result = mysqli_query($conn, $latest_products_query);
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/about.css">

<div class="about-simple-page">
    <!-- Простой блок с информацией -->
    <div class="about-simple-container">
        <h1 class="about-simple-title">О компании "Зелёная лавка"</h1>
        <div class="about-divider"></div>
        
        <div class="about-simple-content">
            <p><strong>Зелёная лавка</strong> — это интернет-магазин товаров для сада и огорода, где каждый найдет всё необходимое для создания идеального сада.</p>
            
            <h2>Наша миссия</h2>
            <p>Сделать садоводство доступным и приятным занятием для каждого. Мы стремимся помочь нашим клиентам создавать красивые и ухоженные сады, предоставляя качественные товары и полезные советы.</p>
            
            <h2>Что мы предлагаем</h2>
            <ul>
                <li>Широкий ассортимент растений и саженцев</li>
                <li>Качественные удобрения и грунты</li>
                <li>Садовая техника и инструменты</li>
                <li>Сопутствующие товары для ухода за растениями</li>
                <li>Профессиональные консультации по садоводству</li>
            </ul>
            
            <h2>Наши ценности</h2>
            <p><strong>Качество</strong> — все товары проходят тщательный отбор.<br>
            <strong>Доступность</strong> — разумные цены и регулярные акции.<br>
            <strong>Экологичность</strong> — безопасные для природы материалы и методы.<br>
            <strong>Поддержка</strong> — помощь на всех этапах, от выбора до ухода.</p>
            
            <h2>Контакты</h2>
            <p>Если у вас есть вопросы или нужна помощь в выборе товаров, обращайтесь:</p>
            <div class="contact-info">
                <p>📞 Телефон: 8 (800) 123-45-67</p>
                <p>📧 Email: info@green-shop.ru</p>
                <p>📍 Адрес: г. Москва, ул. Садовая, д. 12</p>
            </div>
            
            <p class="about-slogan">"Выращено с заботой - упаковано с любовью!"</p>
        </div>
    </div>
    
    <!-- Блок с последними товарами -->
    <?php if(mysqli_num_rows($latest_result) > 0): ?>
    <div class="latest-products-section">
        <h2 class="section-title">Последние добавленные товары</h2>
        <div class="products-grid">
            <?php while($product = mysqli_fetch_assoc($latest_result)): ?>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-link">
                    <div class="product-card">
                        <img src="images/<?php echo $product['image']; ?>" 
                             alt="<?php echo $product['name']; ?>" 
                             class="product-image">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $product['name']; ?></h3>
                            <div class="product-weight">
                                <?php if($product['weight']): ?>
                                <span>(<?php echo $product['weight']; ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-price">
                                <?php if($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                                <?php endif; ?>
                                <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="catalog.php" class="btn-primary">Смотреть весь каталог</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>