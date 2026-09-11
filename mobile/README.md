# SHE Inspection System - Mobile

A Flutter-based Android mobile application for Safety, Health, and Environment (SHE) inspection management. The app enables inspectors to conduct on-site inspections, report incidents, and sync data with the backend API in real-time.

## Features

- **Authentication** - Login/logout with Bearer token (Laravel Sanctum)
- **Dashboard** - Overview of inspection statistics and recent activities
- **Hydrant Inspection** - QR-based check-in and digital inspection forms for fire hydrants
- **Fire Extinguisher Inspection** - QR-based check-in and digital inspection forms for fire extinguishers
- **Fire Alarm Inspection** - QR-based check-in and digital inspection forms for fire alarms
- **ES/EW Inspection** - Emergency Shower & Eye Wash station monthly inspection
- **Permit Matrix** - Safe Work Permit inspection form and list (Job Performance is a dropdown managed from the website)
- **Safety Talk / Training** - On-site safety talk / training activity form and list
- **Checklist Inspection** - Custom checklist-based inspections by category
- **Inspection / Incident** - Report inspections/incidents with "Foto Temuan Awal" (mandatory) and "Perbaikan" (optional) photos; edit is only available to the creator and admin/super admin
- **Offline Mode & Auto Sync** - Inspections (Hydrant, Fire Extinguisher, ES/EW, and Inspection/incident) can be saved as local drafts when offline. Drafts are automatically sent to the server when the connection returns, when the app starts, and when the dashboard is refreshed. If an online save fails (network error or server error), the data is automatically queued as a draft so it is never lost. Each item shows a save-status badge ("Menunggu sinkronisasi" while pending)
- **QR Code Scanner** - Scan asset QR codes to quickly open inspection forms
- **GPS Check-In** - Verify on-site presence with geolocation during inspections
- **Master Data** - Manage points, locations, users, incident types, and ES&EW areas from the app

## Tech Stack

- **Flutter** - UI framework
- **Provider** - State management
- **HTTP** - REST API client
- **Shared Preferences** - Lightweight local storage for token
- **Geolocator** - GPS location services
- **Image Picker** - Camera and gallery access
- **Google Maps Flutter** - Map display
- **Cached Network Image** - Image caching
- **Flutter SVG** - SVG rendering
- **Intl** - Internationalization and date formatting
- **Hive** - Local storage for offline master-data cache and pending draft queue
- **Connectivity Plus** - Real-time network/connectivity status detection

## Prerequisites

- Flutter SDK ^3.0.0
- Android SDK
- Android device or emulator (API level 21+)

## Installation

```bash
# Install dependencies
flutter pub get

# Analyze and test
flutter analyze
flutter test

# Run the app
flutter run
```

## Test

Basic widget/unit tests live in `test/`. Run them with:

```bash
flutter test
```

## Configuration

Default API endpoint is defined in `lib/services/api_service.dart`:

```text
http://172.16.16.51:83/api
```

`ApiService` also implements a fallback base-URL candidate list so that if the
default host is unreachable it attempts alternate candidates before failing.

Override at build/run time with:

```bash
flutter run --dart-define=API_URL=http://localhost:8000/api
```

> Note: on the Android emulator `localhost` refers to the emulator itself, not
> the host machine. Use the host's LAN address or `adb reverse tcp:8000 tcp:8000`
> when running against a local backend.

## Project Structure

```
mobile/
├── android/                    # Android native configuration
├── assets/                     # Images and icons
├── lib/
│   ├── main.dart              # App entry point and route registry
│   ├── screens/               # UI screens
│   │   ├── inspection/        # Fire hydrant, extinguisher, alarm, ES/EW, checklist
│   │   ├── incident/          # Inspection / incident reporting and list
│   │   ├── permit_matrix/     # Permit Matrix modules
│   │   ├── safety_talk/       # Safety Talk / Training modules
│   │   └── master_data/       # Master data management
│   ├── services/              # ApiService, AuthService, ConnectivityService, SyncService, OfflineStorageService
│   └── theme/                 # Theme and colors
├── test/                      # Unit and widget tests
└── pubspec.yaml               # Dependencies and assets
```

## Backend API reference

Base URL:
```
http://172.16.16.51:83/api
```

Authentication uses Bearer tokens:
```
Authorization: Bearer <token>
```

See [`../backend/api-contract.md`](../backend/api-contract.md) for the API contract,
and [`../backend/she-api/routes/api.php`](../backend/she-api/routes/api.php) as the
source of truth for actual routes. See [`../README.md`](../README.md) for the full
project overview.

## Supported Operations

- Login / Logout
- View dashboard statistics
- Inspections for hydrant, extinguisher, alarm, and ES/EW
- Permit Matrix create/view/edit/delete (edit & delete admin-only on backend)
- Safety Talk / Training create/view/edit/delete
- Inspection / incident creation and editing (creator or admin/super admin)
- QR code scanning and asset lookup
- GPS check-in for inspection sites
- Digital signature capture
- Master data management
- Offline inspection drafts with automatic synchronization (auto-sync on reconnect, app start, dashboard refresh, and returning to the dashboard)

## License

Proprietary - All rights reserved.