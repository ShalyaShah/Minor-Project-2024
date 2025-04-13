<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Check if form data is submitted
if (!isset($_POST['booking_reference']) || empty($_POST['booking_reference'])) {
    echo json_encode(["status" => "error", "message" => "Booking reference is required"]);
    exit();
}

// Get form data
$booking_reference = $_POST['booking_reference'];
$departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : null;
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Update booking
$update_query = "UPDATE flight_booked SET ";
$params = [];
$types = "";

if ($departure_date) {
    $update_query .= "departure_date = ?, ";
    $params[] = $departure_date;
    $types .= "s";
}

if ($total_amount) {
    $update_query .= "total_amount = ?, ";
    $params[] = $total_amount;
    $types .= "d";
}

// Remove trailing comma and space
$update_query = rtrim($update_query, ", ");

$update_query .= " WHERE booking_reference = ?";
$params[] = $booking_reference;
$types .= "s";

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param($types, ...$params);

if ($update_stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Booking updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update booking: " . $update_stmt->error]);
}

$update_stmt->close();
$conn->close();
?>