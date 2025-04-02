const express = require("express");
const mysql = require("mysql");
const cors = require("cors");
require("dotenv").config();

const app = express();
app.use(cors());
app.use(express.json());

// ✅ Connect to MySQL Database
const db = mysql.createConnection({
    host: "localhost", // Change if hosted remotely
    user: "root",      // Your MySQL username
    password: "",      // Your MySQL password (if set)
    database: "hotel_booking"
});

// ✅ Check if the database is connected
db.connect(err => {
    if (err) {
        console.error("❌ Database connection failed:", err);
    } else {
        console.log("✅ Connected to MySQL Database!");
    }
});

// ✅ API to Fetch Hotels Based on Search Filters
app.get("/search-hotels", (req, res) => {
    const { location, checkin, checkout, guests, rooms } = req.query;

    const query = `
        SELECT hotels.name, hotels.price_per_night, hotels.rating, hotels.description, hotels.image_url, hotels.available_rooms
        FROM hotels
        JOIN cities ON hotels.city_id = cities.id
        WHERE (cities.name = ? OR cities.country_code = ?)
        AND hotels.available_rooms >= ?
    `;

    db.query(query, [location, location, rooms], (err, results) => {
        if (err) {
            res.status(500).json({ error: "Database query error" });
        } else {
            res.json(results);
        }
    });
});

// ✅ Start Express Server
const PORT = 5000;
app.listen(PORT, () => {
    console.log(`🚀 Server running on http://127.0.0.1:${PORT}/`);
});
