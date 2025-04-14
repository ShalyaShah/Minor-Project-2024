// Import authentication module
import { getAccessToken } from './auth.js';

// Main event listener when DOM is loaded
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
        const token = await getAccessToken();

        // Search flights
        const flightData = await searchFlights(token, originCode, destinationCode, departureDate, adults);

        // Store original flight data for filtering
        window.originalFlightData = flightData.data;

        // Display results
        displayFlightResults(flightData.data);

    } catch (error) {
        console.error('Error:', error);
        displayError(error);
    }
});

// Function to display search parameters
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

// Function to show loading state
function showLoading() {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (outboundFlightsDiv) {
        outboundFlightsDiv.innerHTML = `
        <div class="loading">
            <p>Searching for flights...</p>
            <div class="spinner"></div>
        </div>
        `;
    }
}

// Function to search flights
async function searchFlights(accessToken, originCode, destinationCode, departureDate, adults) {
    try {
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
    } catch (error) {
        console.error('Error searching flights:', error);
        throw error;
    }
}

// Function to look up airline codes
async function airline_code_lookup(accessToken, airlineCodes) {
    try {
        if (!airlineCodes || airlineCodes.length === 0) {
            return {};
        }
        
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
    } catch (error) {
        console.error('Error looking up airline codes:', error);
        return {};
    }
}

// Function to display flight results
async function displayFlightResults(flights) {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (!outboundFlightsDiv) return;

    if (!flights || flights.length === 0) {
        outboundFlightsDiv.innerHTML = '<p>No flights found</p>';
        return;
    }

    try {
        const token = await getAccessToken();
        const airlineCodes = flights.map(flight => flight.validatingAirlineCodes).flat();
        const airlineMap = await airline_code_lookup(token, airlineCodes);

        // Store flights in sessionStorage for later use
        sessionStorage.setItem('flightResults', JSON.stringify(flights));
        
        // Store airline map in window object for filtering
        window.airlineMap = airlineMap;

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
    } catch (error) {
        console.error('Error displaying flight results:', error);
        outboundFlightsDiv.innerHTML = '<p>Error loading flight details. Please try again.</p>';
    }
}

// Function to generate flight details
function generateFlightDetails(flight, airlineMap) {
    const itinerary = flight.itineraries[0];
    let totalDuration = calculateTotalDuration(itinerary.segments);
    const airlineNames = flight.validatingAirlineCodes.map(code => airlineMap[code] || code).join(', ');
    return `
    <div class="flight-details">
        <p><strong>Airline:</strong> ${airlineNames}</p>
        <p><strong>Total Duration:</strong> ${totalDuration}</p>
        ${generateSegments(itinerary.segments)}
    </div>
    `;
}

// Function to calculate total duration
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

// Function to get duration in minutes for sorting
function getDurationInMinutes(flight) {
    const segments = flight.itineraries[0].segments;
    let totalMinutes = 0;
    
    segments.forEach(segment => {
        const departure = new Date(segment.departure.at);
        const arrival = new Date(segment.arrival.at);
        const durationInMinutes = (arrival - departure) / (1000 * 60);
        totalMinutes += durationInMinutes;
    });

    return totalMinutes;
}

// Function to generate segments
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

// Function to calculate segment duration
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

// Function to format date and time
function formatDateTime(dateTimeString) {
    return new Date(dateTimeString).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Function to display error
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

// Function to apply sort and filter
function applySortAndFilter() {
    // Get filter values
    const sortBy = document.getElementById('sortBy').value;
    const maxPrice = document.getElementById('maxPrice').value;
    const airlineFilter = document.getElementById('airlineFilter').value.toLowerCase();
    
    // Get original flight data
    let flights = JSON.parse(JSON.stringify(window.originalFlightData || []));
    
    // Apply filters
    if (maxPrice && !isNaN(maxPrice) && maxPrice > 0) {
        flights = flights.filter(flight => parseFloat(flight.price.total) <= parseFloat(maxPrice));
    }
    
    if (airlineFilter) {
        flights = flights.filter(flight => {
            const airlineNames = flight.validatingAirlineCodes.map(code => {
                const name = window.airlineMap[code] || code;
                return name.toLowerCase();
            }).join(' ');
            return airlineNames.includes(airlineFilter);
        });
    }
    
    // Apply sorting
    switch (sortBy) {
        case 'priceLowToHigh':
            flights.sort((a, b) => parseFloat(a.price.total) - parseFloat(b.price.total));
            break;
        case 'priceHighToLow':
            flights.sort((a, b) => parseFloat(b.price.total) - parseFloat(a.price.total));
            break;
        case 'durationShortToLong':
            flights.sort((a, b) => getDurationInMinutes(a) - getDurationInMinutes(b));
            break;
        case 'durationLongToShort':
            flights.sort((a, b) => getDurationInMinutes(b) - getDurationInMinutes(a));
            break;
    }
    
    // Update the display
    displayFlightResults(flights);
}

// Function to select flight
function selectFlight(flightIndex) {
    const flights = JSON.parse(sessionStorage.getItem('flightResults'));
    const selectedFlight = flights[flightIndex];
    sessionStorage.setItem('selectedFlight', JSON.stringify(selectedFlight));
    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    showPassengerForm(searchParams.adults);
}

// Function to show passenger form
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
}
function isValidName(name) {
    return /^[A-Za-z]{2,30}$/.test(name);
}

function isValidPassport(passport) {
    return /^[A-Za-z0-9]{6,9}$/.test(passport);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[0-9]{10}$/.test(phone);
}

// Function to submit passenger information
function submitPassengerInfo() {
    const form = document.getElementById('passengerForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    let errorMessage = '';

    inputs.forEach(input => {
        input.classList.remove('error');
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        }
    });

    const searchParams = JSON.parse(sessionStorage.getItem('flightSearchParams'));
    const numPassengers = searchParams.adults;

    for (let i = 0; i < numPassengers; i++) {
        const firstName = document.getElementById(`firstName${i}`).value.trim();
        const lastName = document.getElementById(`lastName${i}`).value.trim();
        const passport = document.getElementById(`passport${i}`).value.trim();

        if (!isValidName(firstName)) {
            isValid = false;
            errorMessage = `Invalid first name for passenger ${i + 1}. Only letters (2-30 characters) allowed.`;
            break;
        }

        if (!isValidName(lastName)) {
            isValid = false;
            errorMessage = `Invalid last name for passenger ${i + 1}. Only letters (2-30 characters) allowed.`;
            break;
        }

        if (!isValidPassport(passport)) {
            isValid = false;
            errorMessage = `Invalid passport number for passenger ${i + 1}. Must be 6–9 alphanumeric characters.`;
            break;
        }
    }

    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();

    if (!isValidEmail(email)) {
        isValid = false;
        errorMessage = 'Invalid email format.';
    }

    if (!isValidPhone(phone)) {
        isValid = false;
        errorMessage = 'Phone number must be 7–15 digits.';
    }

    if (!isValid) {
        alert(errorMessage || 'Please fill in all required fields correctly.');
        return;
    }

    const passengers = [];

    for (let i = 0; i < numPassengers; i++) {
        passengers.push({
            title: document.getElementById(`title${i}`).value,
            firstName: document.getElementById(`firstName${i}`).value.trim(),
            lastName: document.getElementById(`lastName${i}`).value.trim(),
            dob: document.getElementById(`dob${i}`).value,
            passport: document.getElementById(`passport${i}`).value.trim(),
            nationality: document.getElementById(`nationality${i}`).value
        });
    }

    const contactInfo = {
        email: email,
        phone: phone
    };

    sessionStorage.setItem('passengerInfo', JSON.stringify({
        passengers: passengers,
        contactInfo: contactInfo
    }));

    showPaymentForm();
}

// Function to show payment form
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
                <label for="paymentMethod">Payment Method</label>
                <select id="paymentMethod" name="paymentMethod" required>
                    <option value="">Select a payment method</option>
                    <option value="credit-card">Credit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank-transfer">Bank Transfer</option>
                    <option value="upi">UPI</option>
                    <option value="wallet">Wallet</option>
                </select>
            </div>
            <div id="paymentDetails"></div>
            <button type="button" onclick="processPayment()" class="submit-button">Complete Booking</button>
        </form>
    </div>
    `;

    mainContent.innerHTML = paymentFormHTML;

    // Add event listener to dynamically update payment details form
    const paymentMethodSelect = document.getElementById('paymentMethod');
    paymentMethodSelect.addEventListener('change', updatePaymentDetailsForm);
}

// Function to dynamically update payment details form based on selected method
function updatePaymentDetailsForm() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const paymentDetailsDiv = document.getElementById('paymentDetails');

    let paymentDetailsHTML = '';
    switch (paymentMethod) {
        case 'credit-card':
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
        case 'paypal':
            paymentDetailsHTML = `
            <div class="form-group">
                <label for="paypalEmail">PayPal Email</label>
                <input type="email" id="paypalEmail" name="paypalEmail" required>
            </div>
            `;
            break;
        case 'bank-transfer':
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
        case 'upi':
            paymentDetailsHTML = `
            <div class="form-group">
                <label for="upiId">UPI ID</label>
                <input type="text" id="upiId" name="upiId" required>
            </div>
            `;
            break;
        case 'wallet':
            paymentDetailsHTML = `
            <div class="form-group">
                <p id="walletBalance">Fetching wallet balance...</p>
            </div>
            `;
            fetchWalletBalance();
            break;
        default:
            paymentDetailsHTML = '';
    }

    paymentDetailsDiv.innerHTML = paymentDetailsHTML;
}
async function fetchWalletBalance() {
    try {
        const response = await fetch('./recharge_wallet.php?action=get_balance');
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

// Function to process payment
async function processPayment() {
    const mainContent = document.getElementById('outboundFlights');
    if (!mainContent) {
        console.error('Main content container not found');
        return;
    }

    try {
        // Validate payment form
        const paymentForm = document.getElementById('paymentForm');
        if (!paymentForm) {
            throw new Error('Payment form not found');
        }

        const paymentMethod = document.getElementById('paymentMethod').value;
        if (!paymentMethod) {
            throw new Error('Please select a payment method.');
        }

        let isValid = false;
        let errorMessage = '';

        switch (paymentMethod) {
            case 'credit-card':
                isValid = validateCreditCard();
                errorMessage = isValid ? '' : 'Please fill in all credit card fields correctly.';
                break;
            case 'paypal':
                isValid = validatePayPal();
                errorMessage = isValid ? '' : 'Please enter a valid PayPal email.';
                break;
            case 'bank-transfer':
                isValid = validateBankTransfer();
                errorMessage = isValid ? '' : 'Please fill in all bank transfer fields correctly.';
                break;
            case 'upi':
                isValid = validateUPI();
                errorMessage = isValid ? '' : 'Please enter a valid UPI ID.';
                break;
            case 'wallet':
                isValid = await validateWallet();
                errorMessage = isValid ? '' : 'Insufficient wallet balance.';
                break;
            default:
                errorMessage = 'Please select a payment method.';
                isValid = false;
        }

        if (!isValid) {
            throw new Error(errorMessage);
        }

        // Get all necessary data
        const selectedFlight = sessionStorage.getItem('selectedFlight');
        const passengerInfo = sessionStorage.getItem('passengerInfo');
        const searchParams = sessionStorage.getItem('flightSearchParams');

        if (!selectedFlight || !passengerInfo || !searchParams) {
            throw new Error('Missing booking information');
        }

        // Parse the stored data
        const flightData = JSON.parse(selectedFlight);
        const passengerData = JSON.parse(passengerInfo);
        const searchParamsData = JSON.parse(searchParams);

        // Prepare booking data
        const bookingData = {
            flight: flightData,
            passengers: passengerData.passengers,
            contactInfo: passengerData.contactInfo,
            searchParams: searchParamsData,
            paymentMethod: paymentMethod
        };

        // Show loading state
        mainContent.innerHTML = `
            <div class="loading-payment">
                <p>Processing your payment...</p>
                <div class="spinner"></div>
            </div>
        `;

        const response = await fetch('./save_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        // Log the raw response for debugging
        const responseText = await response.text();
        console.log('Server Response:', responseText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = JSON.parse(responseText);

        if (result.success) {
            mainContent.innerHTML = `
                <div class="booking-confirmation">
                    <h2>Booking Confirmed!</h2>
                    <div class="confirmation-details">
                        <p><strong>Booking Reference:</strong> ${result.bookingId}</p>
                        <p><strong>Total Amount:</strong> ${flightData.price.total} ${flightData.price.currency}</p>
                        <p><strong>Thank you for your booking.</strong></p>
                        <p>A confirmation email has been sent to ${passengerData.contactInfo.email}</p>
                    </div>
                    <button onclick="window.location.href='index.php'" class="submit-button">
                        Return to Home
                    </button>
                </div>
            `;

            // Clear session storage
            sessionStorage.removeItem('selectedFlight');
            sessionStorage.removeItem('passengerInfo');
            sessionStorage.removeItem('flightSearchParams');
        } else {
            throw new Error(result.message || 'Booking failed');
        }

    } catch (error) {
        console.error('Payment processing error:', error);
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="error-message">
                    <h3>Error</h3>
                    <p>${error.message || 'An error occurred during payment processing.'}</p>
                    <button onclick="window.location.reload()" class="submit-button">
                        Try Again
                    </button>
                </div>
            `;
        }
    }
}

// Validation functions for each payment method
function validateCreditCard() {
    const cardName = document.getElementById('cardName').value.trim();
    const cardNumber = document.getElementById('cardNumber').value.trim();
    const expiryDate = document.getElementById('expiryDate').value.trim();
    const cvv = document.getElementById('cvv').value.trim();
    return cardName && cardNumber && expiryDate && cvv;
}

function validatePayPal() {
    const paypalEmail = document.getElementById('paypalEmail').value.trim();
    return paypalEmail && /\S+@\S+\.\S+/.test(paypalEmail);
}

function validateBankTransfer() {
    const accountNumber = document.getElementById('accountNumber').value.trim();
    const ifscCode = document.getElementById('ifscCode').value.trim();
    return accountNumber && ifscCode;
}

function validateUPI() {
    const upiId = document.getElementById('upiId').value.trim();
    return upiId && /^[\w.-]+@[\w.-]+$/.test(upiId);
}
async function validateWallet() {
    try {
        const response = await fetch('./recharge_wallet.php?action=get_balance');
        const result = await response.json();

        if (result.status === 'success') {
            const walletBalance = parseFloat(result.balance); // Ensure it's a number
            const selectedFlight = JSON.parse(sessionStorage.getItem('selectedFlight'));
            const totalAmount = parseFloat(selectedFlight.price.total); // Ensure it's a number

            if (walletBalance >= totalAmount) {
                // Deduct wallet balance
                const deductResponse = await fetch('./recharge_wallet.php?action=deduct_balance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ amount: totalAmount }),
                });

                const deductResult = await deductResponse.json();

                if (deductResult.status === 'success') {
                    return true;
                } else {
                    throw new Error(deductResult.message || 'Failed to deduct wallet balance');
                }
            } else {
                console.error('Insufficient wallet balance');
                return false;
            }
        } else {
            throw new Error(result.message || 'Failed to fetch wallet balance');
        }
    } catch (error) {
        console.error('Error validating wallet:', error);
        return false;
    }
}

// Attach functions to window object for global access
window.selectFlight = selectFlight;
window.submitPassengerInfo = submitPassengerInfo;
window.processPayment = processPayment;
window.applySortAndFilter = applySortAndFilter;