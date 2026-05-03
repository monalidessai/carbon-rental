# 🚗 Carbon Rental — Car Management System (CMS)

A web-based platform for digitalizing and streamlining car rental operations. The system serves three types of users: **Customers**, **Drivers**, and an **Owner/Admin**, replacing manual, error-prone processes with a secure and efficient digital solution.

> **Led a team of 3** to build this platform — reducing booking confirmation time from **2 hours to under 10 minutes**, minimizing booking conflicts by **60%**, improving data retrieval speed by **45%**, and boosting fleet utilization by **35%**.

---

## 📋 Table of Contents

- [About the Project](#about-the-project)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [User Roles](#user-roles)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

---

## About the Project

Carbon Rental is a Car Management System (CMS) designed to digitalize car rental operations. It provides a unified platform where customers can book vehicles, drivers can manage their assignments, and the owner/admin can oversee the entire fleet, bookings, and customer feedback — all from a single web interface.

---

## 📊 Key Achievements

| Metric | Result |
|--------|--------|
| Booking confirmation time | Reduced from **2 hours → under 10 minutes** (~92% faster) |
| Booking conflicts | Reduced by **60%** |
| Data retrieval speed | Improved by **45%** via MySQL optimization |
| Fleet utilization | Increased by **35%** via analytics dashboard |

- 👥 **Team Leader** — Led a team of 3 developers across design, backend, and frontend
- ⚡ **Automated booking pipeline** — Eliminated manual confirmation steps end-to-end
- 🗄️ **Optimized MySQL database** — Indexed queries and normalized schema for faster retrieval
- 📊 **Admin analytics dashboard** — Built real-time fleet tracking and booking insights

---

## Features

### 👤 Customer
- Register and log in securely
- Browse available cars and make bookings
- View booking history
- Submit feedback and ratings

### 🚙 Driver
- View assigned trips and schedules
- Manage availability

### 🛠️ Owner / Admin
- Manage the vehicle fleet
- Manage drivers and their assignments
- View and manage all bookings
- Access and review customer feedback
- Dashboard with operational insights

---

## Tech Stack

| Technology | Usage |
|------------|-------|
| PHP | Backend logic, server-side processing |
| MySQL | Database design, query optimization, data storage |
| HTML | Page structure and markup |
| CSS | Styling, responsive layout |
| JavaScript | Frontend interactivity and dynamic UI |

---

## Project Structure

```
carbon-rental/
├── assets/          # Static assets (fonts, icons, etc.)
├── auth/            # Authentication (login, register, session)
├── bookings/        # Booking management logic
├── css/             # Stylesheets
├── dashboards/      # Role-based dashboards
├── images/          # Image files
├── includes/        # Reusable PHP includes (header, footer, db config)
├── js/              # JavaScript files
├── admin_feedback.php   # Admin view for customer feedback
├── feedback.php         # Customer feedback form
├── index.php            # Application entry point
├── submit_feedback.php  # Feedback submission handler
├── test.php             # Testing utilities
└── .htaccess            # Apache URL rewriting rules
```

---

## Getting Started

### Prerequisites

- PHP >= 7.4
- MySQL / MariaDB
- Apache web server (with `mod_rewrite` enabled)
- A local server environment like [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/monalidessai/carbon-rental.git
   cd carbon-rental
   ```

2. **Set up the database**
   - Open your MySQL client (e.g., phpMyAdmin)
   - Create a new database (e.g., `carbon_rental`)
   - Import the SQL file if provided, or set up tables manually

3. **Configure the database connection**
   - Open `includes/db_config.php` (or the relevant config file)
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'carbon_rental');
     ```

4. **Move files to server root**
   - Copy the project folder to your Apache `htdocs` directory (e.g., `C:/xampp/htdocs/carbon-rental`)

5. **Start the server**
   - Launch Apache and MySQL from XAMPP/WAMP
   - Visit `http://localhost/carbon-rental` in your browser

---

## User Roles

| Role | Access Level | Key Capabilities |
|------|-------------|-----------------|
| Customer | Limited | Book cars, view history, submit feedback |
| Driver | Limited | View assigned trips, manage availability |
| Owner/Admin | Full | Manage fleet, drivers, bookings, view feedback |

---

## Screenshots

Screenshots are included in the repository root for reference.

---

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/your-feature-name`)
3. Commit your changes (`git commit -m 'Add some feature'`)
4. Push to the branch (`git push origin feature/your-feature-name`)
5. Open a Pull Request

---

## License

This project is currently unlicensed. Please contact the repository owner for usage permissions.

---

> Developed by [@monalidessai](https://github.com/monalidessai)
