<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Check if booking reference is provided
if (!isset($_POST['booking_reference']) || empty($_POST['booking_reference'])) {
    echo json_encode(["status" => "error", "message" => "Booking reference is required"]);
    exit();
}

$booking_reference = $_POST['booking_reference'];

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete passengers first (foreign key constraint)
    $delete_passengers_query = "DELETE FROM flight_passenger_info WHERE booking_reference = ?";
    $delete_passengers_stmt = $conn->prepare($delete_passengers_query);
    $delete_passengers_stmt->bind_param("s", $booking_reference);
    $delete_passengers_stmt->execute();
    $delete_passengers_stmt->close();
    
    // Delete booking
    $delete_booking_query = "DELETE FROM flight_booked WHERE booking_reference = ?";
    $delete_booking_stmt = $conn->prepare($delete_booking_query);
    $delete_booking_stmt->bind_param("s", $booking_reference);
    $delete_booking_stmt->execute();
    $delete_booking_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(["status" => "success", "message" => "Booking deleted successfully"]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Failed to delete booking: " . $e->getMessage()]);
}

$conn->close();
?>