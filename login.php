<?php
// Connect to the database
$conn = mysqli_connect("localhost", "root", "", "minor-project");

if ($conn) {
    // Fetch the user based on the provided email and password
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $data = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $check = mysqli_query($conn, $data);

    if (mysqli_num_rows($check) > 0) {
        // Start the session
        session_start();

        // Fetch the user data
        $user = mysqli_fetch_assoc($check);

        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];  // Store the user's ID in the session
        $_SESSION['email'] = $user['email']; // Store the user's email in the session
        $_SESSION['name'] = $user['name'];   // Optionally store the user's name if available

        // Redirect to the index page with a success message
        echo '
        <script>
            function login() {
                alert("Login Successfully");
                window.location.href = "index.php";   
            }
            login();
        </script>';
        exit();
    } else {
        // Invalid email or password
        echo '
        <script>
            function login() {
                alert("Invalid Email or Password");
                window.location.href = "login.html";   
            }
            login();
        </script>';
    }
} else {
    // Database connection failed
    echo '
    <script>
        alert("Database connection failed. Please try again later.");
        window.location.href = "login.html";
    </script>';
}
?>