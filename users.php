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

// Funkcja do pobierania użytkowników
function getUsers($conn) {
    $sql = "SELECT users.id, users.name, users.lastname, users.login, rfid.card_id 
            FROM users 
            LEFT JOIN rfid ON users.id = rfid.user_id";
    return $conn->query($sql);
}

// Funkcja do usuwania użytkownika
function deleteUser($conn, $userId) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); // Bindowanie parametru
    return $stmt->execute();
}

// Funkcja do dodawania użytkownika
function addUser($conn, $name, $lastname, $login, $password) {
    $sql = "INSERT INTO users (name, lastname, login, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    // Haszowanie hasła przed zapisaniem
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $lastname, $login, password_hash($password, PASSWORD_DEFAULT)); // Haszowanie hasła (bcrypt z solą)
        return $stmt->execute();
    }
    return false;
}

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user_id'])) {
        // Usuwanie użytkownika
        deleteUser($conn, intval($_POST['delete_user_id']));
    } elseif (isset($_POST['add_user_name'])) {
        // Dodawanie użytkownika
        if (addUser($conn, $_POST['add_user_name'], $_POST['add_user_lastname'], $_POST['add_user_login'], $_POST['add_user_password'])) {
            // Przekierowanie do tej samej strony po dodaniu użytkownika
            header('Location: users.php');
            exit();
        } else {
            echo "<p style='color:red;'>Wystąpił błąd podczas dodawania użytkownika.</p>";
        }
    }
}

$users = getUsers($conn); // Pobranie listy użytkowników
$conn->close(); // Zamknięcie połączenia z bazą danych
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="clock.js"></script>
    <title>Użytkownicy</title>
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
       <h2>Lista użytkowników</h2>

       <table>
           <thead>
               <tr>
                   <th>ID</th>
                   <th>Imię</th>
                   <th>Nazwisko</th>
                   <th>Login</th>
                   <th>ID karty RFID</th>
               </tr>
           </thead>
           <tbody>
               <?php if ($users && $users->num_rows > 0): ?>
                   <?php while ($row = $users->fetch_assoc()): ?>
                       <tr>
                           <td><?php echo htmlspecialchars($row['id']); ?></td>
                           <td><?php echo htmlspecialchars($row['name']); ?></td>
                           <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                           <td><?php echo htmlspecialchars($row['login']); ?></td>
                           <td><?php echo !empty($row['card_id']) ? htmlspecialchars($row['card_id']) : 'Brak karty'; ?></td>
                       </tr>
                   <?php endwhile; ?>
               <?php else: ?>
                   <tr><td colspan="5">Brak użytkowników w systemie.</td></tr>
               <?php endif; ?>
           </tbody>
       </table>

       <h3>Usuń użytkownika</h3>
       <form method="POST" action="">
           <label for="delete_user_id">Podaj ID użytkownika do usunięcia:</label><br/>
           <input type="text" name="delete_user_id" id="delete_user_id" required><br/>
           <button type="submit">Usuń</button>
       </form>

       <h3>Dodaj nowego użytkownika</h3>
       <form method="POST" action="">
           <label for="add_user_name">Imię:</label><br/>
           <input type="text" name="add_user_name" id="add_user_name" required><br/>
           
           <label for="add_user_lastname">Nazwisko:</label><br/>
           <input type="text" name="add_user_lastname" id="add_user_lastname" required><br/>
           
           <label for="add_user_login">Login:</label><br/>
           <input type="text" name="add_user_login" id="add_user_login" required><br/>
           
           <label for="add_user_password">Hasło:</label><br/>
           <input type="password" name="add_user_password" id="add_user_password" required><br/>

           <button type="submit">Dodaj</button>
       </form>

       <button type="button" onclick="location.reload()">Odśwież listę użytkowników</button>

   </div>

    <footer>
       Andrzej Berndt 2024/2025
    </footer>

</body>
</html>
