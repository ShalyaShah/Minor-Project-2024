<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$hotelId = (int)($_GET['hotel_id'] ?? 0);

if ($hotelId <= 0) {
    echo json_encode(["error" => "Invalid hotel ID."]);
    exit;
}

$sqlHotel = "SELECT hotels.id, hotels.name, hotels.price_per_night, hotels.rating, 
                    hotels.description, hotels.image_url, cities.name AS city, countries.name AS country 
             FROM hotels 
             JOIN cities ON hotels.city_id = cities.id
             JOIN countries ON cities.country_code = countries.code
             WHERE hotels.id = ?";
$stmtHotel = $conn->prepare($sqlHotel);
$stmtHotel->bind_param("i", $hotelId);
$stmtHotel->execute();
$resultHotel = $stmtHotel->get_result();
$hotel = $resultHotel->fetch_assoc();
$stmtHotel->close();

if (!$hotel) {
    echo json_encode(["error" => "Hotel not found."]);
    exit;
}

$sqlRooms = "SELECT id, room_type, price, availability, image_url 
             FROM rooms 
             WHERE hotel_id = ?";
$stmtRooms = $conn->prepare($sqlRooms);
$stmtRooms->bind_param("i", $hotelId);
$stmtRooms->execute();
$resultRooms = $stmtRooms->get_result();

$rooms = [];
while ($row = $resultRooms->fetch_assoc()) {
    $rooms[] = $row;
}

$stmtRooms->close();
$conn->close();

echo json_encode(["hotel" => $hotel, "rooms" => $rooms]);
?>