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

// Get current settings
$settings = [
    'site_name' => 'GoTrip',
    'site_email' => 'admin@gotrip.com',
    'currency' => 'INR',
    'currency_symbol' => '₹',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
    'items_per_page' => '10',
    'enable_bookings' => '1',
    'maintenance_mode' => '0',
    'admin_email' => 'admin@gotrip.com'
];

// Check if settings table exists
$table_check_query = "SHOW TABLES LIKE 'settings'";
$table_check_result = $conn->query($table_check_query);

if ($table_check_result->num_rows > 0) {
    // Get settings from database
    $settings_query = "SELECT * FROM settings";
    $settings_result = $conn->query($settings_query);
    
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Get admin user info
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM users WHERE id = $admin_id";
$admin_result = $conn->query($admin_query);
$admin_info = $admin_result->fetch_assoc();

// Handle form submissions via AJAX
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_general') {
        // Save general settings
        $site_name = $_POST['site_name'];
        $site_email = $_POST['site_email'];
        $currency = $_POST['currency'];
        $currency_symbol = $_POST['currency_symbol'];
        $date_format = $_POST['date_format'];
        $time_format = $_POST['time_format'];
        $items_per_page = $_POST['items_per_page'];
        
        // Create settings table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL,
            setting_value TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        )";
        $conn->query($create_table_query);
        
        // Update settings
        $settings_to_update = [
            'site_name' => $site_name,
            'site_email' => $site_email,
            'currency' => $currency,
            'currency_symbol' => $currency_symbol,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'items_per_page' => $items_per_page
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $check_query = "SELECT * FROM settings WHERE setting_key = '$key'";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows > 0) {
                $update_query = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
                $conn->query($update_query);
            } else {
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
                $conn->query($insert_query);
            }
        }
        
        $message = 'General settings saved successfully!';
    } elseif ($action === 'save_booking') {
        // Save booking settings
        $enable_bookings = isset($_POST['enable_bookings']) ? '1' : '0';
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
        $booking_confirmation_message = $_POST['booking_confirmation_message'];
        
        // Create settings table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL,
            setting_value TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        )";
        $conn->query($create_table_query);
        
        // Update settings
        $settings_to_update = [
            'enable_bookings' => $enable_bookings,
            'maintenance_mode' => $maintenance_mode,
            'booking_confirmation_message' => $booking_confirmation_message
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $check_query = "SELECT * FROM settings WHERE setting_key = '$key'";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows > 0) {
                $update_query = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
                $conn->query($update_query);
            } else {
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
                $conn->query($insert_query);
            }
        }
        
        $message = 'Booking settings saved successfully!';
    } elseif ($action === 'save_email') {
        // Save email settings
        $admin_email = $_POST['admin_email'];
        $email_sender_name = $_POST['email_sender_name'];
        $booking_confirmation_subject = $_POST['booking_confirmation_subject'];
        $booking_confirmation_template = $_POST['booking_confirmation_template'];
        
        // Create settings table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL,
            setting_value TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        )";
        $conn->query($create_table_query);
        
        // Update settings
        $settings_to_update = [
            'admin_email' => $admin_email,
            'email_sender_name' => $email_sender_name,
            'booking_confirmation_subject' => $booking_confirmation_subject,
            'booking_confirmation_template' => $booking_confirmation_template
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $check_query = "SELECT * FROM settings WHERE setting_key = '$key'";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows > 0) {
                $update_query = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
                $conn->query($update_query);
            } else {
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
                $conn->query($insert_query);
            }
        }
        
        $message = 'Email settings saved successfully!';
    } elseif ($action === 'update_account') {
        // Update admin account
        $admin_id = $_SESSION['user_id'];
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        
        // Verify current password
        $password_query = "SELECT password FROM users WHERE id = $admin_id";
        $password_result = $conn->query($password_query);
        $password_row = $password_result->fetch_assoc();
        
        if ($password_row['password'] === $current_password) {
            // Update user info
            $update_query = "UPDATE users SET fname = '$fname', lname = '$lname', email = '$email'";
            
            // Update password if provided
            if (!empty($new_password)) {
                $update_query .= ", password = '$new_password'";
            }
            
            $update_query .= " WHERE id = $admin_id";
            $conn->query($update_query);
            
            $message = 'Account updated successfully!';
        } else {
            $message = 'Current password is incorrect!';
        }
    }
}
?>

<div class="section-header">
    <h2>System Settings</h2>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-success">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="settings-container">
    <div class="settings-tabs">
        <button class="tab-btn active" data-tab="general">General Settings</button>
        <button class="tab-btn" data-tab="booking">Booking Settings</button>
        <button class="tab-btn" data-tab="email">Email Settings</button>
        <button class="tab-btn" data-tab="account">Account Settings</button>
    </div>
    
    <div class="settings-content">
        <!-- General Settings Tab -->
        <div class="tab-content active" id="general-tab">
            <form id="general-settings-form" class="settings-form">
                <input type="hidden" name="action" value="save_general">
                
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="site_email">Site Email</label>
                    <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency">
                        <option value="INR" <?php echo $settings['currency'] == 'INR' ? 'selected' : ''; ?>>Indian Rupee (INR)</option>
                        <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                        <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                        <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="currency_symbol">Currency Symbol</label>
                    <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_format">Date Format</label>
                    <select id="date_format" name="date_format">
                        <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                        <option value="m/d/Y" <?php echo $settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                        <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="time_format">Time Format</label>
                    <select id="time_format" name="time_format">
                        <option value="H:i" <?php echo $settings['time_format'] == 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                        <option value="h:i A" <?php echo $settings['time_format'] == 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="items_per_page">Items Per Page</label>
                    <input type="number" id="items_per_page" name="items_per_page" min="5" max="100" value="<?php echo htmlspecialchars($settings['items_per_page']); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Save General Settings</button>
                </div>
            </form>
        </div>
        
        <!-- Booking Settings Tab -->
        <div class="tab-content" id="booking-tab">
            <form id="booking-settings-form" class="settings-form">
                <input type="hidden" name="action" value="save_booking">
                
                <div class="form-group">
                    <label for="enable_bookings">Enable Bookings</label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="enable_bookings" name="enable_bookings" <?php echo $settings['enable_bookings'] == '1' ? 'checked' : ''; ?>>
                        <label for="enable_bookings"></label>
                    </div>
                    <p class="form-help">If disabled, users will not be able to make new bookings.</p>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_mode">Maintenance Mode</label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                        <label for="maintenance_mode"></label>
                    </div>
                    <p class="form-help">If enabled, the website will display a maintenance message to visitors.</p>
                </div>
                
                <div class="form-group">
                    <label for="booking_confirmation_message">Booking Confirmation Message</label>
                    <textarea id="booking_confirmation_message" name="booking_confirmation_message" rows="4"><?php echo isset($settings['booking_confirmation_message']) ? htmlspecialchars($settings['booking_confirmation_message']) : 'Thank you for your booking! Your booking has been confirmed.'; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Save Booking Settings</button>
                </div>
            </form>
        </div>
        
        <!-- Email Settings Tab -->
        <div class="tab-content" id="email-tab">
            <form id="email-settings-form" class="settings-form">
                <input type="hidden" name="action" value="save_email">
                
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                    <p class="form-help">Email address where admin notifications will be sent.</p>
                </div>
                
                <div class="form-group">
                    <label for="email_sender_name">Email Sender Name</label>
                    <input type="text" id="email_sender_name" name="email_sender_name" value="<?php echo isset($settings['email_sender_name']) ? htmlspecialchars($settings['email_sender_name']) : 'GoTrip'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="booking_confirmation_subject">Booking Confirmation Subject</label>
                    <input type="text" id="booking_confirmation_subject" name="booking_confirmation_subject" value="<?php echo isset($settings['booking_confirmation_subject']) ? htmlspecialchars($settings['booking_confirmation_subject']) : 'Your Booking Confirmation'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="booking_confirmation_template">Booking Confirmation Template</label>
                    <textarea id="booking_confirmation_template" name="booking_confirmation_template" rows="6"><?php echo isset($settings['booking_confirmation_template']) ? htmlspecialchars($settings['booking_confirmation_template']) : 'Dear {customer_name},

Thank you for booking with GoTrip. Your booking reference is {booking_reference}.

Booking Details:
- Route: {origin} to {destination}
- Date: {departure_date}
- Passengers: {total_passengers}
- Total Amount: {total_amount}

You can view your booking details by logging into your account.

Best regards,
The GoTrip Team'; ?></textarea>
                    <p class="form-help">You can use placeholders like {customer_name}, {booking_reference}, etc.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Save Email Settings</button>
                </div>
            </form>
        </div>
        
        <!-- Account Settings Tab -->
        <div class="tab-content" id="account-tab">
            <form id="account-settings-form" class="settings-form">
                <input type="hidden" name="action" value="update_account">
                
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($admin_info['fname']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($admin_info['lname']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_info['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <p class="form-help">Enter your current password to confirm changes.</p>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password">
                    <p class="form-help">Leave blank to keep your current password.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .settings-container {
        display: flex;
        flex-direction: column;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .settings-tabs {
        display: flex;
        border-bottom: 1px solid #eee;
        background-color: #f8f9fa;
    }
    
    .tab-btn {
        padding: 15px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: var(--secondary-color);
        position: relative;
    }
    
    .tab-btn.active {
        color: var(--primary-color);
    }
    
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .settings-content {
        padding: 20px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .settings-form {
        max-width: 800px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--dark-color);
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-help {
        margin-top: 5px;
        font-size: 12px;
        color: var(--secondary-color);
    }
    
    .form-actions {
        margin-top: 30px;
    }
    
    .submit-btn {
        padding: 10px 20px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }
    
    .submit-btn:hover {
        background-color: var(--primary-dark-color);
    }
    
    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-switch label {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    
    .toggle-switch label:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    .toggle-switch input:checked + label {
        background-color: var(--primary-color);
    }
    
    .toggle-switch input:checked + label:before {
        transform: translateX(26px);
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }
</style>

<script>
    $(document).ready(function() {
        // Tab switching
        $(".tab-btn").click(function() {
            $(".tab-btn").removeClass("active");
            $(this).addClass("active");
            
            const tabId = $(this).data("tab");
            $(".tab-content").removeClass("active");
            $("#" + tabId + "-tab").addClass("active");
        });
        
        // General settings form submission
        $("#general-settings-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_sections/settings.php",
                data: $(this).serialize(),
                success: function(response) {
                    // Reload the settings page to show the updated settings
                    $("#settings-section").html(response);
                    
                    // Show success message
                    alert("General settings saved successfully!");
                },
                error: function() {
                    alert("An error occurred while saving general settings.");
                }
            });
        });
        
        // Booking settings form submission
        $("#booking-settings-form").submit(function(e) {
            e.preventDefault();
            
            // Get form data
            let formData = $(this).serializeArray();
            
            // Add checkbox values
            formData.push({
                name: "enable_bookings",
                value: $("#enable_bookings").is(":checked") ? "1" : "0"
            });
            
            formData.push({
                name: "maintenance_mode",
                value: $("#maintenance_mode").is(":checked") ? "1" : "0"
            });
            
            $.ajax({
                type: "POST",
                url: "admin_sections/settings.php",
                data: formData,
                success: function(response) {
                    // Reload the settings page to show the updated settings
                    $("#settings-section").html(response);
                    
                    // Show success message
                    alert("Booking settings saved successfully!");
                },
                error: function() {
                    alert("An error occurred while saving booking settings.");
                }
            });
        });
        
        // Email settings form submission
        $("#email-settings-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_sections/settings.php",
                data: $(this).serialize(),
                success: function(response) {
                    // Reload the settings page to show the updated settings
                    $("#settings-section").html(response);
                    
                    // Show success message
                    alert("Email settings saved successfully!");
                },
                error: function() {
                    alert("An error occurred while saving email settings.");
                }
            });
        });
        
        // Account settings form submission
        $("#account-settings-form").submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: "admin_sections/settings.php",
                data: $(this).serialize(),
                success: function(response) {
                    // Reload the settings page to show the updated settings
                    $("#settings-section").html(response);
                    
                    // Check if there's an error message
                    if (response.includes("Current password is incorrect")) {
                        alert("Current password is incorrect!");
                    } else {
                        alert("Account updated successfully!");
                    }
                },
                error: function() {
                    alert("An error occurred while updating account.");
                }
            });
        });
    });
</script>