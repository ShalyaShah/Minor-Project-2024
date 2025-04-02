<?php
$conn = mysqli_connect("localhost", "root", "", "minor-project");
if ($conn) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Insert data into the users table, including wallet_balance with a default value of 0.00
    $data = "INSERT INTO users (id, fname, lname, email, password, wallet_balance) 
             VALUES ('', '$fname', '$lname', '$email', '$password', 0.00)";
    
    $check = mysqli_query($conn, $data);
    if ($check) {
        echo '<script>
            function datainserted() {
                alert("Data Inserted Successfully");
                window.location.href = "login.html";   
            }
            datainserted();
        </script>';
    } else {
        echo "Failed to insert data";
    }
} else {
    echo "Failed to connect to the database";
}
?>