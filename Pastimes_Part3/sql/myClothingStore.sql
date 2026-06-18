CREATE DATABASE IF NOT EXISTS clothingstore;
USE clothingstore;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS tblMessage;
DROP TABLE IF EXISTS tblOrder;
DROP TABLE IF EXISTS tblCart;
DROP TABLE IF EXISTS tblAddress;
DROP TABLE IF EXISTS tblClothes;
DROP TABLE IF EXISTS tblAdmin;
DROP TABLE IF EXISTS tblUser;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE tblUser (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','seller') NOT NULL DEFAULT 'customer',
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tblAdmin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tblClothes (
    clothes_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    category VARCHAR(60) NOT NULL,
    brand VARCHAR(80),
    size VARCHAR(20) NOT NULL,
    item_condition VARCHAR(40) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    status ENUM('pending','approved','sold','hidden','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clothes_user FOREIGN KEY (seller_id) REFERENCES tblUser(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tblAddress (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    street_address VARCHAR(150) NOT NULL,
    suburb VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_address_user FOREIGN KEY (user_id) REFERENCES tblUser(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tblCart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    clothes_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (buyer_id) REFERENCES tblUser(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_clothes FOREIGN KEY (clothes_id) REFERENCES tblClothes(clothes_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tblOrder (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    clothes_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    delivery_status ENUM('processing','packed','shipped','delivered') NOT NULL DEFAULT 'processing',
    delivery_address_id INT NULL,
    notes VARCHAR(255),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user FOREIGN KEY (buyer_id) REFERENCES tblUser(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_clothes FOREIGN KEY (clothes_id) REFERENCES tblClothes(clothes_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_address FOREIGN KEY (delivery_address_id) REFERENCES tblAddress(address_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tblMessage (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_role ENUM('user','admin') NOT NULL,
    sender_id INT NOT NULL,
    receiver_role ENUM('user','admin') NOT NULL,
    receiver_id INT NOT NULL,
    related_order_id INT DEFAULT 0,
    related_clothes_id INT DEFAULT 0,
    subject VARCHAR(150) NOT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tblUser (full_name, username, email, phone, password_hash, role, status) VALUES
('John Doe','johndoe','john@example.co.za','0821234567',MD5('abc'),'customer','verified'),
('Neo Maseko','seller1','neo@example.co.za','0831112233',MD5('abc'),'seller','verified'),
('Lerato Mokoena','lerato','lerato@example.co.za','0824567788',MD5('abc'),'customer','verified'),
('Aisha Patel','seller2','aisha@example.co.za','0845551122',MD5('abc'),'seller','pending'),
('Thabo Ndlovu','thabo','thabo@example.co.za','0817778899',MD5('abc'),'customer','verified');

INSERT INTO tblAdmin (full_name, username, email, password_hash) VALUES
('Pastimes Administrator','admin','admin@pastimes.co.za',MD5('abc')),
('Support Admin','supportadmin','support@pastimes.co.za',MD5('abc'));

INSERT INTO tblClothes (seller_id, title, category, brand, size, item_condition, description, price, image_path, status) VALUES
(2,'Vintage Denim Jacket','Jackets','Levi Style','L','Good','Blue pre-owned denim jacket in great condition.',350.00,NULL,'approved'),
(2,'Black Formal Dress','Dresses','Zara Style','M','Excellent','Elegant second-hand black dress for formal occasions.',420.00,NULL,'approved'),
(4,'White Sneakers','Shoes','Nike Style','8','Good','Clean white casual sneakers listed by a pending seller.',500.00,NULL,'pending'),
(2,'Graphic T-Shirt','T-Shirts','Local Brand','XL','Fair','Casual printed t-shirt for everyday wear.',120.00,NULL,'approved'),
(4,'Brown Hoodie','Hoodies','Cotton Basics','2XL','Excellent','Warm unisex hoodie waiting for admin approval.',280.00,NULL,'pending');

INSERT INTO tblAddress (user_id, street_address, suburb, city, province, postal_code) VALUES
(1,'12 Market Street','Hatfield','Pretoria','Gauteng','0083'),
(3,'44 Main Road','Braamfontein','Johannesburg','Gauteng','2001');

INSERT INTO tblCart (buyer_id, clothes_id, quantity) VALUES
(1,1,1),
(1,4,1);

INSERT INTO tblOrder (buyer_id, clothes_id, quantity, total_amount, payment_status, delivery_status, delivery_address_id, notes) VALUES
(3,2,1,420.00,'paid','shipped',2,'Payment method: Card'),
(5,1,1,350.00,'paid','delivered',1,'Payment method: EFT');

INSERT INTO tblMessage (sender_role, sender_id, receiver_role, receiver_id, related_order_id, related_clothes_id, subject, message_text) VALUES
('user',1,'admin',1,0,1,'Question about jacket','Hi admin, please confirm if the denim jacket is still available.'),
('admin',1,'user',1,0,1,'Re: Question about jacket','Yes, the jacket is available and ready for purchase.'),
('user',2,'admin',1,0,5,'Seller approval','I have submitted a hoodie request. Please review it when you can.');
