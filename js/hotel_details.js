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
  
  // Update payment fields dynamically
  document.addEventListener("DOMContentLoaded", function() {
    const paymentMethodSelect = document.getElementById("paymentMethod");
    if (paymentMethodSelect) {
      paymentMethodSelect.addEventListener("change", updatePaymentDetailsForm);
      // Initialize payment form
      updatePaymentDetailsForm();
    }
  });
  
  function updatePaymentDetailsForm() {
    const paymentMethod = document.getElementById("paymentMethod").value;
    const paymentDetailsDiv = document.getElementById("paymentDetails");
  
    if (!paymentDetailsDiv) return;
  
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
    // Get the payment button if it exists
    const paymentButton = document.getElementById("paymentButton");
    
    // Only modify the button if it exists
    if (paymentButton) {
        paymentButton.disabled = true;
        paymentButton.textContent = "Processing...";
    }    
    const selectedHotel = JSON.parse(sessionStorage.getItem("selectedHotel"));
    const selectedRoom = sessionStorage.getItem("selectedRoom");
    const guestInfo = JSON.parse(sessionStorage.getItem("guestInfo"));
    const paymentMethod = document.getElementById("paymentMethod").value;
    
    // Get search data with check-in and check-out dates
    let searchData;
    try {
      searchData = JSON.parse(sessionStorage.getItem("searchData"));
      if (!searchData || !searchData.checkIn || !searchData.checkOut) {
        // If searchData doesn't have check-in/check-out, try to get them from URL params
        const urlParams = new URLSearchParams(window.location.search);
        if (!searchData) searchData = {};
        if (!searchData.checkIn) searchData.checkIn = urlParams.get("checkIn") || new Date().toISOString().split('T')[0];
        if (!searchData.checkOut) searchData.checkOut = urlParams.get("checkOut") || new Date(Date.now() + 86400000).toISOString().split('T')[0];
      }
    } catch (error) {
      console.error("Error parsing search data:", error);
      alert("Missing booking dates. Please try again.");
      if (paymentButton) {
        paymentButton.disabled = false;
        paymentButton.textContent = "Complete Payment";
      }
      return;
    }
  
    if (!selectedHotel || !selectedRoom || !guestInfo || !paymentMethod) {
      alert("Missing required booking information. Please try again.");
      if (paymentButton) {
        paymentButton.disabled = false;
        paymentButton.textContent = "Complete Payment";
      }
      return;
    }
  
    // Validate payment details based on payment method
    const paymentDetails = validatePaymentDetails(paymentMethod);
    if (!paymentDetails) {
      if (paymentButton) {
        paymentButton.disabled = false;
        paymentButton.textContent = "Complete Payment";
      }
      return; // Validation failed
    }
  
    // Calculate nights and total amount
    const nights = calculateNights(searchData.checkIn, searchData.checkOut);
    const totalAmount = calculateTotalAmount(selectedHotel, nights);
  
    const bookingData = {
      hotel: selectedHotel,
      roomId: selectedRoom,
      guests: guestInfo,
      paymentMethod: paymentMethod,
      paymentDetails: paymentDetails,
      searchData: searchData
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
        // Generate booking confirmation object
        const bookingConfirmation = {
          bookingReference: data.bookingReference,
          bookingDate: new Date().toISOString(),
          hotel: selectedHotel,
          roomType: getRoomTypeById(selectedRoom),
          checkIn: searchData.checkIn,
          checkOut: searchData.checkOut,
          guests: guestInfo,
          nights: nights,
          totalAmount: totalAmount,
          paymentMethod: paymentMethod
        };
        
        // Store booking confirmation in sessionStorage
        sessionStorage.setItem("bookingConfirmation", JSON.stringify(bookingConfirmation));
        
        // Show booking confirmation
        showBookingConfirmation(bookingConfirmation);
      } else {
        // Show error message
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
    // Hide payment section
    const paymentSection = document.getElementById("payment-section");
    if (paymentSection) {
      paymentSection.style.display = "none";
    }
    
    // Create booking confirmation section if it doesn't exist
    if (!document.getElementById("booking-confirmation")) {
      const bookingConfirmationSection = document.createElement("div");
      bookingConfirmationSection.id = "booking-confirmation";
      document.querySelector("main").appendChild(bookingConfirmationSection);
    }
    
    // Get booking confirmation section
    const bookingConfirmationSection = document.getElementById("booking-confirmation");
    
    // Format dates
    let formattedCheckIn = "N/A";
    let formattedCheckOut = "N/A";
    
    try {
      if (bookingData.checkIn) {
        const checkInDate = new Date(bookingData.checkIn);
        formattedCheckIn = checkInDate.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
      }
      
      if (bookingData.checkOut) {
        const checkOutDate = new Date(bookingData.checkOut);
        formattedCheckOut = checkOutDate.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
      }
    } catch (error) {
      console.error("Error formatting dates:", error);
    }
    
    // Populate booking confirmation section
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
          <span class="detail-label">Check-in:</span>
          <span class="detail-value">${formattedCheckIn}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Check-out:</span>
          <span class="detail-value">${formattedCheckOut}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Nights:</span>
          <span class="detail-value">${bookingData.nights}</span>
        </div>
      </div>
      
      <div class="booking-details">
        <h3>Guest Information</h3>
        ${bookingData.guests.map((guest, index) => `
          <div class="detail-row">
            <span class="detail-label">Guest ${index + 1}:</span>
            <span class="detail-value">${guest.firstName} ${guest.lastName}</span>
          </div>
        `).join('')}
      </div>
      
      <div class="booking-details">
        <h3>Payment Details</h3>
        <div class="detail-row">
          <span class="detail-label">Payment Method:</span>
          <span class="detail-value">${formatPaymentMethod(bookingData.paymentMethod)}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Total Amount:</span>
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
    
    // Scroll to booking confirmation
    bookingConfirmationSection.scrollIntoView({ behavior: 'smooth' });
  }