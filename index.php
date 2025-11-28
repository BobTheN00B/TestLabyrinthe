<?php
session_start();

if (isset($_SESSION['cle'])){
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Projet Labyrinthe Web - Menu Principal</title>
</head>
<body>
    <h1>Maze for idiots</h1>
    <p>La sortie c'est bien</p>
    <a href="jeu.php"><button>Start a new round</button></a>
</body>
</html>
