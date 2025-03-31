# Job Board Application

This is a Laravel-based job board application that allows users to filter and paginate job listings based on various criteria.

---

## Features

- Job filtering by attributes such as languages, locations, categories, and more.
- Pagination support for job listings.
- API-first design for seamless integration.

---

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- SQLite (or another database supported by Laravel)

### Installation

1. Clone the repository:

   ```bash
   From main branch
   git clone https://github.com/MostafaHassanHelal/job-board.git
   cd job-board

2. Install PHP dependencies:
    ```bash 
    composer install

3. Create a .env file:
    ```bash
    cp .env.example .env

4. ```bash
    php artisan key:generate
   
5. ```bash
    php artisan migrate
    php artisan db:seed

6.  ```bash
    php artisan serve

The application will be available at http://localhost:8000.



Base URL
All API endpoints are prefixed with the base URL: http://localhost:8000/api.

Endpoints
1. Get Job Listings
Endpoint: /api/jobs
Method: GET
Description: Retrieve a paginated list of jobs with optional filtering.

Query Parameters:

Parameter	Type	    Description
filter	    string	    A filter string to narrow down job results.
page	    int	        The page number to retrieve.