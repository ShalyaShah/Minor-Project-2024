document.addEventListener("DOMContentLoaded", () => {
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