<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<div class="contacts-page">
    <h1 class="page-title">Где нас найти?</h1>
    
    <div class="contacts-container">
        <div class="contact-info">
            <h2>Контактная информация</h2>
            <div class="contact-item">
                <h3>Адрес:</h3>
                <p>г. Москва, ул. Садовая, д. 123</p>
            </div>
            <div class="contact-item">
                <h3>Телефон:</h3>
                <p>+7 (999) 123-45-67</p>
            </div>
            <div class="contact-item">
                <h3>Email:</h3>
                <p>info@green-shop.ru</p>
            </div>
            <div class="contact-item">
                <h3>Режим работы:</h3>
                <p>Пн-Пт: 9:00 - 20:00<br>Сб-Вс: 10:00 - 18:00</p>
            </div>
        </div>
        
        <div class="map-container">
            <iframe src="https://yandex.ru/map-widget/v1/?um=constructor%3A1a2b3c4d5e6f7g8h9i0j&amp;source=constructor" 
                    width="100%" height="500" frameborder="0"></iframe>
        </div>
    </div>
</div>

<style>
.contacts-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-title {
    text-align: center;
    font-size: 48px;
    color: #725E53;
    margin-bottom: 40px;
}

.contacts-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 40px;
    margin-top: 40px;
}

.contact-info {
    background: #EDEDE3;
    box-shadow: 4px 4px 9px 2px #725E53;
    border-radius: 10px;
    padding: 30px;
}

.contact-info h2 {
    color: #725E53;
    margin-bottom: 30px;
    font-size: 28px;
}

.contact-item {
    margin-bottom: 25px;
}

.contact-item h3 {
    color: #2D6E45;
    font-size: 18px;
    margin-bottom: 5px;
}

.contact-item p {
    color: #725E53;
    font-size: 16px;
}

.map-container {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>

<?php include 'includes/footer.php'; ?>