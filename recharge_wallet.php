<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$dbname = 'minor-project';
$username = 'root';
$password = '';

// Establish a database connection using MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Start the session
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_balance') {
    // Fetch the wallet balance
    $query = "SELECT wallet_balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    if ($stmt->fetch()) {
        // Debugging: Log the wallet balance
        error_log("Wallet balance for user $user_id: $wallet_balance");
        echo json_encode(['status' => 'success', 'balance' => (float)$wallet_balance]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch wallet balance']);
    }
    $stmt->close();
} elseif ($action === 'recharge') {
    // Recharge the wallet
    $amount = floatval($_POST['amount']);

    // Validate the recharge amount
    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid recharge amount']);
        exit;
    }

    // Update the wallet balance for the user
    $query = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('di', $amount, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Wallet recharged successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to recharge wallet: ' . $stmt->error]);
    }
    $stmt->close();
} elseif ($action === 'deduct_balance') {
    // Deduct balance from the wallet
    $input = json_decode(file_get_contents('php://input'), true); // Decode JSON input
    $amount = floatval($input['amount']); // Ensure amount is parsed correctly

    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
        exit;
    }

    // Fetch the current wallet balance
    $query = "SELECT wallet_balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();

    if ($wallet_balance >= $amount) {
        // Deduct the amount from the wallet
        $query = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('di', $amount, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Balance deducted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to deduct balance']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Close the database connection
$conn->close();
?>