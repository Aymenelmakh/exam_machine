<?php
require_once __DIR__ . '/../config/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendConfirmation
{
    public static function send(PDO $pdo, array $event, string $name, string $email, string $token, int $registrationId): bool
    {
        try {
            // Load HTML template
            $templatePath = __DIR__ . '/templates/confirmation.html';
            if (!file_exists($templatePath)) {
                // Fallback to simple HTML if template missing
                $html = self::buildFallbackHtml($name, $event, $token, $registrationId);
            } else {
                $html = file_get_contents($templatePath);
            }

            // Format date
            $formattedDate = date('d/m/Y à H\hi', strtotime($event['event_date']));

            // Build URLs
            $scheme  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $unsubscribeLink = $baseUrl . '/events/unsubscribe.php?token=' . urlencode($token);
            $ticketLink      = $baseUrl . '/pdf/ticket.php?registration_id=' . $registrationId . '&token=' . urlencode($token);

            // Replace placeholders
            $html = str_replace(
                ['{{PARTICIPANT_NAME}}', '{{EVENT_TITLE}}', '{{EVENT_DATE}}', '{{EVENT_LOCATION}}', '{{UNSUBSCRIBE_LINK}}', '{{TICKET_LINK}}', '{{YEAR}}'],
                [htmlspecialchars($name), htmlspecialchars($event['title']), $formattedDate, htmlspecialchars($event['location']), $unsubscribeLink, $ticketLink, date('Y')],
                $html
            );

            $mail = createMailer();
            $mail->addAddress($email, $name);
            $mail->Subject = 'Confirmation d\'inscription : ' . $event['title'];
            $mail->Body    = $html;
            $mail->AltBody = "Bonjour $name,\n\nVotre inscription pour '{$event['title']}' est confirmée.\nDate: $formattedDate\nLieu: {$event['location']}\n\nTicket: $ticketLink";

            // Attach PDF ticket only if pdf/ticket.php exists
            $tempPdfPath = null;
            if (file_exists(__DIR__ . '/../pdf/ticket.php')) {
                try {
                    require_once __DIR__ . '/../pdf/ticket.php';
                    $tempPdfPath = sys_get_temp_dir() . '/ticket_' . $registrationId . '_' . uniqid() . '.pdf';
                    generateTicketPDF($pdo, $registrationId, $token, 'F', $tempPdfPath);
                    if (file_exists($tempPdfPath)) {
                        $mail->addAttachment($tempPdfPath, 'ticket-eventhub.pdf');
                    }
                } catch (Throwable $pdfErr) {
                    error_log('[EventHub] PDF ticket error: ' . $pdfErr->getMessage());
                }
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('[EventHub] SendConfirmation error: ' . $e->getMessage());
            if (function_exists('logMailError')) {
                logMailError($pdo, 'confirmation', $email, $e->getMessage());
            }
            return false;
        } finally {
            if (!empty($tempPdfPath) && file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
        }
    }

    private static function buildFallbackHtml(string $name, array $event, string $token, int $registrationId): string
    {
        $title    = htmlspecialchars($event['title']);
        $date     = date('d/m/Y à H\hi', strtotime($event['event_date']));
        $location = htmlspecialchars($event['location']);
        return "
        <html><body style='font-family:sans-serif;color:#1e293b;padding:32px'>
        <h2 style='color:#2563eb'>Inscription confirmée !</h2>
        <p>Bonjour <strong>" . htmlspecialchars($name) . "</strong>,</p>
        <p>Votre inscription à <strong>$title</strong> est confirmée.</p>
        <p>📅 $date</p>
        <p>📍 $location</p>
        <hr/>
        <p style='color:#64748b;font-size:12px'>EventHub Pro · ENSA Marrakech</p>
        </body></html>";
    }
}
?>