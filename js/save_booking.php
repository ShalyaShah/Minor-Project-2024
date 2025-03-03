<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "flight"; // Ensure this matches your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Database connection failed: " . $conn->connect_error
    ]));
}

// Get booking data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    die(json_encode([
        'success' => false,
        'message' => "Invalid data received"
    ]));
}

// Generate booking reference
$bookingReference = generateBookingReference();

// Start transaction
$conn->begin_transaction();

try {
    // Insert flight booking
    $flightData = $data['flight'];
    $searchParams = $data['searchParams'];

    // Extract flight details
    $airline = implode(',', $flightData['validatingAirlineCodes']);
    $flightNumber = '';
    $departureTime = '';
    $arrivalTime = '';

    if (!empty($flightData['itineraries'][0]['segments'])) {
        $segment = $flightData['itineraries'][0]['segments'][0];
        $flightNumber = $segment['carrierCode'] . $segment['number'];
        $departureTime = $segment['departure']['at'];
        $arrivalTime = $segment['arrival']['at'];
    }

    $stmt = $conn->prepare("INSERT INTO Flight_booked (
        booking_reference, 
        origin_code, 
        destination_code, 
        departure_date, 
        airline, 
        flight_number, 
        departure_time, 
        arrival_time, 
        total_price, 
        currency, 
        booking_date,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed')");

    $stmt->bind_param(
        "sssssssiss",
        $bookingReference,
        $searchParams['originCode'],
        $searchParams['destinationCode'],
        $searchParams['departureDate'],
        $airline,
        $flightNumber,
        $departureTime,
        $arrivalTime,
        $flightData['price']['total'],
        $flightData['price']['currency']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting flight booking: " . $stmt->error);
    }

    // Insert passenger information
    foreach ($data['passengers'] as $passenger) {
        $stmt = $conn->prepare("INSERT INTO flight_passenger_info (
            booking_reference,
            title,
            first_name,
            last_name,
            date_of_birth,
            passport_number,
            nationality,
            email,
            phone
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssssss",
            $bookingReference,
            $passenger['title'],
            $passenger['firstName'],
            $passenger['lastName'],
            $passenger['dob'],
            $passenger['passport'],
            $passenger['nationality'],
            $data['contactInfo']['email'],
            $data['contactInfo']['phone']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting passenger info: " . $stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingReference
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();

// Function to generate a unique booking reference
function generateBookingReference() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $reference = '';
    for ($i = 0; $i < 6; $i++) {
        $reference .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $reference;
}
?>