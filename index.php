<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoTrip - Travel Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/Logo GoTrip.jpeg" alt="GoTrip Logo">
            <span>GoTrip</span>
        </div>
        
        <div class="menu">
    <a href="flight.html" class="menu-item"><i class="fa-solid fa-plane"></i> Flights</a>
    <a href="hotel.html" class="menu-item"><i class="fa-solid fa-hotel"></i> Hotels</a>
    <?php
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Show login/signup link for non-logged in users
        echo '<a href="login.html" class="menu-item"><i class="fa-solid fa-user"></i>Login / Signup</a>';
    } else {
        // Add the "View Bookings" button for logged-in users
        echo '<a href="view_bookings.php" class="menu-item"><i class="fa-solid fa-book"></i> View Bookings</a>';
        // Add the wallet link for logged-in users
        echo '<a href="#" class="menu-item wallet-link" id="walletLink"><i class="fa-solid fa-wallet"></i> Wallet</a>';
        // Show logout link for logged in users
        echo '<a href="logout.php" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>';
    }
    ?>
        </div>
        
    </header>
    <!-- Wallet Popup -->
<div class="wallet-popup" id="walletPopup">
    <div class="wallet-popup-content">
        <h3>💰 Wallet</h3>
        <p>Your Wallet Balance: ₹<span id="walletBalance">Loading...</span></p>
        <form id="rechargeWalletForm">
            <label for="rechargeAmount">Add Amount:</label>
            <input type="number" id="rechargeAmount" name="amount" min="1" placeholder="Enter amount" required>
            <button type="submit" class="btn">Recharge</button>
        </form>
        <button class="close-btn" id="closeWalletPopup">Close</button>
        <div id="rechargeMessage"></div>
    </div>
</div>
    

  <!-- Hero Section -->
  <section class="hero">
        <div class="hero-content">
            <h1>Discover Your Next Adventure</h1>
            <?php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Get the email from the session
    $email = $_SESSION['email'];

    // Database connection
    $servername = "localhost"; // Replace with your database server
    $username = "root"; // Replace with your database username
    $password = ""; // Replace with your database password
    $dbname = "minor-project"; // Replace with your database name

    // Create a connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to fetch the first name
    $sql = "SELECT fname FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a result is found
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fname = $row['fname'];
        echo "<p>Welcome, $fname!</p>";
    } else {
        echo "<p>Welcome, Guest!</p>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "<p>Welcome, Guest!</p>";
}
?>
        <button class="explore-btn">Explore Now</button>
        </div>
    </section>

    <div class="search-form">
        <form id="flightSearchForm">
        <div class="fields">
            <label class="label">Depature Airport</label>
            <input type="text" id="departure_airport" placeholder="Enter airport or city" required>
            <label class="label">Arrival Airport</label>
            <input type="text" id="arrival_airport" placeholder="Enter airport or city" required>
            <label class="label">Depature Date</label>
            <input type="date" name="date" id="departure_date" placeholder="Depature Date" required>
            <label class="label">No. of Adults</label>
            <input type="number" id="adults" min="1" value="1" max="9" placeholder="Passengers" required>
        </div>
        <div class="search-button">
            <button id="flight_search_button">Search Flights</button>
        </div>
        </form>
    </div>



    <!-- Popular Flight Routes -->
    <section class="flight-routes">
        <h2>Popular Flight Routes</h2>
        <div class="routes-container">
            
            <div class="route-card">
                <div class="route-front">
                    <span class="discount-badge">20% OFF</span>
                    <img src="images/mumbai-delhi.jpg" alt="Mumbai to Delhi">
                    <div class="route-info">
                        <h3>Mumbai to Delhi</h3>
                        <p>Starting from ₹2,999. Duration: 2 hrs</p>
                    </div>
                </div>
                <div class="route-back">
                    <p>🔹 Non-stop flights available <br> 
                       🔹 Free meal on board <br>
                       🔹 Flexible cancellation</p>
                    <button class="book-btn">Book Now</button>
                </div>
            </div>
    
            <div class="route-card">
                <div class="route-front">
                    <span class="discount-badge">15% OFF</span>
                    <img src="images/nyc-la.png" alt="New York to Los Angeles">
                    <div class="route-info">
                        <h3>New York to Los Angeles</h3>
                        <p>Starting from ₹17,000. Duration: 6 hrs</p>
                    </div>
                </div>
                <div class="route-back">
                    <p>✈️ Business class upgrade available <br>
                       🏨 Free hotel stay on delays <br>
                       🍽️ Complimentary meal</p>
                    <button class="book-btn">Book Now</button>
                </div>
            </div>
    
            <div class="route-card">
                <div class="route-front">
                    <span class="discount-badge">30% OFF</span>
                    <img src="images/london-paris.jpg" alt="London to Paris">
                    <div class="route-info">
                        <h3>Mumbai to Ram Mandir</h3>
                        <p>Starting from ₹5,300. Duration: 1.5 hrs</p>
                    </div>
                </div>
                <div class="route-back">
                    <p>🎒 Free baggage allowance <br>
                       🌎 Airport lounge access <br>
                       🏨 Stay & Fly discounts</p>
                    <button class="book-btn">Book Now</button>
                </div>
            </div>
    
        </div>
    </section>
    
    
    <!-- Flight Offers and Promotions -->
    <section class="exclusive-offers">
        <h2>✈️ Exclusive Flight Offers</h2>
        <div class="offers-container">
            <div class="offer-card">
                <img src="images/discount1.jpg" alt="Early Bird Discount">
                <h3>Early Bird Discount</h3>
                <p>Book your tickets 3 months in advance and save up to 30%.</p>
                <button class="view-offers-btn">View Offers</button>
            </div>
            
            <div class="offer-card">
                <img src="images/discount2.png" alt="Weekend Getaway">
                <h3>Weekend Getaway</h3>
                <p>Flat ₹1,500 off on domestic round-trip flights every weekend.</p>
                <button class="view-offers-btn">View Offers</button>
            </div>

            <div class="offer-card">
                <img src="images/discount3.png" alt="International Sale">
                <h3>International Sale</h3>
                <p>Save up to ₹10,000 on international flights to top destinations.</p>
                <button class="view-offers-btn">View Offers</button>
            </div>
        </div>
    </section>
  
    <section class="exclusive-offers">
        <h2>🔥 Exclusive Offers</h2>
        <div class="offers-container">
            
            <div class="offer-card">
                <div class="icon">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <h3>50% Off to Bali</h3>
                <p>Save 50% on flights to Bali. Limited time offer!</p>
                <button class="offer-btn">Grab Deal</button>
            </div>
            
            <div class="offer-card">
                <div class="icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <h3>Save on Hotels</h3>
                <p>Get 30% off on hotel bookings worldwide.</p>
                <button class="offer-btn">Book Now</button>
            </div>

            <div class="offer-card">
                <div class="icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Student Discounts</h3>
                <p>Special discounts for students on domestic flights.</p>
                <button class="offer-btn">Check Offers</button>
            </div>

        </div>
    </section>

    <!-- Travel Inspiration -->
    <section class="travel-inspiration">
        <h2>🌍 Travel Inspiration</h2>
        <div class="inspiration-container">
            
            <div class="inspiration-card">
                <img src="images/beach.jpg" alt="Beach Destinations">
                <h3>Top Beach Destinations</h3>
                <p>Escape to the sunniest shores across the globe.</p>
                <button class="explore-btn">Explore Now</button>
            </div>
            
            <div class="inspiration-card">
                <img src="images/mountains.jpg" alt="Mountain Getaways">
                <h3>Mountain Getaways</h3>
                <p>Find peace and adventure in the world's most stunning peaks.</p>
                <button class="explore-btn">Discover More</button>
            </div>

            <div class="inspiration-card">
                <img src="images/cities.jpg" alt="City Breaks">
                <h3>City Breaks</h3>
                <p>Discover vibrant cities and their hidden treasures.</p>
                <button class="explore-btn">See Destinations</button>
            </div>

        </div>
    </section>

    
<!-- Customer Reviews -->
<section class="testimonials">
    <h2>What Our Customers Say</h2>
    <div class="testimonial-container">
        <div class="testimonial-card">
            <span class="quote-icon">❝</span>
            <p>"Booking with GoTrip was a breeze! Got amazing discounts on my tickets."</p>
            <p class="customer-name">- Akshat D.</p>
        </div>
        <div class="testimonial-card">
            <span class="quote-icon">❝</span>
            <p>"The best platform for last-minute travel plans. Highly recommended!"</p>
            <p class="customer-name">- Meet S.</p>
        </div>
        <div class="testimonial-card">
            <span class="quote-icon">❝</span>
            <p>"Excellent customer service and unbeatable offers. Will use it again."</p>
            <p class="customer-name">- Shalya S.</p>
        </div>
    </div>
</section>

    <!-- Newsletter Signup -->
<section class="newsletter">
    <h2>Stay Updated with the Latest Deals</h2>
    <p>Subscribe to receive exclusive offers and travel discounts directly to your inbox.</p>
    <form class="newsletter-form">
        <input type="email" placeholder="Enter your email address" required>
        <button type="submit" class="btn">Subscribe</button>
    </form>
</section>

    <footer>
        <div class="footer-container">
            <div class="footer-section about">
                <h3><a href="about.html">About GoTrip</a></h3>
                <p>Your one-stop destination for flights, hotels, and travel experiences. Explore the world with ease!</p>
            </div>
    
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Destinations</a></li>
                    <li><a href="#">Hotels</a></li>
                    <li><a href="#">Flights</a></li>
                    <li><a href="#">Holiday Packages</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
    
            <div class="footer-section">
                <h3>Customer Support</h3>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Cancellation & Refund Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
    
            <div class="footer-section">
                <h3>Payment Methods</h3>
                <div class="payment-icons">
                    <img src="images/visa.png" alt="Visa">
                    <img src="images/mastercard.png" alt="Mastercard">
                    <img src="images/paypal.png" alt="PayPal">
                    <img src="images/upi.png" alt="UPI">
                </div>
            </div>
    
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><img src="images/facebook.png" alt="Facebook"></a>
                    <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
                    <a href="#"><img src="images/twitterr.png" alt="Twitter"></a>
                    <a href="#"><img src="images/linkedin.png" alt="LinkedIn"></a>
                </div>
            </div>
    
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>📧 Email: <a href="mailto:support@gotrip.com">support@gotrip.com</a></p>
                <p>📞 Phone: +91-9145785230</p>
                <p>📍 Address: Noida, New Delhi</p>
            </div>
        </div>
    
        <div class="footer-bottom">
            <p>© 2025 GoTrip. All Rights Reserved.</p>
        </div>
    </footer>
    <script type="module" src="js/auth.js"></script>
    <script type="module" src="js/airport_search.js"></script>
    <script type="module" src="js/flight_offers.js"></script>
    <script src="js/wallet.js"></script>
    </body>
</html>
