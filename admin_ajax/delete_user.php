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

// Get user ID
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit();
}

// Prevent deleting the current admin user
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
    exit();
}

// Delete user
$query = "DELETE FROM users WHERE id = $user_id";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $conn->error]);
}

$conn->close();
?>