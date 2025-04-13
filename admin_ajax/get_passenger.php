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

// Get passenger ID
$passenger_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($passenger_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid passenger ID']);
    exit();
}

// Get passenger details with booking reference
$query = "SELECT fpi.*, fb.booking_reference 
          FROM flight_passenger_info fpi 
          LEFT JOIN flight_booked fb ON fpi.booking_id = fb.id 
          WHERE fpi.id = $passenger_id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $passenger = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $passenger]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Passenger not found']);
}

$conn->close();
?>