import { accessToken, getAccessToken } from './auth.js';

document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Get search parameters from sessionStorage
        const searchParamsString = sessionStorage.getItem('flightSearchParams');
        if (!searchParamsString) {
            throw new Error('No search parameters found');
        }

        const searchParams = JSON.parse(searchParamsString);
        const { originCode, destinationCode, departureDate, adults, origin, destination } = searchParams;

        // Display search parameters
        displaySearchParams(searchParams);

        // Show loading state
        showLoading();

        // Get access token
        const accessToken = await getAccessToken();

        // Search flights
        const flightData = await searchFlights(accessToken, originCode, destinationCode, departureDate, adults);

        // Display results
        displayFlightResults(flightData.data);

    } catch (error) {
        console.error('Error:', error);
        displayError(error);
    }
});

function displaySearchParams(params) {
    const searchParamsDiv = document.getElementById('searchParams');
    if (searchParamsDiv) {
        searchParamsDiv.innerHTML = `
            <div class="search-summary">
                <h3>Flight Search Details</h3>
                <p><strong>From:</strong> ${params.origin}</p>
                <p><strong>To:</strong> ${params.destination}</p>
                <p><strong>Date:</strong> ${new Date(params.departureDate).toLocaleDateString()}</p>
                <p><strong>Passengers:</strong> ${params.adults}</p>
            </div>
        `;
    }
}

function showLoading() {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (outboundFlightsDiv) {
        outboundFlightsDiv.innerHTML = `
            <div class="loading">
                <p>Searching for flights...</p>
                <div class="spinner"></div>
                <video autoplay loop playsinline id="myVideo">
                    <source src="/images/plane.mp4" type="video/mp4">
                </video>
            </div>
        `;
    }
}

async function searchFlights(accessToken, originCode, destinationCode, departureDate, adults) {
    const response = await fetch(`https://test.api.amadeus.com/v2/shopping/flight-offers?originLocationCode=${originCode}&destinationLocationCode=${destinationCode}&departureDate=${departureDate}&adults=${adults}&currencyCode=INR`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${accessToken}`
        }
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
}

async function airline_code_lookup(accessToken, airlineCodes) {
    const response = await fetch(`https://test.api.amadeus.com/v1/reference-data/airlines?airlineCodes=${airlineCodes.join(',')}`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${accessToken}`
        }
    });
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    const airlineMap = {};
    data.data.forEach(airline => {
        airlineMap[airline.iataCode] = airline.businessName;
    });
    return airlineMap;
}

function generateFlightDetails(flight, airlineMap) {
    const itinerary = flight.itineraries[0];
    let totalDuration = calculateTotalDuration(itinerary.segments);
    const airlineNames = flight.validatingAirlineCodes.map(code => airlineMap[code]).join(', ');
    return `
        <div class="flight-details">
            <p><strong>Airline:</strong> ${airlineNames}</p>
            <p><strong>Total Duration:</strong> ${totalDuration}</p>
            ${generateSegments(itinerary.segments)}
        </div>
    `;
}

async function displayFlightResults(flights) {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (!outboundFlightsDiv) return;

    if (!flights || flights.length === 0) {
        outboundFlightsDiv.innerHTML = '<p>No flights found</p>';
        return;
    }

    const accessToken = await getAccessToken();
    const airlineCodes = flights.map(flight => flight.validatingAirlineCodes).flat();
    const airlineMap = await airline_code_lookup(accessToken, airlineCodes);

    // Store flights in sessionStorage for later use
    sessionStorage.setItem('flightResults', JSON.stringify(flights));

    outboundFlightsDiv.innerHTML = flights.map((flight, index) => `
        <div class="flight-card">
            <div class="flight-header">
                <h3>Flight Option ${index + 1}</h3>
                <span class="price">${flight.price.total} ${flight.price.currency}</span>
            </div>
            ${generateFlightDetails(flight, airlineMap)}
            <button onclick="selectFlight(${index})" class="select-button">
                Select Flight
            </button>
        </div>
    `).join('');

    // Add the selectFlight function to the window object
    window.selectFlight = selectFlight;
}

function calculateTotalDuration(segments) {
    let totalMinutes = 0;
    
    segments.forEach(segment => {
        const departure = new Date(segment.departure.at);
        const arrival = new Date(segment.arrival.at);
        const durationInMinutes = (arrival - departure) / (1000 * 60);
        totalMinutes += durationInMinutes;
    });

    const hours = Math.floor(totalMinutes / 60);
    const minutes = Math.round(totalMinutes % 60);

    let durationStr = '';
    if (hours > 0) durationStr += `${hours}h `;
    if (minutes > 0) durationStr += `${minutes}m`;

    return durationStr.trim() || '0m';
}

function generateSegments(segments) {
    return segments.map(segment => {
        const duration = calculateSegmentDuration(segment);
        return `
            <div class="segment">
                <div class="segment-details">
                    <div class="departure">
                        <strong>Departure Airport: ${segment.departure.iataCode}</strong>
                        <p>${formatDateTime(segment.departure.at)}</p>
                    </div>
                    <div class="flight-info">
                        <i class="fas fa-plane"></i>
                        <p>Flight ${segment.carrierCode}${segment.number}</p>
                        <p>Duration: ${duration}</p>
                    </div>
                    <div class="arrival">
                        <strong>Arrival Airport: ${segment.arrival.iataCode}</strong>
                        <p>${formatDateTime(segment.arrival.at)}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function calculateSegmentDuration(segment) {
    const departure = new Date(segment.departure.at);
    const arrival = new Date(segment.arrival.at);
    const durationInMinutes = (arrival - departure) / (1000 * 60);

    const hours = Math.floor(durationInMinutes / 60);
    const minutes = Math.round(durationInMinutes % 60);

    let durationStr = '';
    if (hours > 0) durationStr += `${hours}h `;
    if (minutes > 0) durationStr += `${minutes}m`;

    return durationStr.trim() || '0m';
}

function formatDateTime(dateTimeString) {
    return new Date(dateTimeString).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function displayError(error) {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (outboundFlightsDiv) {
        outboundFlightsDiv.innerHTML = `
            <div class="error-message">
                <h3>Error</h3>
                <p>${error.message}</p>
            </div>
        `;
    }
}

// New booking functions
function selectFlight(flightIndex) {
    // Get the selected flight from sessionStorage
    const flights = JSON.parse(sessionStorage.getItem('flightResults'));
    const selectedFlight = flights[flightIndex];
    
    // Store the selected flight in sessionStorage
    sessionStorage.setItem('selectedFlight', JSON.stringify(selectedFlight));
    
    // Get search parameters
    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    
    // Show passenger information form
    showPassengerForm(searchParams.adults);
}

function showPassengerForm(numPassengers) {
    const mainContent = document.getElementById('outboundFlights');
    
    let passengerFormHTML = `
        <div class="booking-section">
            <h3>Passenger Information</h3>
            <form id="passengerForm">
    `;
    
    for (let i = 0; i < numPassengers; i++) {
        passengerFormHTML += `
            <div class="passenger-card">
                <h4>Passenger ${i + 1}</h4>
                <div class="form-group">
                    <label for="title${i}">Title</label>
                    <select id="title${i}" name="title${i}" required>
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="firstName${i}">First Name</label>
                    <input type="text" id="firstName${i}" name="firstName${i}" required>
                </div>
                <div class="form-group">
                    <label for="lastName${i}">Last Name</label>
                    <input type="text" id="lastName${i}" name="lastName${i}" required>
                </div>
                <div class="form-group">
                    <label for="dob${i}">Date of Birth</label>
                    <input type="date" id="dob${i}" name="dob${i}" required>
                </div>
                <div class="form-group">
                    <label for="passport${i}">Passport Number</label>
                    <input type="text" id="passport${i}" name="passport${i}" required>
                </div>
                <div class="form-group">
                    <label for="nationality${i}">Nationality</label>
                    <input type="text" id="nationality${i}" name="nationality${i}" required>
                </div>
            </div>
        `;
    }
    
    passengerFormHTML += `
            <div class="form-group">
                <label for="email">Contact Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Contact Phone</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <button type="button" onclick="submitPassengerInfo()" class="submit-button">Continue to Payment</button>
        </form>
        </div>
    `;
    
    mainContent.innerHTML = passengerFormHTML;
    
    // Add the submitPassengerInfo function to the window object
    window.submitPassengerInfo = submitPassengerInfo;
}

function submitPassengerInfo() {
    const form = document.getElementById('passengerForm');
    
    // Basic form validation
    if (!form.checkValidity()) {
        alert('Please fill in all required fields correctly.');
        return;
    }
    
    // Collect passenger data
    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    const numPassengers = searchParams.adults;
    const passengers = [];
    
    for (let i = 0; i < numPassengers; i++) {
        passengers.push({
            title: document.getElementById(`title${i}`).value,
            firstName: document.getElementById(`firstName${i}`).value,
            lastName: document.getElementById(`lastName${i}`).value,
            dob: document.getElementById(`dob${i}`).value,
            passport: document.getElementById(`passport${i}`).value,
            nationality: document.getElementById(`nationality${i}`).value
        });
    }
    
    const contactInfo = {
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value
    };
    
    // Store passenger information in sessionStorage
    sessionStorage.setItem('passengerInfo', JSON.stringify({
        passengers: passengers,
        contactInfo: contactInfo
    }));
    
    // Show payment form
    showPaymentForm();
}

function showPaymentForm() {
    const mainContent = document.getElementById('outboundFlights');
    const selectedFlight = JSON.parse(sessionStorage.getItem('selectedFlight'));
    
    const paymentFormHTML = `
        <div class="booking-section">
            <h3>Payment Information</h3>
            <div class="payment-summary">
                <h4>Booking Summary</h4>
                <p><strong>Total Amount:</strong> ${selectedFlight.price.total} ${selectedFlight.price.currency}</p>
            </div>
            <form id="paymentForm">
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
                <div class="form-group">
                    <label for="billingAddress">Billing Address</label>
                    <textarea id="billingAddress" name="billingAddress" required></textarea>
                </div>
                <button type="button" onclick="processPayment()" class="submit-button">Complete Booking</button>
            </form>
        </div>
    `;
    
    mainContent.innerHTML = paymentFormHTML;
    
    // Add the processPayment function to the window object
    window.processPayment = processPayment;
}

function processPayment() {
    const form = document.getElementById('paymentForm');
    
    // Basic form validation
    if (!form.checkValidity()) {
        alert('Please fill in all required fields correctly.');
        return;
    }
    
    // Show loading state
    const mainContent = document.getElementById('outboundFlights');
    mainContent.innerHTML = `
        <div class="loading">
            <p>Processing your payment...</p>
            <div class="spinner"></div>
        </div>
    `;
    
    // Get all booking data
    const selectedFlight = JSON.parse(sessionStorage.getItem('selectedFlight'));
    const passengerInfo = JSON.parse(sessionStorage.getItem('passengerInfo'));
    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    
    // Create booking data object
    const bookingData = {
        flight: selectedFlight,
        passengers: passengerInfo.passengers,
        contactInfo: passengerInfo.contactInfo,
        searchParams: searchParams,
        paymentInfo: {
            cardName: document.getElementById('cardName').value,
            // Only store last 4 digits of card number for security
            cardNumberLast4: document.getElementById('cardNumber').value.slice(-4),
            billingAddress: document.getElementById('billingAddress').value
        }
    };
    
    // Send booking data to server
    saveBooking(bookingData);
}

async function saveBooking(bookingData) {
    try {
        const response = await fetch('./save_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to save booking');
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Show booking confirmation
            showBookingConfirmation(result.bookingId);
        } else {
            throw new Error(result.message || 'Failed to save booking');
        }
    } catch (error) {
        console.error('Error saving booking:', error);
        const mainContent = document.getElementById('outboundFlights');
        mainContent.innerHTML = `
            <div class="error-message">
                <h3>Payment Error</h3>
                <p>${error.message}</p>
                <button onclick="showPaymentForm()" class="submit-button">Try Again</button>
            </div>
        `;
    }
}

function showBookingConfirmation(bookingId) {
    const mainContent = document.getElementById('outboundFlights');
    const selectedFlight = JSON.parse(sessionStorage.getItem('selectedFlight'));
    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    
    mainContent.innerHTML = `
        <div class="booking-confirmation">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Booking Confirmed!</h3>
            <p>Your booking has been successfully completed.</p>
            <div class="booking-details">
                <p><strong>Booking Reference:</strong> ${bookingId}</p>
                <p><strong>From:</strong> ${searchParams.origin} (${searchParams.originCode})</p>
                <p><strong>To:</strong> ${searchParams.destination} (${searchParams.destinationCode})</p>
                <p><strong>Date:</strong> ${new Date(searchParams.departureDate).toLocaleDateString()}</p>
                <p><strong>Total Amount Paid:</strong> ${selectedFlight.price.total} ${selectedFlight.price.currency}</p>
            </div>
            <p>A confirmation email has been sent to your email address.</p>
            <button onclick="window.location.href='index.html'" class="submit-button">Return to Home</button>
        </div>
    `;
    
    // Clear booking data from sessionStorage
    sessionStorage.removeItem('selectedFlight');
    sessionStorage.removeItem('passengerInfo');
    // Keep flightSearchParams for reference
}