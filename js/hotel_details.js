document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const hotelId = urlParams.get("hotel_id");
  
    if (!hotelId) {
      document.getElementById("hotel-details").innerHTML = "<p>Invalid hotel ID. Please go back and try again.</p>";
      return;
    }
  
    fetch(`fetch_hotel_details.php?hotel_id=${hotelId}`)
      .then((response) => response.json())
      .then((data) => {
        const hotelDetailsContainer = document.getElementById("hotel-details");
  
        const hotel = data.hotel;
        hotelDetailsContainer.innerHTML = `
          <div class="hotel-info">
            <img src="${hotel.image_url}" alt="Hotel Image" />
            <h2>${hotel.name}</h2>
            <p>${hotel.description}</p>
            <p>Location: ${hotel.city}, ${hotel.country}</p>
            <p>Price per night: ₹${parseFloat(hotel.price_per_night).toLocaleString()}</p>
            <p>Rating: ⭐ ${parseFloat(hotel.rating).toFixed(2)}</p>
          </div>
          <h3>Available Rooms</h3>
          <div class="rooms-container">
            ${data.rooms
              .map(
                (room) => `
              <div class="room-card">
                <img src="${room.image_url}" alt="Room Image" />
                <h4>${room.room_type}</h4>
                <p>Price: ₹${parseFloat(room.price).toLocaleString()}</p>
                <p>Availability: ${room.availability} rooms</p>
              </div>
            `
              )
              .join("")}
          </div>
        `;
      })
      .catch(() => {
        document.getElementById("hotel-details").innerHTML = "<p>Error fetching hotel details. Please try again later.</p>";
      });
  });