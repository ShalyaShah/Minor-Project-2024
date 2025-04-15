<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "minor-project");

    $email = $_SESSION['reset_email'];
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    
    // Update password
    $update = mysqli_query($conn, "UPDATE users SET password='$new_password', recovery_code=NULL WHERE email='$email'");
    
    if ($update) {
        // Clear all session variables
        session_unset();
        session_destroy();
        
        echo "
        <script>
            alert('Password reset successful! Please login with your new password.');
            window.location.href = 'login.html';
        </script>";
    } else {
        echo "
        <script>
            alert('Failed to reset password!');
            window.location.href = 'login.html';
        </script>";
    }
?>