# 🏎️ WFCARS | Luxury Car Dealership Platform

![WFCARS Banner](https://placehold.co/1200x400/0A0A0A/C8C8CA?text=WFCARS+Premium+Auto)

> **"Where your dream meets the road."**

Welcome to **WFCARS**, a premium web platform designed for the high-end automotive market. This project isn't just about selling cars; it's about selling an experience. Built with a robust **PHP** backend and a sleek **Tailwind CSS** frontend, it seamlessly bridges the gap between performance and elegance.

### 🌐 [Live Demo: wfcars.pt](https://wfcars.pt)

---

## ✨ Features

### 🚘 For the User (Frontend)
* **Immersive "Dark Mode" Design:** A custom color palette featuring *Deep Black* and *Brushed Silver* accents for that premium feel.
* **Smooth Navigation:** Powered by a Rolls-Royce inspired mobile menu and smooth scrolling.
* **Dynamic Inventory:** Filter cars by Brand, Fuel Type, Transmission, Price, and Kilometers in real-time.
* **Interactive Galleries:** High-resolution photo sliders using **Swiper.js** for a tactile browsing experience.
* **Responsive:** Fully optimized for everything from a 4K monitor to a smartphone.
* **Contact Concierge:** AJAX-powered contact forms ensuring user inquiries are sent instantly without page reloads.

### 🔧 For the Admin (Backoffice)
* **Secure Dashboard:** Login system with hashed passwords and role management (Admin/Editor/Viewer).
* **Stats at a Glance:** View active listings, sold units, and total revenue calculations instantly.
* **CRUD Operations:** Create, Read, Update, and Delete car listings with ease.
* **Drag & Drop Uploads:** Modern image uploader supporting up to 8 photos with drag-and-drop reordering.
* **Status Management:** One-click actions to mark cars as **Sold** or **Featured** (Gold Border effect).

---

## 🛠️ Tech Stack

**The Engine Room:**
* ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) **Core Logic:** Native PHP for a fast, dependency-free backend.
* ![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white) **Database:** Robust data management for inventory and users.

**The Bodywork:**
* ![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white) **Styling:** Utility-first CSS for rapid, custom UI development.
* ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black) **Interactivity:** Vanilla JS + Swiper.js for sliders.
* ![Bootstrap](https://img.shields.io/badge/MDB_UI-563d7c?style=for-the-badge&logo=bootstrap&logoColor=white) **Admin UI:** Material Design Bootstrap for the backoffice components.

---

## 📸 Screenshots

| **Homepage (Hero)** | **Inventory Filter** |
|:---:|:---:|
| <img src="app/heroimage.jpeg" width="400" alt="Hero Section"> | <img src="app/heroimage2.jpeg" width="400" alt="Inventory"> |

| **Admin Dashboard** | **Car Details** |
|:---:|:---:|
| *Manage your empire* | *Sleek galleries* |

---

## 🚀 Getting Started

Want to take this for a test drive on your local machine? Follow these steps:

### 1. Clone the Repo
```bash
git clone [https://github.com/your-username/wfcars.git](https://github.com/your-username/wfcars.git)
cd wfcars
```

### 2. Database Setup
1.  Open your database manager (phpMyAdmin, DBeaver, etc.).
2.  Create a new database named `wfcars_db`.
3.  Import the provided sql file to create the users, `anuncios` (listings), and `fotos_anuncio` (photos) tables.
    * *Tip: Make sure to create an admin user with a hashed password!*

### 3. Configure Connection
Open `app/db_connect.php` and update your credentials:

```php
$host = 'localhost';
$username = 'root';
$password = ''; // Your DB password
$db_name = 'wfcars_db';
```

### 4. Start the Engine
If you have PHP installed locally, run:

```bash
php -S localhost:8000 -t app/
```
Visit `http://localhost:8000` in your browser.

---

## 📂 Project Structure

```text
wfcars/
├── app/
│   ├── admin-*.php       # Administration pages (Dashboard, Create, Edit)
│   ├── uploads/          # Car images storage
│   ├── process_*.php     # Logic handlers (Login, Create User, Listing Actions)
│   ├── index.php         # Public Homepage
│   ├── inventory.php     # Public Catalog
│   ├── car-details.php   # Individual car page
│   ├── db_connect.php    # Database connection
│   └── globals.css       # Main styles (Tailwind imports)
├── components/           # Assets and images
└── tailwind.config.js    # Custom styling configuration
```

## 🎨 Design System

We utilize a custom configuration in Tailwind to achieve the "WFCARS Look":

* **Dark Primary:** `#0A0A0A` (Deep luxury black)
* **Highlight:** `#C8C8CA` (Platinum Silver)
* **Fonts:** *Poppins* for UI, *Playfair Display* & *Bodoni Moda* for elegance.

---

## 🤝 Contribution

Feel free to fork this project and submit a Pull Request. Whether it's fixing a bug or adding a new "Supercar Mode," all contributions are welcome!

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

<p align="center">
  <br>
  Made with ❤️ and a lot of ☕ in <b>Portugal</b>.
  <br>
  <i>WFCARS © 2025</i>
</p>
