// main.js or flight_offers.js (on your main page)
document.getElementById('flight_search_button').addEventListener('click', async function(event) {
  event.preventDefault();
  
  try {
      // Get DOM elements
      const departureInput = document.getElementById('departure_airport');
      const arrivalInput = document.getElementById('arrival_airport');
      const dateInput = document.getElementById('departure_date');
      const adultsInput = document.getElementById('adults');

      // Validate inputs exist
      if (!departureInput || !arrivalInput || !dateInput || !adultsInput) {
          throw new Error('Required input fields not found');
      }

      // Get values
      const origin = departureInput.value;
      const destination = arrivalInput.value;
      const departureDate = dateInput.value;
      const adults = adultsInput.value;

      // Validate input values
      if (!origin || !destination) {
          throw new Error('Please enter both departure and arrival airports');
      }

      // Extract airport codes
      const originCode = origin.match(/\(([^)]+)\)/)?.[1];
      const destinationCode = destination.match(/\(([^)]+)\)/)?.[1];

      if (!originCode || !destinationCode) {
          throw new Error('Invalid airport code format. Please include airport code in parentheses (e.g., "New York (JFK)")');
      }

      // Validate date and adults
      if (!departureDate) {
          throw new Error('Please select a departure date');
      }

      if (!adults || adults < 1) {
          throw new Error('Please enter a valid number of adults');
      }

      // Store search parameters in sessionStorage
      const searchParams = {
          originCode,
          destinationCode,
          departureDate,
          adults,
          origin,
          destination
      };
      
      sessionStorage.setItem('flightSearchParams', JSON.stringify(searchParams));

      // Redirect to flight.html
      window.location.href = 'flight.html';

  } catch (error) {
      console.error('Error:', error);
      alert(error.message);
  }
});