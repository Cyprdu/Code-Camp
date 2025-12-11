<?php
// Fichier: /api/logout.php

session_start(); // On récupère la session existante

// On détruit toutes les variables de session
$_SESSION = [];

// On détruit la session elle-même
session_destroy();

// On redirige l'utilisateur vers la page d'accueil
header('Location: ../index.php');
exit;
?>
