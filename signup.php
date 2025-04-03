<?php
// Database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
$conn = mysqli_connect("localhost", "root", "", "minor-project");
if ($conn) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Insert data into the users table, including wallet_balance with a default value of 0.00
    $data = "INSERT INTO users (id, fname, lname, email, password, wallet_balance) 
             VALUES ('', '$fname', '$lname', '$email',  '$password', 0.00)";
    
    $check = mysqli_query($conn, $data);
    if ($check) {

        // Load Composer's autoloader (created by composer, not included with PHPMailer)
        require 'vendor/autoload.php';

        // Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'gotrip.minorproject@gmail.com';        // SMTP username
            $mail->Password   = 'wwfs dcfp zrlv rbio';                  // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
            $mail->Port       = 465;                                    // TCP port to connect to

            // Recipients
            $mail->setFrom('gotrip.minorproject@gmail.com', 'GoTrip');
            $mail->addAddress($email, $fname . ' ' . $lname);           // Add recipient (user's email)

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = 'Welcome to GoTrip!';
            $mail->Body    = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <h2 style="color: #4CAF50;">Welcome to GoTrip!</h2>
                    <p>Hi ' . $fname . ' ' . $lname . ',</p>
                    <p>Thank you for signing up with GoTrip. We’re excited to have you on board!</p>
                    <p>Start exploring amazing travel destinations and plan your next adventure with us.</p>
                    <p>If you have any questions, feel free to <a href="https://www.gotrip.com/contact-us" style="color: #4CAF50; text-decoration: none;">contact our support team</a>.</p>
                    <p>Happy traveling,</p>
                    <p><strong>The GoTrip Team</strong></p>
                    <hr>
                    <p style="font-size: 12px; color: #777;">If you didn’t sign up for this account, please ignore this email.</p>
                </body>
                </html>';
            $mail->AltBody = 'Hi ' . $fname . ' ' . $lname . ', 
    
            Thank you for signing up with GoTrip. We’re excited to have you on board!
            
            Start exploring amazing travel destinations and plan your next adventure with us.
            
            If you have any questions, feel free to contact our support team: https://www.gotrip.com/contact-us.
            
            Happy traveling,
            The GoTrip Team
            
            If you didn’t sign up for this account, please ignore this email.';

            $mail->send();
            echo '<script>
                function datainserted() {
                    alert("Data Inserted Successfully. A welcome email has been sent to your email address.");
                    window.location.href = "login.html";   
                }
                datainserted();
            </script>';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Failed to insert data";
    }
} else {
    echo "Failed to connect to the database";
}
?>