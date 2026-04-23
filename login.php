<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный пароль';
        }
    } else {
        $error = 'Пользователь не найден';
    }
}
?>

<link rel="stylesheet" href="css/auth.css">
<h2 class="auth-title">Авторизация</h2>
<div class="auth-page">
    
    <div class="auth-container">
        
        
        <div class="auth-content">
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-fields">
                    <div class="form-group">
                        <label for="username" class="form-label">Логин или Email</label>
                        <input type="text" id="username" name="username" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" id="password" name="password" required 
                               class="form-input">
                    </div>
                    
                    <button type="submit" class="auth-button">Войти</button>
                    
                    <div class="auth-link">
                        Нет аккаунта? <a href="register.php">Создайте!</a>
                    </div>
                </div>
                
                <div class="form-image">
                    <img src="images\image 1.png" alt="Авторизация">
                </div>
            </form>
        </div>
    </div>
</div>
