# Portal Layanan SDM PUSRI

Portal web satu pintu untuk aplikasi layanan SDM PT Pupuk Sriwidjaja Palembang, dilengkapi panel admin untuk mengelola layanan, kategori, banner, dan akun admin.

**M. Zacky Irwansyah** (062430701384) · Kerja Praktik Departemen Teknologi Informasi PSP · 03 Agustus – 03 November 2026

**Teknologi:** PHP Native · MySQL/MariaDB (XAMPP) · Keycloak 26.7.3 (login admin, OpenID Connect) · MinIO (penyimpanan gambar)

---

## Isi Repository

```
portal-pusri/                       Kode aplikasi
database/portal_pusri.sql           Struktur dan data database
keycloak/pusri-realm.json           Export realm "pusri" (client, role ADMIN, user)
minio/portal-pusri/                 Isi bucket (ikon layanan dan banner)
secure/portal-pusri-secrets.example.php   Contoh file kredensial
```

File kredensial asli (`portal-pusri-secrets.php`) tidak disertakan dan dikirim secara terpisah.

## Kebutuhan

- XAMPP (Apache + MySQL)
- Composer
- Java JDK 21
- [Keycloak 26.7.3](https://www.keycloak.org/downloads) (versi ZIP)
- [MinIO Server](https://min.io/download) untuk Windows

## Instalasi

### 1. Kode dan kredensial
```
C:\xampp\htdocs\portal-pusri\            <- isi folder portal-pusri
C:\xampp\secure\portal-pusri-secrets.php <- file kredensial
```
Lalu pasang dependency:
```
cd C:\xampp\htdocs\portal-pusri
composer install
```

### 2. Database
1. Buat database `portal_pusri` (utf8mb4_general_ci) di phpMyAdmin, lalu import `database/portal_pusri.sql`.
2. Jalankan di tab SQL (password sesuai `mysql_password` di file kredensial):
```sql
CREATE USER 'portal_pusri_user'@'localhost' IDENTIFIED BY '<mysql_password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON portal_pusri.* TO 'portal_pusri_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Keycloak
Ekstrak Keycloak ke `C:\Keycloak`, salin `keycloak/pusri-realm.json` ke folder tersebut, lalu:
```
cd C:\Keycloak\keycloak-26.7.3
bin\kc.bat import --file pusri-realm.json
bin\kc.bat start-dev
```
Pada start pertama, buat akun admin konsol melalui http://localhost:8080.

### 4. MinIO
```
cd C:\Minio
set MINIO_ROOT_USER=<minio_access_key>
set MINIO_ROOT_PASSWORD=<minio_secret_key>
minio.exe server C:\Minio\Data --console-address ":9001"
```
Buka http://localhost:9001, buat bucket `portal-pusri` (Private), lalu unggah folder `applications` dan `banners` dari `minio/portal-pusri/`.

### 5. Jalankan
Start Apache dan MySQL di XAMPP, pastikan MinIO dan Keycloak berjalan, lalu buka:

http://localhost/portal-pusri/

Login admin menggunakan akun dengan realm role **ADMIN** di realm `pusri`.

## Konfigurasi

| Item | Nilai |
|---|---|
| Keycloak realm | `pusri` |
| Client login | `portal-pusri` (confidential) |
| Redirect URI | `http://localhost/portal-pusri/callback.php` |
| Client kelola admin | `portal-pusri-admin-api` (service account) |
| Role admin | `ADMIN` (realm role) |
| MinIO endpoint | `http://127.0.0.1:9000` |
| MinIO bucket | `portal-pusri` |

Alamat `localhost` dapat diubah pada folder `config/`, `logout.php`, dan `admin/kelola-admin.php` jika aplikasi dipindahkan ke server lain.

## Catatan

- Jika file kredensial tidak ditemukan di `C:\xampp\secure\`, aplikasi tidak dapat dibuka (error 500).
- Jika gambar tidak tampil, pastikan MinIO sudah berjalan.
- Keycloak dan MinIO memakai port 8080 dan 9000/9001; matikan Tomcat XAMPP jika bentrok.

---

Terima kasih atas bimbingan dan kesempatan yang telah diberikan.
