// hotel_offers.js
document.getElementById('hotel_search_button').addEventListener('click', async function(event) {
    event.preventDefault();
    
    try {
        // Get DOM elements
        const cityInput = document.getElementById('city');
        const checkInInput = document.getElementById('check_in_date');
        const checkOutInput = document.getElementById('check_out_date');
        const guestsInput = document.getElementById('guests');
        const roomsInput = document.getElementById('rooms');

        // Validate inputs exist
        if (!cityInput || !checkInInput || !checkOutInput || !guestsInput || !roomsInput) {
            throw new Error('Required input fields not found');
        }

        // Get values
        const city = cityInput.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;
        const guests = guestsInput.value;
        const rooms = roomsInput.value;

        // Validate input values
        if (!city) {
            throw new Error('Please enter a city');
        }

        // Validate dates
        if (!checkIn || !checkOut) {
            throw new Error('Please select both check-in and check-out dates');
        }

        const checkInDate = new Date(checkIn);
        const checkOutDate = new Date(checkOut);

        if (checkInDate >= checkOutDate) {
            throw new Error('Check-out date must be after check-in date');
        }

        // Store search parameters in sessionStorage
        const searchParams = {
            city,
            checkIn,
            checkOut,
            guests,
            rooms
        };
        
        sessionStorage.setItem('hotelSearchParams', JSON.stringify(searchParams));

        // Redirect to hotel results page
        window.location.href = 'hotel_results.html';

    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});