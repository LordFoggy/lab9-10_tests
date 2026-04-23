<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: admin_products.php');
    exit();
}

// Получаем данные товара
$product_query = "SELECT * FROM products WHERE id = $product_id";
$product_result = mysqli_query($conn, $product_query);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    header('Location: admin_products.php');
    exit();
}

// Получаем категории и страны
$categories_query = "SELECT * FROM categories WHERE id > 1";
$categories_result = mysqli_query($conn, $categories_query);

$countries_query = "SELECT * FROM countries WHERE id > 1";
$countries_result = mysqli_query($conn, $countries_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Всегда получаем значения из POST, даже если они пустые
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $old_price = $_POST['old_price'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $country_id = $_POST['country_id'] ?? '';
    $production_year = $_POST['production_year'] ?? date('Y');
    $producer = $_POST['producer'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $is_promotional = isset($_POST['is_promotional']) ? 1 : 0;
    
    // Экранируем строки
    $name = escape($name);
    $description = escape($description);
    $weight = escape($weight);
    $producer = escape($producer);
    
    // Преобразуем числовые значения
    $price = floatval($price);
    $old_price_value = !empty($old_price) ? floatval($old_price) : NULL;
    $country_id = !empty($country_id) ? intval($country_id) : NULL;
    $production_year = intval($production_year);
    $category_id = intval($category_id);
    
    // Валидация
    if (empty($name)) {
        $error = 'Название товара обязательно для заполнения';
    } elseif ($price <= 0) {
        $error = 'Цена должна быть больше 0';
    } elseif ($old_price_value !== NULL && $old_price_value <= $price) {
        $error = 'Старая цена должна быть больше текущей цены';
    } elseif ($category_id <= 1) {
        $error = 'Необходимо выбрать категорию';
    }
    
    // Проверка года производства
    $current_year = date('Y');
    if ($production_year < 2000 || $production_year > ($current_year + 1)) {
        $error = 'Год производства должен быть между 2000 и ' . ($current_year + 1);
    }
    
    // Обработка загрузки изображения
    $image_name = $product['image'];
    if (empty($error) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Удаляем старое изображение
            if (!empty($image_name) && file_exists('images/' . $image_name)) {
                unlink('images/' . $image_name);
            }
            
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('product_') . '.' . $file_ext;
            $upload_path = 'images/' . $image_name;
            
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Размер изображения слишком большой (максимум 5MB)';
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $error = 'Ошибка при загрузке изображения';
            }
        } else {
            $error = 'Недопустимый формат изображения';
        }
    }
    
    if (empty($error)) {
        // Формируем SQL-запрос с правильной обработкой NULL
        $query = "UPDATE products SET 
                 name = '$name',
                 description = '$description',
                 price = $price,
                 old_price = " . ($old_price_value !== NULL ? $old_price_value : "NULL") . ",
                 weight = '$weight',
                 country_id = " . ($country_id !== NULL ? $country_id : "NULL") . ",
                 production_year = $production_year,
                 producer = '$producer',
                 category_id = $category_id,
                 is_promotional = $is_promotional,
                 image = '$image_name'
                 WHERE id = $product_id";
        
        // Отладка запроса
        // echo "SQL Query: " . $query; // Раскомментируйте для отладки
        
        if (mysqli_query($conn, $query)) {
            $success = 'Товар успешно обновлен!';
            // Обновляем данные товара
            $product_result = mysqli_query($conn, $product_query);
            $product = mysqli_fetch_assoc($product_result);
        } else {
            $error = 'Ошибка при обновлении товара: ' . mysqli_error($conn);
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css\admin.css">
<div class="admin-page">
    <h1 class="page-title">Редактировать товар</h1>
    
    <div class="admin-form-container">
        <a href="admin_products.php" class="btn-secondary" style="margin-bottom: 20px;">
            ← Назад к списку товаров
        </a>
        
        <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="product-form">
            <div class="form-preview">
                <?php if(!empty($product['image'])): ?>
                <div class="current-image">
                    <img src="images/<?php echo $product['image']; ?>" 
                         alt="<?php echo $product['name']; ?>">
                    <p>Текущее изображение</p>
                </div>
                <?php endif; ?>
            </div>
            
            <input type="hidden" name="form_submitted" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Название товара *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($product['name']); ?>"
                           class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Цена (₽) *</label>
                    <input type="number" id="price" name="price" required step="0.01" min="0.01" max="10000000"
                           value="<?php echo $product['price']; ?>"
                           class="form-input">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="old_price" class="form-label">Старая цена (₽)</label>
                    <input type="number" id="old_price" name="old_price" step="0.01" min="0" max="10000000"
                           value="<?php echo $product['old_price'] ? $product['old_price'] : ''; ?>"
                           class="form-input" placeholder="Для акционных товаров">
                </div>
                
                <div class="form-group">
                    <label for="weight" class="form-label">Вес/объем</label>
                    <input type="text" id="weight" name="weight" 
                           value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>"
                           class="form-input" placeholder="Например: 700г, 1.5л, 10м">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Описание товара</label>
                <textarea id="description" name="description" rows="4" 
                          class="form-input"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id" class="form-label">Категория *</label>
                    <select id="category_id" name="category_id" required class="form-input">
                        <option value="">Выберите категорию</option>
                        <?php 
                        $categories_result = mysqli_query($conn, "SELECT * FROM categories WHERE id > 1");
                        while($category = mysqli_fetch_assoc($categories_result)): 
                        ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="country_id" class="form-label">Страна производства</label>
                    <select id="country_id" name="country_id" class="form-input">
                        <option value="">Не указана</option>
                        <?php 
                        $countries_result = mysqli_query($conn, "SELECT * FROM countries WHERE id > 1");
                        while($country = mysqli_fetch_assoc($countries_result)): 
                        ?>
                        <option value="<?php echo $country['id']; ?>"
                                <?php echo ($product['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                            <?php echo $country['name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="production_year" class="form-label">Год производства</label>
                    <input type="number" id="production_year" name="production_year" 
                           min="2000" max="<?php echo date('Y') + 1; ?>"
                           value="<?php echo $product['production_year']; ?>"
                           class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="producer" class="form-label">Производитель</label>
                    <input type="text" id="producer" name="producer" 
                           value="<?php echo htmlspecialchars($product['producer'] ?? ''); ?>"
                           class="form-input" placeholder="Название производителя">
                </div>
            </div>
            
            <div class="form-group">
                <label for="image" class="form-label">Новое изображение</label>
                <input type="file" id="image" name="image" accept="image/*" class="form-input">
                <small>Оставьте пустым, чтобы сохранить текущее изображение</small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_promotional" name="is_promotional" value="1"
                           <?php echo $product['is_promotional'] ? 'checked' : ''; ?>>
                    <span>Акционный товар</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Сохранить изменения</button>
                <a href="admin_products.php" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<script>
// Валидация формы
document.querySelector('form').addEventListener('submit', function(e) {
    const price = parseFloat(document.getElementById('price').value);
    const oldPrice = document.getElementById('old_price').value;
    
    if (oldPrice && parseFloat(oldPrice) <= price) {
        e.preventDefault();
        alert('Старая цена должна быть больше текущей цены');
        return false;
    }
    
    const fileInput = document.getElementById('image');
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('Размер изображения превышает 5MB');
            return false;
        }
    }
    
    return true;
});
</script>

<?php include 'includes/footer.php'; ?>