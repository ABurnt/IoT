<?php
session_start();

// Sprawdzenie, czy użytkownik jest już zalogowany
if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] === true) {
    header('Location: home.php');
    exit();
}

// Inicjalizacja zmiennej błędu
$errorMessage = isset($_SESSION['blad']) ? $_SESSION['blad'] : '';
unset($_SESSION['blad']); // Usunięcie błędu po jego wyświetleniu

?>

<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Panel administracyjny</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="align">

<div class="content-container">
    <form action="login.php" method="POST" class="form login">
        <label for="login__username">
            <i class="fas fa-user"></i>
            <span class="hidden">Login</span>
        </label>
        <input id="login__username" type="text" name="login" class="form__input" placeholder="Login" required>

        <label for="login__password">
            <i class="fas fa-lock"></i>
            <span class="hidden">Password</span>
        </label>
        <input id="login__password" type="password" name="password" class="form__input" placeholder="Password" required>

        <input type="submit" value="Zaloguj się">
    </form>

    <?php if ($errorMessage): ?>
        <span style="color:red"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php endif; ?>
</div>

</body>
</html>
