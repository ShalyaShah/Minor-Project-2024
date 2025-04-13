<?php
// Check if this file is accessed directly
if (!defined('ADMIN_ACCESS')) {
    // If accessed via AJAX from admin_dashboard.php, this will be defined
    define('ADMIN_ACCESS', true);
}

// Check for admin session if accessed directly
if (!isset($_SESSION) || !isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        echo "Unauthorized access";
        exit();
    }
}

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all bookings with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Check the structure of flight_booked table
$table_info_query = "SHOW COLUMNS FROM flight_booked";
$table_info_result = $conn->query($table_info_query);
$has_user_id = false;
$user_id_field = '';

if ($table_info_result) {
    while ($column = $table_info_result->fetch_assoc()) {
        if ($column['Field'] == 'user_id') {
            $has_user_id = true;
            $user_id_field = 'user_id';
            break;
        } else if ($column['Field'] == 'userid') {
            $has_user_id = true;
            $user_id_field = 'userid';
            break;
        }
    }
}

$total_query = "SELECT COUNT(*) as total FROM flight_booked";
$total_result = $conn->query($total_query);
$total_bookings = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $limit);

// Adjust the query based on the table structure
if ($has_user_id) {
    $bookings_query = "SELECT fb.*, u.fname, u.lname, u.email 
                      FROM flight_booked fb 
                      LEFT JOIN users u ON fb.$user_id_field = u.id 
                      ORDER BY fb.booking_date DESC 
                      LIMIT $offset, $limit";
} else {
    // If there's no user_id column, just fetch the bookings without user info
    $bookings_query = "SELECT fb.* 
                      FROM flight_booked fb 
                      ORDER BY fb.booking_date DESC 
                      LIMIT $offset, $limit";
}

$bookings_result = $conn->query($bookings_query);
?>

<div class="section-header">
    <h2>Booking Management</h2>
</div>

<div class="bookings-filters">
    <div class="filter-group">
        <label for="booking-status">Status:</label>
        <select id="booking-status">
            <option value="all">All Bookings</option>
            <option value="confirmed">Confirmed</option>
            <option value="pending">Pending</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="booking-date-from">From:</label>
        <input type="date" id="booking-date-from">
    </div>
    <div class="filter-group">
        <label for="booking-date-to">To:</label>
        <input type="date" id="booking-date-to">
    </div>
    <button id="apply-filters" class="filter-btn">Apply Filters</button>
    <button id="reset-filters" class="reset-btn">Reset</button>
</div>

<div class="table-container">
    <table id="bookings-table">
        <thead>
            <tr>
                <th>Booking Ref</th>
                <?php if ($has_user_id): ?>
                <th>User</th>
                <?php endif; ?>
                <th>Route</th>
                <th>Travel Date</th>
                <th>Booking Date</th>
                <th>Passengers</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($bookings_result && $bookings_result->num_rows > 0) {
                while($row = $bookings_result->fetch_assoc()) {
                    // Determine status based on booking date and current date
                    $booking_date = new DateTime($row['booking_date']);
                    $current_date = new DateTime();
                    $departure_date = new DateTime($row['departure_date']);
                    
                    if ($departure_date < $current_date) {
                        $status = "Completed";
                        $status_class = "completed";
                    } else if ($booking_date->diff($current_date)->days < 1) {
                        $status = "Confirmed";
                        $status_class = "confirmed";
                    } else {
                        $status = "Upcoming";
                        $status_class = "upcoming";
                    }
                    
                    echo "<tr>
                            <td>" . $row['booking_reference'] . "</td>";
                    
                    if ($has_user_id && isset($row['fname'])) {
                        echo "<td>" . $row['fname'] . " " . $row['lname'] . "<br><small>" . $row['email'] . "</small></td>";
                    }
                    
                    echo "<td>" . $row['origin_name'] . " to " . $row['destination_name'] . "</td>
                            <td>" . date('d M Y', strtotime($row['departure_date'])) . "</td>
                            <td>" . date('d M Y H:i', strtotime($row['booking_date'])) . "</td>
                            <td>" . $row['total_passengers'] . "</td>
                            <td>₹" . number_format($row['total_amount'], 2) . "</td>
                            <td><span class='status-badge $status_class'>$status</span></td>
                            <td>
                                <div class='actions'>
                                    <button class='view-booking' data-id='" . $row['booking_reference'] . "'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <button class='edit-booking' data-id='" . $row['booking_reference'] . "'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='delete-booking' data-id='" . $row['booking_reference'] . "'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </div>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='" . ($has_user_id ? 9 : 8) . "' class='no-data'>No bookings found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_bookings); ?> of <?php echo $total_bookings; ?> bookings
        </div>
        <div class="pagination-controls">
            <?php if ($page > 1): ?>
                <a href="#" class="page-link" data-page="<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<a href="#" class="page-link" data-page="1">1</a>';
                if ($start_page > 2) {
                    echo '<span class="page-ellipsis">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $page) ? 'active' : '';
                echo '<a href="#" class="page-link ' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="page-ellipsis">...</span>';
                }
                echo '<a href="#" class="page-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="#" class="page-link" data-page="<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View Booking Modal -->
<div id="view-booking-modal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Booking Details</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="booking-details-container">
                <div class="loading">Loading booking details...</div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div id="edit-booking-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Booking</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="edit-booking-form">
                <input type="hidden" id="edit_booking_reference" name="booking_reference">
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_departure_date">Departure Date</label>
                    <input type="date" id="edit_departure_date" name="departure_date">
                </div>
                
                <div class="form-group">
                    <label for="edit_total_amount">Total Amount</label>
                    <input type="number" id="edit_total_amount" name="total_amount" step="0.01" min="0">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Booking Confirmation Modal -->
<div id="delete-booking-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Booking</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this booking? This action cannot be undone.</p>
            <input type="hidden" id="delete_booking_reference">
            <div class="form-actions">
                <button id="confirm-delete-btn" class="delete-btn">Delete</button>
                <button id="cancel-delete-btn" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bookings-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        align-items: center;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .filter-group label {
        font-size: 14px;
        color: var(--secondary-color);
    }
    
    .filter-group select, .filter-group input {
        padding: 8px 10px;
        border: 1px solid #eee;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .filter-btn, .reset-btn {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
    }
    
    .filter-btn {
        background-color: var(--primary-color);
        color: white;
    }
    
    .reset-btn {
        background-color: var(--secondary-color);
        color: white;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .confirmed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .completed {
        background-color: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }
    
    .upcoming {
        background-color: rgba(74, 108, 247, 0.1);
        color: #4a6cf7;
    }
    
    .view-booking {
        background-color: var(--info-color);
        color: white;
    }
    
    .edit-booking {
        background-color: var(--warning-color);
        color: white;
    }
    
    .delete-booking {
        background-color: var(--danger-color);
        color: white;
    }
    
    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }
    
    .pagination-info {
        color: var(--secondary-color);
        font-size: 14px;
    }
    
    .pagination-controls {
        display: flex;
        gap: 5px;
    }
    
    .page-link {
        padding: 5px 10px;
        border-radius: 5px;
        text-decoration: none;
        color: var(--primary-color);
        background-color: #fff;
        border: 1px solid #eee;
        cursor: pointer;
    }
    
    .page-link.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .page-ellipsis {
        padding: 5px 10px;
        color: var(--secondary-color);
    }
    
    .modal-lg {
        width: 70%;
    }
    
    #booking-details-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .booking-header {
        display: flex;
        justify-content: space-between;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .booking-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .booking-section {
        margin-bottom: 20px;
    }
    
    .booking-section h4 {
        margin-bottom: 10px;
        color: var(--dark-color);
        font-size: 16px;
    }
    
    .info-group {
        display: flex;
        margin-bottom: 10px;
    }
    
    .info-label {
        width: 150px;
        font-weight: 500;
        color: var(--secondary-color);
    }
    
    .info-value {
        flex: 1;
        color: var(--dark-color);
    }
    
    .passenger-list {
        margin-top: 20px;
    }
    
    .passenger-card {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .passenger-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .passenger-name {
        font-weight: 500;
        color: var(--dark-color);
    }
    
    .passenger-type {
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 20px;
        background-color: rgba(74, 108, 247, 0.1);
        color: var(--primary-color);
    }
    
    .passenger-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .passenger-detail {
        font-size: 14px;
    }
    
    .passenger-detail-label {
        color: var(--secondary-color);
        margin-bottom: 3px;
    }
    
    .passenger-detail-value {
        color: var(--dark-color);
        font-weight: 500;
    }
    
    .payment-details {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
    }
    
    .payment-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .payment-label {
        color: var(--secondary-color);
    }
    
    .payment-value {
        color: var(--dark-color);
        font-weight: 500;
    }
    
    .payment-total {
        border-top: 1px solid #eee;
        padding-top: 10px;
        margin-top: 10px;
        font-size: 18px;
    }
    
    .payment-total .payment-value {
        color: var(--primary-color);
    }
    
    @media (max-width: 992px) {
        .booking-info {
            grid-template-columns: 1fr;
        }
        
        .passenger-details {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .modal-lg {
            width: 90%;
        }
    }
    
    @media (max-width: 576px) {
        .passenger-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    $(document).ready(function() {
        // Close modals when clicking the close button
        $(".close").click(function() {
            $(".modal").css("display", "none");
        });
        
        // Close modals when clicking outside the modal
        $(window).click(function(event) {
            if ($(event.target).hasClass("modal")) {
                $(".modal").css("display", "none");
            }
        });
        
        // Pagination
        $(".page-link").click(function(e) {
            e.preventDefault();
            const page = $(this).data("page");
            loadBookingsPage(page);
        });
        
        // Apply filters
        $("#apply-filters").click(function() {
            const status = $("#booking-status").val();
            const dateFrom = $("#booking-date-from").val();
            const dateTo = $("#booking-date-to").val();
            
            loadBookingsWithFilters(status, dateFrom, dateTo);
        });
        
        // Reset filters
        $("#reset-filters").click(function() {
            $("#booking-status").val("all");
            $("#booking-date-from").val("");
            $("#booking-date-to").val("");
            
            loadBookingsPage(1);
        });
        
        // View booking details
        $(".view-booking").click(function() {
            const bookingRef = $(this).data("id");
            
            // Show modal with loading state
            $("#view-booking-modal").css("display", "block");
            $("#booking-details-container").html('<div class="loading">Loading booking details...</div>');
            
            // Fetch booking details
            $.ajax({
                type: "GET",
                url: "admin_ajax/get_booking.php",
                data: { booking_reference: bookingRef },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        displayBookingDetails(response.data);
                    } else {
                        $("#booking-details-container").html('<div class="error">Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $("#booking-details-container").html('<div class="error">An error occurred while fetching booking details.</div>');
                }
            });
        });
        
        // Edit booking
        $(".edit-booking").click(function() {
            const bookingRef = $(this).data("id");
            
            $.ajax({
                type: "GET",
                url: "admin_ajax/get_booking.php",
                data: { booking_reference: bookingRef },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        const booking = response.data.booking;
                        
                        // Fill the edit form with booking data
                        $("#edit_booking_reference").val(booking.booking_reference);
                        $("#edit_departure_date").val(booking.departure_date);
                        $("#edit_total_amount").val(booking.total_amount);
                        
                        // Determine status
                        const bookingDate = new Date(booking.booking_date);
                        const currentDate = new Date();
                        const departureDate = new Date(booking.departure_date);
                        
                        let status = "confirmed";
                        if (departureDate < currentDate) {
                            status = "completed";
                        } else if (bookingDate.getTime() > currentDate.getTime() - 86400000) {
                            status = "confirmed";
                        } else {
                            status = "upcoming";
                        }
                        
                        $("#edit_status").val(status);
                        
                        // Show the edit modal
                        $("#edit-booking-modal").css("display", "block");
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while fetching booking data.");
                }
            });
        });
        
        // Edit booking form submission
        $("#edit-booking-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/update_booking.php",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("Booking updated successfully!");
                        $("#edit-booking-modal").css("display", "none");
                        // Reload bookings to show the updated booking
                        loadBookingsPage(1);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while updating the booking.");
                }
            });
        });
        
        // Delete booking
        $(".delete-booking").click(function() {
            const bookingRef = $(this).data("id");
            $("#delete_booking_reference").val(bookingRef);
            $("#delete-booking-modal").css("display", "block");
        });
        
        // Confirm delete booking
        $("#confirm-delete-btn").click(function() {
            const bookingRef = $("#delete_booking_reference").val();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/delete_booking.php",
                data: { booking_reference: bookingRef },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("Booking deleted successfully!");
                        $("#delete-booking-modal").css("display", "none");
                        // Reload bookings to remove the deleted booking
                        loadBookingsPage(1);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while deleting the booking.");
                }
            });
        });
        
        // Cancel delete booking
        $("#cancel-delete-btn").click(function() {
            $("#delete-booking-modal").css("display", "none");
        });
    });
    
    // Function to load bookings page
    function loadBookingsPage(page) {
        $("#bookings-section").html('<div class="loading">Loading bookings...</div>');
        
        $.ajax({
            url: "admin_sections/bookings.php?page=" + page,
            success: function(response) {
                $("#bookings-section").html(response);
            },
            error: function() {
                $("#bookings-section").html('<div class="error">Failed to load bookings.</div>');
            }
        });
    }
    
    // Function to load bookings with filters
    function loadBookingsWithFilters(status, dateFrom, dateTo) {
        $("#bookings-section").html('<div class="loading">Loading bookings...</div>');
        
        $.ajax({
            url: "admin_sections/bookings.php",
            data: {
                status: status,
                date_from: dateFrom,
                date_to: dateTo,
                page: 1
            },
            success: function(response) {
                $("#bookings-section").html(response);
            },
            error: function() {
                $("#bookings-section").html('<div class="error">Failed to load bookings.</div>');
            }
        });
    }
    
    // Function to display booking details
    function displayBookingDetails(data) {
        const booking = data.booking;
        const passengers = data.passengers;
        
        // Calculate booking status
        const bookingDate = new Date(booking.booking_date);
        const currentDate = new Date();
        const departureDate = new Date(booking.departure_date);
        
        let status = "Confirmed";
        let statusClass = "confirmed";
        
        if (departureDate < currentDate) {
            status = "Completed";
            statusClass = "completed";
        } else if (bookingDate.getTime() > currentDate.getTime() - 86400000) {
            status = "Confirmed";
            statusClass = "confirmed";
        } else {
            status = "Upcoming";
            statusClass = "upcoming";
        }
        
        // Format dates
        const formattedBookingDate = new Date(booking.booking_date).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const formattedDepartureDate = new Date(booking.departure_date).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        // Build HTML for booking details
        let html = `
            <div class="booking-header">
                <div>
                    <h3>Booking #${booking.booking_reference}</h3>
                    <p>Booked on ${formattedBookingDate}</p>
                </div>
                <div>
                    <span class="status-badge ${statusClass}">${status}</span>
                </div>
            </div>
            
            <div class="booking-info">
                <div class="booking-section">
                    <h4>Flight Details</h4>
                    <div class="info-group">
                        <div class="info-label">Route:</div>
                        <div class="info-value">${booking.origin_name} to ${booking.destination_name}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Departure Date:</div>
                        <div class="info-value">${formattedDepartureDate}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Flight Number:</div>
                        <div class="info-value">${booking.flight_number || 'N/A'}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Total Passengers:</div>
                        <div class="info-value">${booking.total_passengers}</div>
                    </div>
                </div>`;
                
        // Only add customer information if we have user data
        if (booking.fname) {
            html += `
                <div class="booking-section">
                    <h4>Customer Information</h4>
                    <div class="info-group">
                        <div class="info-label">Name:</div>
                        <div class="info-value">${booking.fname} ${booking.lname}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Email:</div>
                        <div class="info-value">${booking.email}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">${booking.phone || 'N/A'}</div>
                    </div>
                </div>`;
        }
            
        html += `</div>
            
            <div class="booking-section">
                <h4>Passenger Details</h4>
                <div class="passenger-list">`;
        
        // Add passengers
        if (passengers && passengers.length > 0) {
            passengers.forEach((passenger, index) => {
                const passengerType = passenger.passenger_type === 'adult' ? 'Adult' : 
                                     (passenger.passenger_type === 'child' ? 'Child' : 'Infant');
                
                html += `
                    <div class="passenger-card">
                        <div class="passenger-header">
                            <div class="passenger-name">Passenger ${index + 1}: ${passenger.first_name} ${passenger.last_name}</div>
                            <div class="passenger-type">${passengerType}</div>
                        </div>
                        <div class="passenger-details">
                            <div class="passenger-detail">
                                <div class="passenger-detail-label">Gender</div>
                                <div class="passenger-detail-value">${passenger.gender}</div>
                            </div>
                            <div class="passenger-detail">
                                <div class="passenger-detail-label">Age</div>
                                <div class="passenger-detail-value">${passenger.age || 'N/A'}</div>
                            </div>
                            <div class="passenger-detail">
                                <div class="passenger-detail-label">Seat</div>
                                <div class="passenger-detail-value">${passenger.seat_number || 'Not Assigned'}</div>
                            </div>
                        </div>
                    </div>`;
            });
        } else {
            html += '<p class="no-data">No passenger details available</p>';
        }
        
        html += `
                </div>
            </div>
            
            <div class="booking-section">
                <h4>Payment Details</h4>
                <div class="payment-details">
                    <div class="payment-row">
                        <div class="payment-label">Base Fare</div>
                        <div class="payment-value">₹${parseFloat(booking.total_amount * 0.85).toFixed(2)}</div>
                    </div>
                    <div class="payment-row">
                        <div class="payment-label">Taxes & Fees</div>
                        <div class="payment-value">₹${parseFloat(booking.total_amount * 0.15).toFixed(2)}</div>
                    </div>
                    <div class="payment-row payment-total">
                        <div class="payment-label">Total Amount</div>
                        <div class="payment-value">₹${parseFloat(booking.total_amount).toFixed(2)}</div>
                    </div>
                </div>
            </div>
        `;
        
        $("#booking-details-container").html(html);
    }
</script>