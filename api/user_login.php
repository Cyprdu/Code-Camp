<?php
// Fichier: /api/user_login.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Méthode non autorisée.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$mail = trim($input['mail'] ?? '');
$password = $input['password'] ?? '';

if (empty($mail) || empty($password)) {
    sendJson(['error' => 'Email et mot de passe requis.'], 400);
}

try {
    // Requête SQL préparée (sécurisée contre les injections)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $mail]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendJson(['error' => 'Email ou mot de passe incorrect.'], 401);
    }

    // Récupération des favoris
    $stmtFav = $pdo->prepare("SELECT camp_id FROM favoris WHERE user_id = ?");
    $stmtFav->execute([$user['id']]);
    $favorites = $stmtFav->fetchAll(PDO::FETCH_COLUMN);

    // Mise en session (Mapping des noms de colonnes SQL vers les clés de session)
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'mail' => $user['email'],
        'tel' => $user['tel'],
        'photo_url' => $user['photo_url'],
        'is_directeur' => (bool)$user['is_directeur'],
        'is_admin' => (bool)$user['is_admin'],
        'is_animateur' => (bool)$user['is_animateur'],
        'demande_en_cours' => (bool)$user['demande_en_cours'],
        'is_refused' => (bool)$user['is_refused'],
        'favorites' => $favorites
    ];

    sendJson(['success' => true]);

} catch (Exception $e) {
    sendJson(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
?>