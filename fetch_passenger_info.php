<?php
header('Content-Type: text/html');

// Database connection parameters
$host = 'localhost';
$dbname = 'minor-project';
$username = 'root';
$password = '';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get booking ID from POST request
    $bookingId = $_POST['booking_id'];

    // Fetch passenger info for the booking
    $sql = "SELECT * FROM flight_passenger_info WHERE booking_id = :booking_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['booking_id' => $bookingId]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($passengers) > 0) {
        echo '<table>';
        echo '<thead><tr><th>Title</th><th>First Name</th><th>Last Name</th><th>Date of Birth</th><th>Passport Number</th><th>Nationality</th></tr></thead>';
        echo '<tbody>';
        foreach ($passengers as $passenger) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($passenger['title']) . '</td>';
            echo '<td>' . htmlspecialchars($passenger['first_name']) . '</td>';
            echo '<td>' . htmlspecialchars($passenger['last_name']) . '</td>';
            echo '<td>' . htmlspecialchars($passenger['date_of_birth']) . '</td>';
            echo '<td>' . htmlspecialchars($passenger['passport_number']) . '</td>';
            echo '<td>' . htmlspecialchars($passenger['nationality']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No passenger information found for this booking.</p>';
    }

} catch (Exception $e) {
    echo '<p>Error: ' . $e->getMessage() . '</p>';
}
?>