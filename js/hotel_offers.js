document.getElementById("hotel_search_button").addEventListener("click", async () => {
    // Get form values
    const city = document.getElementById("city").value.trim();
    const checkInDate = document.getElementById("check_in_date").value;
    const checkOutDate = document.getElementById("check_out_date").value;
    const guests = document.getElementById("guests").value;
    const rooms = document.getElementById("rooms").value;

    // Validate form inputs
    if (!city) {
        alert("Please enter a city name.");
        return;
    }
    if (!checkInDate || !checkOutDate) {
        alert("Please select both check-in and check-out dates.");
        return;
    }
    if (new Date(checkInDate) >= new Date(checkOutDate)) {
        alert("Check-out date must be after check-in date.");
        return;
    }
    if (!guests || guests <= 0) {
        alert("Please enter a valid number of guests.");
        return;
    }
    if (!rooms || rooms <= 0) {
        alert("Please enter a valid number of rooms.");
        return;
    }

    try {
        // Send search request to fetch_hotel.php
        const response = await fetch("./fetch_hotel.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                city: city,
                checkInDate: checkInDate,
                checkOutDate: checkOutDate,
                guests: guests,
                rooms: rooms,
            }),
        });

        if (!response.ok) {
            throw new Error("Failed to fetch hotel data.");
        }

        const hotelData = await response.json();
        console.log("Hotel Data from PHP:", hotelData);

        if (!hotelData || hotelData.length === 0) {
            alert("No hotels found for this city. Please try a different city.");
            return;
        }

        // Store hotel results in sessionStorage
        sessionStorage.setItem("hotelResults", JSON.stringify(hotelData));

        // Redirect to results page
        window.location.href = "hotel_results.html";
    } catch (error) {
        console.error("Error fetching hotel data:", error);
        alert("Failed to retrieve hotel data. Please try again.");
    }
});
