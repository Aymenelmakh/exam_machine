<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — api/events.php                              ║
 * ║  Endpoint AJAX — Liste et recherche des événements          ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : ⚠️ Partiel — Partie 1.3 + Partie 4.1
 *
 * FOURNI :
 *   ✅  Structure de réponse JSON
 *   ✅  Lecture des paramètres GET/POST
 *   ✅  Requête de base sans filtre
 *
 * À COMPLÉTER :
 *   🔴  searchEvents() — filtres dynamiques combinables (Partie 1.3)
 *   🔴  Pagination des résultats
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

// ── Lecture des paramètres (GET ou POST JSON) ─────────────────────────────
$params = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = file_get_contents('php://input');
    $params = json_decode($body, true) ?? [];
} else {
    $params = $_GET;
}

$keyword  = isset($params['keyword'])  ? trim($params['keyword'])   : '';
$category = isset($params['category']) ? trim($params['category'])  : '';
$dateFrom = isset($params['date_from'])? trim($params['date_from']) : '';
$dateTo   = isset($params['date_to'])  ? trim($params['date_to'])   : '';
$hasPlaces= isset($params['has_places'])? (bool)$params['has_places']: false;
$page     = isset($params['page'])     ? max(1, (int)$params['page']): 1;
$perPage  = 6;

try {
    $pdo    = getDB();
    $result = searchEvents($pdo, $keyword, $category, $dateFrom, $dateTo, $hasPlaces, $page, $perPage);

    echo json_encode([
        'success' => true,
        'data'    => $result['events'],
        'meta'    => [
            'total'    => $result['total'],
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => ceil($result['total'] / $perPage),
        ]
    ]);

} catch (Exception $e) {
    error_log('[EventHub] api/events.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}


// ═════════════════════════════════════════════════════════════════════════
// TODO 1.3 — Implémenter searchEvents() avec filtres dynamiques
// ═════════════════════════════════════════════════════════════════════════

/**
 * Recherche des événements avec filtres combinables.
 *
 * CONTRAINTES OBLIGATOIRES :
 *   → Tous les filtres sont OPTIONNELS (aucun n'est requis)
 *   → Les filtres actifs se COMBINENT (AND)
 *   → Aucune concaténation SQL directe → requêtes préparées uniquement
 *   → La requête doit être construite dynamiquement selon les filtres reçus
 *
 * STRATÉGIE SUGGÉRÉE (plusieurs approches valides) :
 *   Construire un tableau $conditions[] et un tableau $bindings[]
 *   Ajouter une condition à chaque filtre actif
 *   Assembler : WHERE implode(' AND ', $conditions)
 *   Binder : execute($bindings)
 *
 * CHAMPS RETOURNÉS PAR ÉVÉNEMENT :
 *   id, title, description, event_date, location,
 *   capacity, category, organizer_email,
 *   registered_count   (COUNT via JOIN)
 *   available_places   (capacity - registered_count)
 *   fill_percentage    (arrondi à l'entier)
 *
 * @param PDO    $pdo
 * @param string $keyword     Recherche dans title et description
 * @param string $category    Filtre par catégorie exacte
 * @param string $dateFrom    Date minimum (format Y-m-d)
 * @param string $dateTo      Date maximum (format Y-m-d)
 * @param bool   $hasPlaces   Si true : exclure les événements complets
 * @param int    $page        Numéro de page (pagination)
 * @param int    $perPage     Résultats par page
 * @return array              ['events' => [...], 'total' => int]
 */
function searchEvents(
    PDO    $pdo,
    string $keyword   = '',
    string $category  = '',
    string $dateFrom  = '',
    string $dateTo    = '',
    bool   $hasPlaces = false,
    int    $page      = 1,
    int    $perPage   = 6
): array {

    // ── Construction dynamique de la requête ───────────────────────────
    $baseQuery  = "FROM events e LEFT JOIN registrations r ON r.event_id = e.id";
    $conditions = [];
    $bindings   = [];
    $havingConditions = [];

    // Filtre par mot-clé (titre ou description)
    if ($keyword !== '') {
        $conditions[] = '(e.title LIKE :keyword OR e.description LIKE :keyword)';
        $bindings[':keyword'] = '%' . $keyword . '%';
    }

    // Filtre par catégorie
    if ($category !== '') {
        $conditions[] = 'e.category = :category';
        $bindings[':category'] = $category;
    }

    // Filtre par date de début
    if ($dateFrom !== '') {
        $conditions[] = 'e.event_date >= :date_from';
        $bindings[':date_from'] = $dateFrom;
    }

    // Filtre par date de fin
    if ($dateTo !== '') {
        $conditions[] = 'e.event_date <= :date_to';
        $bindings[':date_to'] = $dateTo . ' 23:59:59'; // Inclure toute la journée
    }

    // Filtre pour les événements ayant des places disponibles
    if ($hasPlaces) {
        // HAVING est utilisé car la condition porte sur un résultat d'agrégation
        $havingConditions[] = '(e.capacity - COUNT(r.id)) > 0';
    }

    // ── Assemblage de la clause WHERE et HAVING ────────────────────────
    $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $havingClause = !empty($havingConditions) ? ' HAVING ' . implode(' AND ', $havingConditions) : '';

    // ── Requête pour le total (pour la pagination) ─────────────────────
    $countSql = "SELECT COUNT(*) FROM (
                    SELECT 1
                    $baseQuery
                    $whereClause
                    GROUP BY e.id
                    $havingClause
                ) AS subquery";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($bindings);
    $total = $countStmt->fetchColumn();

    // ── Requête principale pour récupérer les événements ────────────────
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT e.id, e.title, e.description, e.event_date, e.location,
                e.capacity, e.category, e.organizer_email,
                COUNT(r.id) AS registered_count,
                (e.capacity - COUNT(r.id)) AS available_places,
                   ROUND(COUNT(r.id) / NULLIF(e.capacity, 0) * 100) AS fill_percentage
            $baseQuery
            $whereClause
            GROUP BY e.id
            $havingClause
            ORDER BY e.event_date ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Fusionner les bindings des filtres avec les bindings de la pagination
    $allBindings = array_merge($bindings, [
        ':limit'  => $perPage,
        ':offset' => $offset
    ]);
    $stmt->execute($allBindings);
    $events = $stmt->fetchAll();

    return ['events' => $events, 'total' => $total];
}
