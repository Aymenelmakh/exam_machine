<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — events/create.php                           ║
 * ║  Création d'un événement                                    ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À compléter — Partie 1.2
 *
 * Ce fichier reçoit les données du formulaire (POST JSON)
 * et les insère en base de données.
 *
 * BUGS INTENTIONNELS À CORRIGER (Partie 1.2) :
 *   ❌  Injection SQL directe dans createEvent()
 *   ❌  Aucune validation des données entrantes
 *   ❌  Retour toujours true même en cas d'échec
 *   ❌  Aucune gestion d'exception PDO
 *
 * À IMPLÉMENTER :
 *   ✅  Corriger createEvent() avec requêtes préparées
 *   ✅  Valider et assainir les données reçues
 *   ✅  Retourner une vraie réponse JSON success/error
 *   ✅  Brancher l'appel fetch() depuis assets/js/app.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/db.php';

// ── Point d'entrée ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

// Lecture du body JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides.']);
    exit;
}

// TODO 1.2 — Valider les champs obligatoires
// $required = ['title', 'description', 'date', 'location', 'capacity', 'category', 'organizer_email'];
// foreach ($required as $field) { ... }

try {
    $pdo    = getDB();
    $result = createEvent($pdo, $data);

    echo json_encode([
        'success'  => true,
        'event_id' => $result,
        'message'  => 'Événement créé avec succès.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ═════════════════════════════════════════════════════════════════════════
// FONCTION PRINCIPALE — BUGS INTENTIONNELS À CORRIGER (Partie 1.2)
// ═════════════════════════════════════════════════════════════════════════

/**
 * Insère un nouvel événement en base de données.
 *
 * ⚠️  ATTENTION : Cette fonction contient des erreurs volontaires.
 *     Identifiez-les, corrigez-les et justifiez chaque correction
 *     dans un commentaire inline.
 *
 * @param  PDO   $pdo
 * @param  array $data Données issues du formulaire
 * @return int        L'ID de l'événement nouvellement créé.
 */
function createEvent(PDO $pdo, array $data): int
{
    // ✅ FIX 1 : Remplacer la concaténation par des placeholders (ici, nommés).
    //    Cela sépare la requête SQL des données, empêchant les injections SQL.
    $sql = "INSERT INTO events (title, description, event_date, location, capacity, category, organizer_email)
            VALUES (:title, :description, :event_date, :location, :capacity, :category, :organizer_email)";

    // ✅ FIX 2 : Utiliser prepare() et execute() au lieu de query().
    //    prepare() pré-compile la requête, et execute() envoie les données de manière sécurisée.
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':title'           => $data['title'],
        ':description'     => $data['description'],
        ':event_date'      => $data['date'],
        ':location'        => $data['location'],
        ':capacity'        => $data['capacity'],
        ':category'        => $data['category'],
        ':organizer_email' => $data['organizer_email'],
    ]);

    // ✅ FIX 3 : Retourner l'ID de l'enregistrement créé au lieu de 'true'.
    //    lastInsertId() confirme que l'insertion a réussi et renvoie une information utile.
    //    En cas d'échec de execute(), une PDOException sera levée et capturée en amont.
    return (int) $pdo->lastInsertId();
}
