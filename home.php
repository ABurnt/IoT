<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['zalogowany'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="clock.js"></script>
    <title>Strona Domowa</title>

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
	   <p id="date-info">Dzisiaj jest ...</p>
    </div>

   <footer>
       Andrzej Berndt 2024/2025
   </footer>

</body>
</html>
