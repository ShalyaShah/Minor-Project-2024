<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require 'vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'gotrip.minorproject@gmail.com';                     //SMTP username
    $mail->Password   = 'wwfs dcfp zrlv rbio';                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('gotrip.minorproject@gmail.com', 'Mailer');
    $mail->addAddress('shalyashah2802@gmail.com', 'Joe User');     //Add a recipient

    //Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'Login Alert - Welcome Back to GoTrip!';
    $mail->Body    = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <h2 style="color: #4CAF50;">Welcome Back to GoTrip!</h2>
            <p>Hi [User Name],</p>
            <p>We noticed a login to your GoTrip account just now. If this was you, welcome back! Here are the details of the login:</p>
            <ul>
                <li><strong>Date & Time:</strong> [Login Date & Time]</li>
                <li><strong>IP Address:</strong> [User IP Address]</li>
                <li><strong>Device:</strong> [User Device/Browser]</li>
            </ul>
            <p>If this wasn’t you, please <a href="https://www.gotrip.com/reset-password" style="color: #4CAF50; text-decoration: none;">reset your password</a> immediately to secure your account.</p>
            <p>We’re here to help if you have any questions. Feel free to <a href="https://www.gotrip.com/contact-us" style="color: #4CAF50; text-decoration: none;">contact our support team</a>.</p>
            <p>Happy traveling,</p>
            <p><strong>The GoTrip Team</strong></p>
            <hr>
            <p style="font-size: 12px; color: #777;">If you didn’t log in or no longer wish to receive these notifications, you can update your preferences in your account settings.</p>
        </body>
        </html>';
    $mail->AltBody = 'Hi [User Name], 
    
    We noticed a login to your GoTrip account just now. If this was you, welcome back! Here are the details of the login:
    
    - Date & Time: [Login Date & Time]
    - IP Address: [User IP Address]
    - Device: [User Device/Browser]
    
    If this wasnt you, please reset your password immediately to secure your account: https://www.gotrip.com/reset-password
    
    Were here to help if you have any questions. Feel free to contact our support team: https://www.gotrip.com/contact-us.
    
    Happy traveling,
    The GoTrip Team
    
    If you didnt log in or no longer wish to receive these notifications, you can update your preferences in your account settings.';
    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}