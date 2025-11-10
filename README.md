# Gutenberg API (Laravel)

REST API for accessing and filtering Project Gutenberg books using Laravel.

## ✅ Features
- Filter books by ID, language, author, title, mime type, topic
- Pagination (25 per page)
- Sorted by popularity (downloads)
- PostgreSQL database
- Swagger API documentation

---

## 📦 Requirements
- PHP >= 8.3
- Composer
- PostgreSQL
- Laravel 12+

---

## 🚀 Installation

### 1. Clone Repository
```bash
git clone https://github.com/anilkumarbind06/gutenberg_backend.git
cd gutenberg_backend
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Copy Environment File
```bash
cp .env.example .env
```

### 4. Configure Database
Update `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gutenberg
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

> ❗ Database tables already exist — no migrations needed.

### 5. Generate App Key
```bash
php artisan key:generate
```

### 6. Clear Cache
```bash
php artisan optimize:clear
```

### 7. Run Server
```bash
php artisan serve
```

App URL:
```
http://localhost:8000
```

---

## 📚 Swagger API Documentation

Generate docs:
```bash
php artisan l5-swagger:generate
```

Open Swagger UI:
```
http://localhost:8000/api/documentation
```

---

## 📎 API Endpoints
- `GET /api/books` — Get filtered books

Example request:
```
/api/books?language=en&author=doyle&mime_type=application/pdf
```

---

## 🛠 Development Commands
Clear cache:
```bash
php artisan optimize:clear
```

Rebuild swagger docs:
```bash
php artisan l5-swagger:generate
```

---

## 🤝 Contributing
Pull requests are welcome.

---

## ⭐ Support

If you like this project, **please star ⭐ the repo!**

---

## 📄 License
MIT