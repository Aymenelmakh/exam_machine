<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — mail/AlertMailer.php                        ║
 * ║  Email d'alerte organisateur (seuil 80%)                    ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : 🔴 À compléter — Partie 2.2
 *
 * APPELÉ DEPUIS : events/register.php quand taux >= 80%
 *
 * QUESTION DE CONCEPTION (à répondre dans CHOIX_TECHNIQUES.md) :
 *   Comment éviter d'envoyer cet email plusieurs fois pour le même
 *   événement quand plusieurs personnes s'inscrivent rapidement ?
 *   Implémentez votre solution dans sendCapacityAlert() et commentez-la.
 */

require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../pdf/report.php';

// ✅ FIX: Import PHPMailer classes into the global namespace so the catch block works.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AlertMailer
{
    /**
     * Envoie l'email d'alerte de capacité à l'organisateur.
     *
     * @param  PDO   $pdo
     * @param  array $event   Données complètes de l'événement
     * @return bool
     */
    public static function sendCapacityAlert(PDO $pdo, array $event): bool
    {
        try {
            // ════════════════════════════════════════════════════════════════
            // TODO 2.2.A — Vérifier et marquer si l'alerte a déjà été envoyée
            // ════════════════════════════════════════════════════════════════
            //
            // QUESTION DE CONCEPTION : Comment éviter d'envoyer cet email plusieurs fois ?
            //
            // SOLUTION IMPLÉMENTÉE : Utilisation de la colonne `alert_sent` et d'une requête UPDATE atomique.
            //
            // JUSTIFICATION :
            // 1. ATOMICITÉ : La requête `UPDATE ... WHERE alert_sent = 0` est une opération atomique.
            //    Elle garantit que même si deux inscriptions simultanées déclenchent cette fonction,
            //    seul le premier processus parvenant à exécuter l'UPDATE (et obtenant rowCount() > 0)
            //    pourra envoyer l'e-mail. Le second trouvera `alert_sent` déjà à 1 et s'arrêtera.
            // 2. PERFORMANCE : C'est plus performant qu'un `SELECT` suivi d'un `UPDATE`, car cela évite
            //    une "race condition" entre la lecture et l'écriture, sans nécessiter de transactions complexes.
            // 3. PERSISTANCE : L'état est stocké en base de données, ce qui est fiable et robuste,
            //    contrairement à des solutions volatiles comme des fichiers de verrouillage.
            $stmt = $pdo->prepare("UPDATE events SET alert_sent = 1 WHERE id = :event_id AND alert_sent = 0");
            $stmt->execute([':event_id' => $event['id']]);

            // Si aucune ligne n'a été affectée, cela signifie que l'alerte a déjà été envoyée par un autre processus.
            if ($stmt->rowCount() === 0) {
                return false; // On arrête ici pour ne pas envoyer de doublon.
            }

            // ════════════════════════════════════════════════════════════════
            // TODO 2.2.B — Générer le rapport PDF en fichier temporaire
            // ════════════════════════════════════════════════════════════════
            $tempPdf  = sys_get_temp_dir() . '/report_event_' . $event['id'] . '_' . time() . '.pdf';
            // La fonction generateReportPDF doit enregistrer le fichier sur le disque.
            generateReportPDF($pdo, $event['id'], 'F', $tempPdf);


            // ════════════════════════════════════════════════════════════════
            // TODO 2.2.C — Charger le template et envoyer l'email
            // ════════════════════════════════════════════════════════════════
            $html = file_get_contents(__DIR__ . '/templates/alert.html');

            // Calculer le taux de remplissage actuel
            $registeredCount = $event['registered_count'] ?? 0;
            $capacity = $event['capacity'] ?? 1;
            $fillPct = round(($registeredCount / $capacity) * 100);

            // ✅ FIX: Construire une URL de base plus robuste pour éviter les erreurs de chemin.
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/php_projects/exam/exam_machine';
            $dashboardLink = $baseUrl . '/index.html#dashboard'; // Ancre vers la section dashboard

            // Remplacer les placeholders
            $replacements = [
                '{{ORGANIZER_NAME}}' => 'Organisateur', // Peut être amélioré en récupérant le nom depuis la table users
                '{{EVENT_TITLE}}'    => htmlspecialchars($event['title']),
                '{{FILL_PCT}}'       => $fillPct,
                '{{REGISTERED}}'     => $registeredCount,
                '{{CAPACITY}}'       => $capacity,
                '{{DASHBOARD_LINK}}' => $dashboardLink,
            ];
            $html = str_replace(array_keys($replacements), array_values($replacements), $html);

            $mail = createMailer();
            $mail->addAddress($event['organizer_email']);
            $mail->Subject = '⚠️ Alerte capacité — ' . $event['title'];
            $mail->Body    = $html;
            $mail->AltBody = "Votre événement '" . $event['title'] . "' a atteint " . $fillPct . "% de sa capacité (" . $registeredCount . "/" . $capacity . ").";
            
            // Attacher le rapport PDF généré
            $mail->addAttachment($tempPdf, 'rapport_inscriptions_' . $event['id'] . '.pdf');
            
            $mail->send();

            return true;

        } catch (Exception $e) {
            logMailError($pdo, 'capacity_alert', $event['organizer_email'], $e->getMessage());
            return false;
        } finally {
            // Nettoyer le fichier PDF temporaire après l'envoi (ou en cas d'erreur)
            if (isset($tempPdf) && file_exists($tempPdf)) {
                @unlink($tempPdf);
            }
        }
    }
}
