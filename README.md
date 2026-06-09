# 🚗 CarVault — PapaGroup

A full-stack web-based car marketplace where users can buy and sell pre-owned vehicles, managed by an admin/manager role with listing moderation and sales reporting.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Features](#features)
  - [Authentication](#authentication)
  - [Buyer Features](#buyer-features)
  - [Seller Features](#seller-features)
  - [Manager / Admin Features](#manager--admin-features)
  - [User Profile](#user-profile)
- [Database Schema](#database-schema)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)

---

## Overview

CarVault is a PHP + MySQL car listing platform that supports three roles: **buyers**, **sellers**, and **managers**. Sellers submit car listings which go through a moderation flow (pending → approved/rejected) before becoming visible to buyers. Buyers can browse, filter, contact sellers, secure a car, and leave reviews. Managers oversee listings, users, transactions, and view a sales chart dashboard.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP (REST-style API endpoints) |
| Database | MySQL (via phpMyAdmin / XAMPP) |
| Icons | Font Awesome 6 |
| Charts | Chart.js |
| Server | Apache (XAMPP recommended) |

---

## Features

### Authentication

- **User Registration** — sign up with username, first/last name, phone number (11-digit validation), email, and password.
- **Login / Logout** — session-based authentication via `login.php` and `logout.php`; role stored in `sessionStorage`.
- **Role-Based Access** — users are assigned either `user` or `manager` role; the manager dashboard redirects non-managers away.
- **Forgot Password** — dedicated `forgot-password.html` page for password recovery flow.

---

### Buyer Features

- **Browse Approved Listings** — fetches only `approved` car listings from the API to display to buyers.
- **Car Listing Cards** — each card shows the car photo, year, brand, model, transmission, mileage, price, city, and seller info.
- **Vehicle Details Modal** — click a listing to open a detailed modal with full specs: brand, model, year, variant, transmission, mileage, price, and city.
- **Contact Seller Modal** — buyers can open a modal to view seller contact details (username, phone, email) for any listing.
- **Secure a Car** — buyers can initiate a purchase transaction ("Secure Car"), which creates a pending transaction record and marks the car as sold.
- **Reviews** — buyers can submit a 1–5 star rating with a comment for a car listing, and view all existing reviews per listing.
- **Search** — search bar on the front page routes to the buyer listing page.

---

### Seller Features

- **List a Car for Sale** — sellers fill out a form with: brand (30+ supported brands), model, year, variant, transmission, mileage, price, city, and an optional car photo upload.
- **Supported Brands** — Audi, BMW, BYD, Chana, Chery, Chevrolet, Chrysler, Dodge, FAW, Ferrari, Ford, Foton, Haima, Honda, Hyundai, Isuzu, Jaguar, JMC, Kia, King Long, Lamborghini, Land Rover, Lexus, Mahindra, Maxus, Mazda, Mercedes-Benz, MG, Mitsubishi, Nissan, Porsche, Subaru, Suzuki, Toyota, Volkswagen, Volvo.
- **Brand Selection UI** — a visual brand-card grid lets sellers click their car brand before filling in listing details, shown in a modal.
- **Listing Status Tracking** — sellers can view all their listings with live status badges: `PENDING`, `APPROVED`, `REJECTED`, or `SOLD`.
- **Delete Listing** — sellers can remove their own listings.
- **Image Upload** — car photos are uploaded to `uploads/cars/` and stored as file paths in the database.

---

### Manager / Admin Features

- **Manager-Only Access** — the manager dashboard (`manager.html`) validates the session role and redirects unauthorized users.
- **Car Listing Moderation** — managers see all submitted listings with their current status and can **Approve** or **Reject** each pending listing.
- **User Management Table** — displays all registered users with columns for name, username, email, phone, role, and a verified/unverified badge.
- **Seller Verification** — sellers with at least one approved listing are automatically flagged as verified in the manager's seller overview.
- **Buyer Transaction Tracking** — managers can view a table of all transactions, including buyer username, seller username, car details, and transaction status.
- **Sales Chart Dashboard** — a Chart.js bar chart showing monthly/yearly sales figures, auto-refreshing every 10 seconds via the API.

---

### User Profile

- **View Profile** — displays the logged-in user's info pulled from `api/profile.php`.
- **Edit Profile** — users can update their profile information and save changes.
- **Seller Rating Display** — user profiles include a cumulative seller rating and total number of ratings.

---

## Database Schema

The MySQL database (`papagroup`) contains four tables:

| Table | Key Columns |
|---|---|
| `users` | `id`, `username`, `email`, `phone`, `password_hash`, `first_name`, `last_name`, `role` (user/manager), `verified`, `rating`, `total_ratings` |
| `cars` | `id`, `seller_id`, `brand`, `model`, `year`, `transmission`, `variant`, `mileage`, `price`, `image_path`, `city`, `status` (pending/approved/rejected/sold) |
| `transactions` | `id`, `car_id`, `buyer_id`, `seller_id`, `purchase_price`, `transaction_status` (pending/completed/cancelled), `payment_date`, `notes` |
| `reviews` | `id`, `car_id`, `reviewer_id`, `rating` (1–5), `comment` |

---

## Project Structure

```
PapaGroup-main/
├── index.html              # Landing / login page
├── frontpage.html          # Home page after login (search + about)
├── buyer.html              # Car listings for buyers
├── seller.html             # Seller dashboard + list a car
├── manager.html            # Manager/admin dashboard
├── profile.html            # User profile page
├── register.html           # Registration page
├── forgot-password.html    # Password recovery page
├── login.php               # Login handler
├── logout.php              # Logout handler
├── register.php            # Registration handler
├── get_users.php           # Returns user list (manager use)
├── api/
│   ├── cars.php            # Car CRUD + status management
│   ├── transactions.php    # Transaction management
│   ├── reviews.php         # Review CRUD
│   └── profile.php         # Profile read/update
├── db/
│   ├── connection.php      # PDO database connection
│   └── setup.sql           # Database & table creation script
├── styles.css              # Global styles
├── stylec.css              # Buyer page styles
├── styless.css             # Seller page styles
├── style3.css              # Additional styles
├── pictures/               # Brand logo images
└── uploads/cars/           # User-uploaded car photos
```

---

## Setup & Installation

1. **Requirements** — XAMPP (or any Apache + PHP + MySQL stack).
2. **Clone / copy** the project into your `htdocs` folder.
3. **Database** — open phpMyAdmin and run `db/setup.sql` to create the `papagroup` database and all tables.
4. **Config** — update `db/connection.php` with your MySQL credentials if needed.
5. **Run** — start Apache and MySQL in XAMPP, then visit `http://localhost/PapaGroup/`.
6. **Manager account** — manually set a user's `role` to `manager` in the `users` table via phpMyAdmin to access the manager dashboard.
