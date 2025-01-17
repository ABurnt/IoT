<?php
session_start();

// Usuń wszystkie zmienne sesji
$_SESSION = array();

// Zniszcz sesję
session_destroy();

// Przekierowanie do strony głównej (lub logowania)
header('Location: index.php');
exit();
?>
