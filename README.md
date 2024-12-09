Laravel API Project

This project is a Laravel-based API that includes functionality for managing roles, permissions, tasks, and projects, with authentication features such as login and logout. Below is an overview of the project setup and key functionalities.

Prerequisites

PHP >= 8.0

Composer

MySQL or any compatible database

Postman

Installation Guide

1. Clone the Repository

git clone <repository-url>
cd <repository-folder>

2. Install Dependencies

composer install

3. Setup Environment

Copy the .env.example file to .env and update the database configuration:



4. Run Migrations and Seeders

php artisan migrate 

This will:

Create necessary tables.

Seed the database with default roles, permissions, and an admin user.

5. Generate Application Key

php artisan key:generate

6. Start the Development Server

php artisan serve

Your API will be accessible at http://127.0.0.1:8000.

Key Functionalities

Authentication

Login: POST /api/login

Parameters: email, password

Returns: Auth token on success.

Logout: POST /api/logout

Requires: Bearer token in headers.

Roles and Permissions

Roles and permissions are managed using Spatie's Laravel Permission.

Default Roles:

Admin

User

Project Owner

Default Permissions:

CRUD operations for users, tasks, projects, roles, and permissions.

Tasks Management

Project Management



Default Admin User

Upon running the seeders, a default admin user will be created:

Email: mubashir99955@gmail.com

Password: securepassword

use : default Otp :sdm23

Use this account to log in and manage the application.

API Documentation

For detailed API routes and their parameters, refer to the Postman Collection.
https://documenter.getpostman.com/view/21817867/2sAYBd77tb
