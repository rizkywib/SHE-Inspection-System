# SHE Inspection System - Project Context

Dokumen ini adalah referensi utama untuk developer dan AI yang bekerja pada proyek
SHE Inspection System. Isinya menjelaskan kondisi kode yang terdeteksi saat
dokumentasi dibuat, bukan janji bahwa semua fitur di README lama sudah aktif.

Last reviewed: 2026-08-06

## 1. Ringkasan

SHE Inspection System adalah sistem inspeksi Safety, Health, and Environment
untuk inspeksi peralatan keselamatan, pelaporan inspection/incident, check-in GPS,
foto, dan tanda tangan digital.

Repository ini memiliki dua aplikasi utama:

- `mobile/`: aplikasi Flutter Android untuk inspector.
- `backend/she-api/`: REST API Laravel untuk autentikasi, master data, inspeksi,
  upload file, dan permission.

Folder lain di root seperti APK, log server, `platform-tools`, dan
`she-inspection-system/` bukan entry point aplikasi utama.

## 2. Struktur Repository

```text
.
├── PROJECT_CONTEXT.md             # Referensi utama proyek
├── CLAUDE.md                      # Instruksi konteks untuk Claude/AI
├── mobile/                        # Flutter Android application
│   ├── lib/main.dart              # Entry point dan route registry
│   ├── lib/screens/               # Screen dan flow UI
│   ├── lib/services/              # API client dan auth state
│   ├── lib/theme/                 # Theme dan warna aplikasi
│   ├── android/                   # Konfigurasi Android/Gradle
│   ├── test/                      # Flutter tests
│   └── pubspec.yaml               # Dependency Flutter
├── backend/
│   ├── api-contract.md            # Kontrak API lama/umum
│   ├── README.md                  # Dokumentasi backend umum
│   ├── schema.sql                 # Schema SQL legacy/reference
│   └── she-api/                   # Laravel application aktif
│       ├── app/Models/             # Eloquent models
│       ├── app/Http/Controllers/Api/
│       ├── app/Http/Requests/
│       ├── database/migrations/
│       ├── database/seeders/
│       ├── routes/api.php         # Route API aktual
│       └── tests/                 # PHPUnit feature tests
└── ...                            # Artifact dan tooling lokal
```

Source of truth untuk route adalah `backend/she-api/routes/api.php`.
Source of truth untuk perilaku endpoint adalah controller, request validation,
model, dan migration Laravel. README/schema lama harus dianggap referensi dan
perlu dicocokkan sebelum digunakan.

## 3. Technology Stack

### Mobile

- Flutter/Dart, SDK constraint `>=3.0.0 <4.0.0`.
- Provider untuk state management.
- `http` untuk REST API.
- SharedPreferences untuk token saat ini.
- Geolocator untuk lokasi.
- Image Picker untuk foto.
- Mobile Scanner untuk QR.
- Google Maps, cached network image, SVG, dan intl tersedia sebagai dependency.

### Backend

- Laravel `^11.0`.
- PHP `^8.0` di `composer.json`.
- Laravel Sanctum untuk Bearer token.
- MySQL 8+ sebagai database yang ditargetkan.
- PHPUnit 11 untuk test backend.

## 4. Mobile Architecture

Entry point berada di `mobile/lib/main.dart`.

Provider yang diregister secara global:

- `AuthService`: token, user aktif, login, logout, restore session.
- `ApiService`: HTTP API client dan token header.

Route utama:

| Route | Screen | Keterangan |
|---|---|---|
| `/splash` | `SplashScreen` | Restore token lalu menentukan login/home |
| `/login` | `LoginScreen` | Login menggunakan username dan password |
| `/home` | `HomeScreen` | Dashboard dan aktivitas terbaru |
| `/profile` | `ProfileScreen` | Profil user |
| `/qr-scanner` | `QrScannerScreen` | Scan QR asset |
| `/fire-hydrant` | `FireHydrantScreen` | Daftar/detail hydrant |
| `/fire-extinguisher` | `FireExtinguisherScreen` | Daftar/detail APAR |
| `/fire-extinguisher-create` | `FireExtinguisherCreateScreen` | Form APAR |
| `/fire-alarm` | `FireAlarmScreen` | Fire alarm |
| `/es-ew` | `EsEwScreen` | Emergency shower/eye wash |
| `/permit-matrix` | `PermitMatrixScreen` | Safe work permit inspection |
| `/safety-talk-training` | `SafetyTalkScreen` | Safety talk/training |
| `/checklist` | `ChecklistScreen` | Checklist umum |
| `/incidents` | `IncidentListScreen` | Daftar inspection/incident |
| `/incident-form` | `IncidentFormScreen` | Form inspection/incident |

### Alur login

1. Splash memanggil `AuthService.init()`.
2. Token dibaca dari SharedPreferences.
3. Jika ada token, mobile memanggil `GET /auth/me`.
4. Token invalid akan dihapus dan user diarahkan ke login.
5. Login memanggil `POST /auth/login` dengan body `username` dan `password`.
6. Token disimpan dan dikirim sebagai `Authorization: Bearer <token>`.

### API client

`ApiService` memiliki fallback base URL dan timeout request 15 detik. Default
base URL saat ini:

```text
http://eoblas10.ecogreenoleo.co.id:82/api
```

Base URL dapat dioverride saat build dengan `--dart-define=API_URL=...`.
Beberapa method sudah memakai fallback request, tetapi beberapa method lama
masih langsung memakai `baseUrl`; jangan menganggap fallback berlaku untuk semua
endpoint.

## 5. Backend Architecture

Laravel menggunakan konfigurasi routing di `bootstrap/app.php`:

- API route: `routes/api.php`.
- Authentication middleware: `auth:sanctum`.
- Permission middleware alias: `permission` -> `EnsurePermission`.

Semua route API kecuali login dan register berada dalam group
`auth:sanctum`.

Controller utama:

- `AuthController`: login, register, logout, current user, profile.
- `DashboardController`: statistik, recent inspections, incident summary.
- `FireHydrantController`: hydrant inspection, items, photos, check-in, sign.
- `FireExtinguisherController`: APAR inspection.
- `FireAlarmController`: fire alarm inspection.
- `EsEwController`: ES/EW inspection dan item.
- `SafeWorkPermitInspectionController`: Permit Matrix.
- `SafetyTalkTrainingController`: Safety Talk/Training.
- `IncidentController`: inspection/incident dan image.
- `QrCodeController`: generate, scan, dan list QR.
- Controller master data: users, organization, locations, areas, points,
  incident types, dan area inspeksi.

## 6. API Endpoint Groups Aktual

Semua endpoint berikut memiliki prefix `/api` dari base URL.

### Public authentication

- `POST /auth/login`
- `POST /auth/register`

### Authenticated authentication

- `POST /auth/logout`
- `GET /auth/me`
- `PUT /auth/profile`

### Dashboard

- `GET /dashboard/stats`
- `GET /dashboard/recent-inspections`
- `GET /dashboard/incident-summary`

### Master data

- CRUD `companies`, `branches`, `divisions`, `departments`, `sections`.
- CRUD `locations`, `points`, `areas`, `categories`.
- CRUD `fire-hydrant-locations`, `fire-extinguisher-locations`, `es-ew-areas`.
- CRUD `users`.
- CRUD `incident-types`.

### Inspections

Untuk hydrant, extinguisher, dan fire alarm:

- `GET /fire-hydrants`
- `POST /fire-hydrants`
- `GET|PUT|DELETE /fire-hydrants/{id}`
- `POST /fire-hydrants/{id}/checkin`
- `POST /fire-hydrants/{id}/sign`
- Pola yang sama berlaku pada `/fire-extinguishers` dan `/fire-alarms`.

ES/EW:

- `GET /es-ew/master-data`
- `GET /es-ew/next-reference`
- `GET|POST /es-ew`
- `GET|PUT|DELETE /es-ew/{id}`
- `POST /es-ew/{id}/checkin`
- `POST /es-ew/{id}/sign`
- Endpoint item tersedia di `/es-ew/{inspectionId}/items`.

Permit Matrix:

- `GET /safe-work-permit-inspections/master-data`
- `GET|POST /safe-work-permit-inspections`
- `GET|PUT|DELETE /safe-work-permit-inspections/{id}`

Safety Talk:

- `GET /safety-talk-trainings/master-data`
- `GET|POST /safety-talk-trainings`
- `GET|PUT|DELETE /safety-talk-trainings/{id}`

Checklist:

- CRUD `/checklists`.
- `POST /checklists/{id}/checkin`.
- `GET /checklist-questions/{categoryId}`.
- `POST /checklist-questions`.

Incidents/inspection:

- CRUD `/incidents`.
- `POST /incidents/{id}/images`.
- `POST /incidents/{id}/investigate`.

Other:

- `GET /qr-codes/generate/{assetType}/{assetId}`.
- `POST /qr-codes/scan`.
- `GET /qr-codes`.
- CRUD `/medical-reports`.
- `POST /upload`.

## 7. Authentication and Permission Rules

### Login contract aktual

Backend memvalidasi dan mencari user menggunakan `username`, bukan `email`:

```json
{
  "username": "inspector01",
  "password": "password"
}
```

Response login berisi `token` dan `user`. User memiliki `role`, data organisasi,
dan daftar permission.

### Permission

`EnsurePermission` memeriksa `User::hasPermission()`.

- `super_admin` otomatis memiliki semua permission.
- Role lain mendapatkan permission melalui `user_group_members`, `user_groups`,
  dan `permissions`.
- Permission menggunakan format `<module>.<action>`, misalnya
  `safe-work-permit-inspection.view`.
- Action `update` dipetakan ke kolom database `can_edit`.
- Route Permit Matrix dan Safety Talk memakai permission middleware secara
  eksplisit.
- Banyak route master data dan inspeksi lain masih hanya dilindungi oleh
  `auth:sanctum`, tanpa permission middleware khusus di route.

## 8. Domain Data

Model penting di `app/Models` meliputi:

- Organization: `Company`, `Branch`, `Division`, `Department`, `Section`.
- Location: `Location`, `LocationType`, `Area`, `Point`.
- User/auth: `User`, Sanctum personal access tokens.
- Fire equipment: hydrant, extinguisher, alarm, dan item inspection masing-masing.
- ES/EW: area, inspection, dan item.
- Permit: main area, sub area, type, inspector, dan inspection.
- Safety Talk: speaker dan training.
- Incident: incident, type, images, investigation.
- Medical: medical report.

Inspection umumnya memiliki relasi ke inspector, lokasi/area, item, timestamp
check-in, dan data tanda tangan. Foto biasanya disimpan sebagai path relatif
seperti `images/...` atau `storage/...` dan dikonversi menjadi URL oleh mobile.

Database dikelola melalui migration Laravel. `schema.sql` dan file setup SQL
harus dianggap reference/legacy sampai diverifikasi terhadap migration terbaru.

## 9. Cara Menjalankan

### Mobile

Dari folder `mobile/`:

```bash
flutter pub get
flutter analyze
flutter test
flutter run
```

Override API URL:

```bash
flutter run --dart-define=API_URL=http://localhost:8000/api
```

Untuk Android emulator, `localhost` biasanya menunjuk ke emulator, bukan host.
Gunakan alamat host yang sesuai atau `adb reverse` bila backend lokal digunakan.

### Backend

Dari folder `backend/she-api/`:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=82
php artisan test
```

Konfigurasi database dan application URL berada di `.env`. Jangan menambahkan
file `.env`, password, token, atau credential ke commit.

## 10. Testing

Backend memiliki feature tests untuk area seperti login, profile, dashboard,
ES/EW, area ES/EW, Permit Matrix, Safety Talk, dan print logo.

Mobile memiliki widget test dasar di `mobile/test/widget_test.dart`.

Saat mengubah endpoint, validasi minimal yang disarankan:

1. Jalankan test controller/backend terkait.
2. Jalankan `php artisan test`.
3. Jalankan `flutter analyze`.
4. Jalankan `flutter test`.
5. Uji login dan satu alur create/detail pada device atau emulator.

## 11. Konvensi Perubahan

- Baca `PROJECT_CONTEXT.md` sebelum mengubah arsitektur atau endpoint.
- Periksa route aktual sebelum menambah method API mobile.
- Pertahankan response JSON yang ada, terutama key `data`, `message`, `errors`,
  dan `token`.
- Untuk endpoint baru, tambahkan validasi backend terlebih dahulu, lalu method
  di `ApiService`, kemudian screen/flow mobile.
- Gunakan `apply_patch` untuk perubahan manual yang terukur.
- Jangan menghapus perubahan user atau file yang tidak terkait.
- Jangan menyimpan credential, token, atau konfigurasi produksi rahasia.
- Saat mengubah migration, pastikan foreign key dan nama kolom sesuai model
  serta controller yang memakainya.

## 12. Gotchas dan Ketidaksesuaian yang Diketahui

- Dokumentasi lama sering menyebut login dengan `email`; implementasi aktual
  menggunakan `username`.
- `mobile/lib/services/auth_service.dart` membuat instance `ApiService` sendiri,
  terpisah dari instance Provider di `main.dart`. Token tetap diset pada instance
  auth tersebut, tetapi perubahan konfigurasi/base URL perlu diperhatikan.
- Dependency `flutter_secure_storage` tercantum, tetapi token aktual disimpan
  di SharedPreferences.
- Beberapa method `ApiService` tidak memakai fallback URL atau tidak memeriksa
  status HTTP secara konsisten. Perbaiki dengan hati-hati agar tidak mengubah
  perilaku modul lain.
- Dashboard mobile menggabungkan beberapa endpoint dan menampilkan incident
  sebagai item bertipe `Inspection`; jangan menyamakan label UI tersebut dengan
  model incident backend tanpa memeriksa konteks.
- Tanda tangan digital backend mengambil `signature_path` milik user saat sign;
  inspeksi tidak menerima file tanda tangan baru pada endpoint sign.
- Beberapa route memiliki urutan khusus, misalnya `master-data` dan
  `next-reference` harus tetap didefinisikan sebelum route `/{id}`.
- Root berisi artifact lokal berukuran besar dan log server. Jangan memasukkan
  artifact tersebut ke perubahan fitur tanpa alasan yang jelas.

## 13. Instruksi untuk AI Berikutnya

Sebelum bekerja:

1. Baca dokumen ini dan `CLAUDE.md`.
2. Tentukan apakah tugas menyentuh mobile, backend, database, atau kontrak API.
3. Baca hanya file source yang relevan dengan tugas setelah konteks ini dipahami.
4. Periksa `git status` dan jangan membatalkan perubahan yang bukan milikmu.

Saat menyelesaikan tugas:

- Jelaskan file yang diubah dan alasan perubahan.
- Sebutkan test/analyze/build yang dijalankan atau alasan tidak menjalankannya.
- Jika menemukan dokumentasi yang tidak sinkron, gunakan implementasi aktual
  sebagai acuan dan catat mismatch tersebut.
- Jangan mengasumsikan endpoint, field, role, atau response hanya berdasarkan
  `backend/api-contract.md`; verifikasi `routes/api.php` dan controller.
