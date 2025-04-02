<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
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

    // Fetch bookings for the logged-in user
    $email = $_SESSION['email']; // Assuming the user's email is stored in the session
    $sql = "SELECT * FROM flight_booked WHERE contact_email = :email ORDER BY booking_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - GoTrip</title>
    <link rel="stylesheet" href="css/view_bookings_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/Logo GoTrip.jpeg" alt="GoTrip Logo">
            <span>GoTrip</span>
        </div>
        <div class="menu">
            <a href="index.php" class="menu-item"><i class="fa-solid fa-home"></i>Home</a>
            <a href="logout.php" class="menu-item"><i class="fa-solid fa-user"></i>Logout</a>
        </div>
    </header>

    <section class="bookings">
        <h1>Your Bookings</h1>
        <?php if (count($bookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking Reference</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Departure Date</th>
                        <th>Total Passengers</th>
                        <th>Total Amount</th>
                        <th>Booking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                            <td><?php echo htmlspecialchars($booking['origin_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['destination_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['departure_date']); ?></td>
                            <td><?php echo htmlspecialchars($booking['total_passengers']); ?></td>
                            <td><?php echo htmlspecialchars($booking['currency'] . ' ' . $booking['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                            <td>
                                <button class="view-passenger-info" data-booking-id="<?php echo $booking['id']; ?>">Passenger Info</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No bookings found.</p>
        <?php endif; ?>
    </section>

    <!-- Modal for Passenger Info -->
    <div id="passenger-info-modal" style="display: none;">
        <div class="modal-content">
            <span id="close-modal" style="cursor: pointer;">&times;</span>
            <h2>Passenger Information</h2>
            <div id="passenger-info-content">
                <!-- Passenger details will be loaded here -->
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 GoTrip. All Rights Reserved.</p>
    </footer>

    <script>
        $(document).ready(function () {
            // Handle Passenger Info button click
            $('.view-passenger-info').on('click', function () {
                const bookingId = $(this).data('booking-id');

                // Fetch passenger info via AJAX
                $.ajax({
                    url: 'fetch_passenger_info.php',
                    method: 'POST',
                    data: { booking_id: bookingId },
                    success: function (response) {
                        $('#passenger-info-content').html(response);
                        $('#passenger-info-modal').show();
                    },
                    error: function () {
                        alert('Failed to fetch passenger information.');
                    }
                });
            });

            // Close modal
            $('#close-modal').on('click', function () {
                $('#passenger-info-modal').hide();
            });
        });
    </script>
</body>
</html>