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
$passenger_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = isset($_POST['title']) ? $conn->real_escape_string($_POST['title']) : '';
$first_name = isset($_POST['first_name']) ? $conn->real_escape_string($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? $conn->real_escape_string($_POST['last_name']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? $conn->real_escape_string($_POST['date_of_birth']) : '';
$passport_number = isset($_POST['passport_number']) ? $conn->real_escape_string($_POST['passport_number']) : '';
$nationality = isset($_POST['nationality']) ? $conn->real_escape_string($_POST['nationality']) : '';

// Validate data
if ($passenger_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid passenger ID']);
    exit();
}

if (empty($title) || empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($passport_number) || empty($nationality)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
    exit();
}

// Update passenger
$query = "UPDATE flight_passenger_info SET 
          title = '$title', 
          first_name = '$first_name', 
          last_name = '$last_name', 
          date_of_birth = '$date_of_birth', 
          passport_number = '$passport_number', 
          nationality = '$nationality' 
          WHERE id = $passenger_id";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'Passenger updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update passenger: ' . $conn->error]);
}

$conn->close();
?>