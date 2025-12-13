<?php
// Fichier: /api/user_register.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Méthode non autorisée.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$nom = $input['nom'] ?? null;
$prenom = $input['prenom'] ?? null;
$mail = $input['mail'] ?? null;
$password = $input['password'] ?? null;
$tel = $input['tel'] ?? '';
$role = $input['role'] ?? 'parent';
$bafa = !empty($input['bafa']) ? 1 : 0;

// Validation basique
if (!$nom || !$prenom || !$mail || !$password) {
    sendJson(['error' => 'Tous les champs obligatoires doivent être remplis.'], 400);
}

try {
    // 1. Vérifier si l'email existe déjà
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$mail]);
    if ($stmtCheck->fetch()) {
        sendJson(['error' => 'Un compte avec cet email existe déjà.'], 409);
    }

    // 2. Créer le nouvel utilisateur
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $isAnimateur = ($role === 'animateur') ? 1 : 0;

    $sql = "INSERT INTO users (nom, prenom, email, password, tel, is_animateur, bafa) 
            VALUES (:nom, :prenom, :email, :pass, :tel, :anim, :bafa)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $mail,
        'pass' => $hashedPassword,
        'tel' => $tel,
        'anim' => $isAnimateur,
        'bafa' => $bafa
    ]);

    sendJson(['success' => 'Compte créé avec succès ! Connectez-vous maintenant.'], 201);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>