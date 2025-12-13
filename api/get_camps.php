<?php
// Fichier: /api/get_camps.php
require_once 'config.php';

try {
    // MODIFICATION 1 : On ajoute "AND prive = 0" pour cacher les camps privés des listes publiques
    $sql = "SELECT * FROM camps WHERE valide = 1 AND prive = 0 AND date_debut > CURDATE()";
    $params = [];

    // Filtre par nom
    if (!empty($_GET['name'])) {
        $sql .= " AND nom LIKE :name";
        $params['name'] = '%' . $_GET['name'] . '%';
    }

    // Filtre par département
    if (!empty($_GET['department'])) {
        $sql .= " AND code_postal LIKE :dept";
        $params['dept'] = $_GET['department'] . '%';
    }

    // Filtre par âge
    if (!empty($_GET['age'])) {
        $age = (int)$_GET['age'];
        $sql .= " AND age_min <= :age AND age_max >= :age";
        $params['age'] = $age;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $camps = $stmt->fetchAll();

    $formattedCamps = array_map(function($camp) {
        return [
            'id' => $camp['id'],
            // MODIFICATION 2 : On envoie le token au JavaScript pour construire le lien
            'token' => $camp['token'], 
            'nom' => $camp['nom'],
            'ville' => $camp['ville'],
            'prix' => $camp['prix'],
            'age_min' => $camp['age_min'],
            'age_max' => $camp['age_max'],
            'date_debut' => $camp['date_debut'],
            'image_url' => $camp['image_url'] ?? 'https://placehold.co/600x400'
        ];
    }, $camps);

    sendJson($formattedCamps);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>