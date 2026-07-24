# SHE Inspection System - Backend API

Sistem pemeriksaan Safety, Health, dan Environment (SHE) yang komprehensif untuk mengelola inspeksi peralatan pemadam api, pelaporan insiden, dan audit keselamatan kerja.

## Technology Stack

- **Framework**: Laravel 11.x
- **PHP**: 8.0+
- **Authentication**: Laravel Sanctum
- **Database**: MySQL 8.0+ (utf8mb4_unicode_ci)
- **Testing**: PHPUnit 11.x

## Fitur Utama

### 1. Manajemen Organisasi
- Hierarki: Company → Branch → Division → Department → Section
- Manajemen lokasi dan area
- Kategori inspeksi yang dapat dikustomisasi

### 2. Autentikasi & Authorization
- Role-based access control (Super Admin, Admin, Inspector, Viewer)
- User groups dengan sistem permissions
- API token authentication via Laravel Sanctum

### 3. Inspeksi Peralatan
- **Fire Hydrant**: Inspeksi hidran pemadam api
- **Fire Extinguisher**: Inspeksi tabung pemadam api
- **Fire Alarm**: Inspeksi alarm kebakaran
- **Emergency Shower/Eye Wash**: Inspeksi perkakas darurat
- **General Checklist**: Inspeksi umum yang dapat disesuaikan

Setiap inspeksi mencakup:
- Check-in dengan GPS tracking
- Digital signature
- Dokumentasi foto (before/after)
- Status tracking (draft, completed, signed)

### 4. Pelaporan Insiden
- Registrasi insiden dengan tipe dan level keparahan
- Investigasi insiden
- Upload dokumentasi
- Tracking status insiden

### 5. Laporan Medis
- Pelaporan cedera/kecelakaan kerja
- Tracking pemulihan dan estimasi hari kerja hilang

### 6. QR Code Asset Tracking
- Generate QR code untuk asset
- Scan QR code untuk memulai inspeksi
- GPS validation saat check-in

## Struktur Database

Database terdiri dari 15+ tabel utama:

- **Organization**: companies, branches, divisions, departments, sections
- **Users & Auth**: users, user_groups, user_group_members, permissions, api_tokens
- **Locations**: location_types, locations, areas
- **Inspections**: fire_hydrant_inspections, fire_extinguisher_inspections, fire_alarm_inspections, es_ew_inspections, inspection_checklists
- **Incidents**: incidents, incident_images, incident_investigations
- **Medical**: medical_reports
- **System**: audit_logs, system_config

Lihat [schema.sql](./schema.sql) untuk struktur lengkap.

## Installation & Setup

### Prerequisites

- PHP 8.0 atau lebih tinggi
- Composer 2.x
- MySQL 8.0+
- Node.js 16+ (opsional, untuk asset compilation)

### Langkah-langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd backend/she-api
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   # Edit file .env sesuai dengan konfigurasi database
   ```
   
   Pastikan konfigurasi berikut di file `.env`:
   ```env
   APP_NAME=SHE Inspection System
   APP_ENV=local
   APP_KEY=base64:GENERATE_KEY_USING_PHP_ARTISAN_KEY_GENERATE
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=she_inspection
   DB_USERNAME=root
   DB_PASSWORD=

   SANCTUM_STATEFUL_DOMAINS=localhost:8000
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Setup Database**
   
   Opsi A - Menggunakan migration Laravel:
   ```bash
   php artisan migrate
   ```
   
   Opsi B - Menggunakan SQL schema lengkap:
   ```bash
   mysql -u root -p < ../../schema.sql
   ```

6. **Storage Link (Opsional)**
   ```bash
   php artisan storage:link
   ```

7. **Jalankan Server**
   ```bash
   php artisan serve --port=8000
   ```
   
   Atau menggunakan script yang disediakan:
   ```bash
   # Windows PowerShell
   .\start-she-server.ps1
   ```

Server akan berjalan di `http://localhost:8000`

## API Documentation

Base URL: `http://localhost:8000/api`

### Autentikasi

Semua endpoint memerlukan Bearer token kecuali login dan register:
```
Authorization: Bearer <token>
```

### Endpoints Utama

#### 1. Authentication
- `POST /api/auth/login` - Login user
- `POST /api/auth/register` - Register user baru
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user
- `PUT /api/auth/profile` - Update profile

#### 2. Dashboard
- `GET /api/dashboard/stats` - Statistik dashboard
- `GET /api/dashboard/recent-inspections` - Inspeksi terbaru
- `GET /api/dashboard/incident-summary` - Ringkasan insiden

#### 3. Master Data (CRUD)
- `GET|POST /api/companies`
- `GET|POST /api/branches`
- `GET|POST /api/divisions`
- `GET|POST /api/departments`
- `GET|POST /api/sections`
- `GET|POST /api/locations`
- `GET|POST /api/areas`
- `GET|POST /api/categories`
- `GET|POST /api/users`

#### 4. QR Code Management
- `GET /api/qr-codes/generate/{assetType}/{assetId}` - Generate QR
- `POST /api/qr-codes/scan` - Scan QR code
- `GET /api/qr-codes` - List all QR codes

#### 5. Inspeksi
- **Fire Hydrant**: `GET|POST /api/fire-hydrants`
- **Fire Extinguisher**: `GET|POST /api/fire-extinguishers`
- **Fire Alarm**: `GET|POST /api/fire-alarms`
- **ES/EW**: `GET|POST /api/es-ew`
- **Checklist**: `GET|POST /api/checklists`

Setiap module memiliki endpoints:
- `GET /{id}` - Detail
- `PUT /{id}` - Update
- `DELETE /{id}` - Delete
- `POST /{id}/checkin` - Check-in dengan GPS
- `POST /{id}/sign` - Digital signature

#### 6. Incidents
- `GET|POST /api/incidents`
- `GET /api/incidents/{id}`
- `PUT /api/incidents/{id}`
- `DELETE /api/incidents/{id}`
- `POST /api/incidents/{id}/images` - Upload gambar
- `POST /api/incidents/{id}/investigate` - Buat investigation
- `GET /api/incident-types` - List tipe insiden
- `GET /api/incident-levels` - List level keparahan

#### 7. Medical Reports
- `GET|POST /api/medical-reports`
- `GET /api/medical-reports/{id}`
- `PUT /api/medical-reports/{id}`
- `DELETE /api/medical-reports/{id}`

#### 8. Upload
- `POST /api/upload` - Upload file (multipart/form-data, key: `file`)

Lihat [api-contract.md](../api-contract.md) untuk dokumentasi API lengkap dengan contoh request dan response.

## Default Credentials

Setelah install database dengan schema.sql:

```
Email: admin@sheinspection.com
Password: admin123
Role: super_admin
```

**Catatan**: Ganti password default setelah instalasi pertama!

## User Roles

- **super_admin**: Akses penuh ke semua fitur
- **admin**: Manajemen master data dan monitoring
- **inspector**: Membuat dan mengelola inspeksi
- **viewer**: Hanya membaca data

## Testing

Jalankan test suite:
```bash
php artisan test
```

Atau menggunakan PHPUnit langsung:
```bash
vendor/bin/phpunit
```

## Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: Migration already exists
```bash
# Hapus tabel yang bermasalah dari database atau
php artisan migrate:fresh --seed
```

### Permission denied pada storage
```bash
# Windows (PowerShell as Administrator)
icacls storage /grant "IIS_IUSRS:(OI)(CI)F"

# Linux/Mac
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Folder Structure

```
she-api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/          # API Controllers
│   ├── Models/               # Eloquent Models
│   └── Services/             # Business Logic
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── routes/
│   ├── api.php               # API routes
│   └── web.php               # Web routes
├── storage/
│   └── app/
│       └── public/           # Uploaded files
└── resources/
    └── views/                # Blade templates (jika ada web interface)
```

## API Response Format

### Success Response
```json
{
  "status": "success",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "errors": { ... }
}
```

### Paginated Response
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

## Security Features

- Laravel Sanctum untuk API authentication
- Rate limiting pada endpoint sensitif
- GPS validation untuk check-in inspeksi
- Audit log untuk semua aktivitas penting
- Role-based access control
- SQL injection protection via Eloquent ORM
- XSS protection

## Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

Proprietary - PT. Ecogreen Oleochemicals Indonesia

## Support

Untuk pertanyaan atau issue, silakan hubungi tim IT Ecogreen.

---

**Version**: 1.0.0  
**Last Updated**: July 2026