<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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
    
    // Send booking details via email
    require 'vendor/autoload.php'; // Load PHPMailer

    

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gotrip.minorproject@gmail.com'; // Your email
        $mail->Password = 'wwfs dcfp zrlv rbio'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('gotrip.minorproject@gmail.com', 'GoTrip');
        $mail->addAddress($contactInfo['email'], $contactInfo['name']); // User's email and name

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your Booking Confirmation - ' . $bookingReference;
        $mail->Body = '
            <html>
            <body>
                <h2>Booking Confirmation</h2>
                <p>Dear ' . $contactInfo['name'] . ',</p>
                <p>Thank you for booking with GoTrip! Here are your booking details:</p>
                <ul>
                    <li><strong>Booking Reference:</strong> ' . $bookingReference . '</li>
                    <li><strong>Origin:</strong> ' . $searchParams['origin'] . ' (' . $searchParams['originCode'] . ')</li>
                    <li><strong>Destination:</strong> ' . $searchParams['destination'] . ' (' . $searchParams['destinationCode'] . ')</li>
                    <li><strong>Departure Date:</strong> ' . $searchParams['departureDate'] . '</li>
                    <li><strong>Total Passengers:</strong> ' . $searchParams['adults'] . '</li>
                    <li><strong>Total Amount:</strong> ' . $flight['price']['total'] . ' ' . $flight['price']['currency'] . '</li>
                </ul>
                <p>We wish you a pleasant journey!</p>
                <p>Best regards,<br>The GoTrip Team</p>
            </body>
            </html>';
        $mail->AltBody = 'Dear ' . $contactInfo['name'] . ',
        
        Thank you for booking with GoTrip! Here are your booking details:
        - Booking Reference: ' . $bookingReference . '
        - Origin: ' . $searchParams['origin'] . ' (' . $searchParams['originCode'] . ')
        - Destination: ' . $searchParams['destination'] . ' (' . $searchParams['destinationCode'] . ')
        - Departure Date: ' . $searchParams['departureDate'] . '
        - Total Passengers: ' . $searchParams['adults'] . '
        - Total Amount: ' . $flight['price']['total'] . ' ' . $flight['price']['currency'] . '
        
        We wish you a pleasant journey!
        
        Best regards,
        The GoTrip Team';

        $mail->send();
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingReference,
        'message' => 'Booking successfully saved and email sent'
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