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
            </div>
        `;
    }
}

async function searchFlights(accessToken, originCode, destinationCode, departureDate, adults) {
    const response = await fetch(`https://test.api.amadeus.com/v2/shopping/flight-offers?originLocationCode=${originCode}&destinationLocationCode=${destinationCode}&departureDate=${departureDate}&adults=${adults}`, {
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

function displayFlightResults(flights) {
    const outboundFlightsDiv = document.getElementById('outboundFlights');
    if (!outboundFlightsDiv) return;

    if (!flights || flights.length === 0) {
        outboundFlightsDiv.innerHTML = '<p>No flights found</p>';
        return;
    }

    outboundFlightsDiv.innerHTML = flights.map((flight, index) => `
        <div class="flight-card">
            <div class="flight-header">
                <h3>Flight Option ${index + 1}</h3>
                <span class="price">${flight.price.total} ${flight.price.currency}</span>
            </div>
            ${generateFlightDetails(flight)}
            <button onclick="selectFlight('${flight.id}')" class="select-button">
                Select Flight
            </button>
        </div>
    `).join('');
}

function generateFlightDetails(flight) {
    const itinerary = flight.itineraries[0];
    return `
        <div class="flight-details">
            <p><strong>Airline:</strong> ${flight.validatingAirlineCodes.join(', ')}</p>
            <p><strong>Duration:</strong> ${itinerary.duration}</p>
            ${generateSegments(itinerary.segments)}
        </div>
    `;
}

function generateSegments(segments) {
    return segments.map(segment => `
        <div class="segment">
            <div class="segment-details">
                <div class="departure">
                    <strong>Departure Airport: ${segment.departure.iataCode}</strong>
                    <p>${formatDateTime(segment.departure.at)}</p>
                </div>
                <div class="flight-info">
                    <i class="fas fa-plane"></i>
                    <p>Flight ${segment.carrierCode}${segment.number}</p>
                </div>
                <div class="arrival">
                    <strong>Arrival Airport: ${segment.arrival.iataCode}</strong>
                    <p>${formatDateTime(segment.arrival.at)}</p>
                </div>
            </div>
        </div>
    `).join('');
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