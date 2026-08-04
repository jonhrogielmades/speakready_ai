# SpeakReady AI

SpeakReady AI is a comprehensive web application for interview preparation. Follow these instructions to clone, configure, and run the project locally.

## Requirements

Before you begin, ensure you have the following installed on your machine:
- **PHP** 8.2 or higher
- **Composer** (Dependency Manager for PHP). Make sure `php` is available on PATH before running `composer install`.
- **Node.js** 20.19 or higher, or 22.12 or higher, with **npm** 10 or higher
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
Install all the required PHP and front-end dependencies. Make sure you are in the project root directory.
```bash
# Install PHP dependencies
composer install

# Install JavaScript/CSS dependencies
npm install
```

If you are using Laragon and `composer install` fails with `php is not recognized`, open Laragon's terminal or add Laragon's active PHP folder to PATH before running Composer.

### 3. Configure Environment Variables
Copy the example environment file to create your active `.env` file:
```bash
cp .env.example .env
```
*(On Windows Command Prompt, use `copy .env.example .env`, or simply copy and rename it in File Explorer)*

Open the `.env` file in your code editor and update the following essential configurations:

**Application & Database:**
```env
APP_NAME="SpeakReady AI"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=speakready_ai   # Create this empty database in your SQL manager first
DB_USERNAME=root            # Your database username (usually 'root' locally)
DB_PASSWORD=                # Your database password (leave blank if none)
```

**Render Production Database:**
Set these core production variables in the Render service dashboard:
```env
APP_NAME="SpeakReady AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-service.onrender.com
SESSION_SECURE_COOKIE=true
```

If `APP_URL` is missing or accidentally left as `http://localhost`, the Render
start script and app config fall back to Render's `RENDER_EXTERNAL_URL`. For a
custom domain, set `APP_URL` to that exact HTTPS domain.

For Render Postgres, prefer the full connection URL instead of splitting the
credentials into separate `DB_*` values:
```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://user:password@host:5432/database
```

Use the Internal Database URL only when the Render web service and Render
Postgres database are in the same account and region. If they are not, use the
full External Database URL from the database Connect/Info page. Do not use only
a partial Render host such as `dpg-...-a` for `DB_HOST`; that can produce
`could not translate host name ... to address`.

If production still has only a partial `DB_HOST`, the Render start script expands
it to the Singapore public Postgres hostname by default. Set
`RENDER_POSTGRES_REGION=oregon`, `frankfurt`, `ohio`, or `virginia` if your
database was created in a different Render region.

**Mail Configuration (SMTP):**
*(If you are using Gmail, you MUST use a Google "App Password" instead of your regular password)*
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=capstonespeakreadyai@gmail.com
MAIL_PASSWORD=your_app_password   # Generate a 16-character App Password in Google Account settings
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="capstonespeakreadyai@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Render Free web services cannot send through SMTP ports `25`, `465`, or `587`.
For Gmail SMTP on Render, use a paid Render instance. On a Free Render instance,
use an HTTPS/API mail provider instead of SMTP.

**Render Free Password Reset Email:**
For a free Render web service, use Brevo's HTTPS API instead of SMTP:
```env
BREVO_API_KEY=your_brevo_api_key
MAIL_FROM_ADDRESS=your_verified_brevo_sender@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Create a free Brevo account, verify the sender email/domain, and generate an API
key from Brevo's SMTP & API settings. The app will automatically use Brevo for
password reset emails when `BREVO_API_KEY` is present.

### 4. Generate Application Key
Generate a new cryptographic key for the application. This will automatically update your `.env` file securely:
```bash
php artisan key:generate
```

### 5. Setup the Database
Make sure your database server (e.g., MySQL via XAMPP, Laragon, or Docker) is running and you have created an empty database named `speakready_ai`.

**Option A: Import Database Dump (Recommended)**
Since a `database_dump.sql` file is included in the root folder, you can import it directly using your preferred MySQL client (phpMyAdmin, HeidiSQL, Laragon's default database client, etc.) or via terminal:
```bash
mysql -u root -p speakready_ai < database_dump.sql
```

**Option B: Run Migrations**
Alternatively, if you prefer to build the schema from scratch, run the Laravel migrations:
```bash
php artisan migrate
```
*(If you have seeders to populate initial data, run: `php artisan migrate --seed`)*

### 6. Link Storage
Create a symbolic link for the storage folder so that public assets (like uploaded images or audio files) are accessible to the browser:
```bash
php artisan storage:link
```

### 7. Build Front-End Assets
Compile the front-end assets using Vite.

For local development (this command will keep running and watch for changes):
```bash
npm run dev
```

*(Keep this terminal running in the background. Open a new terminal tab/window for the next step.)*

### 8. Run the Local Development Server
In a **new terminal window** inside your project directory, start the Laravel development server:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 9. Access the Application
Your application should now be fully functional and accessible in your web browser at:
[http://localhost:8000](http://localhost:8000)

---

## 🛠 Troubleshooting
- **500 Server Error**: Ensure your `.env` file is properly configured, the `APP_KEY` has been generated, and your MySQL database is actively running.
- **Vite or CSS not loading**: Ensure you are running `npm run dev` in a separate terminal and haven't closed it.
- **Images/Media not loading**: Run `php artisan storage:link` to ensure the symbolic link is created correctly.
- **View not found or cache issues**: Run `php artisan optimize:clear` to clear all compiled caches.
- **Email not sending**: Double-check your Google App Password and ensure your Gmail account has 2-Step Verification enabled to allow App Passwords.
# speakready_ai
