<?php
// Disable displaying errors in the output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'hotel_booking_errors.log');

// Always set content type to JSON
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

try {
    // Get JSON data from the request
    $jsonData = file_get_contents('php://input');
    $bookingData = json_decode($jsonData, true);

    if (!$bookingData) {
        throw new Exception('Invalid booking data');
    }

    // Debug: Log the received data
    error_log('Received booking data: ' . print_r($bookingData, true));

    // Validate required fields
    if (empty($bookingData['hotel']) || 
        empty($bookingData['roomId']) || 
        empty($bookingData['guests']) || 
        empty($bookingData['paymentMethod'])) {
        throw new Exception('Missing required booking data');
    }

    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $pdo->beginTransaction();

    // Generate booking reference
    $bookingReference = 'HTL' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    // Check if all required fields exist in the hotel object
    if (empty($bookingData['hotel']['id'])) {
        throw new Exception('Missing hotel ID');
    }

    // Check if check-in and check-out dates exist
    $checkInDate = $bookingData['hotel']['checkInDate'] ?? date('Y-m-d');
    $checkOutDate = $bookingData['hotel']['checkOutDate'] ?? date('Y-m-d', strtotime('+1 day'));
    
    // Check if price exists
    $totalPrice = $bookingData['hotel']['price_per_night'] ?? 0;

    // Check if guest information exists
    if (empty($bookingData['guests'][0]['firstName']) || 
        empty($bookingData['guests'][0]['lastName'])) {
        throw new Exception('Missing guest information');
    }

    // Get guest email and phone (with defaults if not provided)
    $guestEmail = $bookingData['guests'][0]['email'] ?? 'guest@example.com';
    $guestPhone = $bookingData['guests'][0]['phone'] ?? '0000000000';

    // Insert booking data
    $sql = "INSERT INTO hotel_bookings (booking_reference, hotel_id, room_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, total_price, payment_method) 
            VALUES (:booking_reference, :hotel_id, :room_id, :guest_name, :guest_email, :guest_phone, :check_in_date, :check_out_date, :total_price, :payment_method)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'booking_reference' => $bookingReference,
        'hotel_id' => $bookingData['hotel']['id'],
        'room_id' => $bookingData['roomId'],
        'guest_name' => $bookingData['guests'][0]['firstName'] . ' ' . $bookingData['guests'][0]['lastName'],
        'guest_email' => $guestEmail,
        'guest_phone' => $guestPhone,
        'check_in_date' => $checkInDate,
        'check_out_date' => $checkOutDate,
        'total_price' => $totalPrice,
        'payment_method' => $bookingData['paymentMethod'],
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode(['success' => true, 'bookingReference' => $bookingReference]);
    exit;
    
} catch (PDOException $e) {
    // Database-specific error handling
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Database error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
    
} catch (Exception $e) {
    // General error handling
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Booking error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>