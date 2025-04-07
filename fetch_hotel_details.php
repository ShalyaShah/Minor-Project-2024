<?php
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
if ($hotel_id === 0) {
    echo json_encode(["error" => "Invalid hotel ID"]);
    exit;
}

// Fetch hotel
$hotel_sql = "SELECT hotels.*, cities.name AS city, countries.name AS country
              FROM hotels
              JOIN cities ON hotels.city_id = cities.id
              JOIN countries ON cities.country_code = countries.code
              WHERE hotels.id = ?";
$stmt = $conn->prepare($hotel_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel_result = $stmt->get_result();
$hotel = $hotel_result->fetch_assoc();

// Fetch rooms
$room_sql = "SELECT room_type, room_price FROM rooms WHERE hotel_id = ?";
$stmt = $conn->prepare($room_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$room_result = $stmt->get_result();
$rooms = [];
while ($room = $room_result->fetch_assoc()) {
    $rooms[] = $room;
}

$stmt->close();
$conn->close();

echo json_encode([
    "hotel" => $hotel,
    "rooms" => $rooms
]);
?>
