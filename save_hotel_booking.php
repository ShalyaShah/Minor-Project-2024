<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get JSON data from the request
    $jsonData = file_get_contents('php://input');
    $bookingData = json_decode($jsonData, true);

    if (!$bookingData) {
        throw new Exception('Invalid booking data');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Generate booking reference
    $bookingReference = 'HTL' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    // Insert booking data
    $sql = "INSERT INTO hotel_bookings (booking_reference, hotel_id, room_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, total_price, payment_method) 
            VALUES (:booking_reference, :hotel_id, :room_id, :guest_name, :guest_email, :guest_phone, :check_in_date, :check_out_date, :total_price, :payment_method)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'booking_reference' => $bookingReference,
        'hotel_id' => $bookingData['hotel']['id'],
        'room_id' => $bookingData['roomId'],
        'guest_name' => $bookingData['guests'][0]['firstName'] . ' ' . $bookingData['guests'][0]['lastName'],
        'guest_email' => 'example@example.com', // Replace with actual email
        'guest_phone' => '1234567890', // Replace with actual phone
        'check_in_date' => $bookingData['hotel']['checkInDate'],
        'check_out_date' => $bookingData['hotel']['checkOutDate'],
        'total_price' => $bookingData['hotel']['price_per_night'],
        'payment_method' => $bookingData['paymentMethod'],
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode(['success' => true, 'bookingReference' => $bookingReference]);
} catch (Exception $e) {
    // Rollback transaction if there was an error
    if (isset($pdo)) {
        $pdo->rollBack();
    }

    // Log the error
    error_log('Booking error: ' . $e->getMessage());

    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>