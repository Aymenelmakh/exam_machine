<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — config/mailer.php                           ║
 * ║  Configuration PHPMailer                                    ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : ⚠️ Partiel — Variables SMTP manquantes à compléter
 *
 * PARTIE 2 — Complétez ce fichier :
 *   → Renseignez les constantes SMTP_* avec vos vraies valeurs
 *   → La fonction createMailer() est fournie et fonctionnelle
 *   → Vous devez créer les fonctions d'envoi dans mail/
 */

// ── TODO 2.0 — Complétez ces constantes avec vos paramètres SMTP ──────────
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io');          // Ex: smtp.gmail.com  ou  smtp.mailtrap.io
define('SMTP_PORT', 587);         // 587 (TLS) ou 465 (SSL)
define('SMTP_USER', 'dbc24132ee5a48');          // Remplacez par VOTRE nom d'utilisateur Mailtrap
define('SMTP_PASS', '191c0c2353ae36');          // Remplacez par VOTRE mot de passe Mailtrap
define('SMTP_FROM_NAME',  'EventHub Pro — ENSA Marrakech');
define('SMTP_FROM_EMAIL', 'noreply@eventhub.ensa.ma'); // ✅ FIX: Add a valid "From" email address.
define('SMTP_ENCRYPTION', 'tls');       // 'tls' ou 'ssl'

// Conseil : utilisez Mailtrap (mailtrap.io) pour tester sans envoyer de vrais emails

// ── Chargement PHPMailer ───────────────────────────────────────────────────
// PHPMailer est disponible via composer dans vendor/ OU via inclusion directe
// Choisissez la méthode adaptée à votre installation :

// Option A — via Composer (recommandé)
// require_once __DIR__ . '/../vendor/autoload.php';

// Option B — inclusion directe (si pas de Composer)
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

/**
 * Crée et retourne une instance PHPMailer préconfigurée.
 *
 * Paramètres SMTP déjà appliqués. Il reste à :
 *   → addAddress() — destinataire
 *   → Subject      — sujet
 *   → Body         — corps HTML
 *   → AltBody      — version texte brut
 *   → send()       — envoi
 *
 * @return PHPMailer
 */
function createMailer(): \PHPMailer\PHPMailer\PHPMailer
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true); // true = exceptions activées

    // Serveur SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Expéditeur
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME); // ✅ FIX: Use the valid "From" email address.
    $mail->isHTML(true);

    return $mail;
}

/**
 * Enregistre une erreur d'email en base de données.
 *
 * STATUT : ✅ Fourni
 *
 * @param PDO    $pdo
 * @param string $type     Type d'email ('confirmation', 'alert', 'ticket')
 * @param string $to       Destinataire
 * @param string $error    Message d'erreur
 */
function logMailError(PDO $pdo, string $type, string $to, string $error): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO mail_logs (type, recipient, error_message, created_at)
            VALUES (:type, :to, :error, NOW())'
        );
        $stmt->execute([
            ':type'  => $type,
            ':to'    => $to,
            ':error' => $error,
        ]);
    } catch (PDOException $e) {
        error_log('[EventHub] logMailError DB failed: ' . $e->getMessage());
    }
}
