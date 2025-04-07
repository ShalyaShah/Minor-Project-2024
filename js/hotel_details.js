document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const hotelId = urlParams.get("hotel_id");

    if (!hotelId) {
        document.getElementById("hotel").innerText = "Invalid hotel.";
        return;
    }

    fetch(`fetch_hotel_details.php?hotel_id=${hotelId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.hotel) {
                document.getElementById("hotel").innerText = "Hotel not found.";
                return;
            }

            const hotel = data.hotel;
            const rooms = data.rooms;

            document.getElementById("hotel").innerHTML = `
                <h2>${hotel.name}</h2>
                <p><strong>City:</strong> ${hotel.city}, ${hotel.country}</p>
                <p><strong>Rating:</strong> ⭐ ${hotel.rating}</p>
                <p><strong>Description:</strong> ${hotel.description}</p>
                <img src="${hotel.image_url}" alt="Hotel Image" width="300" style="margin-top:10px;">
                <h3 style="margin-top: 30px;">Available Rooms</h3>
            `;

            const roomList = document.getElementById("roomList");
            rooms.forEach(room => {
                roomList.innerHTML += `
                    <div class="room">
                        <h4>${room.room_type}</h4>
                        <p>Price: ₹${room.room_price} / night</p>
                    </div>
                `;
            });
        })
        .catch(err => {
            console.error("Error:", err);
        });
});
