<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Зелёная лавка</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="index.php" class="logo"><div class="logo">Зелёная лавка</div></a>
            <nav class="main-nav">
                <a href="about.php">О нас</a>
                <a href="catalog.php">Каталог</a>
                <a href="cart.php" class="cart-link">
    Корзина 
    <?php if(isLoggedIn()): ?>
    <?php
        $user_id = $_SESSION['user_id'];
        $cart_count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
        $cart_count_result = mysqli_query($conn, $cart_count_query);
        $cart_count = mysqli_fetch_assoc($cart_count_result);
        $count = $cart_count['total'] ?: 0;
    ?>
    <?php if($count > 0): ?>
        <span id="cartCounter" class="cart-counter">(<?php echo $count; ?>)</span>
    <?php endif; ?>
    <?php endif; ?>
</a>
            </nav>
            <div class="header-actions">
                
<?php if(isAdmin()): ?>
<div class="admin-menu">
    <div class="admin-dropdown">
        <button class="admin-toggle">Админ-панель ▼</button>
        <div class="admin-dropdown-content">
            <a href="admin.php">Управление заказами</a>
            <a href="admin_products.php">Управление товарами</a>
            <a href="admin_product_add.php">Добавить товар</a>
        </div>
    </div>
</div>
<?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="profile-link"><img src="images\User Male.png" alt=""></a>

                <?php else: ?>
                    <a href="login.php" class="login-btn">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container">