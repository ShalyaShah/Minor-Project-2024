<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$dbname = 'minor-project';
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
    $bookingReference = 'FLT' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    
    // Extract data
    $flight = $bookingData['flight'];
    $searchParams = $bookingData['searchParams'];
    $contactInfo = $bookingData['contactInfo'];
    
    // Prepare flight_booked table data
    $flightBookedData = [
        'booking_reference' => $bookingReference,
        'origin_code' => $searchParams['originCode'],
        'destination_code' => $searchParams['destinationCode'],
        'origin_name' => $searchParams['origin'],
        'destination_name' => $searchParams['destination'],
        'departure_date' => $searchParams['departureDate'],
        'total_passengers' => $searchParams['adults'],
        'total_amount' => $flight['price']['total'],
        'currency' => $flight['price']['currency'],
        'contact_email' => $contactInfo['email'],
        'contact_phone' => $contactInfo['phone'],
        'booking_date' => date('Y-m-d H:i:s'),
        'flight_details' => json_encode($flight)
    ];
    
    // Debug log
    error_log('Inserting flight booking data: ' . print_r($flightBookedData, true));
    
    // Insert into flight_booked table
    $sql = "INSERT INTO flight_booked (
                booking_reference, origin_code, destination_code, origin_name, 
                destination_name, departure_date, total_passengers, total_amount, 
                currency, contact_email, contact_phone, booking_date, flight_details
            ) VALUES (
                :booking_reference, :origin_code, :destination_code, :origin_name, 
                :destination_name, :departure_date, :total_passengers, :total_amount, 
                :currency, :contact_email, :contact_phone, :booking_date, :flight_details
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($flightBookedData);
    $bookingId = $pdo->lastInsertId();
    
    // Insert passenger information
    foreach ($bookingData['passengers'] as $passenger) {
        $passengerData = [
            'booking_id' => $bookingId,
            'title' => $passenger['title'],
            'first_name' => $passenger['firstName'],
            'last_name' => $passenger['lastName'],
            'date_of_birth' => $passenger['dob'],
            'passport_number' => $passenger['passport'],
            'nationality' => $passenger['nationality']
        ];
        
        $sql = "INSERT INTO flight_passenger_info (
                    booking_id, title, first_name, last_name, 
                    date_of_birth, passport_number, nationality
                ) VALUES (
                    :booking_id, :title, :first_name, :last_name, 
                    :date_of_birth, :passport_number, :nationality
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($passengerData);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingReference,
        'message' => 'Booking successfully saved'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if there was an error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Booking error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>