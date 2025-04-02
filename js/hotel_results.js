document.addEventListener("DOMContentLoaded", () => {
    const resultsContainer = document.getElementById("results");

    // Get search data from sessionStorage
    const searchData = JSON.parse(sessionStorage.getItem("searchData"));

    if (!searchData) {
        resultsContainer.innerHTML = "<p>No search data found. Please go back and search again.</p>";
        return;
    }

    // Fetch hotels from the server
    fetch("fetch_hotel.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(searchData),
    })
        .then((response) => response.json())
        .then((hotels) => {
            if (hotels.length === 0) {
                resultsContainer.innerHTML = "<p>No hotels found for your search criteria.</p>";
                return;
            }

            // Display each hotel
            hotels.forEach((hotel) => {
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
        })
        .catch((error) => {
            console.error("Error fetching hotels:", error);
            resultsContainer.innerHTML = "<p>There was an error fetching the hotel data. Please try again later.</p>";
        });
});