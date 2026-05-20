<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — pdf/report.php                              ║
 * ║  Rapport de gestion multi-pages pour l'organisateur         ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À créer — Partie 3.2
 *
 * USAGE :
 *   GET  /pdf/report.php?event_id=3&token=xxx   → téléchargement
 *   $path = generateReportPDF($pdo, $eventId)   → chemin fichier (pour email)
 *
 * CONTENU ATTENDU (3 pages minimum) :
 *
 * ── PAGE 1 : Résumé exécutif ──────────────────────────────────────────
 *   Nom de l'événement, date, lieu, organisateur
 *   Capacité totale / Inscrits / Places disponibles
 *   Taux de remplissage (%)
 *   Date de génération du rapport
 *
 * ── PAGE 2 : Liste des inscrits ───────────────────────────────────────
 *   Tableau : N° | Nom | Email | Date inscription
 *   Trié par nom alphabétique
 *   Paginé si > 25 inscrits par page
 *
 * ── PAGE 3 : Statistiques visuelles ──────────────────────────────────
 *   🎯 DÉFI TECHNIQUE : Graphique en barres des inscriptions par jour
 *      → Généré en PHP pur avec les primitives TCPDF ou Dompdf
 *      → Sans JavaScript, sans image externe
 *      → Axes X (jours) et Y (nombre d'inscrits) annotés
 *      → Minimum 7 derniers jours
 *
 *   Avec TCPDF : utilisez Rect(), Line(), Cell(), SetFillColor()
 *   Avec Dompdf : construire le graphique en HTML/CSS inline
 *
 * EXIGENCES COMMUNES :
 *   ✅  Numérotation des pages (Page X / Y)
 *   ✅  En-tête répété sur chaque page (titre + logo)
 *   ✅  Pied de page avec date de génération
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

/**
 * CHOIX DE BIBLIOTHÈQUE (TCPDF) :
 *   J'ai choisi TCPDF pour ce rapport pour plusieurs raisons :
 *   1. CONTRÔLE PRÉCIS : TCPDF permet de positionner des éléments au pixel près,
 *      ce qui est indispensable pour créer des graphiques personnalisés comme
 *      le diagramme en barres des inscriptions.
 *   2. AUTONOMIE : Il ne dépend pas d'un moteur de rendu HTML/CSS, ce qui le rend
 *      plus performant et prévisible pour des mises en page complexes.
 *   3. FONCTIONNALITÉS AVANCÉES : La gestion native des en-têtes, pieds de page,
 *      et des sauts de page automatiques pour les tableaux est robuste et bien adaptée
 *      à la génération de rapports multi-pages.
 */

/**
 * Classe PDF personnalisée pour définir l'en-tête et le pied de page.
 */
class ReportPDF extends TCPDF
{
    // En-tête personnalisé
    public function Header() {
        $this->Image(__DIR__ . '/../assets/img/logo.png', 10, 10, 25, 0, 'PNG');
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 15, 'Rapport de Gestion d\'Événement', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Line(10, 25, $this->getPageWidth() - 10, 25);
    }

    // Pied de page personnalisé
    public function Footer() {
        $this->SetY(-15); $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'L');
        $this->Cell(0, 10, 'Généré le ' . date('d/m/Y à H:i'), 0, false, 'R');
    }
}


// ═════════════════════════════════════════════════════════════════════════
// FONCTION PRINCIPALE — À implémenter
// ═════════════════════════════════════════════════════════════════════════

/**
 * Génère le rapport PDF complet pour un événement.
 *
 * @param  PDO    $pdo
 * @param  int    $eventId
 * @param  string $outputMode 'I' pour navigateur, 'D' pour download, 'F' pour fichier
 * @param  string $filePath  Chemin si output = 'F'
 * @return string|void
 */
function generateReportPDF(PDO $pdo, int $eventId, string $outputMode = 'D', string $filePath = '')
{
    // ── Récupérer l'événement (FIX: Separated queries for robustness) ─────
    // 1. Get event details
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute([':id' => $eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        die('Événement introuvable.');
    }

    // 2. Get registration stats
    $capacity = (int)$event['capacity'] > 0 ? (int)$event['capacity'] : 1; // Prevent division by zero error
    $statsStmt = $pdo->prepare(
        'SELECT COUNT(*) AS registered_count,
                (' . $capacity . ' - COUNT(*)) AS available_places,
                ROUND(COUNT(*) / ' . $capacity . ' * 100) AS fill_pct
         FROM registrations WHERE event_id = :id'
    );
    $statsStmt->execute([':id' => $eventId]);
    $event = array_merge($event, $statsStmt->fetch(PDO::FETCH_ASSOC));

    // ── Récupérer la liste des inscrits (fourni) ──────────────────────
    $stmt = $pdo->prepare(
        'SELECT id, name, email, registered_at
         FROM   registrations
         WHERE  event_id = :id
         ORDER  BY name ASC'
    );
    $stmt->execute([':id' => $eventId]);
    $registrations = $stmt->fetchAll();

    // ── Récupérer les stats par jour — 7 derniers jours (fourni) ─────
    $stmt = $pdo->prepare(
        'SELECT DATE(registered_at) AS day,
                COUNT(*)            AS count
         FROM   registrations
         WHERE  event_id    = :id
           AND  registered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP  BY DATE(registered_at)
         ORDER  BY day ASC'
    );
    $stmt->execute([':id' => $eventId]);
    $statsByDay = $stmt->fetchAll();
    
    // 2. Initialiser le document PDF
    $pdf = new ReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('EventHub Pro');
    $pdf->SetTitle('Rapport - ' . $event['title']);
    $pdf->SetMargins(PDF_MARGIN_LEFT, 30, PDF_MARGIN_RIGHT); // Marge haute de 30 pour le header
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // ── PAGE 1 : RÉSUMÉ EXÉCUTIF ───────────────────────────────────────────
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, $event['title'], 0, 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(40, 8, 'Date :', 0, 0);
    $pdf->Cell(0, 8, (new DateTime($event['event_date']))->format('d/m/Y H:i'), 0, 1);
    $pdf->Cell(40, 8, 'Lieu :', 0, 0);
    $pdf->Cell(0, 8, $event['location'], 0, 1);
    $pdf->Ln(8);

    $pdf->SetFillColor(240, 245, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques Clés', 0, 1, 'L', true);
    $pdf->Ln(4);

    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(60, 8, 'Capacité totale :', 0, 0);
    $pdf->Cell(0, 8, $event['capacity'] . ' places', 0, 1);
    $pdf->Cell(60, 8, 'Inscriptions confirmées :', 0, 0);
    $pdf->Cell(0, 8, $event['registered_count'], 0, 1);
    $pdf->Cell(60, 8, 'Taux de remplissage :', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $event['fill_pct'] . '%', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(60, 8, 'Places restantes :', 0, 0);
    $pdf->Cell(0, 8, $event['available_places'], 0, 1);

    // ── PAGE 2 : LISTE DES INSCRITS ──────────────────────────────────────
    if (!empty($registrations)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Liste des Participants', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(70, 7, 'Nom', 1, 0, 'L', 1);
        $pdf->Cell(70, 7, 'Email', 1, 0, 'L', 1);
        $pdf->Cell(0, 7, 'Date d\'inscription', 1, 1, 'L', 1);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($registrations as $reg) {
            $pdf->Cell(70, 6, $reg['name'], 'LRB', 0, 'L');
            $pdf->Cell(70, 6, $reg['email'], 'LRB', 0, 'L');
            $pdf->Cell(0, 6, (new DateTime($reg['registered_at']))->format('d/m/Y H:i'), 'LRB', 1, 'L');
        }
    }

    // ── PAGE 3 : GRAPHIQUE DES INSCRIPTIONS ──────────────────────────────
    if (!empty($statsByDay)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Évolution des Inscriptions (7 derniers jours)', 0, 1, 'L');
        $pdf->Ln(8);

        $chartWidth = 160; $chartHeight = 80;
        $chartX = $pdf->GetX() + 10; $chartY = $pdf->GetY();

        $pdf->SetDrawColor(150);
        $pdf->Rect($chartX, $chartY, $chartWidth, $chartHeight);

        $maxCount = max(array_column($statsByDay, 'count')) ?: 1;
        $barWidth = $chartWidth / (count($statsByDay) * 1.5);
        $xPos = $chartX + ($barWidth / 2);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(37, 99, 235);

        foreach ($statsByDay as $day) {
            $barHeight = ($day['count'] / $maxCount) * $chartHeight * 0.9;
            $yPos = $chartY + $chartHeight - $barHeight;

            $pdf->Rect($xPos, $yPos, $barWidth, $barHeight, 'F');
            $pdf->Text($xPos + ($barWidth / 2) - 2, $yPos - 4, $day['count']);

            $pdf->StartTransform();
            $pdf->Rotate(75, $xPos, $chartY + $chartHeight + 2);
            $pdf->Text($xPos, $chartY + $chartHeight + 2, date('d/m', strtotime($day['day'])));
            $pdf->StopTransform();

            $xPos += $barWidth * 1.5;
        }

        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->Text($chartX - 8, $chartY, $maxCount);
        $pdf->Text($chartX - 4, $chartY + $chartHeight - 3, '0');
        $pdf->StartTransform();
        $pdf->Rotate(90, $chartX - 10, $chartY + ($chartHeight / 2));
        $pdf->Text($chartX - 10, $chartY + ($chartHeight / 2) - 15, 'Nombre d\'inscriptions');
        $pdf->StopTransform();
    } else {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Évolution des Inscriptions', 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->Cell(0, 10, 'Aucune inscription enregistrée dans les 7 derniers jours.', 0, 1, 'C');
    }

    // 3. Sortie du PDF
    $pdf->Output($filePath, $outputMode);
}


// ── Point d'entrée GET ────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && isset($_GET['event_id'])) {
    // TODO : Vérifier que l'utilisateur est bien l'organisateur de cet événement
    $pdo = getDB();
    generateReportPDF($pdo, (int)$_GET['event_id'], 'I'); // 'I' pour afficher dans le navigateur
}
