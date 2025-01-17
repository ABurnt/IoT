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

// Funkcja do pobierania kart RFID
function getCards($conn) {
    $sql = "SELECT rfid.card_id, rfid.card_name, users.name AS user_name, users.lastname AS user_lastname 
            FROM rfid 
            LEFT JOIN users ON users.id = rfid.user_id";
    return $conn->query($sql);
}

// Funkcja do usuwania karty RFID
function deleteCard($conn, $cardId) {
    $sql = "DELETE FROM rfid WHERE card_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cardId); // Bindowanie parametru
    return $stmt->execute();
}

// Funkcja do dodawania karty RFID
function addCard($conn, $cardId, $cardName) {
    $sql = "INSERT INTO rfid (card_id, card_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $cardId, $cardName); // Bindowanie parametrów
    return $stmt->execute();
}

// Funkcja do przypisywania użytkownika do karty RFID
function assignUserToCard($conn, $cardId, $login) {

    $sql = "SELECT id FROM users WHERE login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Przypisz użytkownika do karty RFID
        $userId = $row['id'];
        $updateSql = "UPDATE rfid SET user_id = ? WHERE card_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("is", $userId, $cardId);
        return $updateStmt->execute();
    }
    
    return false; // Zwróć false jeśli nie znaleziono użytkownika
}

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_card_id'])) {
        // Usuwanie karty RFID
        deleteCard($conn, $_POST['delete_card_id']);
    } elseif (isset($_POST['add_card_id'])) {
        // Dodawanie karty RFID
        addCard($conn, $_POST['add_card_id'], $_POST['add_card_name']);
    } elseif (isset($_POST['assign_card_id']) && isset($_POST['assign_user_login'])) {
        // Przypisywanie użytkownika do karty RFID
        assignUserToCard($conn, $_POST['assign_card_id'], $_POST['assign_user_login']);
    }
}

$cards = getCards($conn); // Pobranie listy kart RFID
$conn->close(); // Zamknięcie połączenia z bazą danych
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="clock.js"></script>
    <title>Karty RFID</title>
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
       <h2>Lista kart RFID</h2>

       <table>
           <thead>
               <tr>
                   <th>ID Karty</th>
                   <th>Nazwa Karty</th>
                   <th>Imię Użytkownika</th>
                   <th>Nazwisko Użytkownika</th>
               </tr>
           </thead>
           <tbody>
               <?php if ($cards && $cards->num_rows > 0): ?>
                   <?php while ($row = $cards->fetch_assoc()): ?>
                       <tr>
                           <td><?php echo htmlspecialchars($row['card_id']); ?></td>
                           <td><?php echo htmlspecialchars($row['card_name']); ?></td>
                           <td><?php echo !empty($row['user_name']) ? htmlspecialchars($row['user_name']) : 'Brak przypisanego użytkownika'; ?></td>
                           <td><?php echo !empty($row['user_lastname']) ? htmlspecialchars($row['user_lastname']) : 'Brak przypisanego użytkownika'; ?></td>
                       </tr>
                   <?php endwhile; ?>
               <?php else: ?>
                   <tr><td colspan="4">Brak kart RFID w systemie.</td></tr>
               <?php endif; ?>
           </tbody>
       </table>

       <h3>Usuń kartę RFID</h3>
       <form method="POST" action="">
           <label for="delete_card_id">Podaj ID karty do usunięcia:</label><br/>
           <input type="text" name="delete_card_id" id="delete_card_id" required><br/>
           <button type="submit">Usuń</button>
       </form>

       <h3>Dodaj nową kartę RFID</h3>
       <form method="POST" action="">
           <label for="add_card_id">ID Karty:</label><br/>
           <input type="text" name="add_card_id" id="add_card_id" required><br/>
           
           <label for="add_card_name">Nazwa Karty:</label><br/>
           <input type="text" name="add_card_name" id="add_card_name" required><br/>

           <button type="submit">Dodaj</button>
       </form>

       <h3>Przypisz użytkownika do karty RFID</h3>
       <form method="POST" action="">
           <label for="assign_card_id">ID Karty:</label><br/>
           <input type="text" name="assign_card_id" id="assign_card_id" required><br/>

           <label for="assign_user_login">Login Użytkownika:</label><br/>
           <input type="text" name="assign_user_login" id="assign_user_login" required><br/>

           <button type="submit">Przypisz</button>
       </form>

       <button type="button" onclick="location.reload()">Odśwież listę kart RFID</button>

   </div>

   <footer>
       Andrzej Berndt 2024/2025
   </footer>

</body>
</html>
