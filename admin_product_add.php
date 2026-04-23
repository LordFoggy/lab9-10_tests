<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

// Получаем категории и страны для выпадающих списков
$categories_query = "SELECT * FROM categories WHERE id > 1";
$categories_result = mysqli_query($conn, $categories_query);

$countries_query = "SELECT * FROM countries WHERE id > 1";
$countries_result = mysqli_query($conn, $countries_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = escape($_POST['name']);
    $description = escape($_POST['description']);
    $price = floatval($_POST['price']);
    $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : NULL;
    $weight = escape($_POST['weight']);
    $country_id = intval($_POST['country_id']);
    $production_year = intval($_POST['production_year']);
    $producer = escape($_POST['producer']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);
    $is_promotional = isset($_POST['is_promotional']) ? 1 : 0;
    
    // Обработка загрузки изображения
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('product_') . '.' . $file_ext;
            $upload_path = 'images/' . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $error = 'Ошибка при загрузке изображения';
            }
        } else {
            $error = 'Недопустимый формат изображения. Разрешены: JPG, PNG, GIF';
        }
    }
    
    if (empty($error)) {
        $query = "INSERT INTO products (name, description, price, old_price, weight, 
                  country_id, production_year, producer, quantity, category_id, 
                  is_promotional, image) 
                  VALUES ('$name', '$description', $price, " . 
                  ($old_price ? $old_price : "NULL") . ", '$weight', $country_id, 
                  $production_year, '$producer', $quantity, $category_id, 
                  $is_promotional, '$image_name')";
        
        if (mysqli_query($conn, $query)) {
            $product_id = mysqli_insert_id($conn);
            
            // Запись в историю склада
            $admin_id = $_SESSION['user_id'];
            $history_query = "INSERT INTO stock_history (product_id, action, quantity_change, 
                             old_quantity, new_quantity, reason, admin_id) 
                             VALUES ($product_id, 'add', $quantity, 0, $quantity, 
                             'Первоначальное добавление товара', $admin_id)";
            mysqli_query($conn, $history_query);
            
            $success = 'Товар успешно добавлен! ID: ' . $product_id;
            
            // Очистка формы после успешного добавления
            $_POST = array();
        } else {
            $error = 'Ошибка при добавлении товара: ' . mysqli_error($conn);
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css\admin.css">
<div class="admin-page">
    <h1 class="page-title">Добавить новый товар</h1>
    
    <div class="admin-form-container">
        <a href="admin_products.php" class="btn-secondary" style="margin-bottom: 20px;">
            ← Назад к списку товаров
        </a>
        
        <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <a href="admin_product_add.php" class="btn-small">Добавить еще</a>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="product-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Название товара *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           class="form-input" placeholder="Введите название товара">
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Цена (₽) *</label>
                    <input type="number" id="price" name="price" required step="0.01" min="0"
                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>"
                           class="form-input" placeholder="0.00">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="old_price" class="form-label">Старая цена (₽)</label>
                    <input type="number" id="old_price" name="old_price" step="0.01" min="0"
                           value="<?php echo isset($_POST['old_price']) ? $_POST['old_price'] : ''; ?>"
                           class="form-input" placeholder="Для акционных товаров">
                </div>
                
                <div class="form-group">
                    <label for="quantity" class="form-label">Количество на складе *</label>
                    <input type="number" id="quantity" name="quantity" required min="0"
                           value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : '0'; ?>"
                           class="form-input">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Описание товара</label>
                <textarea id="description" name="description" rows="4" 
                          class="form-input" placeholder="Подробное описание товара..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id" class="form-label">Категория *</label>
                    <select id="category_id" name="category_id" required class="form-input">
                        <option value="">Выберите категорию</option>
                        <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="country_id" class="form-label">Страна производства</label>
                    <select id="country_id" name="country_id" class="form-input">
                        <option value="">Выберите страну</option>
                        <?php while($country = mysqli_fetch_assoc($countries_result)): ?>
                        <option value="<?php echo $country['id']; ?>"
                                <?php echo (isset($_POST['country_id']) && $_POST['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                            <?php echo $country['name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="weight" class="form-label">Вес/объем</label>
                    <input type="text" id="weight" name="weight" 
                           value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : ''; ?>"
                           class="form-input" placeholder="Например: 700г, 1.5л, 10м">
                </div>
                
                <div class="form-group">
                    <label for="production_year" class="form-label">Год производства</label>
                    <input type="number" id="production_year" name="production_year" 
                           min="2000" max="<?php echo date('Y') + 1; ?>"
                           value="<?php echo isset($_POST['production_year']) ? $_POST['production_year'] : date('Y'); ?>"
                           class="form-input">
                </div>
            </div>
            
            <div class="form-group">
                <label for="producer" class="form-label">Производитель</label>
                <input type="text" id="producer" name="producer" 
                       value="<?php echo isset($_POST['producer']) ? htmlspecialchars($_POST['producer']) : ''; ?>"
                       class="form-input" placeholder="Название производителя">
            </div>
            
            <div class="form-group">
                <label for="image" class="form-label">Изображение товара</label>
                <input type="file" id="image" name="image" accept="image/*" class="form-input">
                <small>Рекомендуемый размер: 500x500px, формат: JPG, PNG, GIF</small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_promotional" name="is_promotional" value="1"
                           <?php echo (isset($_POST['is_promotional']) && $_POST['is_promotional']) ? 'checked' : ''; ?>>
                    <span>Акционный товар</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Добавить товар</button>
                <button type="reset" class="btn-secondary">Очистить форму</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>