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
    $bookingReference = 'GT' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    // Check if all required fields exist in the hotel object
    if (empty($bookingData['hotel']['id'])) {
        throw new Exception('Missing hotel ID');
    }

    // Get check-in and check-out dates from searchData if available
    $checkInDate = date('Y-m-d'); // Default to today
    $checkOutDate = date('Y-m-d', strtotime('+1 day')); // Default to tomorrow
    
    if (!empty($bookingData['searchData'])) {
        if (!empty($bookingData['searchData']['checkIn'])) {
            $checkInDate = $bookingData['searchData']['checkIn'];
        }
        if (!empty($bookingData['searchData']['checkOut'])) {
            $checkOutDate = $bookingData['searchData']['checkOut'];
        }
    }
    
    // Calculate number of nights
    $checkInDateTime = new DateTime($checkInDate);
    $checkOutDateTime = new DateTime($checkOutDate);
    $interval = $checkInDateTime->diff($checkOutDateTime);
    $nights = $interval->days > 0 ? $interval->days : 1;
    
    // Calculate total price
    $pricePerNight = $bookingData['hotel']['price_per_night'] ?? 0;
    $totalPrice = $pricePerNight * $nights;

    // Check if guest information exists
    if (empty($bookingData['guests'][0]['firstName']) || 
        empty($bookingData['guests'][0]['lastName'])) {
        throw new Exception('Missing guest information');
    }

    // Get guest email and phone (with defaults if not provided)
    $guestEmail = $bookingData['guests'][0]['email'] ?? 'guest@example.com';
    $guestPhone = $bookingData['guests'][0]['phone'] ?? '0000000000';

    // Insert booking data
    $sql = "INSERT INTO hotel_bookings (booking_reference, hotel_id, room_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, nights, total_price, payment_method) 
            VALUES (:booking_reference, :hotel_id, :room_id, :guest_name, :guest_email, :guest_phone, :check_in_date, :check_out_date, :nights, :total_price, :payment_method)";
    
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
        'nights' => $nights,
        'total_price' => $totalPrice,
        'payment_method' => $bookingData['paymentMethod'],
    ]);

    // Insert additional guests if any
    if (count($bookingData['guests']) > 1) {
        $bookingId = $pdo->lastInsertId();
        $guestSql = "INSERT INTO booking_guests (booking_id, first_name, last_name, email, phone) 
                     VALUES (:booking_id, :first_name, :last_name, :email, :phone)";
        $guestStmt = $pdo->prepare($guestSql);
        
        // Start from the second guest (index 1)
        for ($i = 1; $i < count($bookingData['guests']); $i++) {
            $guest = $bookingData['guests'][$i];
            $guestStmt->execute([
                'booking_id' => $bookingId,
                'first_name' => $guest['firstName'],
                'last_name' => $guest['lastName'],
                'email' => $guest['email'] ?? '',
                'phone' => $guest['phone'] ?? '',
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return success response with additional data for confirmation
    echo json_encode([
        'success' => true, 
        'bookingReference' => $bookingReference,
        'checkIn' => $checkInDate,
        'checkOut' => $checkOutDate,
        'nights' => $nights,
        'totalAmount' => $totalPrice
    ]);
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