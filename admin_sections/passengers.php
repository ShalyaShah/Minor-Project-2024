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

// Get all passengers with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $search_condition = " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR booking_id LIKE '%$search%'";
}

$total_query = "SELECT COUNT(*) as total FROM flight_passenger_info" . $search_condition;
$total_result = $conn->query($total_query);
$total_passengers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_passengers / $limit);

// Join with flight_booked to get booking reference
$passengers_query = "SELECT fpi.*, fb.booking_reference 
                    FROM flight_passenger_info fpi 
                    LEFT JOIN flight_booked fb ON fpi.booking_id = fb.id" . 
                    $search_condition . " ORDER BY fpi.id DESC LIMIT $offset, $limit";
$passengers_result = $conn->query($passengers_query);
?>

<div class="section-header">
    <h2>Passenger Management</h2>
</div>

<div class="search-container">
    <form id="passenger-search-form" class="search-form">
        <input type="text" id="passenger-search" name="search" placeholder="Search by name or booking ID..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="table-container">
    <table id="passengers-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Booking ID</th>
                <th>Name</th>
                <th>Title</th>
                <th>Date of Birth</th>
                <th>Passport</th>
                <th>Nationality</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($passengers_result && $passengers_result->num_rows > 0) {
                while($row = $passengers_result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row['id'] . "</td>
                            <td>" . $row['booking_id'] . "</td>
                            <td>" . $row['first_name'] . " " . $row['last_name'] . "</td>
                            <td>" . $row['title'] . "</td>
                            <td>" . $row['date_of_birth'] . "</td>
                            <td>" . $row['passport_number'] . "</td>
                            <td>" . $row['nationality'] . "</td>
                            <td>
                                <div class='actions'>
                                    <button class='view-passenger' data-id='" . $row['id'] . "'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <button class='edit-passenger' data-id='" . $row['id'] . "'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='delete-passenger' data-id='" . $row['id'] . "'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </div>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='no-data'>No passengers found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_passengers); ?> of <?php echo $total_passengers; ?> passengers
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

<!-- View Passenger Modal -->
<div id="view-passenger-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Passenger Details</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="passenger-details-container">
                <div class="loading">Loading passenger details...</div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Passenger Modal -->
<div id="edit-passenger-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Passenger</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="edit-passenger-form">
                <input type="hidden" id="edit_passenger_id" name="id">
                
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <select id="edit_title" name="title" required>
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Miss">Miss</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_first_name">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_last_name">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_date_of_birth">Date of Birth</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_passport_number">Passport Number</label>
                    <input type="text" id="edit_passport_number" name="passport_number" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_nationality">Nationality</label>
                    <input type="text" id="edit_nationality" name="nationality" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Update Passenger</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Passenger Confirmation Modal -->
<div id="delete-passenger-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Passenger</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this passenger? This action cannot be undone.</p>
            <input type="hidden" id="delete_passenger_id">
            <div class="form-actions">
                <button id="confirm-delete-btn" class="delete-btn">Delete</button>
                <button id="cancel-delete-btn" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>
    .search-container {
        margin-bottom: 20px;
    }
    
    .search-form {
        display: flex;
        max-width: 500px;
    }
    
    .search-form input {
        flex: 1;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 5px 0 0 5px;
        font-size: 14px;
    }
    
    .search-btn {
        padding: 10px 15px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
    }
    
    .view-passenger {
        background-color: var(--info-color);
        color: white;
    }
    
    .edit-passenger {
        background-color: var(--warning-color);
        color: white;
    }
    
    .delete-passenger {
        background-color: var(--danger-color);
        color: white;
    }
    
    #passenger-details-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .passenger-detail-group {
        display: flex;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .passenger-detail-label {
        width: 150px;
        font-weight: 500;
        color: var(--secondary-color);
    }
    
    .passenger-detail-value {
        flex: 1;
        color: var(--dark-color);
    }
    
    .booking-link {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .booking-link a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
    }
    
    .booking-link a:hover {
        text-decoration: underline;
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
        
        // Search form submission
        $("#passenger-search-form").submit(function(e) {
            e.preventDefault();
            const search = $("#passenger-search").val();
            loadPassengersWithSearch(search);
        });
        
        // Pagination
        $(".page-link").click(function(e) {
            e.preventDefault();
            const page = $(this).data("page");
            const search = $("#passenger-search").val();
            loadPassengersPage(page, search);
        });
        
        // View passenger details
        $(".view-passenger").click(function() {
            const passengerId = $(this).data("id");
            
            // Show modal with loading state
            $("#view-passenger-modal").css("display", "block");
            $("#passenger-details-container").html('<div class="loading">Loading passenger details...</div>');
            
            // Fetch passenger details
            $.ajax({
                type: "GET",
                url: "admin_ajax/get_passenger.php",
                data: { id: passengerId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        displayPassengerDetails(response.data);
                    } else {
                        $("#passenger-details-container").html('<div class="error">Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $("#passenger-details-container").html('<div class="error">An error occurred while fetching passenger details.</div>');
                }
            });
        });
        
        // Edit passenger
        $(".edit-passenger").click(function() {
            const passengerId = $(this).data("id");
            
            $.ajax({
                type: "GET",
                url: "admin_ajax/get_passenger.php",
                data: { id: passengerId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        const passenger = response.data;
                        
                        // Fill the edit form with passenger data
                        $("#edit_passenger_id").val(passenger.id);
                        $("#edit_title").val(passenger.title);
                        $("#edit_first_name").val(passenger.first_name);
                        $("#edit_last_name").val(passenger.last_name);
                        $("#edit_date_of_birth").val(passenger.date_of_birth);
                        $("#edit_passport_number").val(passenger.passport_number);
                        $("#edit_nationality").val(passenger.nationality);
                        
                        // Show the edit modal
                        $("#edit-passenger-modal").css("display", "block");
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while fetching passenger data.");
                }
            });
        });
        
        // Edit passenger form submission
        $("#edit-passenger-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/update_passenger.php",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("Passenger updated successfully!");
                        $("#edit-passenger-modal").css("display", "none");
                        // Reload passengers to show the updated passenger
                        const search = $("#passenger-search").val();
                        loadPassengersWithSearch(search);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while updating the passenger.");
                }
            });
        });
        
        // Delete passenger
        $(".delete-passenger").click(function() {
            const passengerId = $(this).data("id");
            $("#delete_passenger_id").val(passengerId);
            $("#delete-passenger-modal").css("display", "block");
        });
        
        // Confirm delete passenger
        $("#confirm-delete-btn").click(function() {
            const passengerId = $("#delete_passenger_id").val();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/delete_passenger.php",
                data: { id: passengerId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("Passenger deleted successfully!");
                        $("#delete-passenger-modal").css("display", "none");
                        // Reload passengers to remove the deleted passenger
                        const search = $("#passenger-search").val();
                        loadPassengersWithSearch(search);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while deleting the passenger.");
                }
            });
        });
        
        // Cancel delete passenger
        $("#cancel-delete-btn").click(function() {
            $("#delete-passenger-modal").css("display", "none");
        });
    });
    
    // Function to load passengers page
    function loadPassengersPage(page, search = '') {
        $("#passengers-section").html('<div class="loading">Loading passengers...</div>');
        
        let url = "admin_sections/passengers.php?page=" + page;
        if (search) {
            url += "&search=" + encodeURIComponent(search);
        }
        
        $.ajax({
            url: url,
            success: function(response) {
                $("#passengers-section").html(response);
            },
            error: function() {
                $("#passengers-section").html('<div class="error">Failed to load passengers.</div>');
            }
        });
    }
    
    // Function to load passengers with search
    function loadPassengersWithSearch(search) {
        $("#passengers-section").html('<div class="loading">Searching passengers...</div>');
        
        $.ajax({
            url: "admin_sections/passengers.php?search=" + encodeURIComponent(search),
            success: function(response) {
                $("#passengers-section").html(response);
            },
            error: function() {
                $("#passengers-section").html('<div class="error">Failed to search passengers.</div>');
            }
        });
    }
    
    // Function to display passenger details
    function displayPassengerDetails(passenger) {
        let html = `
            <div class="passenger-detail-group">
                <div class="passenger-detail-label">Name:</div>
                <div class="passenger-detail-value">${passenger.title} ${passenger.first_name} ${passenger.last_name}</div>
            </div>
            <div class="passenger-detail-group">
                <div class="passenger-detail-label">Date of Birth:</div>
                <div class="passenger-detail-value">${passenger.date_of_birth}</div>
            </div>
            <div class="passenger-detail-group">
                <div class="passenger-detail-label">Passport Number:</div>
                <div class="passenger-detail-value">${passenger.passport_number}</div>
            </div>
            <div class="passenger-detail-group">
                <div class="passenger-detail-label">Nationality:</div>
                <div class="passenger-detail-value">${passenger.nationality}</div>
            </div>
            <div class="passenger-detail-group">
                <div class="passenger-detail-label">Booking ID:</div>
                <div class="passenger-detail-value">${passenger.booking_id}</div>
            </div>`;
            
        if (passenger.booking_reference) {
            html += `
            <div class="booking-link">
                <a href="#" onclick="loadBookingDetails('${passenger.booking_reference}'); return false;">
                    <i class="fas fa-external-link-alt"></i> View Booking Details
                </a>
            </div>`;
        }
        
        $("#passenger-details-container").html(html);
    }
    
    // Function to load booking details
    function loadBookingDetails(bookingRef) {
        // Close the passenger modal
        $("#view-passenger-modal").css("display", "none");
        
        // Load the bookings section
        $("#sidebar-bookings").click();
        
        // After a short delay to allow the bookings section to load
        setTimeout(function() {
            // Find and click the view button for this booking
            $(".view-booking[data-id='" + bookingRef + "']").click();
        }, 500);
    }
</script>