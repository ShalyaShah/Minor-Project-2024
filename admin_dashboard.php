<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT fname, lname FROM users WHERE id = $admin_id";
$admin_result = $conn->query($admin_query);
$admin_name = "Admin";
if ($admin_result && $admin_result->num_rows > 0) {
    $admin_data = $admin_result->fetch_assoc();
    $admin_name = $admin_data['fname'] . " " . $admin_data['lname'];
}

// Get counts for dashboard
$users_count = 0;
$bookings_count = 0;
$revenue = 0;

// Count users
$users_query = "SELECT COUNT(*) as count FROM users";
$users_result = $conn->query($users_query);
if ($users_result && $users_result->num_rows > 0) {
    $users_count = $users_result->fetch_assoc()['count'];
}

// Count bookings
$bookings_query = "SELECT COUNT(*) as count FROM flight_booked";
$bookings_result = $conn->query($bookings_query);
if ($bookings_result && $bookings_result->num_rows > 0) {
    $bookings_count = $bookings_result->fetch_assoc()['count'];
}

// Calculate revenue
$revenue_query = "SELECT SUM(total_amount) as total FROM flight_booked";
$revenue_result = $conn->query($revenue_query);
if ($revenue_result && $revenue_result->num_rows > 0) {
    $revenue_data = $revenue_result->fetch_assoc();
    $revenue = $revenue_data['total'] ? $revenue_data['total'] : 0;
}

// Get recent bookings
$recent_bookings_query = "SELECT booking_reference, origin_name, destination_name, 
                          departure_date, total_passengers, total_amount 
                          FROM flight_booked 
                          ORDER BY booking_date DESC LIMIT 5";
$recent_bookings_result = $conn->query($recent_bookings_query);

// Get recent users
$recent_users_query = "SELECT id, fname, lname, email, wallet_balance 
                      FROM users 
                      ORDER BY id DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoTrip Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>GoTrip</h2>
                <span>Admin Panel</span>
            </div>
            <ul class="sidebar-menu">
                <li class="active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </li>
                <li data-section="users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </li>
                <li data-section="bookings">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Bookings</span>
                </li>
                <li data-section="passengers">
                    <i class="fas fa-user-friends"></i>
                    <span>Passengers</span>
                </li>
                <li data-section="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </li>
                <li data-section="settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $admin_name; ?></span>
                </div>
            </div>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="admin-section active">
                <div class="section-header">
                    <h2>Dashboard</h2>
                    <p>Welcome back, <?php echo $admin_name; ?>!</p>
                </div>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $users_count; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $bookings_count; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₹<?php echo number_format($revenue, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-content">
                    <div class="recent-bookings">
                        <div class="content-header">
                            <h3>Recent Bookings</h3>
                            <a href="#" class="view-all" data-section="bookings">View All</a>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Route</th>
                                        <th>Date</th>
                                        <th>Passengers</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($recent_bookings_result && $recent_bookings_result->num_rows > 0) {
                                        while($row = $recent_bookings_result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>" . $row['booking_reference'] . "</td>
                                                    <td>" . $row['origin_name'] . " to " . $row['destination_name'] . "</td>
                                                    <td>" . date('d M Y', strtotime($row['departure_date'])) . "</td>
                                                    <td>" . $row['total_passengers'] . "</td>
                                                    <td>₹" . number_format($row['total_amount'], 2) . "</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='no-data'>No bookings found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="recent-users">
                        <div class="content-header">
                            <h3>Recent Users</h3>
                            <a href="#" class="view-all" data-section="users">View All</a>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Wallet Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($recent_users_result && $recent_users_result->num_rows > 0) {
                                        while($row = $recent_users_result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>" . $row['id'] . "</td>
                                                    <td>" . $row['fname'] . " " . $row['lname'] . "</td>
                                                    <td>" . $row['email'] . "</td>
                                                    <td>₹" . number_format($row['wallet_balance'], 2) . "</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='no-data'>No users found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Other Sections (loaded via AJAX) -->
            <div id="users-section" class="admin-section"></div>
            <div id="bookings-section" class="admin-section"></div>
            <div id="passengers-section" class="admin-section"></div>
            <div id="reports-section" class="admin-section"></div>
            <div id="settings-section" class="admin-section"></div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>