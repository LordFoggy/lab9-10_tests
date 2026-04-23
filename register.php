<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = escape($_POST['first_name']);
    $last_name = escape($_POST['last_name']);
    $patronymic = escape($_POST['patronymic']);
    $username = escape($_POST['username']);
    $email = escape($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Все обязательные поля должны быть заполнены';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        // Проверка существующего пользователя
        $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            // Хеширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Вставка пользователя
            $insert_query = "INSERT INTO users (first_name, last_name, patronymic, username, email, password) 
                            VALUES ('$first_name', '$last_name', '$patronymic', '$username', '$email', '$hashed_password')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка при регистрации: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<link rel="stylesheet" href="css/auth.css">
<h2 class="auth-title">Регистрация</h2>
<div class="auth-page">
    <div class="auth-container">
        
        
        <div class="auth-content">
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-fields">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Имя</label>
                        <input type="text" id="first_name" name="first_name" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Фамилия</label>
                        <input type="text" id="last_name" name="last_name" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="patronymic" class="form-label">Отчество</label>
                        <input type="text" id="patronymic" name="patronymic" 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Логин</label>
                        <input type="text" id="username" name="username" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" id="password" name="password" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Повторите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="agree" name="agree" required class="checkbox-input">
                        <label for="agree" class="checkbox-label">
                            Я принимаю условия политики пользовательского соглашения
                        </label>
                    </div>
                    
                    <button type="submit" class="auth-button">Зарегистрироваться</button>
                    
                    <div class="auth-link">
                        Есть аккаунт? <a href="login.php">Войдите!</a>
                    </div>
                </div>
                
                <div class="form-image">
                    <img src="images\image 1.png" alt="Регистрация">
                </div>
            </form>
        </div>
    </div>
</div>
