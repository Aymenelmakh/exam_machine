<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — api/stats.php                               ║
 * ║  Endpoint AJAX — Statistiques temps réel (Dashboard)        ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À créer entièrement — Partie 4.2
 *
 * CE FICHIER DOIT RETOURNER (JSON) :
 * ┌────────────────────────────────────────────────────────────┐
 * │  {                                                          │
 * │    "success": true,                                         │
 * │    "generated_at": "2025-09-20 14:32:00",                  │
 * │                                                             │
 * │    "summary": {                                             │
 * │      "total_events"    : 6,                                 │
 * │      "total_registered": 384,                               │
 * │      "new_last_24h"    : 12,     ← inscriptions < 24h      │
 * │      "avg_fill_pct"    : 67,     ← taux moyen arrondi       │
 * │      "alert_count"     : 2       ← événements >= 80%        │
 * │    },                                                       │
 * │                                                             │
 * │    "top3": [                                                 │
 * │      { "id":1, "title":"...", "fill_pct":85, "reg":170 },  │
 * │      ...                                                    │
 * │    ],                                                       │
 * │                                                             │
 * │    "per_event": [                                            │
 * │      { "id":1, "title":"...", "capacity":200,               │
 * │        "registered":170, "fill_pct":85, "is_full":false },  │
 * │      ...                                                    │
 * │    ],                                                       │
 * │                                                             │
 * │    "registrations_by_day": [                                 │
 * │      { "day": "2025-09-15", "count": 5 },                   │
 * │      ...                                                    │
 * │    ]   ← Derniers 7 jours, pour le graphique PDF            │
 * │  }                                                          │
 * └────────────────────────────────────────────────────────────┘
 *
 * CONTRAINTES :
 *   → Accès réservé aux organisateurs connectés (vérifier la session)
 *   → Toutes les requêtes en PDO préparé
 *   → En cas d'erreur : retourner JSON { success: false, error: "..." }
 *   → Temps de réponse < 500ms (pas de requête N+1 !)
 *
 * CONSEIL : Une seule requête avec sous-requêtes vaut mieux que
 *           6 requêtes séparées dans une boucle.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// ════════════════════════════════════════════════════════════════════════
// TODO 4.2 — Implémentez cet endpoint complet
// ════════════════════════════════════════════════════════════════════════

// Étapes suggérées :
//
// 1. Vérifier que l'utilisateur est organisateur (session)
//    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'organizer') { 403 }
//
// 2. Ouvrir la connexion PDO
//    $pdo = getDB();
//
// 3. Requête summary — total inscrits, nouveaux 24h, taux moyen, alertes
//
// 4. Requête top3 — ORDER BY fill_pct DESC LIMIT 3
//
// 5. Requête per_event — tous les événements avec leurs stats
//
// 6. Requête registrations_by_day — 7 derniers jours
//    GROUP BY DATE(registered_at) ORDER BY day ASC
//
// 7. Encoder et retourner le JSON

echo json_encode([
    'success' => false,
    'error'   => 'api/stats.php non implémenté — Partie 4.2'
]);
