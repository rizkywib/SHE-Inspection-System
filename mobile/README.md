# SHE Inspection System - Mobile

A Flutter-based Android mobile application for Safety, Health, and Environment (SHE) inspection management. The app enables inspectors to conduct on-site inspections, report incidents, and sync data with the backend API in real-time.

## Features

- **Authentication** - Secure login/logout with JWT token and biometric support
- **Dashboard** - Overview of inspection statistics and recent activities
- **Fire Hydrant Inspection** - QR-based check-in and digital inspection forms for fire hydrants
- **Fire Extinguisher Inspection** - QR-based check-in and digital inspection forms for fire extinguishers
- **Fire Alarm Inspection** - QR-based check-in and digital inspection forms for fire alarms
- **ES/EW Inspection** - Earthquake and Earthquake-Wind equipment inspection
- **Checklist Inspection** - Custom checklist-based inspections by category
- **Incident Reporting** - Report incidents with GPS coordinates and photo attachments
- **Medical Reports** - Log and manage medical reports
- **QR Code Scanner** - Scan asset QR codes to quickly open inspection forms
- **GPS Check-In** - Verify on-site presence with geolocation during inspections
- **Digital Signature** - Capture inspector signatures on-site

## Tech Stack

- **Flutter** - UI framework
- **Provider** - State management
- **HTTP** - REST API client
- **Flutter Secure Storage** - Secure token storage
- **Shared Preferences** - Lightweight local storage
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
# Clone the repository
git clone <repository-url>
cd she-inspection-system/mobile

# Install dependencies
flutter pub get

# Run the app
flutter run
```

## Configuration

Update the API endpoint in `lib/services/api_service.dart`:

```dart
static const String baseUrl = 'http://your-backend-domain/api';
```

## Project Structure

```
mobile/
├── android/                    # Android native configuration
├── assets/                     # Images and icons
├── lib/
│   ├── main.dart              # App entry point
│   ├── screens/               # UI screens
│   │   └── inspection/        # Inspection screen sub-modules
│   ├── services/              # API services and utilities
│   ├── models/                # Data models
│   └── providers/             # State management providers
├── test/                      # Unit and widget tests
├── pubspec.yaml               # Dependencies and assets
└── README.md
```

## Available Inspection Modules

- `fire_hydrant_screen.dart`
- `fire_extinguisher_screen.dart`
- `fire_alarm_screen.dart`
- `es_ew_screen.dart`

## Backend API

See [`backend/api-contract.md`](../she-api/api-contract.md) for full API documentation.

Base URL:
```
http://localhost:8000/api
```

Authentication uses Bearer tokens:
```
Authorization: Bearer <token>
```

## Supported Operations

- Login / Register / Logout
- View dashboard statistics
- CRUD on inspections (Create, Read, Update, Delete)
- QR code scanning and asset lookup
- GPS check-in for inspection sites
- Digital signature capture
- Incident reporting with location and photos
- Medical report management

## License

Proprietary - All rights reserved.