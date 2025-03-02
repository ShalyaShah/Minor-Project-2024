import { accessToken, getAccessToken } from './auth.js';
const cityCodes = {
    "Mumbai": "BOM",
    "New York": "NYC",
    "London": "LON",
    "Paris": "PAR",
    "Tokyo": "TYO",
    "Dubai": "DXB"
};

document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Get search parameters from sessionStorage
        const searchParamsString = sessionStorage.getItem('hotelSearchParams');
        if (!searchParamsString) {
            throw new Error('No search parameters found');
        }

        const searchParams = JSON.parse(searchParamsString);
        const { city, checkIn, checkOut, guests, rooms } = searchParams;

        // Display search parameters
        displaySearchParams(searchParams);

        // Show loading state
        showLoading();

        // Get access token
        const accessToken = await getAccessToken();

        // First get city code
        const cityCode = await getCityCode(city);

        // Search hotels
        const hotelData = await searchHotels(accessToken, cityCode, checkIn, checkOut, guests, rooms);

        // Display results
        displayHotelResults(hotelData.data);

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

async function getCityCode(cityName) {
    // Use the inline city codes directly
    const cityCode = cityCodes[cityName];
    if (!cityCode) {
        throw new Error(`City "${cityName}" not found in the predefined list`);
    }
    return cityCode;
}

async function searchHotels(accessToken, cityCode, checkIn, checkOut, guests, rooms) {
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    };

    if (!cityCode || !checkIn || !checkOut || !guests || !rooms) {
        throw new Error('Missing required parameters');
    }

    const formattedCheckIn = formatDate(checkIn);
    const formattedCheckOut = formatDate(checkOut);

    const hotelIds = await fetchHotelIds(accessToken, cityCode);
    if (!hotelIds || hotelIds.length === 0) {
        throw new Error('No hotels found for the specified city');
    }

    const params = new URLSearchParams({
        cityCode: cityCode,
        checkInDate: formattedCheckIn,
        checkOutDate: formattedCheckOut,
        adults: guests,
        roomQuantity: rooms,
        currency: 'INR',
        hotelIds: hotelIds.join(','), // Include limited hotel IDs
        bestRateOnly: 'true',
        radius: '50'
    });

    try {
        const response = await fetch(`https://test.api.amadeus.com/v3/shopping/hotel-offers?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(
                `API request failed: ${response.status} ${response.statusText}` +
                (errorData.errors ? ` - ${errorData.errors[0]?.detail || ''}` : '')
            );
        }

        const data = await response.json();

        if (!data || !data.data) {
            throw new Error('Invalid response format from API');
        }

        return data;

    } catch (error) {
        console.error('Hotel search error:', error);
        throw new Error(`Failed to fetch hotel offers: ${error.message}`);
    }
}
async function fetchHotelIds(accessToken, cityCode) {
    try {
        const response = await fetch(`https://test.api.amadeus.com/v1/reference-data/locations/hotels/by-city?cityCode=${cityCode}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(
                `Failed to fetch hotel IDs: ${response.status} ${response.statusText}` +
                (errorData.errors ? ` - ${errorData.errors[0]?.detail || ''}` : '')
            );
        }

        const data = await response.json();

        // Extract hotel IDs from the response and limit the number of IDs
        const maxHotelIds = 20; // Adjust this based on the API's limit
        const hotelIds = data.data.map(hotel => hotel.hotelId).slice(0, maxHotelIds);

        return hotelIds;

    } catch (error) {
        console.error('Error fetching hotel IDs:', error);
        throw new Error(`Failed to fetch hotel IDs: ${error.message}`);
    }
}

function displayHotelResults(hotels) {
    const hotelResultsDiv = document.getElementById('hotelResults');
    if (!hotelResultsDiv) return;

    if (!hotels || hotels.length === 0) {
        hotelResultsDiv.innerHTML = '<p>No hotels found</p>';
        return;
    }

    hotelResultsDiv.innerHTML = hotels.map((hotel, index) => {
        const hotelName = hotel.hotel?.name || 'Unknown Hotel';
        const addressLines = hotel.hotel?.address?.lines?.join(', ') || 'Address not available';
        const cityName = hotel.hotel?.address?.cityName || 'City not available';
        const distance = hotel.hotel?.hotelDistance?.distance || 'N/A';
        const distanceUnit = hotel.hotel?.hotelDistance?.distanceUnit || '';
        const amenities = generateAmenities(hotel.hotel?.amenities);
        const offers = generateOffers(hotel.offers);

        return `
            <div class="hotel-card">
                <div class="hotel-header">
                    <h3>${hotelName}</h3>
                    <div class="hotel-rating">
                        ${generateRatingStars(hotel.hotel?.rating)}
                    </div>
                </div>
                <div class="hotel-details">
                    <p><strong>Address:</strong> ${addressLines}, ${cityName}</p>
                    <p><strong>Distance from city center:</strong> ${distance} ${distanceUnit}</p>
                    ${amenities}
                </div>
                <div class="offer-details">
                    ${offers}
                </div>
                <button onclick="selectHotel('${hotel.hotel?.hotelId}')" class="select-button">
                    Select Hotel
                </button>
            </div>
        `;
    }).join('');
}

function generateRatingStars(rating) {
    const stars = parseInt(rating) || 0;
    return '⭐'.repeat(stars);
}

function generateAmenities(amenities) {
    if (!amenities || amenities.length === 0) return '';
    return `
        <div class="amenities">
            <p><strong>Amenities:</strong></p>
            <p>${amenities.slice(0, 5).join(', ')}</p>
        </div>
    `;
}

function generateOffers(offers) {
    if (!offers || offers.length === 0) return '';
    return offers.map(offer => `
        <div class="offer">
            <p><strong>Room Type:</strong> ${offer.room.type}</p>
            <p><strong>Board Type:</strong> ${offer.boardType || 'Not specified'}</p>
            <p class="price"><strong>Price:</strong> ${offer.price.total} ${offer.price.currency}</p>
        </div>
    `).join('');
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

function selectHotel(hotelId) {
    alert(`Hotel ${hotelId} selected! Proceeding to booking...`);
    // Add booking logic here
}