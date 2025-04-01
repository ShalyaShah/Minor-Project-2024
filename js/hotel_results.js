document.addEventListener("DOMContentLoaded", () => {
    // Retrieve hotel results from sessionStorage
    const hotelResults = JSON.parse(sessionStorage.getItem("hotelResults"));
    console.log("Hotel Results:", hotelResults);

    // Check if hotelResults is valid
    if (!hotelResults || hotelResults.length === 0) {
        alert("No hotel results found.");
        return;
    }

    const resultsContainer = document.getElementById("results");

    // Iterate through the hotel results
    hotelResults.forEach((result, index) => {
        // Safely access the hotel name and city
        const hotelName = result.hotel?.name;
        const cityName = result.hotel?.address?.cityName;

        // Log each result for debugging
        console.log(`Result ${index + 1}:`, { hotelName, cityName });

        // Create a new element for each hotel
        const hotelElement = document.createElement("div");
        hotelElement.className = "hotel";
        hotelElement.innerHTML = `
            <h3>${hotelName}</h3>
            <p>City: ${cityName}</p>
        `;

        // Append the hotel element to the results container
        resultsContainer.appendChild(hotelElement);
    });
});