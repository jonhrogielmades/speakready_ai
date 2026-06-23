# SpeakReady AI

SpeakReady AI is a comprehensive web application. Follow these instructions to clone, configure, and run the project locally.

## Requirements

Before you begin, ensure you have the following installed on your machine:
- **PHP** 8.1 or higher
- **Composer** (Dependency Manager for PHP)
- **Node.js** & **npm** (JavaScript runtime and package manager)
- **MySQL** (or any preferred database)
- **Git**

## 🚀 Full Configuration & Setup Guide

### 1. Clone the Repository
Open your terminal and clone the repository to your local machine:
```bash
git clone https://github.com/jonhrogielmades/speakready_ai.git
cd speakready_ai
```

### 2. Install PHP Dependencies
Install all the required PHP packages using Composer:
```bash
composer install
```

### 3. Install NPM Dependencies
Install all the required front-end dependencies using npm:
```bash
npm install
```

### 4. Configure Environment Variables
Copy the example environment file and create your own `.env` file:
```bash
cp .env.example .env
```
*(On Windows Command Prompt, use `copy .env.example .env`)*

### 5. Generate Application Key
Generate a new application key. This will automatically update your `.env` file:
```bash
php artisan key:generate
```

### 6. Database Configuration
1. Open your database manager (e.g., phpMyAdmin, TablePlus, or MySQL CLI) and create a new empty database (for example, `speakready_ai`).
2. Open the `.env` file in the root of your project and update the database credentials to match your local setup:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=speakready_ai   # Your database name
DB_USERNAME=root            # Your database username
DB_PASSWORD=                # Your database password
```

### 7. Run Database Migrations or Import Database Dump
You can either run the migrations to create the necessary tables or import the provided database dump.

**Option A: Run Migrations**
```bash
php artisan migrate
```

**Option B: Import SQL Dump (Recommended if you need initial data)**
Import the provided `database_dump.sql` file directly into your newly created database using your database manager (e.g., phpMyAdmin or MySQL CLI).

### 8. Build Front-End Assets
Compile the front-end assets (using Vite):
```bash
npm run dev
```
*(Keep this terminal running in the background)*

### 9. Run the Local Development Server
Open a **new terminal window**, navigate to your project directory, and start the Laravel development server:
```bash
php artisan serve
```

### 10. Access the Application
Your application should now be accessible in your web browser at:
[http://localhost:8000](http://localhost:8000)

---

## Troubleshooting
- If you encounter a `500 Server Error`, ensure your `.env` file is properly configured and the database is running.
- Make sure your local MySQL server is running before executing migrations.
