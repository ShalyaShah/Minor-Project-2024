document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search);
  const hotelId = urlParams.get("hotel_id");

  // Add this line for room price tracking
  let selectedRoomPrice = 0;

  if (!hotelId) {
      document.getElementById("hotel-details").innerHTML = "<p>Invalid hotel ID. Please go back and try again.</p>";
      return;
  }

  fetch(`fetch_hotel_details.php?hotel_id=${hotelId}`)
      .then((response) => response.json())
      .then((data) => {
          const hotelDetailsContainer = document.getElementById("hotel-details");

          const hotel = data.hotel;
          sessionStorage.setItem("selectedHotel", JSON.stringify(hotel));

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
                          <p>Price per night: ₹${parseFloat(room.price).toLocaleString()}</p>
                          <p>Availability: ${room.availability} rooms</p>
                          <button onclick="selectRoom(${room.id}, ${room.price})" class="select-button">Select Room</button>
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

function selectRoom(roomId, price) {
  sessionStorage.setItem("selectedRoom", roomId);
  sessionStorage.setItem("roomPrice", price);

  document.getElementById("guest-info-section").style.display = "block";
  document.getElementById("payment-section").style.display = "none";
  document.getElementById("hotel-details").style.display = "none";

  const guestDetailsDiv = document.getElementById("guestDetails");
  const searchData = JSON.parse(sessionStorage.getItem("searchData"));

  console.log('Raw searchData:', sessionStorage.getItem("searchData"));

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

  // Calculate nights - using the correct property names
  const checkIn = new Date(searchData.checkInDate); // Changed to checkInDate
  const checkOut = new Date(searchData.checkOutDate); // Changed to checkOutDate
  const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
  const totalAmount = price * nights * searchData.rooms;

  guestDetailsDiv.innerHTML += `
      <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
          <h3>Booking Summary</h3>
          <p>Room Price per Night: ₹${price.toLocaleString()}</p>
          <p>Check-in Date: ${checkIn.toLocaleDateString()}</p>
          <p>Check-out Date: ${checkOut.toLocaleDateString()}</p>
          <p>Number of Nights: ${nights}</p>
          <p>Number of Rooms: ${searchData.rooms}</p>
          <p>Calculation: ₹${price.toLocaleString()} × ${nights} nights × ${searchData.rooms} rooms</p>
          <p><strong>Total Amount: ₹${totalAmount.toLocaleString()}</strong></p>
      </div>
  `;
}

function showPaymentSection() {
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

  document.getElementById("guest-info-section").style.display = "none";
  document.getElementById("payment-section").style.display = "block";

  // Calculate total amount with correct property names
  const roomPrice = parseFloat(sessionStorage.getItem("roomPrice"));
  const checkIn = new Date(searchData.checkInDate); // Changed to checkInDate
  const checkOut = new Date(searchData.checkOutDate); // Changed to checkOutDate
  const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
  const totalAmount = roomPrice * nights * searchData.rooms;

  const paymentSection = document.getElementById("payment-section");
  if (!document.getElementById("total-amount-display")) {
      const totalAmountDiv = document.createElement("div");
      totalAmountDiv.id = "total-amount-display";
      totalAmountDiv.style.cssText = "margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center;";
      totalAmountDiv.innerHTML = `
          <h3>Payment Summary</h3>
          <p>Room Price per Night: ₹${roomPrice.toLocaleString()}</p>
          <p>Check-in Date: ${checkIn.toLocaleDateString()}</p>
          <p>Check-out Date: ${checkOut.toLocaleDateString()}</p>
          <p>Number of Nights: ${nights}</p>
          <p>Number of Rooms: ${searchData.rooms}</p>
          <p>Calculation: ₹${roomPrice.toLocaleString()} × ${nights} nights × ${searchData.rooms} rooms</p>
          <p><strong>Total Amount to Pay: ₹${totalAmount.toLocaleString()}</strong></p>
      `;
      paymentSection.insertBefore(totalAmountDiv, paymentSection.firstChild);
  }
}

// Helper function to calculate nights
function calculateNights(checkInDate, checkOutDate) {
  const checkIn = new Date(checkInDate);
  const checkOut = new Date(checkOutDate);
  return Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
}
  
function updatePaymentDetailsForm() {
  const paymentMethod = document.getElementById("paymentMethod").value;
  const paymentDetailsDiv = document.getElementById("paymentDetails");

  let paymentDetailsHTML = "";
  
  switch (paymentMethod) {
      case "credit-card":
          paymentDetailsHTML = `
              <div class="form-group">
                  <label for="cardName">Name on Card</label>
                  <input type="text" id="cardName" required>
              </div>
              <div class="form-group">
                  <label for="cardNumber">Card Number</label>
                  <input type="text" id="cardNumber" maxlength="16" placeholder="1234 5678 9012 3456" required>
              </div>
              <div class="form-row">
                  <div class="form-group">
                      <label for="expiryDate">Expiry Date</label>
                      <input type="text" id="expiryDate" placeholder="MM/YY" maxlength="5" required>
                  </div>
                  <div class="form-group">
                      <label for="cvv">CVV</label>
                      <input type="password" id="cvv" maxlength="3" placeholder="123" required>
                  </div>
              </div>
          `;
          break;
          
      case "paypal":
          paymentDetailsHTML = `
              <div class="form-group">
                  <label for="paypalEmail">PayPal Email</label>
                  <input type="email" id="paypalEmail" required>
              </div>
          `;
          break;
          
      case "bank-transfer":
          paymentDetailsHTML = `
              <div class="form-group">
                  <label for="accountNumber">Account Number</label>
                  <input type="text" id="accountNumber" required>
              </div>
              <div class="form-group">
                  <label for="ifscCode">IFSC Code</label>
                  <input type="text" id="ifscCode" required>
              </div>
              <div class="form-group">
                  <label for="accountName">Account Holder Name</label>
                  <input type="text" id="accountName" required>
              </div>
          `;
          break;
          
      case "upi":
          paymentDetailsHTML = `
              <div class="form-group">
                  <label for="upiId">UPI ID</label>
                  <input type="text" id="upiId" placeholder="username@upi" required>
              </div>
          `;
          break;
          
      case "wallet":
          paymentDetailsHTML = `
              <div class="form-group">
                  <div id="walletBalance" style="text-align: center; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                      <p>Fetching wallet balance...</p>
                  </div>
              </div>
          `;
          // Call function to fetch wallet balance
          fetchWalletBalance();
          break;
          
      default:
          paymentDetailsHTML = `
              <div class="form-group">
                  <p>Please select a payment method</p>
              </div>
          `;
  }

  paymentDetailsDiv.innerHTML = paymentDetailsHTML;

  // Add event listeners for input formatting if credit card is selected
  if (paymentMethod === "credit-card") {
      document.getElementById("cardNumber").addEventListener("input", function(e) {
          let value = e.target.value.replace(/\D/g, "");
          e.target.value = value;
      });

      document.getElementById("expiryDate").addEventListener("input", function(e) {
          let value = e.target.value.replace(/\D/g, "");
          if (value.length > 2) {
              value = value.slice(0, 2) + "/" + value.slice(2);
          }
          e.target.value = value;
      });

      document.getElementById("cvv").addEventListener("input", function(e) {
          let value = e.target.value.replace(/\D/g, "");
          e.target.value = value;
      });
  }
}
  
  async function fetchWalletBalance() {
    try {
        const response = await fetch('recharge_wallet.php?action=get_balance');
        const result = await response.json();
  
        if (result.status === 'success') {
            const walletBalanceDiv = document.getElementById('walletBalance');
            if (walletBalanceDiv) {
                walletBalanceDiv.innerHTML = `
                    <p><strong>Wallet Balance:</strong> ₹${result.balance.toFixed(2)}</p>
                `;
            }
        } else {
            throw new Error(result.message || 'Failed to fetch wallet balance');
        }
    } catch (error) {
        console.error('Error fetching wallet balance:', error);
        const walletBalanceDiv = document.getElementById('walletBalance');
        if (walletBalanceDiv) {
            walletBalanceDiv.innerHTML = `<p>Error fetching wallet balance. Please try again later.</p>`;
        }
    }
  }
  
  function processPayment() {
    const paymentButton = document.getElementById("paymentButton");
    if (paymentButton) {
        paymentButton.disabled = true;
        paymentButton.textContent = "Processing...";
    }

    const selectedHotel = JSON.parse(sessionStorage.getItem("selectedHotel"));
    const selectedRoom = sessionStorage.getItem("selectedRoom");
    const roomPrice = parseFloat(sessionStorage.getItem("roomPrice")); // Get the actual room price
    const guestInfo = JSON.parse(sessionStorage.getItem("guestInfo"));
    const searchData = JSON.parse(sessionStorage.getItem("searchData"));
    const paymentMethod = document.getElementById("paymentMethod").value;

    // Calculate correct total amount
    const checkIn = new Date(searchData.checkInDate);
    const checkOut = new Date(searchData.checkOutDate);
    const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    const totalAmount = roomPrice * nights * parseInt(searchData.rooms);

    // Validate payment details
    const paymentDetails = validatePaymentDetails(paymentMethod);
    if (!paymentDetails) {
        if (paymentButton) {
            paymentButton.disabled = false;
            paymentButton.textContent = "Complete Payment";
        }
        return;
    }

    const bookingData = {
        hotel: selectedHotel,
        roomId: selectedRoom,
        roomPrice: roomPrice, // Add room price to booking data
        guests: guestInfo,
        paymentMethod: paymentMethod,
        paymentDetails: paymentDetails,
        searchData: searchData,
        totalAmount: totalAmount, // Add calculated total amount
        nights: nights // Add calculated nights
    };

    console.log("Sending booking data:", bookingData);

    // Send booking data to server
    fetch("save_hotel_booking.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(bookingData),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Server response:", data);
        
        if (data.success) {
            // Generate booking confirmation with correct amounts
            const bookingConfirmation = {
                bookingReference: data.bookingReference,
                bookingDate: new Date().toISOString(),
                hotel: selectedHotel,
                roomType: getRoomTypeById(selectedRoom),
                checkIn: searchData.checkInDate,
                checkOut: searchData.checkOutDate,
                guests: guestInfo,
                nights: nights,
                roomPrice: roomPrice,
                totalAmount: totalAmount,
                paymentMethod: paymentMethod
            };
            
            // Store booking confirmation in sessionStorage
            sessionStorage.setItem("bookingConfirmation", JSON.stringify(bookingConfirmation));
            
            // Show booking confirmation
            showBookingConfirmation(bookingConfirmation);
        } else {
            alert(`Booking failed: ${data.message}`);
            if (paymentButton) {
                paymentButton.disabled = false;
                paymentButton.textContent = "Complete Payment";
            }
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert(`An error occurred: ${error.message}`);
        if (paymentButton) {
            paymentButton.disabled = false;
            paymentButton.textContent = "Complete Payment";
        }
    });
}
  
  function validatePaymentDetails(paymentMethod) {
    let isValid = true;
    const paymentDetails = {};
    
    switch (paymentMethod) {
      case "credit-card":
        const cardName = document.getElementById("cardName")?.value;
        const cardNumber = document.getElementById("cardNumber")?.value;
        const expiryDate = document.getElementById("expiryDate")?.value;
        const cvv = document.getElementById("cvv")?.value;
        
        if (!cardName || !cardNumber || !expiryDate || !cvv) {
          alert("Please fill in all credit card details.");
          return null;
        }
        
        paymentDetails.cardName = cardName;
        paymentDetails.cardNumber = maskCardNumber(cardNumber);
        paymentDetails.expiryDate = expiryDate;
        break;
        
      case "paypal":
        const paypalEmail = document.getElementById("paypalEmail")?.value;
        
        if (!paypalEmail) {
          alert("Please enter your PayPal email.");
          return null;
        }
        
        paymentDetails.paypalEmail = paypalEmail;
        break;
        
      case "bank-transfer":
        const accountNumber = document.getElementById("accountNumber")?.value;
        const ifscCode = document.getElementById("ifscCode")?.value;
        
        if (!accountNumber || !ifscCode) {
          alert("Please fill in all bank transfer details.");
          return null;
        }
        
        paymentDetails.accountNumber = maskAccountNumber(accountNumber);
        paymentDetails.ifscCode = ifscCode;
        break;
        
      case "upi":
        const upiId = document.getElementById("upiId")?.value;
        
        if (!upiId) {
          alert("Please enter your UPI ID.");
          return null;
        }
        
        paymentDetails.upiId = upiId;
        break;
        
      case "wallet":
        // Wallet validation would typically check balance against total amount
        paymentDetails.walletUsed = true;
        break;
        
      default:
        alert("Please select a valid payment method.");
        return null;
    }
    
    return paymentDetails;
  }
  
  function maskCardNumber(cardNumber) {
    // Remove spaces and non-numeric characters
    const cleaned = cardNumber.replace(/\D/g, '');
    // Keep first 4 and last 4 digits, mask the rest
    return cleaned.substring(0, 4) + " **** **** " + cleaned.slice(-4);
  }
  
  function maskAccountNumber(accountNumber) {
    // Remove spaces and non-numeric characters
    const cleaned = accountNumber.replace(/\D/g, '');
    // Keep last 4 digits, mask the rest
    return "******" + cleaned.slice(-4);
  }
  
  function getRoomTypeById(roomId) {
    // In a real application, you would fetch this from your data
    // For demo purposes, we'll return a placeholder
    return "Deluxe Room"; // Placeholder
  }
  
  function calculateNights(checkIn, checkOut) {
    if (!checkIn || !checkOut) {
      console.warn("Missing check-in or check-out date, defaulting to 1 night");
      return 1;
    }
    
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    // Validate dates
    if (isNaN(checkInDate.getTime()) || isNaN(checkOutDate.getTime())) {
      console.warn("Invalid date format, defaulting to 1 night");
      return 1;
    }
    
    const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
    const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    // Ensure at least 1 night
    return nights > 0 ? nights : 1;
  }
  
  function calculateTotalAmount(hotel, nights) {
    if (!hotel || !hotel.price_per_night) {
      console.warn("Missing hotel price information, defaulting to 0");
      return 0;
    }
    
    const pricePerNight = parseFloat(hotel.price_per_night);
    if (isNaN(pricePerNight)) {
      console.warn("Invalid price format, defaulting to 0");
      return 0;
    }
    
    return pricePerNight * nights;
  }
  
  function formatPaymentMethod(method) {
    const methods = {
      'credit-card': 'Credit Card',
      'paypal': 'PayPal',
      'bank-transfer': 'Bank Transfer',
      'upi': 'UPI',
      'wallet': 'Wallet'
    };
    
    return methods[method] || method;
  }
  
  function showBookingConfirmation(bookingData) {
    const paymentSection = document.getElementById("payment-section");
    if (paymentSection) {
        paymentSection.style.display = "none";
    }

    const bookingConfirmationSection = document.getElementById("booking-confirmation") || 
        document.createElement("div");
    bookingConfirmationSection.id = "booking-confirmation";
    
    // Format dates
    const checkIn = new Date(bookingData.checkIn);
    const checkOut = new Date(bookingData.checkOut);
    
    bookingConfirmationSection.innerHTML = `
        <div class="confirmation-icon">✓</div>
        <h2>Booking Confirmed!</h2>
        <p>Thank you for booking with GoTrip. Your booking has been confirmed.</p>
        
        <div class="booking-details">
            <h3>Booking Information</h3>
            <div class="detail-row">
                <span class="detail-label">Booking Reference:</span>
                <span class="detail-value">${bookingData.bookingReference}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Booking Date:</span>
                <span class="detail-value">${new Date(bookingData.bookingDate).toLocaleDateString()}</span>
            </div>
        </div>
        
        <div class="booking-details">
            <h3>Hotel Details</h3>
            <div class="detail-row">
                <span class="detail-label">Hotel:</span>
                <span class="detail-value">${bookingData.hotel.name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Room Type:</span>
                <span class="detail-value">${bookingData.roomType}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Room Price per Night:</span>
                <span class="detail-value">₹${bookingData.roomPrice.toLocaleString()}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-in:</span>
                <span class="detail-value">${checkIn.toLocaleDateString()}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-out:</span>
                <span class="detail-value">${checkOut.toLocaleDateString()}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Number of Nights:</span>
                <span class="detail-value">${bookingData.nights}</span>
            </div>
        </div>
        
        <div class="booking-details">
            <h3>Payment Details</h3>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">${formatPaymentMethod(bookingData.paymentMethod)}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Calculation:</span>
                <span class="detail-value">₹${bookingData.roomPrice.toLocaleString()} × ${bookingData.nights} nights</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount Paid:</span>
                <span class="detail-value">₹${bookingData.totalAmount.toLocaleString()}</span>
            </div>
        </div>
        
        <div class="booking-actions">
            <button class="print-button" onclick="window.print()">Print Booking Details</button>
            <button class="home-button" onclick="window.location.href='hotel.html'">Return to Home</button>
        </div>
    `;

    // Show booking confirmation section
    bookingConfirmationSection.style.display = "block";
    document.querySelector("main").appendChild(bookingConfirmationSection);
    bookingConfirmationSection.scrollIntoView({ behavior: 'smooth' });
}