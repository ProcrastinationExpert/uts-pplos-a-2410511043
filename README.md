# 🛒 UMKM Microservices API

> Sistem backend berbasis **microservices** untuk aplikasi UMKM, terdiri dari **User Service** dan **Product Service** yang berjalan secara independen dan terisolasi.

---

## 🗂️ Daftar Isi

- [Arsitektur Sistem](#-arsitektur-sistem)
- [User Service](#-user-service)
- [Product Service](#-product-service)

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────┐
│                   CLIENT / FRONTEND                 │
└──────────────────────┬──────────────────────────────┘
                       │
          ┌────────────┴────────────┐
          │                         │
          ▼                         ▼
┌─────────────────┐       ┌──────────────────────┐
│   User Service  │       │   Product Service    │
│   (Port 3001)   │       │   (Port 3002)        │
│                 │       │                      │
│  Node.js +      │       │  Laravel (PHP)       │
│  Express.js     │       │  MySQL               │
│  MySQL + JWT    │       │  Service Pattern     │
└─────────────────┘       └──────────┬───────────┘
                                     │
                              Inter-Service
                              Communication
                          (check-stock endpoint)
```

| Service         | Port   | Stack                | Database                 |
| --------------- | ------ | -------------------- | ------------------------ |
| User Service    | `3001` | Node.js + Express.js | MySQL (`uts_db_user`)    |
| Product Service | `3002` | Laravel (PHP)        | MySQL (`uts_db_product`) |

---

## 👤 User Service

Bertanggung jawab menangani **autentikasi pengguna**, manajemen profil, dan keamanan akses menggunakan **JWT** serta **GitHub OAuth**.

### 🛠️ Teknologi

| Komponen       | Teknologi                    |
| -------------- | ---------------------------- |
| Runtime        | Node.js                      |
| Framework      | Express.js                   |
| Database       | MySQL (dengan `mysql2`)      |
| Authentication | JWT + `bcryptjs`             |
| OAuth          | GitHub OAuth API (via Axios) |

---

### ⚙️ Instalasi & Konfigurasi

#### 1. Clone & Install Dependencies

```bash
cd user-service
npm install
```

#### 2. Setup Database

Buat database baru di MySQL dengan nama `uts_db_user`, lalu jalankan query berikut:

```sql
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  email       VARCHAR(255) UNIQUE NOT NULL,
  password    VARCHAR(255),
  github_id   VARCHAR(255) UNIQUE,
  role        ENUM('admin', 'customer') DEFAULT 'customer',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 3. Konfigurasi Environment (`.env`)

Buat file `.env` di root folder, lalu isi sesuai konfigurasi berikut:

```env
PORT=3001
DB_HOST=localhost
DB_USER=root
DB_PASS=root
DB_NAME=uts_db_user

JWT_SECRET=rahasia_jwt_super_aman
JWT_EXPIRES_IN=1d

GITHUB_CLIENT_ID=isi_dengan_client_id_dari_github
GITHUB_CLIENT_SECRET=isi_dengan_client_secret_dari_github
GITHUB_CALLBACK_URL=http://localhost:3001/api/auth/github/callback
```

#### 4. Menjalankan Service

```bash
# Mode Development
npm run dev

# Mode Production
npm start
```

---

### 📡 API Endpoints

> Semua respons API dikembalikan dalam format **JSON**.

#### 🔐 Autentikasi Manual (Email & Password)

| Method | Endpoint             | Deskripsi            | Body                        |
| ------ | -------------------- | -------------------- | --------------------------- |
| `POST` | `/api/auth/register` | Daftarkan user baru  | `name`, `email`, `password` |
| `POST` | `/api/auth/login`    | Login & dapatkan JWT | `email`, `password`         |

#### 🐙 GitHub OAuth (SSO)

| Method | Endpoint                    | Deskripsi                         |
| ------ | --------------------------- | --------------------------------- |
| `GET`  | `/api/auth/github/url`      | Mendapatkan URL Login GitHub      |
| `GET`  | `/api/auth/github/callback` | Callback dari GitHub → return JWT |

#### 🔒 Protected Routes

> Wajib menyertakan header: `Authorization: Bearer <token_jwt>`

| Method | Endpoint            | Deskripsi                                |
| ------ | ------------------- | ---------------------------------------- |
| `GET`  | `/api/auth/profile` | Ambil data profil user yang sedang login |

---

## 📦 Product Service

Layanan utama yang bertanggung jawab penuh untuk mengelola **katalog produk**, **galeri gambar**, dan **pengecekan stok** (Inter-service communication) untuk digunakan oleh Order Service.

### 🛠️ Teknologi

| Komponen  | Teknologi                                        |
| --------- | ------------------------------------------------ |
| Framework | Laravel                                          |
| Language  | PHP                                              |
| Database  | MySQL                                            |
| Pattern   | Service Pattern (`Controller → Service → Model`) |

---

### ⚙️ Instalasi & Konfigurasi

#### 1. Clone & Install Dependencies

```bash
cd product-service
composer install
```

#### 2. Konfigurasi Environment (`.env`)

Salin file `.env.example` menjadi `.env`, lalu sesuaikan konfigurasi berikut:

```env
APP_URL=http://localhost:3002

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=4408
DB_DATABASE=uts_db_product
DB_USERNAME=root
DB_PASSWORD=root
```

#### 3. Migrasi & Seeder Database

Perintah ini akan membuat struktur tabel (`Categories`, `Products`, `Product_Images`, `Reviews`) beserta data dummy-nya:

```bash
php artisan migrate:fresh --seed
```

#### 4. Menjalankan Service

Jalankan pada **Port 3002** agar tidak berbenturan dengan service lainnya:

```bash
php artisan serve --port=3002
```

---

### 📡 API Endpoints

> Semua endpoint wajib menggunakan header berikut:
>
> - `Accept: application/json`
> - `Content-Type: application/json` _(khusus POST & PUT)_

#### 📋 CRUD Produk Utama

| Method   | Endpoint             | Deskripsi                                                |
| -------- | -------------------- | -------------------------------------------------------- |
| `GET`    | `/api/products`      | Ambil semua produk (mendukung pagination & filtering)    |
| `POST`   | `/api/products`      | Tambah produk baru (slug di-generate otomatis)           |
| `GET`    | `/api/products/{id}` | Ambil detail 1 produk (beserta relasi kategori & gambar) |
| `PUT`    | `/api/products/{id}` | Ubah data produk (mendukung partial update)              |
| `DELETE` | `/api/products/{id}` | Hapus produk → return `204 No Content`                   |

#### 🔗 Komunikasi Inter-Service

> Endpoint ini dirancang khusus untuk dikonsumsi oleh **Order Service** guna memvalidasi ketersediaan stok sebelum transaksi diproses.

| Method | Endpoint                                    | Deskripsi                    |
| ------ | ------------------------------------------- | ---------------------------- |
| `GET`  | `/api/products/{id}/check-stock?quantity=2` | Cek ketersediaan stok produk |

---

## 📝 Catatan Arsitektur

- **Tabel `product_images`** dipisah menggunakan relasi **One-to-Many** dengan fitur `is_primary` untuk menentukan foto cover produk — menjaga performa database dan fleksibilitas galeri UMKM.
- Setiap service berjalan di **port terpisah** agar terisolasi dan tidak saling berbenturan.
- Inter-service communication dilakukan langsung via HTTP request ke endpoint `check-stock`.

---

<div align="center">
  Made with ❤️ for Muhammad Azzami Yahya
</div>
