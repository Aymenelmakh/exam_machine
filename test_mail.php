<?php
require_once __DIR__ . '/config/mailer.php';

try {
    $mail = createMailer();
    $mail->SMTPDebug = 2;
    $mail->addAddress('test@example.com'); // put your real email here
    $mail->Subject = 'Test EventHub';
    $mail->Body    = '<b>Test mail works!</b>';
    $mail->AltBody = 'Test mail works!';
    $mail->send();
    echo "SENT OK";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage();
}