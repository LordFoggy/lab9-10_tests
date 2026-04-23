<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Получаем данные пользователя
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Добавляем поле аватарки в таблицу пользователей если его нет
$check_avatar = "SHOW COLUMNS FROM users LIKE 'avatar'";
$check_result = mysqli_query($conn, $check_avatar);
if (mysqli_num_rows($check_result) == 0) {
    $add_avatar = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL";
    mysqli_query($conn, $add_avatar);
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($new_password) < 6) {
        $error = 'Новый пароль должен содержать минимум 6 символов';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Текущий пароль указан неверно';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = 'Пароль успешно изменен';
        } else {
            $error = 'Ошибка при изменении пароля: ' . mysqli_error($conn);
        }
    }
}

// Обработка загрузки аватарки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Создаем папку avatars если ее нет
            if (!file_exists('avatars')) {
                mkdir('avatars', 0777, true);
            }
            
            // Удаляем старую аватарку если есть
            if (!empty($user['avatar']) && file_exists('avatars/' . $user['avatar'])) {
                unlink('avatars/' . $user['avatar']);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $avatar_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = 'avatars/' . $avatar_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // Обновляем путь к аватарке в базе
                $update_query = "UPDATE users SET avatar = '$avatar_name' WHERE id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $user['avatar'] = $avatar_name;
                    $success = 'Аватар успешно обновлен';
                }
            } else {
                $error = 'Ошибка при загрузке файла';
            }
        } else {
            $error = 'Недопустимый формат изображения';
        }
    }
}

// Обработка удаления профиля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'];
    
    if ($confirm_delete == 'УДАЛИТЬ') {
        // Удаляем аватарку если есть
        if (!empty($user['avatar']) && file_exists('avatars/' . $user['avatar'])) {
            unlink('avatars/' . $user['avatar']);
        }
        
        // Удаляем пользователя из базы
        $delete_query = "DELETE FROM users WHERE id = $user_id";
        if (mysqli_query($conn, $delete_query)) {
            // Выходим из системы и перенаправляем на главную
            session_destroy();
            header('Location: index.php');
            exit();
        } else {
            $error = 'Ошибка при удалении профиля';
        }
    } else {
        $error = 'Для удаления аккаунта введите слово "УДАЛИТЬ"';
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/profile.css">

<div class="profile-page">
    <div class="profile-container">
        <!-- Левая колонка - информация профиля -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if(!empty($user['avatar'])): ?>
                        <img src="avatars/<?php echo $user['avatar']; ?>" 
                             alt="<?php echo $user['first_name']; ?>"
                             class="avatar-image">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="avatar-form">
                        <label for="avatar" class="avatar-upload">
                            <input type="file" id="avatar" name="avatar" accept="image/*" 
                                   onchange="this.form.submit()">
                            <span class="upload-text">Изменить фото</span>
                        </label>
                    </form>
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-name">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </h2>
                    <p class="profile-role">
                        <?php echo $user['role'] == 'admin' ? 'Администратор' : 'Пользователь'; ?>
                    </p>
                </div>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
            
            <nav class="profile-nav">
    <a href="profile.php" class="nav-link active">
        <span class="nav-icon">👤</span>
        <span>Профиль</span>
    </a>
    <a href="orders.php" class="nav-link">
        <span class="nav-icon">📦</span>
        <span>Мои заказы</span>
    </a>
</nav>
        </div>
        
        <!-- Правая колонка - настройки -->
        <div class="profile-content">
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-sections">
                <!-- Личная информация -->
                <section class="profile-section">
                    <h2 class="section-title">Личная информация</h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Имя:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Фамилия:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['last_name']); ?></span>
                        </div>
                        <?php if(!empty($user['patronymic'])): ?>
                        <div class="info-item">
                            <span class="info-label">Отчество:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['patronymic']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Логин:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Дата регистрации:</span>
                            <span class="info-value">
                                <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </section>
                
                <!-- Смена пароля -->
                <section class="profile-section">
                    <h2 class="section-title">Смена пароля</h2>
                    
                    <form method="POST" class="password-form">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-input" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Повторите новый пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-input" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn-primary">Изменить пароль</button>
                    </form>
                </section>
                
                <!-- Удаление аккаунта -->
                <section class="profile-section danger-section">
                    <h2 class="section-title danger-title">Удаление аккаунта</h2>
                    <p class="danger-text">
                        Внимание! Удаление аккаунта необратимо. Все ваши данные, заказы и история будут удалены.
                    </p>
                    
                    <form method="POST" class="delete-form" onsubmit="return confirmDelete()">
                        <input type="hidden" name="delete_account" value="1">
                        
                        <div class="form-group">
                            <label for="confirm_delete" class="form-label">
                                Для подтверждения введите слово <strong>УДАЛИТЬ</strong>
                            </label>
                            <input type="text" id="confirm_delete" name="confirm_delete" 
                                   class="form-input" placeholder="УДАЛИТЬ">
                        </div>
                        
                        <button type="submit" class="btn-danger">Удалить аккаунт</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    const input = document.getElementById('confirm_delete').value;
    if (input !== 'УДАЛИТЬ') {
        alert('Для удаления аккаунта введите слово "УДАЛИТЬ" в поле подтверждения');
        return false;
    }
    
    return confirm('Вы уверены, что хотите удалить аккаунт? Это действие необратимо!');
}

// Предпросмотр аватарки перед загрузкой
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-image') || 
                           document.querySelector('.avatar-placeholder');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Если был placeholder, заменяем его на изображение
                const newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.className = 'avatar-image';
                preview.parentNode.replaceChild(newImg, preview);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/footer.php'; ?>