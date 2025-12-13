<?php
// Fichier: /api/config.php
session_start(); // On démarre la session ici pour tous les fichiers qui incluent config

// Configuration de la base de données (XAMPP par défaut)
define('DB_HOST', 'localhost');
define('DB_NAME', 'colomap');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, ne jamais afficher l'erreur brute
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

// Fonction helper pour répondre en JSON
function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
