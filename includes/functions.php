<?php
require_once 'config.php';

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Получение данных пользователя
function getUser($id) {
    global $conn;
    $query = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Форматирование цены
function formatPrice($price) {
    return number_format($price, 0, '', ' ') . 'р';
}

function escape($value) {
    global $conn;
    if ($value === null || trim($value) === '') {
        return '';
    }
    return mysqli_real_escape_string($conn, trim($value));
}


?>