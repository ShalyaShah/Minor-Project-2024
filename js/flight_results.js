// flight_results.js
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
                <video autoplay loopplaysinline id="myVideo">
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

    outboundFlightsDiv.innerHTML = flights.map((flight, index) => `
        <div class="flight-card">
            <div class="flight-header">
                <h3>Flight Option ${index + 1}</h3>
                <span class="price">${flight.price.total} ${flight.price.currency}</span>
            </div>
            ${generateFlightDetails(flight, airlineMap)}
            <button onclick="selectFlight('${flight.id}')" class="select-button">
                Select Flight
            </button>
        </div>
    `).join('');
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

function selectFlight(flightId) {
    alert(`Flight ${flightId} selected! Proceeding to booking...`);
    // Add booking logic here
}