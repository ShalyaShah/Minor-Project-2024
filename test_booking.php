<?php
header('Content-Type: application/json');

// Disable displaying errors in the output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'test_booking_errors.log');

try {
    // Get JSON data from the request
    $jsonData = file_get_contents('php://input');
    $bookingData = json_decode($jsonData, true);
    
    // Log the received data
    error_log('Received test data: ' . print_r($bookingData, true));
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Test successful',
        'received' => $bookingData
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Test error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>