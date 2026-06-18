# Pastimes Part 3 - Clothing Marketplace Web Application

## Project Overview

Pastimes Part 3 is a PHP and MySQL web application for a second-hand clothing marketplace. The system allows customers to browse clothing items, add items to a shopping cart, edit the cart, checkout, track orders, and communicate with the administrator.

The system also supports sellers, who can submit requests to sell clothing items with a title, category, brand, size, condition, description, price, and optional image upload. Administrators can manage users, clothing listings, orders, delivery statuses, and communication between buyers and sellers.

This version includes an enhanced luxury-style design using dark emerald, gold, cream, modern cards, improved spacing, responsive layouts, and cleaner admin sections.

---

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- XAMPP
- phpMyAdmin

---

## Project Folder Name

The project folder should be named:

```text
Pastimes_Part3
```

Place this folder inside your XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\Pastimes_Part3
```

---

## How to Run the Project

### 1. Install and Open XAMPP

Make sure XAMPP is installed on your computer.

Open the XAMPP Control Panel and start:

```text
Apache
MySQL
```

Both services must be running.

---

### 2. Copy the Project Folder

Copy the `Pastimes_Part3` folder into:

```text
C:\xampp\htdocs\
```

The final path should look like this:

```text
C:\xampp\htdocs\Pastimes_Part3
```

---

### 3. Load the Database

Open your browser and go to:

```text
http://localhost/Pastimes_Part3/loadClothingStore.php
```

This page creates the required database and tables, then inserts sample data.

The database name is:

```text
clothingstore
```

---

### 4. Open the Website

Customer and seller login page:

```text
http://localhost/Pastimes_Part3/index.php
```

Administrator login page:

```text
http://localhost/Pastimes_Part3/admin_login.php
```

---

## Demo Login Details

### Customer Login

```text
Username: johndoe
Email: john@example.co.za
Password: abc
```

### Seller Login

```text
Username: seller1
Email: neo@example.co.za
Password: abc
```

### Administrator Login

```text
Username: admin
Email: admin@pastimes.co.za
Password: abc
```

---

## Main Features

### Customer Features

- Register and log in
- Browse available second-hand clothing
- Search and filter clothing items
- View item details such as title, category, brand, size, condition, seller, and price
- Add items to the shopping cart
- Edit cart item quantities
- Remove items from the cart
- Continue shopping after adding items
- Checkout with delivery address
- Track order and delivery status
- Send messages to the administrator

---

### Seller Features

- Log in as a seller
- Access the seller dashboard
- Submit requests to sell clothing
- Add clothing details:
  - title
  - category
  - brand
  - size
  - condition
  - description
  - price
  - image
- View submitted listings
- Update listing details
- View listing status:
  - pending
  - approved
  - hidden
  - sold
  - rejected
- Track sales orders
- Update delivery progress for sold items
- Communicate with the administrator

---

### Administrator Features

- Log in as administrator
- View admin dashboard statistics
- Add users
- Update users
- Delete users
- Verify sellers
- Add clothing items
- Update clothing items
- Delete clothing items
- Approve or reject seller clothing requests
- Manage orders
- Update payment status
- Update delivery status
- View buyer and seller messages
- Reply to buyers and sellers

---

## Database Tables

The project uses the following MySQL tables:

### `tblUser`

Stores customer and seller account information.

Main fields:

- user_id
- full_name
- username
- email
- phone
- password_hash
- role
- status
- created_at

---

### `tblAdmin`

Stores administrator login details.

Main fields:

- admin_id
- full_name
- username
- email
- password_hash
- created_at

---

### `tblClothes`

Stores clothing listings submitted by sellers or added by administrators.

Main fields:

- clothes_id
- seller_id
- title
- category
- brand
- size
- item_condition
- description
- price
- image_path
- status
- created_at

---

### `tblCart`

Stores items added to a customer shopping cart.

Main fields:

- cart_id
- buyer_id
- clothes_id
- quantity
- added_at

---

### `tblAddress`

Stores customer delivery addresses.

Main fields:

- address_id
- user_id
- street_address
- suburb
- city
- province
- postal_code
- created_at

---

### `tblOrder`

Stores order and delivery information.

Main fields:

- order_id
- buyer_id
- clothes_id
- quantity
- total_amount
- payment_status
- delivery_status
- delivery_address_id
- notes
- order_date

---

### `tblMessage`

Stores messages between users and administrators.

Main fields:

- message_id
- sender_role
- sender_id
- receiver_role
- receiver_id
- related_order_id
- related_clothes_id
- subject
- message_text
- is_read
- created_at

---

## Important Project Files

### `index.php`

Main login page for customers and sellers.

### `register.php`

Allows new customers or sellers to register.

### `dashboard.php`

Customer browsing page where clothing items are displayed.

### `cart.php`

Allows customers to view, edit, and remove cart items.

### `checkout.php`

Allows customers to add/select a delivery address and place an order.

### `orders.php`

Displays order tracking information for buyers and sales tracking for sellers.

### `seller_dashboard.php`

Allows sellers to submit and manage clothing listings.

### `messages.php`

Allows customers and sellers to send messages to the administrator.

### `admin_login.php`

Login page for administrators.

### `admin_dashboard.php`

Administrator dashboard for managing users, clothing items, orders, and communication.

### `loadClothingStore.php`

Creates the database tables and loads sample data from the SQL file.

### `DBConn.php`

Contains the MySQL database connection settings.

### `helpers.php`

Contains reusable helper functions used across the system.

### `assets/style.css`

Contains the enhanced luxury design styling for the website.

### `sql/myClothingStore.sql`

Contains the SQL commands used to create the database, tables, and sample records.

---

## How to Reset the Database

To reset the project data back to the sample records, open:

```text
http://localhost/Pastimes_Part3/loadClothingStore.php
```

This reloads the database and resets the sample data.

---

## Notes

- The project uses `MD5()` password hashing because the assignment prototype uses the MD5 hash format.
- In a real production system, PHP `password_hash()` and `password_verify()` should be used instead.
- The project is designed for local demonstration using XAMPP.
- The system must be run through `localhost`, not by opening the PHP files directly.

---

## Final POE Requirements Covered

This project covers the required final POE features:

1. Customers can select clothing items, add them to cart, checkout, and continue shopping.
2. Administrators can add, update, and delete clothing items and users.
3. Customers can edit items in their shopping cart.
4. Sellers can submit requests to sell clothing with a description, image, and brand.
5. Administrators can communicate with sellers and buyers.
6. The website is visually appealing and easy to navigate.
7. The system includes additional features from the design document, such as search/filtering, order tracking, delivery address capture, seller dashboard, admin analytics, and messaging.
