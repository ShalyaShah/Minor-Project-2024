<?php
$conn=mysqli_connect("localhost","root","","minor-project");
if($conn){
    $fname=$_POST['fname'];
    $lname=$_POST['lname'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    
    $data="INSERT INTO users values('','$fname','$lname','$email','$password')";
    $check=mysqli_query($conn, $data);
    if($check){
       echo '<script>
            function datainserted(){
                alert("Data Inserted Successfully");
                window.location.href="login.html";   
            }
            datainserted();
            
       </script>';
    }
    else{
        echo"Failed";
    }
}
else{
    echo"Failed";
}


?>