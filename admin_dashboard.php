<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Redirect to login page if not logged in as admin
    header("Location: login.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "minor-project";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get counts for dashboard
$userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$flightBookingCount = $conn->query("SELECT COUNT(*) as count FROM flight_booked")->fetch_assoc()['count'];
$passengerCount = $conn->query("SELECT COUNT(*) as count FROM flight_passenger_info")->fetch_assoc()['count'];

// Get total revenue
$totalRevenue = $conn->query("SELECT SUM(total_amount) as total FROM flight_booked")->fetch_assoc()['total'];
$totalRevenue = $totalRevenue ? $totalRevenue : 0;

// Get recent bookings
$recentBookings = $conn->query("SELECT fb.id, fb.booking_reference, fb.origin_name, fb.destination_name, 
                                fb.departure_date, fb.total_passengers, fb.total_amount, fb.booking_date, 
                                fb.contact_email
                                FROM flight_booked fb
                                ORDER BY fb.booking_date DESC LIMIT 5");

// Get recent users
$recentUsers = $conn->query("SELECT id, fname, lname, email, wallet_balance
                            FROM users 
                            ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoTrip Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="images/Logo GoTrip.jpeg" alt="GoTrip Logo">
                <span>GoTrip Admin</span>
            </div>
            <ul class="nav-links">
                <li class="active">
                    <a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li>
                    <a href="#users"><i class="fas fa-users"></i> Users</a>
                </li>
                <li>
                    <a href="#bookings"><i class="fas fa-ticket-alt"></i> Flight Bookings</a>
                </li>
                <li>
                    <a href="#passengers"><i class="fas fa-user-friends"></i> Passengers</a>
                </li>
                <li>
                    <a href="#reports"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li>
                    <a href="#settings"><i class="fas fa-cog"></i> Settings</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header>
                <div class="header-title">
                    <h2>Dashboard</h2>
                    <span><?php echo date("l, F j, Y"); ?></span>
                </div>
                <div class="user-info">
                    <div class="search">
                        <input type="text" placeholder="Search...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="notification">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    <div class="profile">
                        <img src="images/admin-avatar.png" alt="Admin">
                        <span><?php echo $_SESSION['name'] ?? 'Admin'; ?></span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard" class="dashboard-section">
                <div class="stats-cards">
                    <div class="card">
                        <div class="card-info">
                            <h3>Total Users</h3>
                            <h2><?php echo $userCount; ?></h2>
                        </div>
                        <div class="card-icon user-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-info">
                            <h3>Flight Bookings</h3>
                            <h2><?php echo $flightBookingCount; ?></h2>
                        </div>
                        <div class="card-icon booking-icon">
                            <i class="fas fa-plane-departure"></i>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-info">
                            <h3>Total Passengers</h3>
                            <h2><?php echo $passengerCount; ?></h2>
                        </div>
                        <div class="card-icon passenger-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-info">
                            <h3>Total Revenue</h3>
                            <h2>₹<?php echo number_format($totalRevenue, 2); ?></h2>
                        </div>
                        <div class="card-icon revenue-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </div>

                <div class="recent-data">
                    <div class="recent-bookings">
                        <div class="card-header">
                            <h3>Recent Flight Bookings</h3>
                            <a href="#bookings" class="view-all">View All</a>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Route</th>
                                    <th>Date</th>
                                    <th>Passengers</th>
                                    <th>Amount</th>
                                    <th>Booked On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($booking = $recentBookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $booking['booking_reference']; ?></td>
                                    <td><?php echo $booking['origin_name'] . ' to ' . $booking['destination_name']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['departure_date'])); ?></td>
                                    <td><?php echo $booking['total_passengers']; ?></td>
                                    <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="#" class="view-btn" data-id="<?php echo $booking['id']; ?>"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="edit-btn" data-id="<?php echo $booking['id']; ?>"><i class="fas fa-edit"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($recentBookings->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" class="no-data">No bookings found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="recent-users">
                        <div class="card-header">
                            <h3>Recent Users</h3>
                            <a href="#users" class="view-all">View All</a>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Wallet</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recentUsers->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo $user['fname'] . ' ' . $user['lname']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>₹<?php echo number_format($user['wallet_balance'], 2); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="#" class="view-btn" data-id="<?php echo $user['id']; ?>"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="edit-btn" data-id="<?php echo $user['id']; ?>"><i class="fas fa-edit"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($recentUsers->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="no-data">No users found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Other sections will be loaded via JavaScript -->
            <div id="content-container">
                <!-- Content for other sections will be loaded here -->
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>