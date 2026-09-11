# SHE Inspection System

Sistem inspeksi **Safety, Health, and Environment (SHE)** untuk inspeksi peralatan
keselamatan, pelaporan inspection/incident, check-in GPS, foto, dan tanda tangan
digital.

Proyek terdiri dari dua aplikasi utama:

| Aplikasi | Teknologi | Lokasi |
|---|---|---|
| **Mobile App** | Flutter/Dart (Android) | [`mobile/`](./mobile) |
| **Backend API** | Laravel 11 / PHP | [`backend/she-api/`](./backend/she-api) |

## Fitur Utama

- **Autentikasi** — login/logout dengan Bearer token (Laravel Sanctum), role-based access control (super_admin, admin, inspector, viewer)
- **Inspeksi peralatan** — Fire Hydrant, Fire Extinguisher (APAR), Fire Alarm, ES/EW (Emergency Shower/Eye Wash), dan checklist umum
- **Permit Matrix** — inspeksi Safe Work Permit dengan kolom Job Performance sebagai dropdown
- **Safety Talk / Training** — pendataan kegiatan safety talk/training di lapangan
- **Inspection / Incident** — pelaporan temuan awal (wajib) dan foto perbaikan (opsional), dengan investigasi
- **QR Code** — generate, scan, dan lookup asset untuk memulai inspeksi cepat
- **GPS Check-In** — verifikasi kehadiran di lokasi saat inspeksi
- **Tanda tangan digital** — sign-off inspeksi oleh inspector
- **Offline Mode & Auto Sync** — inspeksi dapat disimpan sebagai draft lokal dan otomatis disinkronkan saat koneksi kembali, saat app start, dan saat dashboard di-refresh
- **Laporan medis** — pelaporan cedera/kecelakaan kerja

## Struktur Repository

```text
.
├── README.md                      # Dokumen ini
├── PROJECT_CONTEXT.md             # Referensi teknis utama untuk developer/AI
├── CLAUDE.md                      # Instruksi konteks untuk Claude/AI
├── mobile/                        # Flutter Android app
│   ├── lib/
│   │   ├── main.dart              # Entry point dan route registry
│   │   ├── screens/               # Screen dan flow UI
│   │   ├── services/              # ApiService, AuthService, SyncService, OfflineStorageService
│   │   └── theme/                 # Theme dan warna aplikasi
│   ├── test/                      # Flutter tests
│   └── pubspec.yaml               # Dependencies Flutter
└── backend/
    ├── api-contract.md            # Kontrak API (referensi)
    ├── schema.sql                 # Schema SQL legacy/reference
    └── she-api/                   # Laravel app aktif
        ├── app/
        │   ├── Http/Controllers/Api/
        │   ├── Models/
        │   └── Http/Requests/
        ├── database/
        │   ├── migrations/
        │   └── seeders/
        ├── routes/api.php         # Route API aktual (source of truth)
        └── tests/                 # PHPUnit feature tests
```

Folder lain di root (APK, log server, `platform-tools/`, `she-inspection-system/`)
merupakan artifact/tooling lokal dan bukan entry point aplikasi.

## Technology Stack

### Mobile

- Flutter/Dart, SDK `>=3.0.0 <4.0.0`
- Provider (state management), `http` (REST API)
- SharedPreferences (token), Hive (offline cache & draft queue)
- Geolocator (GPS), Image Picker (foto), Mobile Scanner (QR)
- Google Maps, Cached Network Image, SVG, Intl, Connectivity Plus

### Backend

- Laravel `^11.0`, PHP `^8.0`
- Laravel Sanctum (Bearer token)
- MySQL 8+ (dikelola via migration Laravel)
- PHPUnit 11 untuk test backend

## Quick Start

### Mobile (dari `mobile/`)

```bash
flutter pub get
flutter analyze
flutter test
flutter run
```

Override API URL saat run/build:

```bash
flutter run --dart-define=API_URL=http://localhost:8000/api
```

> Pada Android emulator, `localhost` menunjuk ke emulator, bukan host. Gunakan
> alamat host atau `adb reverse tcp:8000 tcp:8000` untuk backend lokal.

### Backend (dari `backend/she-api/`)

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=82
php artisan test
```

> Jangan commit file `.env`, password, token, atau credential.

## Konfigurasi Environment

Default API endpoint mobile (`lib/services/api_service.dart`):

```text
http://172.16.16.51:83/api
```

Backend saat ini dideploy sebagai container Docker (`she-inspection-system` dan
`she-inspection-db`). Lihat [`backend/README.md`](./backend/README.md) untuk detail.

## Dokumentasi Lanjutan

| Dokumen | Isi |
|---|---|
| [`PROJECT_CONTEXT.md`](./PROJECT_CONTEXT.md) | Struktur repo, arsitektur, endpoint aktual, permission, konvensi, gotchas |
| [`mobile/README.md`](./mobile/README.md) | Dokumentasi aplikasi mobile |
| [`backend/README.md`](./backend/README.md) | Dokumentasi backend, instalasi, troubleshooting |
| [`backend/api-contract.md`](./backend/api-contract.md) | Kontrak API (referensi) |

> Route API aktual selalu bersumber dari `backend/she-api/routes/api.php` dan
> perilaku endpoint dari controller/request/model — gunakan README lama hanya
> sebagai referensi.

## Testing

```bash
# Mobile
cd mobile && flutter test

# Backend
cd backend/she-api && php artisan test
```

## Lisensi

Proprietary — PT. Ecogreen Oleochemicals Indonesia. Semua hak dilindungi.