<?php
$conn=mysqli_connect("localhost","root","","login");
if($conn){
    $data="SELECT * FROM users where email='".$_POST['email']."' and password='".$_POST['password']."'";
    $check=mysqli_query($conn, $data);
    if(mysqli_num_rows($check)> 0){
        echo'
        <script>
            function login(){
                alert("Login Successfully");
                window.location.href="main.html";   
            }
            login();
            </script>';
    }
    else{
        echo '<script>
        function login(){
            alert("Invalid Email or Password");
            window.location.href="index.html";   
        }';
    }
}