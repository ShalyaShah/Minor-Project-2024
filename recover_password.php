<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "minor-project");

if ($conn) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($check) > 0) {
        // Generate 4-digit code
        $code = rand(1000, 9999);
        
        // Store the code and email in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;
        
        // Update the recovery code in database
        mysqli_query($conn, "UPDATE users SET recovery_code='$code' WHERE email='$email'");
        
        echo "
        <script>
            alert('Recovery code: $code\\nIn a real application, this would be sent to your email.');
            window.location.href = 'login.html#verifyCode';
        </script>";
    } else {
        echo "
        <script>
            alert('Email not found in our records!');
            window.location.href = 'login.html';
        </script>";
    }
} else {
    echo "
    <script>
        alert('Database connection failed!');
        window.location.href = 'login.html';
    </script>";
}
?>