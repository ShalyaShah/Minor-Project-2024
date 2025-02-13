const API_KEY = '4YW2bmhwYKjothBbv8eU7tsUR5XytpXj';
const API_SECRET = 'lyLGo5Qt0LGkGo6Y';
let accessToken = '';

// Function to get access token
async function getAccessToken() {
    const response = await fetch('https://test.api.amadeus.com/v1/security/oauth2/token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `grant_type=client_credentials&client_id=${API_KEY}&client_secret=${API_SECRET}`
    });
    
    const data = await response.json();
    return data.access_token;
}

// Function to search airports
async function searchAirports(keyword) {
    if (!accessToken) {
        accessToken = await getAccessToken();
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

// Setup autocomplete
const inputField = document.getElementById('departure_airport');
const autocompleteList = document.getElementById('autocomplete-list');
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
        displayResults(locations);
    }, 300);
});

function displayResults(locations) {
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

// Close autocomplete list when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== inputField) {
        autocompleteList.innerHTML = '';
    }
});