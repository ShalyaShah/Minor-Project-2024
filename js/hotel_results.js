document.addEventListener("DOMContentLoaded", () => {
    const resultsContainer = document.getElementById("results");
  
    let searchData;
    try {
      searchData = JSON.parse(sessionStorage.getItem("searchData"));
    } catch (error) {
      resultsContainer.innerHTML = "<p>Invalid search data. Please go back and search again.</p>";
      return;
    }
  
    if (!searchData) {
      resultsContainer.innerHTML = "<p>No search data found. Please go back and search again.</p>";
      return;
    }
  
    // Fetch hotels based on search data
    fetch("fetch_hotel.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams(searchData),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then((hotels) => {
        if (!Array.isArray(hotels) || hotels.length === 0) {
          resultsContainer.innerHTML = "<p>No hotels found for your search criteria.</p>";
          return;
        }
  
        // Display each hotel as a card
        hotels.forEach((hotel) => {
          const hotelElement = document.createElement("div");
          hotelElement.className = "hotel-card";
          hotelElement.innerHTML = `
            <img src="${hotel.image_url}" alt="Hotel Image" />
            <div class="hotel-info">
              <h3>${hotel.name}</h3>
              <p>${hotel.city}, ${hotel.country}</p>
              <p>Rating: ⭐ ${parseFloat(hotel.rating).toFixed(2)}</p>
            </div>
          `;
          hotelElement.addEventListener("click", () => {
            // Redirect to hotel details page with hotel_id
            window.location.href = `hotel_details.html?hotel_id=${hotel.id}`;
          });
  
          resultsContainer.appendChild(hotelElement);
        });
      })
      .catch((error) => {
        console.error("Error fetching hotels:", error);
        resultsContainer.innerHTML = "<p>There was an error fetching the hotel data. Please try again later.</p>";
      });
  });