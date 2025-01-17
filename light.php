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

// Funkcja do pobierania natężenia światła
function getLightLevel($conn) {
    $sql = "SELECT light_level FROM light LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['light_level'];
    }
    return null;
}

// Funkcja do pobierania trybu oświetlenia
function getLightMode($conn, $login) {
    $sql = "SELECT light_mode FROM users WHERE login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $login); // Bindowanie parametru
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        return $row['light_mode'];
    }
    return null;
}

// Funkcja do aktualizacji trybu oświetlenia
function updateLightMode($conn, $login, $mode) {
    $sql = "UPDATE users SET light_mode = ? WHERE login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $mode, $login); // Bindowanie parametrów
    return $stmt->execute();
}

$lightLevel = getLightLevel($conn);
$lightMode = getLightMode($conn, $_SESSION['login']); // Pobranie trybu oświetlenia dla zalogowanego użytkownika

// Sprawdzenie, czy formularz został przesłany
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['light_mode'])) {
    // Aktualizacja trybu oświetlenia w bazie danych
    updateLightMode($conn, $_SESSION['login'], intval($_POST['light_mode']));
    
    // Ponowne pobranie trybu oświetlenia po aktualizacji
    $lightMode = getLightMode($conn, $_SESSION['login']);
}

$conn->close(); // Zamknięcie połączenia z bazą danych

// Jeśli to jest zapytanie AJAX, zwróć dane w formacie JSON
if (isset($_GET['action']) && $_GET['action'] === 'get_light_level') {
    echo json_encode(['light_level' => $lightLevel]);
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
       <p>Aktualne natężenie światła na zewnątrz wynosi:
       <strong id="light-level"><?php echo $lightLevel !== null ? htmlspecialchars($lightLevel) : 'Brak danych'; ?></strong> 
       </p>
       
       <button type="button" onclick="refreshLightLevel()">Odśwież</button>

       <p>Aktualnie ustawiony tryb oświetlenia wynosi:
       <strong><?php echo $lightMode !== null ? htmlspecialchars($lightMode) : 'Brak danych'; ?></strong>
       </p>

       <form method="POST" action="">
           <label for="light_mode">Ustaw tryb oświetlenia:</label>
           <select name="light_mode" id="light_mode">
               <option value="0">0 - Tryb automatyczny</option>
               <?php for ($i = 1; $i <= 9; $i++): ?>
                   <option value="<?php echo $i; ?>"><?php echo "$i - Tryb manualny"; ?></option>
               <?php endfor; ?>
           </select>
           <button type="submit">Wyślij</button>
       </form>
       
   </div>

   <script>
       function refreshLightLevel() {
           fetch('light.php?action=get_light_level')
               .then(response => response.json())
               .then(data => {
                   document.getElementById('light-level').innerText = data.light_level !== null ? data.light_level : 'Brak danych';
               })
               .catch(error => console.error('Błąd podczas pobierania natężenia światła:', error));
       }
   </script>

    <footer>
       Andrzej Berndt 2024/2025
    </footer>

</body>
</html>