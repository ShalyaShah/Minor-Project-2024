document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const hotelId = urlParams.get("hotel_id");
  
    if (!hotelId) {
        document.getElementById("hotel-details").innerHTML = "<p>Invalid hotel ID. Please go back and try again.</p>";
        return;
    }
  
    fetch(`fetch_hotel_details.php?hotel_id=${hotelId}`)
        .then((response) => response.json())
        .then((data) => {
            const hotelDetailsContainer = document.getElementById("hotel-details");
  
            const hotel = data.hotel;
            sessionStorage.setItem("selectedHotel", JSON.stringify(hotel)); // Store selected hotel in sessionStorage
  
            hotelDetailsContainer.innerHTML = `
                <div class="hotel-info">
                    <img src="${hotel.image_url}" alt="Hotel Image" />
                    <h2>${hotel.name}</h2>
                    <p>${hotel.description}</p>
                    <p>Location: ${hotel.city}, ${hotel.country}</p>
                    <p>Price per night: ₹${parseFloat(hotel.price_per_night).toLocaleString()}</p>
                    <p>Rating: ⭐ ${parseFloat(hotel.rating).toFixed(2)}</p>
                </div>
                <h3>Available Rooms</h3>
                <div class="rooms-container">
                    ${data.rooms
                        .map(
                            (room) => `
                        <div class="room-card">
                            <img src="${room.image_url}" alt="Room Image" />
                            <h4>${room.room_type}</h4>
                            <p>Price: ₹${parseFloat(room.price).toLocaleString()}</p>
                            <p>Availability: ${room.availability} rooms</p>
                            <button onclick="selectRoom(${room.id})" class="select-button">Select Room</button>
                        </div>
                    `
                        )
                        .join("")}
                </div>
            `;
        })
        .catch(() => {
            document.getElementById("hotel-details").innerHTML = "<p>Error fetching hotel details. Please try again later.</p>";
        });
  });
  
  // Function to select a room
  function selectRoom(roomId) {
    sessionStorage.setItem("selectedRoom", roomId);
  
    // Show guest information section and hide others
    document.getElementById("guest-info-section").style.display = "block";
    document.getElementById("payment-section").style.display = "none";
    document.getElementById("hotel-details").style.display = "none";
  
    // Generate guest information form
    const guestDetailsDiv = document.getElementById("guestDetails");
    const searchData = JSON.parse(sessionStorage.getItem("searchData"));
  
    guestDetailsDiv.innerHTML = "";
    for (let i = 0; i < searchData.guests; i++) {
        guestDetailsDiv.innerHTML += `
            <div>
                <h3>Guest ${i + 1}</h3>
                <label>First Name: <input type="text" id="firstName${i}" required></label>
                <label>Last Name: <input type="text" id="lastName${i}" required></label>
                <label>Email: <input type="email" id="email${i}" required></label>
                <label>Phone: <input type="text" id="phone${i}" required></label>
                <label>Date of Birth: <input type="date" id="dob${i}" required></label>
            </div>
        `;
    }
  }
  
  function showPaymentSection() {
    // Validate guest information
    const searchData = JSON.parse(sessionStorage.getItem("searchData"));
    const guests = [];
    for (let i = 0; i < searchData.guests; i++) {
        const firstName = document.getElementById(`firstName${i}`).value;
        const lastName = document.getElementById(`lastName${i}`).value;
        const email = document.getElementById(`email${i}`).value;
        const phone = document.getElementById(`phone${i}`).value;
        const dob = document.getElementById(`dob${i}`).value;
  
        if (!firstName || !lastName || !email || !phone || !dob) {
            alert("Please fill in all guest details.");
            return;
        }
  
        guests.push({ firstName, lastName, email, phone, dob });
    }
  
    sessionStorage.setItem("guestInfo", JSON.stringify(guests));
  
    // Show payment section and hide others
    document.getElementById("guest-info-section").style.display = "none";
    document.getElementById("payment-section").style.display = "block";
  }
  
  function processPayment() {
    const selectedHotel = JSON.parse(sessionStorage.getItem("selectedHotel"));
    const selectedRoom = sessionStorage.getItem("selectedRoom");
    const guestInfo = JSON.parse(sessionStorage.getItem("guestInfo"));
    const paymentMethod = document.getElementById("paymentMethod").value;
  
    if (!selectedHotel || !selectedRoom || !guestInfo || !paymentMethod) {
        alert("Missing required booking information. Please try again.");
        return;
    }
  
    const bookingData = {
        hotel: selectedHotel,
        roomId: selectedRoom,
        guests: guestInfo,
        paymentMethod: paymentMethod,
    };
  
    console.log("Sending booking data:", bookingData);
  
    fetch("test_booking.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(bookingData),
    })
        .then((response) => {
            // Check if the response is JSON
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If not JSON, get the text and throw an error
                return response.text().then(text => {
                    throw new Error("Server returned non-JSON response: " + text);
                });
            }
        })
        .then((data) => {
            console.log("Server response:", data);
            if (data.success) {
                alert("Booking successful! Reference: " + data.bookingReference);
                sessionStorage.clear();
                window.location.href = "hotel.html";
            } else {
                alert("Booking failed: " + data.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            alert("An error occurred: " + error.message);
        });
  }
  
  // Update payment fields dynamically
  document.getElementById("paymentMethod").addEventListener("change", updatePaymentDetailsForm);
  
  function updatePaymentDetailsForm() {
    const paymentMethod = document.getElementById("paymentMethod").value;
    const paymentDetailsDiv = document.getElementById("paymentDetails");
  
    let paymentDetailsHTML = "";
    switch (paymentMethod) {
        case "credit-card":
            paymentDetailsHTML = `
                <div class="form-group">
                    <label for="cardName">Name on Card</label>
                    <input type="text" id="cardName" name="cardName" required>
                </div>
                <div class="form-group">
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" name="cardNumber" placeholder="XXXX XXXX XXXX XXXX" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiryDate">Expiry Date</label>
                        <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="XXX" required>
                    </div>
                </div>
            `;
            break;
        case "paypal":
            paymentDetailsHTML = `
                <div class="form-group">
                    <label for="paypalEmail">PayPal Email</label>
                    <input type="email" id="paypalEmail" name="paypalEmail" required>
                </div>
            `;
            break;
        case "bank-transfer":
            paymentDetailsHTML = `
                <div class="form-group">
                    <label for="accountNumber">Account Number</label>
                    <input type="text" id="accountNumber" name="accountNumber" required>
                </div>
                <div class="form-group">
                    <label for="ifscCode">IFSC Code</label>
                    <input type="text" id="ifscCode" name="ifscCode" required>
                </div>
            `;
            break;
        case "upi":
            paymentDetailsHTML = `
                <div class="form-group">
                    <label for="upiId">UPI ID</label>
                    <input type="text" id="upiId" name="upiId" required>
                </div>
            `;
            break;
        case "wallet":
            paymentDetailsHTML = `
                <div class="form-group">
                    <p id="walletBalance">Fetching wallet balance...</p>
                </div>
            `;
            fetchWalletBalance();
            break;
        default:
            paymentDetailsHTML = "";
    }
  
    paymentDetailsDiv.innerHTML = paymentDetailsHTML;
  }
  
  async function fetchWalletBalance() {
    try {
        const response = await fetch('recharge_wallet.php?action=get_balance');
        const result = await response.json();
  
        if (result.status === 'success') {
            const walletBalanceDiv = document.getElementById('walletBalance');
            walletBalanceDiv.innerHTML = `
                <p><strong>Wallet Balance:</strong> ₹${result.balance.toFixed(2)}</p>
            `;
        } else {
            throw new Error(result.message || 'Failed to fetch wallet balance');
        }
    } catch (error) {
        console.error('Error fetching wallet balance:', error);
        const walletBalanceDiv = document.getElementById('walletBalance');
        walletBalanceDiv.innerHTML = `<p>Error fetching wallet balance. Please try again later.</p>`;
    }
  }