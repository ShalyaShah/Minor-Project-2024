document.addEventListener("DOMContentLoaded", () => {
  const guestsInput = document.getElementById('guests');
  const roomsInput = document.getElementById('rooms');
  
  // Function to calculate recommended rooms based on guests
  function calculateRooms(guests) {
    if (guests <= 2) return 1;
    if (guests <= 6) return 2;
    return Math.ceil(guests / 3);
  }
  
  // Update rooms when guests change
  guestsInput.addEventListener('input', function() {
    const guests = parseInt(this.value) || 0;
    if (guests >= 4) {
      roomsInput.value = calculateRooms(guests);
    }
  });

  // Form submission handler
  document.getElementById("hotelSearchForm").addEventListener("submit", (e) => {
    e.preventDefault();

    const city = document.getElementById("city").value;
    const checkInDate = document.getElementById("check_in_date").value;
    const checkOutDate = document.getElementById("check_out_date").value;
    const guests = document.getElementById("guests").value;
    const rooms = document.getElementById("rooms").value;

    // Save search data to sessionStorage
    sessionStorage.setItem(
      "searchData",
      JSON.stringify({ city, checkInDate, checkOutDate, guests, rooms })
    );

    // Redirect to results page
    window.location.href = "hotel_results.html";
  });
});