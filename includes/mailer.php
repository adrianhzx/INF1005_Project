<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// for composer. dont remove the vendor file.
require_once __DIR__ . '/../vendor/autoload.php'; 

function sendVerificationEmail($toEmail, $verifyUrl) {
    // instantiate PHPMailer
    $mail = new PHPMailer(true);

    try {
        // --- Server settings ---
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                       
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'ekeaforsit@gmail.com';                
        $mail->Password   = 'lbyqqvuilremjeeh';                    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
        $mail->Port       = 587;                                 

        // --- Recipients ---
        $mail->setFrom('noreply@ekea.localhost', 'EKEA Support');
        $mail->addAddress($toEmail);                            

        // --- Content ---
        $mail->isHTML(true);                                    
        $mail->Subject = 'Verify your EKEA account';
        
        // The HTML body of the email
        $mail->Body    = "
            <h1>Welcome to EKEA</h1>
            <p>Thank you for registering with Ekea!</p>
            <p>Please click the link below to verify your email address and activate your account:</p>
            <p><a href='{$verifyUrl}'>{$verifyUrl}</a></p>
            <br>
            <p>If you did not create an account, no further action is required.</p>
        ";
        
        // The plain-text fallback for non-HTML mail clients
        $mail->AltBody = "Welcome to EKEA!\n\nPlease go to the following link to verify your email address:\n{$verifyUrl}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the phpmailer error message
        ekea_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}", 'ERROR');
        return false;
    }
}