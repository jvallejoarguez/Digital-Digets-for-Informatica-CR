# Digital-Digets-for-Informatica-CR
Internship project.

# Digital Ticket System

## Overview
The **Digital Ticket System** is a PHP-based application that provides users with a digital platform to manage their photocopy quotas. It allows users to:

- Register for an account.
- Log in to view their remaining and used photocopy balances.
- Use black-and-white or color photocopies.
- Generate receipts for each transaction.

This system is designed with a responsive user interface and utilizes a MySQL database to store user data and transaction logs.

---

## Features

1. **User Registration & Login**
   - Secure password storage with `bcrypt` hashing.
   - Validation to prevent duplicate usernames.

2. **Photocopy Management**
   - Track available and used copies for both black-and-white and color categories.
   - Prevent users from exceeding their copy quotas.

3. **Receipts**
   - Generate and store transaction receipts, including details like copies used, remaining copies, and the timestamp of the transaction.

4. **Session Management**
   - Persistent sessions for logged-in users.
   - Logout functionality.

5. **Responsive UI**
   - User-friendly design using HTML and CSS.
   - Interactive modals and transitions for a better user experience.

---

## Technology Stack

- **Frontend**: HTML, CSS (with Google Fonts for styling) and JS (to hide/show all the divs and interactiveness).
- **Backend**: PHP (session-based authentication).
- **Database**: MySQL (user data, copy quotas, and receipts).

---

## Prerequisites

- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache/Nginx

---

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/digital-ticket-system.git
   ```

2. Set up the MySQL database:
   - Import the provided `database.sql` file to create the necessary tables.

3. Configure the database connection:
   - Update the following fields in `index.php` to match your database credentials:
     ```php
     $conn = new mysqli("localhost:3306", "your_db_username", "your_db_password", "your_db_name");
     ```

4. Deploy the application on your web server:
   - Place the project files in your server's public directory.

5. Access the application:
   - Open your browser and navigate to `http://localhost/digital-ticket-system`.

---

## Usage

1. **Register**:
   - Click the **Register** button to create a new account.

2. **Login**:
   - Use your credentials to log in.

3. **Manage Photocopies**:
   - View available and used copies.
   - Use photocopies by specifying the quantity.

4. **Generate Receipts**:
   - Access the receipts section to view your transaction history.

5. **Logout**:
   - Click the **Logout** button to end your session.

---

## File Structure

```
/ (Project Root)
├── index.php           # Main application logic
├── my_receipts.php     # View and download receipts
├── receipt.php         # Generate individual receipt
├── database.sql        # SQL script to create database tables
└── README.md           # Documentation
```

---

## Database Schema

### `users` Table
| Column               | Type         | Description                        |
|----------------------|--------------|------------------------------------|
| `id`                 | INT (PK)    | User ID                            |
| `username`           | VARCHAR     | Username                           |
| `password`           | VARCHAR     | Hashed password                    |
| `available_copies`   | INT         | Black-and-white copies available   |
| `used_copies`        | INT         | Black-and-white copies used        |
| `available_copies_color` | INT     | Color copies available             |
| `used_copies_color`  | INT         | Color copies used                  |

### `receipts` Table
| Column                 | Type         | Description                        |
|------------------------|--------------|------------------------------------|
| `id`                   | INT (PK)    | Receipt ID                         |
| `user_id`              | INT (FK)    | Associated user ID                 |
| `bw_copies_used`       | INT         | Black-and-white copies used        |
| `color_copies_used`    | INT         | Color copies used                  |
| `bw_copies_remaining`  | INT         | Remaining black-and-white copies   |
| `color_copies_remaining` | INT       | Remaining color copies             |
| `transaction_time`     | DATETIME    | Timestamp of the transaction       |

---

## License

This is an internship project, feel free to use the code. InformaticaCR is a registered brand.

---

## Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature-name`.
3. Commit your changes: `git commit -m 'Add new feature'`.
4. Push to the branch: `git push origin feature-name`.
5. Submit a pull request.

---

## Acknowledgments

- Icons and fonts by [Google Fonts](https://fonts.google.com/).
- Developed with ❤️ by jvallejoarguez (https://github.com/jvallejoarguez).

