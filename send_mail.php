<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'vendor/autoload.php'; // Adjust path if you downloaded manually

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = htmlspecialchars(trim($_POST["name"]));
    $email   = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST["subject"])) ?: 'No Subject';
    $message = htmlspecialchars(trim($_POST["message"]));

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // 🔁 Replace with your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'vkmsdrs55@gmail.com'; // 🔁 Your SMTP username
        $mail->Password   = 'Hehe1234.';          // 🔁 Your SMTP password
        $mail->SMTPSecure = 'tls';                    // Or 'ssl'
        $mail->Port       = 587;                      // Or 465 for SSL

        // Recipients
        $mail->setFrom('akidhoka@gmail.com', 'GoTrip Contact');
        $mail->addAddress('vkmsdrs55@gmail.com', 'GoTrip Admin'); // 🔁 Where to send the form

        $mail->addReplyTo($email, $name); // Allow reply to sender

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Contact Form - $subject";
        $mail->Body    = "
            <h2>New Contact Message</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>$message</p>
        ";

        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message";

        $mail->send();
        echo "<script>alert('Message sent successfully!'); window.location.href = 'contact.html';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.history.back();</script>";
    }
} else {
    header("Location: contact.html");
    exit();
}
?>
