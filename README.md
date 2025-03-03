Malaysia Car Sales Dashboard

A Laravel-based dashboard for visualizing Malaysia's car sales data using a CSV dataset from the Department of Statistics Malaysia (DOSM).

Features

Filter by Year, Month, Maker, Model, and State

Interactive Bar & Pie Charts (Chart.js)

DataTables for Search & Pagination

**Automatic CSV Fetching from **DOSM

Optimized for Large Datasets (Batch Processing & Memory Optimization)

Prerequisites

Ensure you have the following installed:

PHP (>=7.4)

Composer

Laravel (>=8.x)

Node.js & npm (for frontend assets)

Git

MySQL (or any database of choice)

Installation Guide

1️⃣ Clone the Repository

# Replace <your-repo> with your GitHub username/repo

git clone https://github.com/<your-repo>/malaysia-car-sales.git
cd malaysia-car-sales

2️⃣ Install Dependencies

composer install
npm install

3️⃣ Set Up Environment Variables

cp .env.example .env
php artisan key:generate

Modify .env file to configure database connection.

4️⃣ Run Migrations (If Using Database)

php artisan migrate

5️⃣ Start Laravel Development Server

php artisan serve

Access the dashboard at: http://127.0.0.1:8000

Publishing to GitHub

1️⃣ Create a New GitHub Repository

Go to GitHub and create a new repository (e.g., malaysia-car-sales).

Copy the repository URL.

2️⃣ Push Your Code to GitHub

git init
git remote add origin https://github.com/<your-repo>/malaysia-car-sales.git
git add .
git commit -m "Initial commit"
git branch -M main
git push -u origin main

3️⃣ Deploy Using Laravel Forge / Shared Hosting (Optional)

For production deployment, consider using Laravel Forge, DigitalOcean, or a shared hosting provider.

License

This project is licensed under the MIT License.

Credits

Developed by Qusyaire Ezwan. Data sourced from DOSM (Department of Statistics Malaysia).

Contributions

Feel free to contribute! Submit pull requests or open issues on GitHub.
