import { accessToken, getAccessToken } from './auth.js';
// Function to search airports
async function searchAirports(keyword) {
    let token = accessToken; // Use a local variable to store the token
    if (!token) {
        token = await getAccessToken();
    }

    try {
        const response = await fetch(
            `https://test.api.amadeus.com/v1/reference-data/locations?keyword=${keyword}&subType=AIRPORT,CITY`, {
            headers: {
                'Authorization': `Bearer ${accessToken}`
            }
        });

        const data = await response.json();
        return data.data || [];
    } catch (error) {
        console.error('Error fetching airports:', error);
        return [];
    }
}

// Setup autocomplete for both departure and arrival fields
function setupAutocomplete(inputId) {
    const inputField = document.getElementById(inputId);
    const autocompleteList = document.createElement('div');
    autocompleteList.className = 'autocomplete-list';
    inputField.parentNode.insertBefore(autocompleteList, inputField.nextSibling);
    
    let debounceTimer;

    inputField.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const keyword = e.target.value;
        
        if (keyword.length < 2) {
            autocompleteList.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            const locations = await searchAirports(keyword);
            displayResults(locations, inputField, autocompleteList);
        }, 300);
    });

    // Close autocomplete list when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== inputField) {
            autocompleteList.innerHTML = '';
        }
    });
}

function displayResults(locations, inputField, autocompleteList) {
    autocompleteList.innerHTML = '';
    
    locations.forEach(location => {
        const div = document.createElement('div');
        div.innerHTML = `${location.name} (${location.iataCode}) - ${location.address.cityName}, ${location.address.countryName}`;
        
        div.addEventListener('click', function() {
            inputField.value = `${location.name} (${location.iataCode})`;
            autocompleteList.innerHTML = '';
        });

        autocompleteList.appendChild(div);
    });
}

// Initialize autocomplete for both fields
document.addEventListener('DOMContentLoaded', function() {
    setupAutocomplete('departure_airport');
    setupAutocomplete('arrival_airport');
});