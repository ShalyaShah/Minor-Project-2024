<!-- admin_sections/users.php -->
<section id="users-section" class="admin-section">
    <div class="section-header">
        <h2>User Management</h2>
        <div class="section-actions">
            <div class="search-box">
                <input type="text" id="user-search" placeholder="Search users...">
                <i class="fas fa-search"></i>
            </div>
            <button class="add-btn" id="add-user-btn">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
    
    <div class="filter-options">
        <div class="filter-group">
            <label for="admin-filter">Admin Status:</label>
            <select id="admin-filter">
                <option value="all">All</option>
                <option value="1">Admin</option>
                <option value="0">Regular User</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="wallet-filter">Wallet Balance:</label>
            <select id="wallet-filter">
                <option value="all">All</option>
                <option value="0">Zero Balance</option>
                <option value="positive">Positive Balance</option>
            </select>
        </div>
        <button class="filter-btn">Apply Filters</button>
        <button class="reset-btn">Reset</button>
    </div>
    
    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Wallet Balance</th>
                    <th>Admin Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Database connection
                $conn = new mysqli("localhost", "root", "", "minor-project");
                
                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                
                // Get users with pagination
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;
                
                $sql = "SELECT id, fname, lname, email, wallet_balance, is_admin FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $adminStatus = $row['is_admin'] ? 'Admin' : 'User';
                        $adminClass = $row['is_admin'] ? 'admin' : 'user';
                        
                        echo "<tr>
                                <td>#" . $row['id'] . "</td>
                                <td>" . $row['fname'] . " " . $row['lname'] . "</td>
                                <td>" . $row['email'] . "</td>
                                <td>₹" . number_format($row['wallet_balance'], 2) . "</td>
                                <td><span class='status " . $adminClass . "'>" . $adminStatus . "</span></td>
                                <td>
                                    <div class='actions'>
                                        <a href='#' class='view-btn' data-id='" . $row['id'] . "'><i class='fas fa-eye'></i></a>
                                        <a href='#' class='edit-btn' data-id='" . $row['id'] . "'><i class='fas fa-edit'></i></a>
                                        <a href='#' class='delete-btn' data-id='" . $row['id'] . "'><i class='fas fa-trash'></i></a>
                                    </div>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='no-data'>No users found</td></tr>";
                }
                
                // Get total number of users for pagination
                $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                $totalPages = ceil($totalUsers / $limit);
                
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        <?php if($totalPages > 1): ?>
            <?php if($page > 1): ?>
                <a href="#" class="page-link" data-page="<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" class="page-link <?php echo $i == $page ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="#" class="page-link" data-page="<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- User Modal -->
<div class="modal" id="user-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Add New User</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="user-form">
                <input type="hidden" id="user-id" name="id">
                
                <div class="form-group">
                    <label for="first-name">First Name</label>
                    <input type="text" id="first-name" name="fname" required>
                </div>
                
                <div class="form-group">
                    <label for="last-name">Last Name</label>
                    <input type="text" id="last-name" name="lname" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password">
                    <small>Leave blank to keep current password (when editing)</small>
                </div>
                
                <div class="form-group">
                    <label for="wallet-balance">Wallet Balance</label>
                    <input type="number" id="wallet-balance" name="wallet_balance" step="0.01" min="0" value="0.00">
                </div>
                
                <div class="form-group">
                    <label for="is-admin">Admin Status</label>
                    <select id="is-admin" name="is_admin">
                        <option value="0">Regular User</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancel-user">Cancel</button>
                    <button type="submit" class="save-btn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .admin-section {
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .section-actions {
        display: flex;
        gap: 15px;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-box input {
        padding: 10px 15px;
        padding-right: 40px;
        border: 1px solid #eee;
        border-radius: 5px;
        width: 250px;
        font-size: 14px;
    }
    
    .search-box i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--secondary-color);
    }
    
    .add-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .filter-options {
        display: flex;
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
    
    .filter-group select {
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
    
    .table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    .user-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .user-table th, .user-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .user-table th {
        color: var(--secondary-color);
        font-weight: 500;
        font-size: 14px;
    }
    
    .user-table td {
        color: var(--dark-color);
        font-size: 14px;
    }
    
    .status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status.admin {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
    }
    
    .status.user {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
    }
    
    .actions {
        display: flex;
        gap: 10px;
    }
    
    .view-btn, .edit-btn, .delete-btn {
        width: 30px;
        height: 30px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
    }
    
    .view-btn {
        background-color: var(--info-color);
    }
    
    .edit-btn {
        background-color: var(--warning-color);
    }
    
    .delete-btn {
        background-color: var(--danger-color);
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }
    
    .page-link {
        padding: 8px 12px;
        border: 1px solid #eee;
        border-radius: 5px;
        color: var(--dark-color);
        text-decoration: none;
        font-size: 14px;
    }
    
    .page-link.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 10px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .close-modal {
        font-size: 24px;
        cursor: pointer;
        color: var(--secondary-color);
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: var(--dark-color);
    }
    
    .form-group input, .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group small {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: var(--secondary-color);
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .cancel-btn, .save-btn {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
    }
    
    .cancel-btn {
        background-color: var(--light-color);
        color: var(--dark-color);
    }
    
    .save-btn {
        background-color: var(--primary-color);
        color: white;
    }
</style>

<script>
    // User management functionality
    document.addEventListener('DOMContentLoaded', function() {
        const addUserBtn = document.getElementById('add-user-btn');
        const userModal = document.getElementById('user-modal');
        const closeModal = document.querySelector('.close-modal');
        const cancelUserBtn = document.getElementById('cancel-user');
        const userForm = document.getElementById('user-form');
        const modalTitle = document.getElementById('modal-title');
        
        // Open modal for adding a new user
        addUserBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New User';
            userForm.reset();
            document.getElementById('user-id').value = '';
            userModal.style.display = 'block';
        });
        
        // Close modal
        closeModal.addEventListener('click', function() {
            userModal.style.display = 'none';
        });
        
        cancelUserBtn.addEventListener('click', function() {
            userModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === userModal) {
                userModal.style.display = 'none';
            }
        });
        
        // Edit user
        const editBtns = document.querySelectorAll('.edit-btn');
        editBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-id');
                modalTitle.textContent = 'Edit User';
                
                // Fetch user data and populate the form
                fetch(`admin_ajax/get_user.php?id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('user-id').value = data.id;
                        document.getElementById('first-name').value = data.fname;
                        document.getElementById('last-name').value = data.lname;
                        document.getElementById('email').value = data.email;
                        document.getElementById('wallet-balance').value = data.wallet_balance;
                        document.getElementById('is-admin').value = data.is_admin;
                        
                        userModal.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching user data:', error);
                        alert('Failed to load user data. Please try again.');
                    });
            });
        });
        
        // Delete user
        const deleteBtns = document.querySelectorAll('.delete-btn');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-id');
                
                if (confirm('Are you sure you want to delete this user?')) {
                    fetch(`admin_ajax/delete_user.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${userId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('User deleted successfully');
                            // Reload the users section
                            loadSectionContent('users');
                        } else {
                            alert('Failed to delete user: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting user:', error);
                        alert('Failed to delete user. Please try again.');
                    });
                }
            });
        });
        
        // Submit user form
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userId = document.getElementById('user-id').value;
            const url = userId ? 'admin_ajax/update_user.php' : 'admin_ajax/add_user.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(userId ? 'User updated successfully' : 'User added successfully');
                    userModal.style.display = 'none';
                    // Reload the users section
                    loadSectionContent('users');
                } else {
                    alert('Failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // User search functionality
        const userSearchInput = document.getElementById('user-search');
        if (userSearchInput) {
            userSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const userRows = document.querySelectorAll('.user-table tbody tr');
                
                userRows.forEach(row => {
                    const userName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const userEmail = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    
                    if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>