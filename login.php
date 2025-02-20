<?php
$conn=mysqli_connect("localhost","root","","login");
if($conn){
    $data="SELECT * FROM users where email='".$_POST['email']."' and password='".$_POST['password']."'";
    $check=mysqli_query($conn, $data);
    if(mysqli_num_rows($check)> 0){
        session_start();
        $_SESSION['logged_in'] = true;
        echo'
        <script>
            function login(){
                alert("Login Successfully");
                window.location.href="index.php";   
            }
            login();
            </script>';
            exit();
    }
    
    else{
        echo '<script>
        function login(){
            alert("Invalid Email or Password");
            window.location.href="login.html";   
        }
            login();
        </script>';
    }
}
?>