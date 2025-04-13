<?php
// Check for admin session
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$fname = isset($_POST['fname']) ? $conn->real_escape_string($_POST['fname']) : '';
$lname = isset($_POST['lname']) ? $conn->real_escape_string($_POST['lname']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : ''; // Store as plain text if provided
$wallet_balance = isset($_POST['wallet_balance']) ? floatval($_POST['wallet_balance']) : 0.00;
$is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;

// Validate data
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit();
}

if (empty($fname) || empty($lname) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
    exit();
}

// Check if email already exists for another user
$check_query = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
$check_result = $conn->query($check_query);
if ($check_result && $check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists for another user']);
    exit();
}

// Update user
$query = "UPDATE users SET 
          fname = '$fname', 
          lname = '$lname', 
          email = '$email', ";

// Only update password if provided
if (!empty($password)) {
    $query .= "password = '$password', ";
}

$query .= "wallet_balance = $wallet_balance, 
          is_admin = $is_admin 
          WHERE id = $user_id";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update user: ' . $conn->error]);
}

$conn->close();
?>