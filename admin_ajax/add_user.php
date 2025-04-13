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
$fname = isset($_POST['fname']) ? $conn->real_escape_string($_POST['fname']) : '';
$lname = isset($_POST['lname']) ? $conn->real_escape_string($_POST['lname']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : ''; // Store as plain text as requested
$wallet_balance = isset($_POST['wallet_balance']) ? floatval($_POST['wallet_balance']) : 0.00;
$is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;

// Validate data
if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
    exit();
}

// Check if email already exists
$check_query = "SELECT id FROM users WHERE email = '$email'";
$check_result = $conn->query($check_query);
if ($check_result && $check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit();
}

// Add user
$query = "INSERT INTO users (fname, lname, email, password, wallet_balance, is_admin) 
          VALUES ('$fname', '$lname', '$email', '$password', $wallet_balance, $is_admin)";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'User added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add user: ' . $conn->error]);
}

$conn->close();
?>