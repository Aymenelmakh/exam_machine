<?php
require_once __DIR__ . '/../config/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AlertMailer
{
    public static function sendCapacityAlert(PDO $pdo, array $event): bool
    {
        try {
            // Atomic UPDATE — prevents duplicate alerts even under concurrent requests
            $stmt = $pdo->prepare("UPDATE events SET alert_sent = 1 WHERE id = :id AND alert_sent = 0");
            $stmt->execute([':id' => $event['id']]);

            if ($stmt->rowCount() === 0) {
                return false; // Already sent by another process
            }

            // Build HTML
            $templatePath = __DIR__ . '/templates/alert.html';
            if (!file_exists($templatePath)) {
                $html = self::buildFallbackHtml($event);
            } else {
                $html = file_get_contents($templatePath);
            }

            $registered = (int)($event['registered_count'] ?? 0);
            $capacity   = (int)($event['capacity'] ?? 1);
            $fillPct    = $capacity > 0 ? round(($registered / $capacity) * 100) : 0;

            $html = str_replace(
                ['{{ORGANIZER_NAME}}', '{{EVENT_TITLE}}', '{{FILL_PCT}}', '{{REGISTERED}}', '{{CAPACITY}}'],
                ['Organisateur', htmlspecialchars($event['title']), $fillPct, $registered, $capacity],
                $html
            );

            $mail = createMailer();
            $mail->addAddress($event['organizer_email']);
            $mail->Subject = '⚠️ Alerte capacité — ' . $event['title'];
            $mail->Body    = $html;
            $mail->AltBody = "Votre événement '{$event['title']}' a atteint {$fillPct}% de sa capacité ({$registered}/{$capacity}).";

            // Attach PDF report only if pdf/report.php exists
            $tempPdf = null;
            if (file_exists(__DIR__ . '/../pdf/report.php')) {
                try {
                    require_once __DIR__ . '/../pdf/report.php';
                    $tempPdf = sys_get_temp_dir() . '/report_' . $event['id'] . '_' . time() . '.pdf';
                    generateReportPDF($pdo, $event['id'], 'F', $tempPdf);
                    if (file_exists($tempPdf)) {
                        $mail->addAttachment($tempPdf, 'rapport_inscriptions.pdf');
                    }
                } catch (Throwable $pdfErr) {
                    error_log('[EventHub] PDF report error: ' . $pdfErr->getMessage());
                }
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('[EventHub] AlertMailer error: ' . $e->getMessage());
            if (function_exists('logMailError')) {
                logMailError($pdo, 'capacity_alert', $event['organizer_email'] ?? '', $e->getMessage());
            }
            return false;
        } finally {
            if (!empty($tempPdf) && file_exists($tempPdf)) {
                @unlink($tempPdf);
            }
        }
    }

    private static function buildFallbackHtml(array $event): string
    {
        $title      = htmlspecialchars($event['title']);
        $registered = (int)($event['registered_count'] ?? 0);
        $capacity   = (int)($event['capacity'] ?? 1);
        $fillPct    = round(($registered / $capacity) * 100);
        return "
        <html><body style='font-family:sans-serif;color:#1e293b;padding:32px'>
        <h2 style='color:#f59e0b'>⚠️ Alerte capacité</h2>
        <p>L'événement <strong>$title</strong> a atteint <strong style='color:#dc2626'>{$fillPct}%</strong> de sa capacité.</p>
        <p>Inscrits : <strong>$registered / $capacity</strong></p>
        <hr/>
        <p style='color:#64748b;font-size:12px'>EventHub Pro · ENSA Marrakech</p>
        </body></html>";
    }
}
?>