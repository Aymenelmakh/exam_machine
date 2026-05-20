<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — pdf/ticket.php                              ║
 * ║  Génération du ticket PDF d'inscription                     ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À créer — Partie 3.1
 *
 * USAGE — Deux modes :
 *
 *   Mode téléchargement (depuis le navigateur) :
 *     GET /pdf/ticket.php?registration_id=42&token=abc123
 *
 *   Mode génération pour email (appelé depuis events/register.php) :
 *     $path = generateTicketPDF($pdo, $registrationId, $token);
 *     → retourne le chemin du fichier temporaire
 *
 * CONTENU ATTENDU DU TICKET :
 *   ✅  Logo EventHub Pro (assets/img/logo.png)
 *   ✅  Nom et email du participant
 *   ✅  Titre, date, lieu de l'événement
 *   ✅  Numéro d'inscription unique
 *   ✅  QR Code encodant : eventId|registrationId|token
 *   ✅  Couleur dynamique selon la catégorie de l'événement
 *   ✅  Lien de désinscription
 *   ✅  [DÉFI CRÉATIF] Élément visuel distinctif de votre choix
 *
 * BIBLIOTHÈQUE :
 *   Justifiez votre choix (TCPDF ou Dompdf) en commentaire ci-dessous.
 */

require_once __DIR__ . '/../config/db.php';

// ── Chargement de la bibliothèque PDF ─────────────────────────────────────
/**
 * Mon choix : TCPDF
 * Justification : J'ai choisi TCPDF car il offre un contrôle de bas niveau sur la
 * génération du PDF. Cela permet de positionner des éléments (comme le logo, le QR code,
 * et les blocs de texte) avec une grande précision et de dessiner des formes
 * personnalisées (comme le bandeau de couleur), ce qui est plus difficile à
 * maîtriser avec une approche basée sur le HTML/CSS de Dompdf pour une mise en page
 * aussi spécifique qu'un ticket.
 */
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

// ── Chargement bibliothèque QR Code ───────────────────────────────────────
require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';

// ═════════════════════════════════════════════════════════════════════════
// FONCTION PRINCIPALE — À implémenter
// ═════════════════════════════════════════════════════════════════════════

/**
 * Génère le ticket PDF pour une inscription donnée.
 *
 * @param  PDO    $pdo
 * @param  int    $registrationId
 * @param  string $token           Token de désinscription
 * @param  string $output          'D' = download, 'F' = save to file, 'S' = string
 * @param  string $filePath        Chemin de sauvegarde si output = 'F'
 * @return string|void             Chemin du fichier si output='F', sinon envoi direct
 */
function generateTicketPDF(
    PDO    $pdo,
    int    $registrationId,
    string $token,
    string $output   = 'D',
    string $filePath = ''
) {
    // ── Récupérer les données de l'inscription (fourni) ───────────────
    $stmt = $pdo->prepare(
        'SELECT r.id,
                r.event_id,
                r.name,
                r.email,
                r.registered_at,
                e.title,
                e.event_date,
                e.location,
                e.category
         FROM   registrations r
         JOIN   events e ON e.id = r.event_id
         WHERE  r.id    = :rid
           AND  r.token = :token'
    );
    $stmt->execute([':rid' => $registrationId, ':token' => $token]);
    $data = $stmt->fetch();

    if (!$data) {
        http_response_code(404);
        die('Inscription introuvable ou token invalide.');
    }

    // ── Couleurs par catégorie ─────────────────────────────────────────
    $categoryColors = [
        'tech'     => ['primary' => '#2563EB', 'light' => '#DBEAFE'],
        'design'   => ['primary' => '#7C3AED', 'light' => '#EDE9FE'],
        'business' => ['primary' => '#EA580C', 'light' => '#FEF3C7'],
        'science'  => ['primary' => '#16A34A', 'light' => '#DCFCE7'],
    ];
    $colors = $categoryColors[$data['category']] ?? ['primary' => '#0F1F3D', 'light' => '#F8FAFC'];

    // ── Données du QR Code ─────────────────────────────────────────────
    $qrData = $data['event_id'] . '|' . $registrationId . '|' . $token;
    $qrTempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.png';
    if (!file_exists(dirname($qrTempFile))) {
        mkdir(dirname($qrTempFile), 0777, true);
    }
    QRcode::png($qrData, $qrTempFile, QR_ECLEVEL_M, 5, 2); // Génère le fichier PNG

    // ════════════════════════════════════════════════════════════════════
    // TODO 3.1 — Construire le PDF
    // ════════════════════════════════════════════════════════════════════
    $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
    $pdf->SetCreator('EventHub Pro');
    $pdf->SetAuthor('ENSA Marrakech');
    $pdf->SetTitle('Ticket — ' . $data['title']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // Bandeau de couleur (Défi créatif)
    $r = $g = $b = 15; // Default to navy blue
    if (strpos($colors['primary'], '#') === 0) {
        list($r, $g, $b) = sscanf($colors['primary'], "#%02x%02x%02x");
    }
    $pdf->SetFillColor($r, $g, $b);
    $pdf->Rect(0, 0, $pdf->getPageWidth(), 15, 'F');

    // Logo et numéro de ticket
    $pdf->Image(__DIR__ . '/../assets/img/logo.png', 15, 18, 30);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(100);
    $pdf->SetXY(150, 20);
    $pdf->Cell(0, 10, 'TICKET D\'INSCRIPTION', 0, 1, 'R');
    $pdf->SetFont('courier', 'B', 14);
    $pdf->SetTextColor(0);
    $pdf->SetXY(150, 26);
    $pdf->Cell(0, 10, 'N° ' . str_pad($registrationId, 6, '0', STR_PAD_LEFT), 0, 1, 'R');

    // Ligne de séparation
    $pdf->Line(15, 42, $pdf->getPageWidth() - 15, 42);

    // Informations de l'événement
    $pdf->SetXY(15, 48);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->MultiCell(120, 8, $data['title'], 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(15, 65);
    $pdf->Cell(120, 8, '📅 ' . (new DateTime($data['event_date']))->format('d/m/Y à H:i'), 0, 1, 'L');
    $pdf->SetX(15);
    $pdf->Cell(120, 8, '📍 ' . $data['location'], 0, 1, 'L');

    // QR Code
    if (file_exists($qrTempFile)) {
        $pdf->Image($qrTempFile, 150, 48, 45, 45, 'PNG');
        @unlink($qrTempFile); // Nettoyer le fichier temporaire
    }

    // Ligne de séparation
    $pdf->Line(15, 98, $pdf->getPageWidth() - 15, 98);

    // Informations du participant
    $pdf->SetXY(15, 102);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(80);
    $pdf->Cell(30, 6, 'Participant:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 6, $data['name'], 0, 1);

    $pdf->SetX(15);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(80);
    $pdf->Cell(30, 6, 'Email:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 6, $data['email'], 0, 1);

    // Pied de page du ticket
    $pdf->SetFillColor(248, 250, 252); // bg-slate-50
    $pdf->Rect(0, $pdf->getPageHeight() - 20, $pdf->getPageWidth(), 20, 'F');
    $pdf->SetY($pdf->getPageHeight() - 16);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(100);
    $pdf->Cell(0, 10, 'Ce ticket est personnel et non cessible. Présentez-le à l\'entrée.', 0, 0, 'C');

    // Sortie
    return $pdf->Output($filePath, $output);
}

// ── Point d'entrée GET (téléchargement direct) ────────────────────────────
if (php_sapi_name() !== 'cli' && isset($_GET['registration_id'], $_GET['token'])) {
    $pdo = getDB();
    generateTicketPDF(
        $pdo,
        (int)$_GET['registration_id'],
        $_GET['token'], // FIX: Do not use htmlspecialchars on the token for DB lookup
        'D'
    );
}
