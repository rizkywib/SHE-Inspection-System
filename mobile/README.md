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

## Prerequisites

- Flutter SDK ^3.0.0
- Android SDK
- Android device or emulator (API level 21+)

## Installation

```bash
# Install dependencies
flutter pub get

# Run the app
flutter run
```

## Configuration

Default API endpoint is defined in `lib/services/api_service.dart`:

```text
http://172.16.16.51:83/api
```

Override at build/run time with:

```bash
flutter run --dart-define=API_URL=http://localhost:8000/api
```

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
│   ├── services/              # ApiService and AuthService
│   └── theme/                 # Theme and colors
├── test/                      # Unit and widget tests
└── pubspec.yaml               # Dependencies and assets
```

## Backend API

See [`../api-contract.md`](../api-contract.md) for the API contract.

Base URL:
```
http://172.16.16.51:83/api
```

Authentication uses Bearer tokens:
```
Authorization: Bearer <token>
```

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

## License

Proprietary - All rights reserved.