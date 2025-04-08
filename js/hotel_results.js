document.addEventListener("DOMContentLoaded", () => {
    const resultsContainer = document.getElementById("results");

    const searchData = JSON.parse(sessionStorage.getItem("searchData"));

    if (!searchData) {
        resultsContainer.innerHTML = "<p>No search data found. Please go back and search again.</p>";
        return;
    }

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

            hotels.forEach((hotel) => {
                const hotelElement = document.createElement("div");
                hotelElement.className = "hotel-card";
                hotelElement.innerHTML = `
                    <img src="${hotel.image_url}" alt="Hotel Image" />
                    <div class="hotel-info">
                        <h3>${hotel.name}</h3>
                        <p>${hotel.city}, ${hotel.country}</p>
                        <p class="price">₹${parseFloat(hotel.price_per_night).toLocaleString()}/night</p>
                        <p>Rating: ⭐ ${parseFloat(hotel.rating).toFixed(2)}</p>
                        <p>${hotel.description}</p>
                    </div>
            `;
                hotelElement.addEventListener("click", () => {
                    window.location.href = `hotel_details.html?hotel_id=${hotel.id}`;
                });

                resultsContainer.appendChild(hotelElement);
            });
        })
        .catch((error) => {
            console.error("Error fetching hotels:", error);
            resultsContainer.innerHTML = "<p>There was an error fetching the hotel data. Please try again later.</p>";
        });

    // Toggle Dark Mode
    const toggleDark = document.getElementById("toggleDark");
    toggleDark.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
    });
});