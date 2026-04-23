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

<style>
.admin-menu {
    margin-left: 20px;
}

.admin-dropdown {
    position: relative;
    display: inline-block;
}

.admin-toggle {
    background: #2D6E45;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.admin-dropdown-content {
    display: none;
    position: absolute;
    background: white;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    border-radius: 5px;
    z-index: 1000;
}

.admin-dropdown:hover .admin-dropdown-content {
    display: block;
}

.admin-dropdown-content a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #725E53;
    border-bottom: 1px solid #eee;
}

.admin-dropdown-content a:hover {
    background: #f5f5f5;
    color: #2D6E45;
}
</style>
<?php endif; ?>