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

// Get all users with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $search_condition = " WHERE fname LIKE '%$search%' OR lname LIKE '%$search%' OR email LIKE '%$search%'";
}

$total_query = "SELECT COUNT(*) as total FROM users" . $search_condition;
$total_result = $conn->query($total_query);
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

$users_query = "SELECT * FROM users" . $search_condition . " ORDER BY id DESC LIMIT $offset, $limit";
$users_result = $conn->query($users_query);
?>

<div class="section-header">
    <h2>User Management</h2>
    <button id="add-user-btn" class="action-btn">
        <i class="fas fa-plus"></i> Add New User
    </button>
</div>

<div class="search-container">
    <form id="user-search-form" class="search-form">
        <input type="text" id="user-search" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="table-container">
    <table id="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Wallet Balance</th>
                <th>Admin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($users_result && $users_result->num_rows > 0) {
                while($row = $users_result->fetch_assoc()) {
                    $is_admin = $row['is_admin'] == 1 ? 'Yes' : 'No';
                    echo "<tr>
                            <td>" . $row['id'] . "</td>
                            <td>" . $row['fname'] . " " . $row['lname'] . "</td>
                            <td>" . $row['email'] . "</td>
                            <td>" . $row['wallet_balance'] . "</td>
                            <td>" . $is_admin . "</td>
                            <td>
                                <div class='actions'>
                                    <button class='edit-user' data-id='" . $row['id'] . "'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='delete-user' data-id='" . $row['id'] . "'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </div>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-data'>No users found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> users
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

<!-- Add User Modal -->
<div id="add-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="add-user-form">
                <div class="form-group">
                    <label for="add_fname">First Name</label>
                    <input type="text" id="add_fname" name="fname" required>
                </div>
                
                <div class="form-group">
                    <label for="add_lname">Last Name</label>
                    <input type="text" id="add_lname" name="lname" required>
                </div>
                
                <div class="form-group">
                    <label for="add_email">Email</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="add_password">Password</label>
                    <input type="password" id="add_password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="add_wallet_balance">Wallet Balance</label>
                    <input type="number" id="add_wallet_balance" name="wallet_balance" step="0.01" value="0.00">
                </div>
                
                <div class="form-group">
                    <label for="add_is_admin">Admin</label>
                    <select id="add_is_admin" name="is_admin">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="edit-user-form">
                <input type="hidden" id="edit_user_id" name="id">
                
                <div class="form-group">
                    <label for="edit_fname">First Name</label>
                    <input type="text" id="edit_fname" name="fname" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lname">Last Name</label>
                    <input type="text" id="edit_lname" name="lname" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_password">Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password">
                </div>
                
                <div class="form-group">
                    <label for="edit_wallet_balance">Wallet Balance</label>
                    <input type="number" id="edit_wallet_balance" name="wallet_balance" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="edit_is_admin">Admin</label>
                    <select id="edit_is_admin" name="is_admin">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div id="delete-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete User</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            <input type="hidden" id="delete_user_id">
            <div class="form-actions">
                <button id="confirm-delete-btn" class="delete-btn">Delete</button>
                <button id="cancel-delete-btn" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Add User button click
        $("#add-user-btn").click(function() {
            $("#add-user-modal").css("display", "block");
        });
        
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
        $("#user-search-form").submit(function(e) {
            e.preventDefault();
            const search = $("#user-search").val();
            loadUsersWithSearch(search);
        });
        
        // Pagination
        $(".page-link").click(function(e) {
            e.preventDefault();
            const page = $(this).data("page");
            const search = $("#user-search").val();
            loadUsersPage(page, search);
        });
        
        // Add user form submission
        $("#add-user-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/add_user.php",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("User added successfully!");
                        $("#add-user-modal").css("display", "none");
                        $("#add-user-form")[0].reset();
                        // Reload users to show the new user
                        const search = $("#user-search").val();
                        loadUsersWithSearch(search);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while adding the user.");
                }
            });
        });
        
        // Edit user
        $(".edit-user").click(function() {
            const userId = $(this).data("id");
            
            $.ajax({
                type: "GET",
                url: "admin_ajax/get_user.php",
                data: { id: userId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        const user = response.data;
                        
                        // Fill the edit form with user data
                        $("#edit_user_id").val(user.id);
                        $("#edit_fname").val(user.fname);
                        $("#edit_lname").val(user.lname);
                        $("#edit_email").val(user.email);
                        $("#edit_password").val(''); // Clear password field
                        $("#edit_wallet_balance").val(user.wallet_balance);
                        $("#edit_is_admin").val(user.is_admin);
                        
                        // Show the edit modal
                        $("#edit-user-modal").css("display", "block");
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while fetching user data.");
                }
            });
        });
        
        // Edit user form submission
        $("#edit-user-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/update_user.php",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("User updated successfully!");
                        $("#edit-user-modal").css("display", "none");
                        // Reload users to show the updated user
                        const search = $("#user-search").val();
                        loadUsersWithSearch(search);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while updating the user.");
                }
            });
        });
        
        // Delete user
        $(".delete-user").click(function() {
            const userId = $(this).data("id");
            $("#delete_user_id").val(userId);
            $("#delete-user-modal").css("display", "block");
        });
        
        // Confirm delete user
        $("#confirm-delete-btn").click(function() {
            const userId = $("#delete_user_id").val();
            
            $.ajax({
                type: "POST",
                url: "admin_ajax/delete_user.php",
                data: { id: userId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("User deleted successfully!");
                        $("#delete-user-modal").css("display", "none");
                        // Reload users to remove the deleted user
                        const search = $("#user-search").val();
                        loadUsersWithSearch(search);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while deleting the user.");
                }
            });
        });
        
        // Cancel delete user
        $("#cancel-delete-btn").click(function() {
            $("#delete-user-modal").css("display", "none");
        });
    });
    
    // Function to load users page
    function loadUsersPage(page, search = '') {
        $("#users-section").html('<div class="loading">Loading users...</div>');
        
        let url = "admin_sections/users.php?page=" + page;
        if (search) {
            url += "&search=" + encodeURIComponent(search);
        }
        
        $.ajax({
            url: url,
            success: function(response) {
                $("#users-section").html(response);
            },
            error: function() {
                $("#users-section").html('<div class="error">Failed to load users.</div>');
            }
        });
    }
    
    // Function to load users with search
    function loadUsersWithSearch(search) {
        $("#users-section").html('<div class="loading">Searching users...</div>');
        
        $.ajax({
            url: "admin_sections/users.php?search=" + encodeURIComponent(search),
            success: function(response) {
                $("#users-section").html(response);
            },
            error: function() {
                $("#users-section").html('<div class="error">Failed to search users.</div>');
            }
        });
    }
</script>