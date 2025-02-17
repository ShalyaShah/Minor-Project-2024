// auth.js
const API_KEY = '4YW2bmhwYKjothBbv8eU7tsUR5XytpXj';
const API_SECRET = 'lyLGo5Qt0LGkGo6Y';
let accessToken = '';

async function getAccessToken() {
    try {
        const response = await fetch('https://test.api.amadeus.com/v1/security/oauth2/token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `grant_type=client_credentials&client_id=${API_KEY}&client_secret=${API_SECRET}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        accessToken = data.access_token;
        return data.access_token;
    } catch (error) {
        console.error('Error getting access token:', error);
        throw error;
    }
}

// Export the functions and variables you want to use in other files
export { API_KEY, API_SECRET, accessToken, getAccessToken };