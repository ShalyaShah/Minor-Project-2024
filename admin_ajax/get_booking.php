<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Check if booking reference is provided
if (!isset($_GET['booking_reference']) || empty($_GET['booking_reference'])) {
    echo json_encode(["status" => "error", "message" => "Booking reference is required"]);
    exit();
}

$booking_reference = $_GET['booking_reference'];

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Check the structure of flight_booked table
$table_info_query = "SHOW COLUMNS FROM flight_booked";
$table_info_result = $conn->query($table_info_query);
$has_user_id = false;
$user_id_field = '';

if ($table_info_result) {
    while ($column = $table_info_result->fetch_assoc()) {
        if ($column['Field'] == 'user_id') {
            $has_user_id = true;
            $user_id_field = 'user_id';
            break;
        } else if ($column['Field'] == 'userid') {
            $has_user_id = true;
            $user_id_field = 'userid';
            break;
        }
    }
}

// Get booking data
if ($has_user_id) {
    $booking_query = "SELECT fb.*, u.fname, u.lname, u.email 
                     FROM flight_booked fb 
                     LEFT JOIN users u ON fb.$user_id_field = u.id 
                     WHERE fb.booking_reference = ?";
} else {
    $booking_query = "SELECT fb.* 
                     FROM flight_booked fb 
                     WHERE fb.booking_reference = ?";
}

$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("s", $booking_reference);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

if ($booking_result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Booking not found"]);
    $booking_stmt->close();
    $conn->close();
    exit();
}

$booking = $booking_result->fetch_assoc();
$booking_stmt->close();

// Get passenger data
$passengers_query = "SELECT * FROM flight_passenger_info WHERE booking_reference = ?";
$passengers_stmt = $conn->prepare($passengers_query);
$passengers_stmt->bind_param("s", $booking_reference);
$passengers_stmt->execute();
$passengers_result = $passengers_stmt->get_result();

$passengers = [];
while ($passenger = $passengers_result->fetch_assoc()) {
    $passengers[] = $passenger;
}
$passengers_stmt->close();

$conn->close();

// Return booking and passenger data
echo json_encode([
    "status" => "success",
    "data" => [
        "booking" => $booking,
        "passengers" => $passengers
    ]
]);
?>