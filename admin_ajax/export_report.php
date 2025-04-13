<?php
// Check for admin session
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo "Unauthorized access";
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get report parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'bookings';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Prepare date condition for SQL queries
$date_condition = "";
if ($report_type == 'bookings') {
    $date_condition = " WHERE booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
    
    // Add CSV header row
    fputcsv($output, ['Booking Reference', 'User', 'Origin', 'Destination', 'Booking Date', 'Departure Date', 'Total Amount', 'Status']);
    
    // Get bookings data
    $query = "SELECT booking_reference, user_id, origin_name, destination_name, booking_date, departure_date, total_amount, status 
              FROM flight_booked" . $date_condition;
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get user name if available
            $user_name = 'Guest';
            if (!empty($row['user_id'])) {
                $user_query = "SELECT CONCAT(fname, ' ', lname) as name FROM users WHERE id = " . $row['user_id'];
                $user_result = $conn->query($user_query);
                if ($user_result && $user_result->num_rows > 0) {
                    $user_name = $user_result->fetch_assoc()['name'];
                }
            }
            
            // Format data for CSV
            $csv_row = [
                $row['booking_reference'],
                $user_name,
                $row['origin_name'],
                $row['destination_name'],
                $row['booking_date'],
                $row['departure_date'],
                $row['total_amount'],
                $row['status'] ?? 'Confirmed'
            ];
            
            fputcsv($output, $csv_row);
        }
    }
} elseif ($report_type == 'revenue') {
    $date_condition = " WHERE booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
    
    // Add CSV header row
    fputcsv($output, ['Date', 'Total Bookings', 'Total Revenue']);
    
    // Get revenue data by date
    $query = "SELECT DATE(booking_date) as date, COUNT(*) as bookings, SUM(total_amount) as revenue 
              FROM flight_booked" . $date_condition . "
              GROUP BY DATE(booking_date) 
              ORDER BY date";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format data for CSV
            $csv_row = [
                $row['date'],
                $row['bookings'],
                $row['revenue']
            ];
            
            fputcsv($output, $csv_row);
        }
    }
    
    // Add total row
    $total_query = "SELECT COUNT(*) as bookings, SUM(total_amount) as revenue 
                   FROM flight_booked" . $date_condition;
    $total_result = $conn->query($total_query);
    
    if ($total_result && $total_result->num_rows > 0) {
        $total = $total_result->fetch_assoc();
        fputcsv($output, ['TOTAL', $total['bookings'], $total['revenue']]);
    }
} elseif ($report_type == 'passengers') {
    // For passengers, we need to join with flight_booked to get the booking date
    $date_condition = " WHERE fb.booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
    
    // Add CSV header row
    fputcsv($output, ['Passenger ID', 'Name', 'Gender', 'Age', 'Type', 'Booking Reference', 'Booking Date']);
    
    // Get passengers data
    $query = "SELECT fpi.id, fpi.first_name, fpi.last_name, fpi.gender, fpi.age, fpi.passenger_type, 
              fpi.booking_reference, fb.booking_date 
              FROM flight_passenger_info fpi 
              JOIN flight_booked fb ON fpi.booking_reference = fb.booking_reference" . $date_condition;
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format data for CSV
            $csv_row = [
                $row['id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['gender'],
                $row['age'],
                ucfirst($row['passenger_type']),
                $row['booking_reference'],
                $row['booking_date']
            ];
            
            fputcsv($output, $csv_row);
        }
    }
}

fclose($output);
exit();
?>