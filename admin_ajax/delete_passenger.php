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

// Get passenger ID
$passenger_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($passenger_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid passenger ID']);
    exit();
}

// Delete passenger
$query = "DELETE FROM flight_passenger_info WHERE id = $passenger_id";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'Passenger deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete passenger: ' . $conn->error]);
}

$conn->close();
?>