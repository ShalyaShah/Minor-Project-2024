import { getAccessToken } from "./auth.js"; // Using existing auth setup

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
        alert("Check-out date must be after the check-in date.");
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
        // Get Amadeus API token
        const accessToken = await getAccessToken();
        console.log("Access Token:", accessToken);

        // Get city code from Amadeus API
        const cityResponse = await fetch(
            `https://test.api.amadeus.com/v1/reference-data/locations?keyword=${city}&subType=CITY`,
            {
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                },
            }
        );

        const cityData = await cityResponse.json();
        console.log("City API Response:", cityData);

        if (!cityData.data || cityData.data.length === 0) {
            console.error("City API returned no data:", cityData);
            alert("No matching city found. Please try a different city.");
            return;
        }

        // Ensure we get the correct city entry
        const cityEntry = cityData.data.find((entry) => entry.subType === "CITY");

        if (!cityEntry) {
            console.error("No valid city entry found:", cityData);
            alert("No valid city found for hotel search.");
            return;
        }

        const cityCode = cityEntry.iataCode;
        console.log("Using City Code:", cityCode);

        // Fetch hotel offers for the city
        const hotelResponse = await fetch(
            `https://test.api.amadeus.com/v1/reference-data/locations/hotels/by-city?cityCode=${cityCode}`,
            {
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                },
            }
        );

        const hotelData = await hotelResponse.json();
        console.log("Hotel API Response:", hotelData);

        if (!hotelData.data || hotelData.data.length === 0) {
            console.error("Hotel API returned no data:", hotelData);
            alert("No hotels found for this city. Please try a different city.");
            return;
        }

        // Store hotel results in sessionStorage
        sessionStorage.setItem("hotelResults", JSON.stringify(hotelData.data));
        console.log("Stored Hotel Results:", hotelData.data);

        // Redirect to the results page
        window.location.href = "hotel_results.html";
    } catch (error) {
        console.error("Error fetching hotel data:", error);
        alert("Failed to retrieve hotel offers. Please try again.");
    }
});