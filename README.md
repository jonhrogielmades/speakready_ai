# SpeakReady AI

SpeakReady AI is a comprehensive web application for interview preparation. Follow these instructions to clone, configure, and run the project locally.

## Requirements

Before you begin, ensure you have the following installed on your machine:
- **PHP** 8.1 or higher
- **Composer** (Dependency Manager for PHP)
- **Node.js** & **npm** (JavaScript runtime and package manager)
- **MySQL** (or any preferred database)
- **Git**

---

## 🚀 Full Configuration & Setup Guide

### 1. Clone the Repository
Open your terminal and clone the repository to your local machine:
```bash
git clone https://github.com/jonhrogielmades/speakready_ai.git
cd speakready_ai
```

### 2. Install Dependencies
Install all the required PHP and front-end dependencies:
```bash
# Install PHP dependencies
composer install

# Install JavaScript/CSS dependencies
npm install
```

### 3. Configure Environment Variables
Copy the example environment file and create your own `.env` file:
```bash
cp .env.example .env
```
*(On Windows Command Prompt, use `copy .env.example .env`)*

Open the `.env` file in your editor and update the following key configurations:

**Application & Database:**
```env
APP_NAME="SpeakReady AI"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=speakready_ai   # Create this database in your SQL manager
DB_USERNAME=root            # Your database username
DB_PASSWORD=                # Your database password
```

**Mail Configuration (SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=capstonespeakreadyai@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="capstonespeakreadyai@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 4. Generate Application Key
Generate a new application key. This will automatically update your `.env` file securely:
```bash
php artisan key:generate
```

### 5. Run Database Migrations & Seeders
Make sure your database server is running and you have created the `speakready_ai` database. Then run the migrations to create the tables:
```bash
php artisan migrate
```
*(If you have sample data seeders available, you can run `php artisan migrate --seed`)*

### 6. Link Storage
Create a symbolic link for the storage folder so that public assets (like uploaded images) are accessible:
```bash
php artisan storage:link
```

### 7. Build Front-End Assets
Compile the front-end assets (using Vite):
```bash
# For local development (keeps watching for changes):
npm run dev

# Or compile for production:
# npm run build
```
*(Keep this terminal running in the background if using `npm run dev`)*

### 8. Run the Local Development Server
Open a **new terminal window**, navigate to your project directory, and start the Laravel development server:
```bash
php artisan serve
```

### 9. Access the Application
Your application should now be accessible in your web browser at:
[http://localhost:8000](http://localhost:8000)

---

## 🛠 Troubleshooting
- **500 Server Error**: Ensure your `.env` file is properly configured, the `APP_KEY` has been generated, and the database is running.
- **Vite/CSS not loading**: Ensure you are running `npm run dev` in a separate terminal.
- **Images not loading**: Run `php artisan storage:link`.
- **View not found or cache issues**: Run `php artisan optimize:clear`.
# speakready_ai
# speakready_ai
