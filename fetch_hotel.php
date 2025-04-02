<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Connect to Database
$servername = "localhost";  // Change if necessary
$username = "root";         // Your MySQL username
$password = "";             // Your MySQL password (default is empty in XAMPP)
$dbname = "hotel_booking";  // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Get search parameters from request
$city = $_POST['city'] ?? '';
$checkInDate = $_POST['checkInDate'] ?? '';
$checkOutDate = $_POST['checkOutDate'] ?? '';
$guests = (int)($_POST['guests'] ?? 1);
$rooms = (int)($_POST['rooms'] ?? 1);

// Query to fetch hotels in the selected city
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

// Return hotels as JSON
echo json_encode($hotels);
?>
