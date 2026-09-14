<?php
/* common_email_helper.php - Modular SMTP Relay Configuration */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CRITICAL PATH FIX: Move up one folder level to find your existing website dependencies
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

function sendCommonEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // --- INHERITED BREVO SMTP CONFIGURATION ---
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOURUSERNAMEHERE';
		$mail->Password   = COMMON_SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Branding adjustments to map perfectly to your anti-slop platform vision
        $mail->setFrom('system@slothscape.com', 'Common Marketplace');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Common Mail System Error: {$mail->ErrorInfo}");
        return false;
    }
}
// Encrypts plain text fields securely before saving them to disk memory
function common_encrypt($plainText) {
    if (empty($plainText)) return '';
    $key = COMMON_ENCRYPTION_KEY;
    $cipher = "AES-128-CTR"; 
    $iv_length = openssl_cipher_iv_length($cipher);
    $options = 0;
    $encryption_iv = '1234567891011121'; // Standard fixed Initialization Vector block for prototype
    
    return openssl_encrypt($plainText, $cipher, $key, $options, $encryption_iv);
}

// Decrypts scrambled data fields back into plain text when the server needs to read them
function common_decrypt($encryptedText) {
    if (empty($encryptedText)) return '';
    $key = COMMON_ENCRYPTION_KEY;
    $cipher = "AES-128-CTR";
    $decryption_iv = '1234567891011121';
    $options = 0;
    
    return openssl_decrypt($encryptedText, $cipher, $key, $options, $decryption_iv);
}

?>
