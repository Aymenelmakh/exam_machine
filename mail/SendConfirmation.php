<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — mail/SendConfirmation.php                   ║
 * ║  Email de confirmation d'inscription                        ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À compléter — Partie 2.1
 *
 * APPELÉ DEPUIS : events/register.php après inscription réussie
 *
 * À IMPLÉMENTER :
 *   🔴  Charger le template HTML (mail/templates/confirmation.html)
 *   🔴  Remplacer les placeholders par les vraies données
 *   🔴  Envoyer l'email avec PHPMailer
 *   🔴  Retourner true/false selon le résultat
 *   🔴  Logger les erreurs avec logMailError() si l'envoi échoue
 */

require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../pdf/ticket.php'; // ✅ FIX: Include the ticket generation script

// ✅ FIX: Import PHPMailer classes into the global namespace so the catch block works.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendConfirmation
{
    /**
     * Envoie l'email de confirmation d'inscription.
     *
     * @param  PDO    $pdo
     * @param  array  $event   Données de l'événement (depuis la BD)
     * @param  string $name    Nom du participant
     * @param  string $email   Email du participant
     * @param  string $token   Token unique de désinscription
     * @param  int    $registrationId ID de l'inscription pour générer le ticket
     * @return bool         True si envoi réussi, false sinon
     */
    public static function send(PDO $pdo, array $event, string $name, string $email, string $token, int $registrationId): bool
    {
        // ════════════════════════════════════════════════════════════════
        // TODO 2.1.A — Charger et personnaliser le template HTML
        // ════════════════════════════════════════════════════════════════
        $html = file_get_contents(__DIR__ . '/templates/confirmation.html');

        // Formater la date en français (ex: lundi 20 septembre 2025 à 09h00)
        $date = new DateTime($event['event_date']);
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT, 'Europe/Paris', IntlDateFormatter::GREGORIAN, "EEEE d MMMM yyyy 'à' HH'h'mm");
        $formattedDate = $formatter->format($date);

        // ✅ FIX: Construire une URL de base plus robuste pour éviter les erreurs de chemin.
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/php_projects/exam/exam_machine';
        $unsubscribeLink = $baseUrl . '/events/unsubscribe.php?token=' . urlencode($token);
        $ticketLink = $baseUrl . '/pdf/ticket.php?registration_id=' . $registrationId . '&token=' . urlencode($token);

        // Remplacer les placeholders
        $replacements = [
            '{{PARTICIPANT_NAME}}' => htmlspecialchars($name),
            '{{EVENT_TITLE}}'      => htmlspecialchars($event['title']),
            '{{EVENT_DATE}}'       => $formattedDate,
            '{{EVENT_LOCATION}}'   => htmlspecialchars($event['location']),
            '{{UNSUBSCRIBE_LINK}}' => $unsubscribeLink,
            '{{TICKET_LINK}}'      => $ticketLink,
            '{{YEAR}}'             => date('Y'),
        ];
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // ════════════════════════════════════════════════════════════════
        // TODO 2.1.B — Envoyer avec PHPMailer
        // ════════════════════════════════════════════════════════════════
        try {
            $mail = createMailer();

            // Envoi au participant
            $mail->addAddress($email, $name);

            $mail->Subject = 'Confirmation d\'inscription: ' . $event['title'];
            $mail->Body    = $html;

            // Créer une version texte simple pour les clients mail qui ne supportent pas le HTML
            $altBody = "Bonjour " . $name . ",\n\nVotre inscription pour l'événement '" . $event['title'] . "' est confirmée.\n";
            $altBody .= "Date: " . $formattedDate . "\nLieu: " . $event['location'] . "\n\n";
            $altBody .= "Téléchargez votre ticket ici: " . $ticketLink . "\n";
            $altBody .= "Pour vous désinscrire: " . $unsubscribeLink . "\n";
            $mail->AltBody = $altBody;

            // ✅ FIX: Générer le PDF du ticket et l'attacher à l'email
            $tempPdfPath = sys_get_temp_dir() . '/ticket_' . $registrationId . '_' . uniqid() . '.pdf';
            generateTicketPDF($pdo, $registrationId, $token, 'F', $tempPdfPath);

            if (file_exists($tempPdfPath)) {
                $mail->addAttachment($tempPdfPath, 'ticket-eventhub.pdf');
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            // En cas d'erreur, on logue l'information en base de données
            // pour un suivi et un débogage futurs.
            logMailError($pdo, 'confirmation', $email, $e->getMessage());
            return false;
        } finally {
            // Nettoyer le fichier PDF temporaire pour ne pas encombrer le serveur
            if (isset($tempPdfPath) && file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
        }
    }
}
