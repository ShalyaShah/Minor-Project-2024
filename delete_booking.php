<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Database connection parameters
$host = 'localhost';
$dbname = 'minor-project';
$username = 'root';
$password = '';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if booking_id is provided
    if (isset($_POST['booking_id'])) {
        $bookingId = $_POST['booking_id'];

        // Delete the booking
        $sql = "DELETE FROM flight_booked WHERE id = :booking_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Booking deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>