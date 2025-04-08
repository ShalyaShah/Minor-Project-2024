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

$city = $_POST['city'] ?? '';
$sql = "SELECT hotels.id, hotels.name, hotels.price_per_night, hotels.rating, 
               hotels.description, hotels.image_url, cities.name AS city, countries.name AS country 
        FROM hotels 
        JOIN cities ON hotels.city_id = cities.id
        JOIN countries ON cities.country_code = countries.code
        WHERE cities.name LIKE ?";
$stmt = $conn->prepare($sql);
$searchCity = "%" . $city . "%";
$stmt->bind_param("s", $searchCity);
$stmt->execute();
$result = $stmt->get_result();

$hotels = [];
while ($row = $result->fetch_assoc()) {
    $hotels[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($hotels);
?>