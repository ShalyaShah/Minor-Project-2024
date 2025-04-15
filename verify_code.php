<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "minor-project");

if ($conn) {
    $entered_code = mysqli_real_escape_string($conn, $_POST['code']);
    $email = $_SESSION['reset_email'];
    
    // Verify the code
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND recovery_code='$entered_code'");
    
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['code_verified'] = true;
        echo "
        <script>
            alert('Code verified successfully!');
            window.location.href = 'login.html#resetPassword';
        </script>";
    } else {
        echo "
        <script>
            alert('Invalid code!');
            window.location.href = 'login.html#verifyCode';
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