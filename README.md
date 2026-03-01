<p align="center">
    <a href="https://canonizer.com" target="_blank" style="border-width:0;"><img src="https://canonizer-public-file.s3.us-east-2.amazonaws.com/site-images/logo.svg" alt="Canonizer" /></a>
    <br>
    <span style="font-size:12px;">Version: 3.0 (Unified API)</span>
</p>

<p align="center">
    <a href="https://canonizer.com/" target="_blank" style="color: #FFF;">View Demo</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-3.0-api/issues" target="_blank" style="color: #FFF;">Report Bug</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-3.0-api/issues" target="_blank" style="color: #FFF;">Request New Feature</a>
</p>

<!-- Table of content -->
# Objective
This project is the unified API backend for Canonizer 3.0, migrated from Lumen to **Laravel 11** and updated for **PHP 8.4**. It consolidates the legacy `canonizer-service` (v1) and the original `canonizer-api` (v3) into a single, high-performance service that manages both MySQL (transactional data) and MongoDB (topic trees/timelines).

<!-- About Project Section -->
# About the Project
A wiki system that solves the critical liabilities of Wikipedia. It solves petty "edit wars" by providing contributors the ability to create and join camps and present their views without having them immediately erased. It also provides ways to standardize definitions and vocabulary, especially important in new fields.

## Key Modernizations
- **Framework**: Migrated from Lumen 8.x to Laravel 11.x.
- **PHP Support**: Fully compatible with PHP 8.4 (using `AllowDynamicProperties` and updated type-hinting).
- **Consolidation**: Unified `v1` and `v3` route namespaces into a single application.
- **Dual Database Support**: seamless integration between MySQL and MongoDB.

## Dependent Modules & Services
- **Canonizer Frontend**: Next.js based frontend.
- **Mailtrap**: Email delivery for sandbox environment.
- **SendinBlue**: Email delivery for production environment.
- **Supervisord**: For managing background queues (Topic tree caching, Notifications).

## Architecture & Design
The application follows a Service-Oriented Architecture (SOA), providing RESTful APIs for the frontend. 
- **MySQL**: Primary store for topics, camps, users, and support data.
- **MongoDB**: Optimized store for hierarchical topic trees and timelines.

Please follow the [link](https://drive.google.com/file/d/1ByCvgzlgwuKUcOMG_OAAb2eKnWCN-HXb/view?usp=share_link) for high level architecture and design.

## Application Queuing System
Long-running jobs (MongoDB tree generation, Notifications) are handled via Laravel Queues.
- **Caching**: Topic trees are pre-rendered and stored in MongoDB via the `tree:all` command.
- **Notifications**: Email and Push notifications are dispatched via background jobs.

<!-- About Setup Section -->
# Getting Started
## Setup Development Environment
### Prerequisites
- **PHP >= 8.4**
    - BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, MongoDB extensions.
- **MySQL >= 8.0**
- **MongoDB >= 6.0**
- **Git**
- **Composer**

### Installation
1. **Clone the repository**:
    ```sh
    git clone git@github.com:the-canonizer/canonizer-3.0-api.git
    cd canonizer-3.0-api
    ```
2. **Install dependencies**:
    ```sh
    composer install
    ```
3. **Environment Setup**:
    ```sh
    cp .env.example .env
    ```
    Update your `.env` file with MySQL, MongoDB, and Cache settings:
    ```bash
    DB_CONNECTION=mysql
    DB_DATABASE=canonizer3_mono
    
    MONGODB_DSN=mongodb://127.0.0.1:27017
    MONGODB_DB=Canonizer
    
    CACHE_STORE=file # Recommended for local dev
    
    # Testing Environment (optional)
    DB_DATABASE_TEST=canonizer_testing
    ```
4. **Generate App Key**:
    ```sh
    php artisan key:generate
    ```
5. **Run Migrations**:
    ```sh
    php artisan migrate
    ```
6. **Populate Topic Trees (MongoDB)**:
    ```sh
    php artisan tree:all
    ```
7. **Populate Timelines (MongoDB)**:
    ```sh
    php artisan timeline:all
    ```

### Running Locally
You can use Laravel's built-in server or a virtual host:
```sh
php artisan serve
```
Verification endpoint (local): `http://127.0.0.1:8000/api/v3/canonizer/api/get-version`

<!-- Verification Process -->
- **Verification Output**:
    ```json
    { "version": "Laravel 11.x (PHP 8.4.x)" }
    ```

## Contribution
1. **Create Branch**: `git checkout -b feature/your-feature-name`
2. **Commit Changes**: Use clear, descriptive messages.
3. **Pull Latest changes**: `git pull origin development`
4. **Push & PR**: Create a Pull Request on GitHub.

## Run Test Cases
```sh
php artisan test
```
Or for specific filters:
```sh
    php artisan test --filter=YourTestName
    ```
    
### External Test Runner (Recommended)
You can also run tests directly via PHPUnit for faster feedback:
```sh
php vendor/bin/phpunit tests/TreeGetApiTest.php
php vendor/bin/phpunit tests/TimelineGetApiTest.php
```

### Database for Testing
If you encounter `Base table or view not found` during tests, ensure your testing database is created:
```sql
CREATE DATABASE canonizer_testing;
```
Then copy data from your main database if needed (simulating `canonizer3_mono`'s structure):
```sh
mysqldump -u root canonizer3_mono | mysql -u root canonizer_testing
```

## Help
1. **Clear Config/Cache**:
    ```sh
    php artisan config:clear && php artisan cache:clear
    ```
2. **Remove Duplicate Trees**:
    ```sh
    php artisan tree:remove-duplicate
    ```
3. **List All Routes**:
    ```sh
    php artisan route:list
    ```

# License
Lesser MIT License
Copyright (c) 2006-2026 Canonizer.com

# Contact
Brent Allsop - brent.allsop@gmail.com
Project Link: [https://canonizer.com](https://canonizer.com)
