-- Create Countries Table
CREATE TABLE countries (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE
);

-- Create Cities Table
CREATE TABLE cities (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    country_code VARCHAR(10) NOT NULL,
    FOREIGN KEY (country_code) REFERENCES countries (code)
);

-- Create Hotels Table
CREATE TABLE hotels (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL,
    city_id INTEGER NOT NULL,
    price_per_night REAL NOT NULL,
    rating REAL NOT NULL,
    description TEXT,
    image_url TEXT,
    FOREIGN KEY (city_id) REFERENCES cities (id)
);
CREATE TABLE rooms (
 id int(11) NOT NULL AUTO_INCREMENT,
 hotel_id int(11) NOT NULL,
 room_type varchar(100) NOT NULL,
 price decimal(10,2) NOT NULL,
 availability int(11) NOT NULL,
 image_url varchar(255) DEFAULT NULL,
 PRIMARY KEY (id),
 KEY hotel_id (hotel_id),
 CONSTRAINT rooms_ibfk_1 FOREIGN KEY (hotel_id) REFERENCES hotels (id)
);
CREATE TABLE hotel_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL UNIQUE,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100) NOT NULL,
    guest_phone VARCHAR(15) NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
CREATE TABLE hotel_guest_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    title VARCHAR(10) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    passport_number VARCHAR(20) NOT NULL,
    nationality VARCHAR(50) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES hotel_bookings(id)
);
-- Insert Countries
INSERT INTO countries (name, code) VALUES 
('India', 'IN'), 
('United States', 'US'), 
('United Kingdom', 'UK'), 
('France', 'FR'),
('Germany', 'DE'), 
('Canada', 'CA'), 
('Australia', 'AU'), 
('Japan', 'JP');

-- Insert Cities
INSERT INTO cities (name, country_code) VALUES 
('New Delhi', 'IN'), 
('Mumbai', 'IN'), 
('Bangalore', 'IN'), 
('Kolkata', 'IN'),
('New York', 'US'), 
('Los Angeles', 'US'), 
('Chicago', 'US'), 
('Houston', 'US'),
('London', 'UK'), 
('Manchester', 'UK'), 
('Birmingham', 'UK'),
('Paris', 'FR'), 
('Lyon', 'FR'), 
('Marseille', 'FR'),
('Berlin', 'DE'), 
('Munich', 'DE'), 
('Frankfurt', 'DE'),
('Toronto', 'CA'), 
('Vancouver', 'CA'), 
('Montreal', 'CA'),
('Sydney', 'AU'), 
('Melbourne', 'AU'), 
('Brisbane', 'AU'),
('Tokyo', 'JP'), 
('Osaka', 'JP'), 
('Kyoto', 'JP');

-- Insert Hotels
INSERT INTO hotels (name, city_id, price_per_night, rating, description, image_url) VALUES 
('The Oberoi', 1, 15000, 4.8, 'Luxury hotel in the heart of New Delhi.', 'https://example.com/oberoi.jpg'),
('Taj Mahal Palace', 2, 20000, 4.9, 'Iconic heritage hotel in Mumbai.', 'https://example.com/taj.jpg'),
('ITC Gardenia', 3, 1800, 4.7, 'Eco-friendly luxury hotel in Bangalore.', 'https://example.com/itc.jpg'),
('The Lalit', 4, 13000, 4.5, 'Modern hotel in Kolkata.', 'https://example.com/lalit.jpg'),
('The Plaza', 5, 4000, 4.8, 'Famous luxury hotel in New York.', 'https://example.com/plaza.jpg'),
('Beverly Hills Hotel', 6, 5000, 4.9, 'Iconic hotel in Los Angeles.', 'https://example.com/beverly.jpg'),
('Waldorf Astoria', 7, 3500, 4.8, 'Historic hotel in Chicago.', 'https://example.com/waldorf.jpg'),
('Four Seasons', 8, 3000, 4.7, 'Luxury hotel in Houston.', 'https://example.com/fourseasons.jpg'),
('The Ritz', 9, 4500, 4.9, 'Exclusive hotel in London.', 'https://example.com/ritz.jpg'),
('Hotel Gotham', 10, 25000, 4.6, 'Boutique hotel in Manchester.', 'https://example.com/gotham.jpg'),
('Hyatt Regency', 11, 22000, 4.5, 'Modern hotel in Birmingham.', 'https://example.com/hyatt.jpg'),
('Le Meurice', 12, 6000, 4.9, 'Luxury hotel in Paris.', 'https://example.com/meurice.jpg'),
('Sofitel', 13, 2200, 4.6, 'Elegant hotel in Lyon.', 'https://example.com/sofitel.jpg'),
('InterContinental', 14, 28000, 4.7, 'Classic hotel in Marseille.', 'https://example.com/intercontinental.jpg'),
('Adlon Kempinski', 15, 5000, 4.9, 'Famous luxury hotel in Berlin.', 'https://example.com/adlon.jpg'),
('Bayerischer Hof', 16, 4000, 4.8, 'Historic hotel in Munich.', 'https://example.com/bayerischer.jpg'),
('Steigenberger', 17, 3500, 4.7, 'Luxury hotel in Frankfurt.', 'https://example.com/steigenberger.jpg'),
('Fairmont Royal York', 18, 2500, 4.6, 'Grand hotel in Toronto.', 'https://example.com/fairmont.jpg'),
('Shangri-La', 19, 2800, 4.7, 'High-end hotel in Vancouver.', 'https://example.com/shangrila.jpg'),
('The Ritz-Carlton', 20, 3000, 4.8, 'Luxury hotel in Montreal.', 'https://example.com/ritzmontreal.jpg'),
('Park Hyatt', 21, 35000, 4.9, 'Elegant hotel in Sydney.', 'https://example.com/parkhyatt.jpg'),
('The Langham', 22, 3200, 4.7, 'Boutique hotel in Melbourne.', 'https://example.com/langham.jpg'),
('Stamford Plaza', 23, 2800, 4.6, 'Premium hotel in Brisbane.', 'https://example.com/stamford.jpg'),
('Aman Tokyo', 24, 7000, 5.0, 'Ultra-luxury hotel in Tokyo.', 'https://example.com/aman.jpg'),
('The St. Regis', 25, 4500, 4.8, 'Luxury hotel in Osaka.', 'https://example.com/stregis.jpg'),
('Hotel Granvia', 26, 6500, 4.5, 'Traditional hotel in Kyoto.', 'https://example.com/granvia.jpg');