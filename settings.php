<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['zalogowany'])) {
    header('Location: index.php');
    exit();
}

// Ustawienia bazy danych
require "connect.php";

// Połączenie z bazą danych
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}

// Funkcja do sprawdzania stanu powiadomień e-mail
function getEmailNotifications($conn, $login) {
    $sql = "SELECT notification.mail FROM users JOIN notification ON users.id = notification.user_id WHERE users.login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result && $row = $result->fetch_assoc()) ? (bool)$row['mail'] : false; // Zwraca true jeśli mail = 1
}

// Funkcja do aktualizacji stanu powiadomień e-mail
function updateEmailNotifications($conn, $login, $enabled) {
    // Ustal id użytkownika
    $sqlUserId = "SELECT id FROM users WHERE login = ?";
    $stmtUserId = $conn->prepare($sqlUserId);
    $stmtUserId->bind_param("s", $login);
    $stmtUserId->execute();
    $resultUserId = $stmtUserId->get_result();
    
    if ($rowUserId = $resultUserId->fetch_assoc()) {
        // Aktualizuj stan powiadomień
        $mailValue = $enabled ? 1 : 0; // 1 jeśli zaznaczony, 0 jeśli odznaczony
        $sqlUpdateNotification = "UPDATE notification SET mail = ? WHERE user_id = ?";
        $stmtUpdateNotification = $conn->prepare($sqlUpdateNotification);
        $stmtUpdateNotification->bind_param("ii", $mailValue, $rowUserId['id']);
        return $stmtUpdateNotification->execute();
    }
    
    return false;
}

$emailNotificationsEnabled = getEmailNotifications($conn, $_SESSION['login']);

// Sprawdzenie, czy formularz został przesłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['email_notifications']) && $_POST['email_notifications'] === '1';
    updateEmailNotifications($conn, $_SESSION['login'], $enabled);
    // Ponowne sprawdzenie stanu powiadomień po aktualizacji
    $emailNotificationsEnabled = getEmailNotifications($conn, $_SESSION['login']);
}


$conn->close(); // Zamknięcie połączenia z bazą danych
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="clock.js"></script>
    <title>Oświetlenie</title>
</head>
<body>
<h1>Witaj, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>

<div class="clock-container">
    <div class="hand hour-hand"></div>
    <div class="hand minute-hand"></div>
    <div class="hand second-hand"></div>
</div>
<div class="menu">
    <div class="dropdown">
        <button class="dropbtn">Menu</button>
        <div class="dropdown-content">
            <a href="home.php">Strona główna</a>
            <a href="light.php">Oświetlenie</a>
            <a href="users.php">Użytkownicy</a>
            <a href="cards.php">Karty</a>
            <a href="access_history.php">Historia dostępu</a>
            <a href="settings.php">Ustawienia</a>
            <a href="logout.php">Wyloguj</a>
        </div>
    </div>
</div>

<div class="content-container">

    <form method="POST" action="">
        <label for="email_notifications">Włącz powiadomienia email:</label>
        <input type="checkbox" id="email_notifications" name="email_notifications" value="1" <?php echo ($emailNotificationsEnabled ? 'checked' : ''); ?>>
        <button type="submit">Zapisz</button>
    </form>

</div>

<footer>
   Andrzej Berndt 2024/2025
</footer>

</body>
</html>
