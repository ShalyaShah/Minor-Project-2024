// hotelSearch.js
import { getAccessToken } from './auth.js';

// Configuration object
const config = {
    RAPIDAPI_KEY: 'dcde4b439emsh36ee5fbd30b29fdp134f82jsn825147056d66', // Verify this key
    RAPIDAPI_HOST: 'booking-com.p.rapidapi.com',
    DEFAULT_CURRENCY: 'INR',
    DEFAULT_LOCALE: 'en-gb'
};

// Event Listeners
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const searchParamsString = sessionStorage.getItem('hotelSearchParams');
        if (!searchParamsString) {
            throw new Error('No search parameters found');
        }

        const searchParams = JSON.parse(searchParamsString);
        const { city, checkIn, checkOut, guests, rooms } = searchParams;

        displaySearchParams(searchParams);
        showLoading();

        // Get destination ID for the city
        const destinationId = await getDestinationId(city);
        
        // Search hotels
        const hotelData = await searchHotels(destinationId, checkIn, checkOut, guests, rooms);

        displayHotelResults(hotelData.result);

    } catch (error) {
        console.error('Error:', error);
        displayError(error);
    }
});

// API Functions
async function getDestinationId(cityName) {
    const options = {
        method: 'GET',
        headers: {
            'X-RapidAPI-Key': config.RAPIDAPI_KEY,
            'X-RapidAPI-Host': config.RAPIDAPI_HOST,
            'Accept': 'application/json' // Added Accept header
        }
    };

    try {
        const url = `https://${config.RAPIDAPI_HOST}/v1/hotels/locations?name=${encodeURIComponent(cityName)}&locale=${config.DEFAULT_LOCALE}`;
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`Failed to get destination ID: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data || data.length === 0) {
            throw new Error(`No destination found for ${cityName}`);
        }

        return data[0].dest_id;

    } catch (error) {
        console.error('Error getting destination ID:', error);
        throw error;
    }
}

async function searchHotels(destId, checkIn, checkOut, guests, rooms) {
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    };

    const options = {
        method: 'GET',
        headers: {
            'X-RapidAPI-Key': config.RAPIDAPI_KEY,
            'X-RapidAPI-Host': config.RAPIDAPI_HOST,
            'Accept': 'application/json' // Added Accept header
        }
    };

    const params = new URLSearchParams({
        dest_id: destId,
        order_by: 'popularity',
        filter_by_currency: config.DEFAULT_CURRENCY,
        adults_number: guests,
        room_number: rooms,
        checkout_date: formatDate(checkOut),
        checkin_date: formatDate(checkIn),
        units: 'metric',
        locale: config.DEFAULT_LOCALE
    });

    try {
        const url = `https://${config.RAPIDAPI_HOST}/v1/hotels/search?${params.toString()}`;
        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`Hotel search failed: ${response.status}`);
        }

        const data = await response.json();

        if (!data || !data.result) {
            throw new Error('Invalid response format from API');
        }

        return data;

    } catch (error) {
        console.error('Hotel search error:', error);
        throw error;
    }
}

// UI Functions
function displaySearchParams(params) {
    const searchParamsDiv = document.getElementById('searchParams');
    if (searchParamsDiv) {
        searchParamsDiv.innerHTML = `
            <div class="search-summary">
                <h3>Hotel Search Details</h3>
                <p><strong>City:</strong> ${params.city}</p>
                <p><strong>Check-in:</strong> ${new Date(params.checkIn).toLocaleDateString()}</p>
                <p><strong>Check-out:</strong> ${new Date(params.checkOut).toLocaleDateString()}</p>
                <p><strong>Guests:</strong> ${params.guests}</p>
                <p><strong>Rooms:</strong> ${params.rooms}</p>
            </div>
        `;
    }
}

function showLoading() {
    const hotelResultsDiv = document.getElementById('hotelResults');
    if (hotelResultsDiv) {
        hotelResultsDiv.innerHTML = `
            <div class="loading">
                <p>Searching for hotels...</p>
                <div class="spinner"></div>
            </div>
        `;
    }
}

function displayHotelResults(hotels) {
    const hotelResultsDiv = document.getElementById('hotelResults');
    if (!hotelResultsDiv) return;

    if (!hotels || hotels.length === 0) {
        hotelResultsDiv.innerHTML = '<p>No hotels found</p>';
        return;
    }

    hotelResultsDiv.innerHTML = hotels.map(hotel => {
        const hotelName = hotel.hotel_name || 'Unknown Hotel';
        const address = hotel.address || 'Address not available';
        const cityName = hotel.city || 'City not available';
        const distance = hotel.distance_to_cc || 'N/A';
        const rating = hotel.review_score || 'N/A';
        const price = hotel.price_breakdown?.gross_price || 'Price not available';
        const reviewCount = hotel.review_count || 0;
        const reviewScore = hotel.review_score_word || 'No reviews';

        return `
            <div class="hotel-card">
                <div class="hotel-header">
                    <h3>${hotelName}</h3>
                    <div class="hotel-rating">
                        ${rating !== 'N/A' ? `
                            <span>${rating}/10</span>
                            <div>${reviewScore}</div>
                            <div>${reviewCount} reviews</div>
                        ` : 'No rating'}
                    </div>
                </div>
                <div class="hotel-details">
                    <p><strong>Address:</strong> ${address}, ${cityName}</p>
                    <p><strong>Distance from city center:</strong> ${distance} km</p>
                    ${hotel.facilities ? `
                        <div class="amenities">
                            <p><strong>Amenities:</strong></p>
                            <p>${hotel.facilities.slice(0, 5).join(', ')}</p>
                        </div>
                    ` : ''}
                </div>
                <div class="offer-details">
                    <div class="offer">
                        <p><strong>Room Type:</strong> ${hotel.room_type || 'Standard Room'}</p>
                        <p class="price"><strong>Price:</strong> ${config.DEFAULT_CURRENCY} ${price}</p>
                    </div>
                </div>
                <button onclick="selectHotel('${hotel.hotel_id}')" class="select-button">
                    Select Hotel
                </button>
            </div>
        `;
    }).join('');
}

function displayError(error) {
    const hotelResultsDiv = document.getElementById('hotelResults');
    if (hotelResultsDiv) {
        hotelResultsDiv.innerHTML = `
            <div class="error-message">
                <h3>Error</h3>
                <p>${error.message}</p>
            </div>
        `;
    }
}

// Make selectHotel available globally
window.selectHotel = function(hotelId) {
    alert(`Hotel ${hotelId} selected! Proceeding to booking...`);
    // Add booking logic here
};
