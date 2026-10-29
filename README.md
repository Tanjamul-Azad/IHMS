# IHMS: Integrated Healthcare Management System

A PHP and MySQL web application that brings the main workflows of a clinic into one place: patients, doctors, appointments, prescriptions, a blood bank, ambulance information and a chatbot.

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Frontend preview](https://img.shields.io/badge/Frontend-preview-000000?style=flat-square&logo=vercel&logoColor=white)](https://ihms-frontend-preview.vercel.app/)

## Features

- **Role-based access** for admins, doctors and patients, with a different dashboard and navigation for each
- **Appointments and prescriptions** for booking visits and recording treatment
- **Patient records** with a viewable medical history
- **Blood bank** for managing donors and letting patients request blood
- **Chatbot** for basic health-system questions
- **Admin area** for managing users and data

## Tech stack

PHP, MySQL, HTML, CSS and JavaScript. Database config is read from a `.env` file.

## Getting started

Requires PHP 8+, MySQL and a web server (Apache or XAMPP).

```bash
git clone https://github.com/Tanjamul-Azad/IHMS.git
cd IHMS
cp .env.example .env     # set DB_HOST, DB_USER, DB_PASS, DB_NAME
```

1. Create a database and import `healthcare_db.sql`.
2. Optional: run `db_seed_demo.php` once to load demo data.
3. Serve the folder with Apache or `php -S localhost:8000`, then open the site in your browser.

## Documentation

- [project_documentation.md](project_documentation.md): how the blood bank and chatbot modules work
- [LARAVEL-MIGRATION-PLAN.md](LARAVEL-MIGRATION-PLAN.md): plan for moving the app to Laravel

## Author

Built by [Tanzamul Azad](https://tanzamul.dev).
