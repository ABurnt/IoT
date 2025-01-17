<?php
session_start();

// Sprawdzenie danych wejściowych
if (!isset($_POST['login']) || !isset($_POST['password'])) {
    header('Location: index.php');
    exit();
}

require_once "connect.php";

$polaczenie = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($polaczenie->connect_errno) {
    error_log("Błąd połączenia z bazą danych: " . $polaczenie->connect_error);
    exit('Wystąpił błąd serwera.');
}

$login = $_POST['login'];
$haslo = $_POST['password'];

$login = htmlentities($login, ENT_QUOTES, "UTF-8");
$haslo = htmlentities($haslo, ENT_QUOTES, "UTF-8");

$stmt = $polaczenie->prepare("SELECT * FROM users WHERE login = ?");
$stmt->bind_param("s", $login); // Bindowanie parametru
$stmt->execute();
$rezultat = $stmt->get_result();

if ($rezultat->num_rows > 0) {
    $wiersz = $rezultat->fetch_assoc();
    
    // Weryfikacja hasła
    if (password_verify($haslo, $wiersz['password'])) {
        $_SESSION['zalogowany'] = true;
        $_SESSION['name'] = $wiersz['name'];
        $_SESSION['login'] = $wiersz['login'];
        
        // Regeneracja ID sesji
        session_regenerate_id(true);
        
        unset($_SESSION['blad']);
        header('Location: home.php');
        exit();
    } else {
        $_SESSION['blad'] = 'Nieprawidłowy login lub hasło!';
        header('Location: index.php');
        exit();
    }
} else {
    $_SESSION['blad'] = 'Nieprawidłowy login lub hasło!';
    header('Location: index.php');
    exit();
}

$polaczenie->close();
?>