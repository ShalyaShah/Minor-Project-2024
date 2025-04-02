document.addEventListener("DOMContentLoaded", () => {
    const resultsContainer = document.getElementById("results");

    // Get hotel results from sessionStorage
    const hotelResults = JSON.parse(sessionStorage.getItem("hotelResults"));

    if (!hotelResults || hotelResults.length === 0) {
        resultsContainer.innerHTML = "<p>No hotels found.</p>";
        return;
    }

    // Display each hotel
    hotelResults.forEach((hotel) => {
        const hotelElement = document.createElement("div");
        hotelElement.className = "hotel";
        hotelElement.innerHTML = `
            <h3>${hotel.name}</h3>
            <p>City: ${hotel.city}, ${hotel.country}</p>
            <p>Price: ₹${hotel.price_per_night}/night</p>
            <p>Rating: ⭐ ${hotel.rating}</p>
            <p>${hotel.description}</p>
            <img src="${hotel.image_url}" width="200" alt="Hotel Image">
        `;
        resultsContainer.appendChild(hotelElement);
    });
});
