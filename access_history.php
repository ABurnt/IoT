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

// Funkcja do pobierania historii dostępu
function getAccessHistory($conn, $login, $timeframe) {
    $sql = "SELECT access_history.date, access_history.direction 
            FROM access_history 
            JOIN rfid ON access_history.rfid_id = rfid.card_id 
            JOIN users ON rfid.user_id = users.id 
            WHERE users.login = ?";

    // Filtrowanie rekordów
    if ($timeframe === '24h') {
        $sql .= " AND access_history.date >= NOW() - INTERVAL 1 DAY";
    } elseif ($timeframe === '2d') {
        $sql .= " AND access_history.date >= NOW() - INTERVAL 2 DAY";
    } elseif ($timeframe === '3d') {
        $sql .= " AND access_history.date >= NOW() - INTERVAL 3 DAY";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $login); // Bindowanie parametru
    $stmt->execute();
    return $stmt->get_result();
}

// Sprawdzenie, czy użytkownik wybrał filtr
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : null;
$accessHistory = getAccessHistory($conn, $_SESSION['login'], $timeframe); // Pobranie historii dostępu
$conn->close(); // Zamknięcie połączenia z bazą danych
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="clock.js"></script>
    <title>Historia Dostępu</title>
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
       <h2>Historia dostępu</h2>

    <form method="GET" action="">
        <label for="timeframe">Filtruj rekordy:</label>
        <select name="timeframe" id="timeframe">
            <option value="">Wszystkie</option>
            <option value="24h" <?php echo (isset($_GET['timeframe']) && $_GET['timeframe'] == '24h') ? 'selected' : ''; ?>>Ostatnie 24 godziny</option>
            <option value="2d" <?php echo (isset($_GET['timeframe']) && $_GET['timeframe'] == '2d') ? 'selected' : ''; ?>>Ostatnie 48 godzin</option>
            <option value="3d" <?php echo (isset($_GET['timeframe']) && $_GET['timeframe'] == '3d') ? 'selected' : ''; ?>>Ostatnie 3 dni</option>
        </select>
        <button type="submit">Filtruj</button>
    </form>

       <table>
           <thead>
               <tr>
                   <th>Data</th>
                   <th>Kierunek</th>
               </tr>
           </thead>
           <tbody>
               <?php if ($accessHistory && $accessHistory->num_rows > 0): ?>
                   <?php while ($row = $accessHistory->fetch_assoc()): ?>
                       <tr>
                           <td><?php echo htmlspecialchars($row['date']); ?></td>
                           <td><?php echo htmlspecialchars($row['direction']); ?></td>
                       </tr>
                   <?php endwhile; ?>
               <?php else: ?>
                   <tr><td colspan="2">Brak historii dostępu.</td></tr>
               <?php endif; ?>
           </tbody>
       </table>

        <form method="GET" action="">
            <input type="hidden" name="timeframe" value="<?php echo htmlspecialchars($timeframe); ?>">
            <button type="submit">Odśwież historię dostępu</button>
        </form>

   </div>

    <footer>
       Andrzej Berndt 2024/2025
    </footer>

</body>
</html>
