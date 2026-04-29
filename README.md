# GasNgalam — Backend API (Laravel 11)

Backend REST API untuk aplikasi wisata **GasNgalam Malang**.

---

## ⚡ Cara Menjalankan (pertama kali)

Buka terminal di folder `backend/`, lalu jalankan perintah berikut satu per satu:

```bash
# 1. Install semua dependency PHP
composer install

# 2. Buat file .env dari template
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Sesuaikan database di file .env:
#    DB_DATABASE=gasngalam
#    DB_USERNAME=root
#    DB_PASSWORD=     (kosongkan jika tidak pakai password)

# 5. Buat database MySQL bernama "gasngalam" di phpMyAdmin / HeidiSQL / TablePlus

# 6. Jalankan migrasi + seeder (buat tabel + isi data awal)
php artisan migrate --seed

# 7. Jalankan server
php artisan serve
```

Server berjalan di: **http://localhost:8000**

---

## 🗄️ Konfigurasi .env (wajib sesuaikan)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gasngalam
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

---

## 👤 Akun Default Setelah Seeder

| Role  | Email                 | Password |
|-------|-----------------------|----------|
| Admin | admin@gasngalam.com   | admin123 |

---

## 📋 Daftar API Endpoint

Base URL: `http://localhost:8000/api`

### 🔓 Public (tanpa login)
| Method | Endpoint                          | Fungsi                     |
|--------|-----------------------------------|----------------------------|
| POST   | /register                         | Daftar akun baru           |
| POST   | /login                            | Login                      |
| GET    | /destinations                     | List semua destinasi       |
| GET    | /destinations?q=keyword           | Search destinasi           |
| GET    | /destinations?category=Alam       | Filter by kategori         |
| GET    | /destinations/{id}                | Detail destinasi           |
| GET    | /categories                       | Daftar kategori            |
| GET    | /destinations/{id}/reviews        | List ulasan                |

### 🔐 Perlu Login (Bearer Token)
| Method | Endpoint                          | Fungsi                     |
|--------|-----------------------------------|----------------------------|
| POST   | /logout                           | Logout                     |
| GET    | /me                               | Info user yang login       |
| GET    | /favorites                        | List favorit saya          |
| GET    | /favorites/ids                    | ID favorit saya            |
| POST   | /favorites/{id}                   | Tambah favorit             |
| DELETE | /favorites/{id}                   | Hapus favorit              |
| POST   | /destinations/{id}/reviews        | Tambah ulasan              |
| POST   | /claims                           | Kirim klaim bisnis         |

### 🛡️ Admin Only
| Method | Endpoint                          | Fungsi                     |
|--------|-----------------------------------|----------------------------|
| GET    | /admin/stats                      | Statistik dashboard        |
| GET    | /admin/users                      | List semua user            |
| DELETE | /admin/users/{id}                 | Hapus user                 |
| POST   | /admin/destinations               | Tambah destinasi           |
| PUT    | /admin/destinations/{id}          | Edit destinasi             |
| DELETE | /admin/destinations/{id}          | Hapus destinasi            |
| GET    | /admin/claims                     | List semua klaim           |
| PATCH  | /admin/claims/{id}                | Approve/reject klaim       |
| GET    | /admin/reviews                    | List semua ulasan          |
| DELETE | /admin/reviews/{id}               | Hapus ulasan               |
| PATCH  | /admin/reviews/{id}/report        | Toggle laporan ulasan      |

### Header untuk request yang butuh login:
```
Authorization: Bearer {token_dari_login}
Content-Type: application/json
Accept: application/json
```

---

## 🗂️ Struktur Folder

```
backend/
├── artisan                    ← File untuk jalankan perintah Laravel
├── public/                    ← Entry point web server (index.php)
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   ← Semua API controller
│   │   └── Middleware/        ← AdminMiddleware
│   └── Models/                ← User, Destination, Review, Favorite, BusinessClaim
├── bootstrap/app.php          ← Konfigurasi app & middleware
├── config/cors.php            ← Setting CORS untuk frontend
├── database/
│   ├── migrations/            ← Struktur tabel database
│   └── seeders/               ← Data awal (admin + 10 destinasi)
├── routes/
│   ├── api.php                ← Semua route API
│   └── web.php                ← Route web (minimal)
├── storage/                   ← Cache, log, session (auto-generated)
├── .env                       ← Konfigurasi environment (EDIT INI)
└── composer.json              ← Dependency PHP
```
