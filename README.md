# Task Management API

A clean, modular Laravel 12 API for managing tasks with features like filtering, assignment, and authentication using Sanctum. Built with testing and scalability in mind.

---

## Features

* User Registration & Login (Sanctum-based authentication)
* Task Creation, Update, Deletion
* Task Filtering by Status, Priority, and Due Date
* Task Sorting by Due Date
* Assign Task to a Specific User
* API Versioning (`/api/v1/...`)
* Structured JSON Responses
* Feature & Unit Tests with PHPUnit

---

## Tech Stack

* **Framework:** Laravel 12
* **Authentication:** Laravel Sanctum
* **Database:** MySQL
* **Testing:** PHPUnit (v11)
* **Documentation:** Scribe

---

##  Installation

```bash
# Clone the repository
git clone https://github.com/moinulibr/task-management-api.git
cd task-management-api

# Install dependencies
composer install

# Copy env and configure
cp .env.example .env

# Generate app key
php artisan key:generate

# Setup your database config in `.env`
# DB_DATABASE=your_database_name
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate
```

---

##  Running the Application

```bash
php artisan serve
```

---

## Running Tests

```bash
# Ensure .env.testing has:
APP_ENV=testing
APP_KEY=
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

php artisan test
```

---

## API Documentation

Scribe is used to generate API documentation.

```bash
php artisan scribe:generate
```

Then visit: `http://localhost:8000/docs`

---

## Available Routes

Run this to view all available endpoints:

```bash
php artisan route:list
```

---

## Example Endpoints

```http
POST /api/v1/register
POST /api/v1/login
GET  /api/v1/tasks
POST /api/v1/tasks
PUT  /api/v1/tasks/{id}
DELETE /api/v1/tasks/{id}
GET  /api/v1/tasks?status=Done&priority=High&sort=-due_date
POST /api/v1/tasks/{id}/assign
```

🔹 `assign` Endpoint Body:

```json
{
  "user_id": 2
}
```

---

## 📄 License

MIT
