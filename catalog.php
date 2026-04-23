<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Параметры фильтрации
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 1;
$country_id = isset($_GET['country']) ? intval($_GET['country']) : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Формирование запроса
$query = "SELECT SQL_CALC_FOUND_ROWS p.*, c.name as country_name 
          FROM products p 
          LEFT JOIN countries c ON p.country_id = c.id 
          WHERE p.quantity > 0 ";

if ($category_id > 1) {
    $query .= " AND p.category_id = $category_id ";
}

if ($country_id > 1) {
    $query .= " AND p.country_id = $country_id ";
}

// Сортировка
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC ";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC ";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC ";
        break;
    case 'year':
        $query .= " ORDER BY p.production_year DESC ";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC ";
}

$query .= " LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

// Общее количество товаров для пагинации
$total_result = mysqli_query($conn, "SELECT FOUND_ROWS()");
$total_row = mysqli_fetch_row($total_result);
$total_products = $total_row[0];
$total_pages = ceil($total_products / $limit);

// Получаем категории и страны для фильтра
$categories = mysqli_query($conn, "SELECT * FROM categories");
$countries = mysqli_query($conn, "SELECT * FROM countries");
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="css/catalog.css">

<div class="catalog-page">
    <h1 class="page-title">Все товары</h1>
    
    <div class="catalog-container">
        <!-- Фильтр -->
        <div class="filters-sidebar">
            <h3>Категории</h3>
            <ul class="filter-list">
                <?php 
                // Сбрасываем указатель категорий
                mysqli_data_seek($categories, 0);
                while($cat = mysqli_fetch_assoc($categories)): ?>
                <li>
                    <label>
                        <input type="radio" name="category" value="<?php echo $cat['id']; ?>" 
                               onchange="updateFilters()" <?php echo $category_id == $cat['id'] ? 'checked' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </label>
                </li>
                <?php endwhile; ?>
            </ul>
            
            <h3>Страна</h3>
            <ul class="filter-list">
                <?php 
                // Сбрасываем указатель стран
                mysqli_data_seek($countries, 0);
                while($country = mysqli_fetch_assoc($countries)): ?>
                <li>
                    <label>
                        <input type="radio" name="country" value="<?php echo $country['id']; ?>" 
                               onchange="updateFilters()" <?php echo $country_id == $country['id'] ? 'checked' : ''; ?>>
                        <?php echo $country['name']; ?>
                    </label>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <!-- Товары -->
        <div class="products-container">
            <div class="sorting-bar">
                <div class="sort-dropdown">
                    <button class="sort-button">
                        <?php 
                        $sort_labels = [
                            'newest' => 'По новизне',
                            'price_asc' => 'По цене (возрастание)',
                            'price_desc' => 'По цене (убывание)',
                            'name' => 'По наименованию',
                            'year' => 'По году производства'
                        ];
                        echo $sort_labels[$sort];
                        ?>
                        ▼
                    </button>
                    <div class="sort-dropdown-content">
                        <button class="sort-option <?php echo $sort == 'newest' ? 'active' : ''; ?>" onclick="setSort('newest')">
                            По новизне
                        </button>
                        <button class="sort-option <?php echo $sort == 'price_asc' ? 'active' : ''; ?>" onclick="setSort('price_asc')">
                            По цене (возрастание)
                        </button>
                        <button class="sort-option <?php echo $sort == 'price_desc' ? 'active' : ''; ?>" onclick="setSort('price_desc')">
                            По цене (убывание)
                        </button>
                        <button class="sort-option <?php echo $sort == 'name' ? 'active' : ''; ?>" onclick="setSort('name')">
                            По наименованию
                        </button>
                        <button class="sort-option <?php echo $sort == 'year' ? 'active' : ''; ?>" onclick="setSort('year')">
                            По году производства
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="products-grid">
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($product = mysqli_fetch_assoc($result)): ?>
                        <div class="product-card">
                            <img src="images/<?php echo $product['image']; ?>" 
                                 alt="<?php echo $product['name']; ?>" 
                                 class="product-image">
                            <div class="product-info">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-name">
                                    <?php echo $product['name']; ?>
                                </a>
                                <?php if($product['weight']): ?>
                                <div class="product-weight">
                                    <span>(<?php echo $product['weight']; ?>)</span>
                                </div>
                                <?php endif; ?>
                                <div class="product-price">
                                    <?php if($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                    <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                                    <?php endif; ?>
                                    <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                                </div>
                                <?php if(isLoggedIn()): ?>
                                <button class="add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    Добавить в корзину
                                </button>
                                <?php else: ?>
                                <a href="login.php" class="add-to-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
                                    Войдите для покупки
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>Товары по вашему запросу не найдены</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Пагинация -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&country=<?php echo $country_id; ?>&sort=<?php echo $sort; ?>"
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateFilters() {
    const category = document.querySelector('input[name="category"]:checked').value;
    const country = document.querySelector('input[name="country"]:checked').value;
    const sort = '<?php echo $sort; ?>';
    
    window.location.href = `catalog.php?category=${category}&country=${country}&sort=${sort}`;
}

function setSort(sortValue) {
    const category = document.querySelector('input[name="category"]:checked').value;
    const country = document.querySelector('input[name="country"]:checked').value;
    
    window.location.href = `catalog.php?category=${category}&country=${country}&sort=${sortValue}`;
}

function addToCart(productId) {
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Товар добавлен в корзину');
            // Обновляем счетчик корзины
            if(data.cart_count !== undefined) {
                const cartCounter = document.getElementById('cartCounter');
                if (cartCounter) {
                    cartCounter.textContent = data.cart_count;
                }
            }
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при добавлении в корзину');
    });
}
</script>

<?php include 'includes/footer.php'; ?>