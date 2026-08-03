# SHE Inspection System - API Contract

## Base URL
```
http://eoblas10.ecogreenoleo.co.id:82/api
```

## Authentication
All endpoints except login/register require Bearer token:
```
Authorization: Bearer <token>
```

---

## 1. Authentication

### POST /auth/login
**Request:**
```json
{
  "email": "admin@sheinspection.com",
  "password": "admin123"
}
```
**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "System Admin",
    "email": "admin@sheinspection.com",
    "role": "super_admin",
    "company": null,
    "branch": null
  }
}
```

### POST /auth/register
**Request:**
```json
{
  "name": "Inspector Name",
  "email": "inspector@company.com",
  "password": "password123",
  "role": "inspector"
}
```

### POST /auth/logout
### GET /auth/me
### PUT /auth/profile

---

## 2. Dashboard

### GET /dashboard/stats
**Response:**
```json
{
  "total_inspections": 150,
  "total_incidents": 12,
  "open_incidents": 3,
  "inspections_by_type": {
    "fire_hydrant": 45,
    "fire_extinguisher": 40,
    "fire_alarm": 35,
    "es_ew": 30
  }
}
```

### GET /dashboard/recent-inspections
### GET /dashboard/incident-summary

---

## 3. Master Data

### Companies
- `GET /companies` - List all
- `POST /companies` - Create
- `GET /companies/{id}` - Show
- `PUT /companies/{id}` - Update
- `DELETE /companies/{id}` - Delete

### Branches
- `GET /branches` - List all
- `POST /branches` - Create
- `GET /branches/{id}` - Show
- `PUT /branches/{id}` - Update
- `DELETE /branches/{id}` - Delete

### Divisions, Departments, Sections
Same CRUD pattern as above.

### Locations
- `GET /locations` - List all
- `POST /locations` - Create
- `GET /locations/{id}` - Show
- `PUT /locations/{id}` - Update
- `DELETE /locations/{id}` - Delete

### Areas
- `GET /areas` - List all
- `POST /areas` - Create
- `GET /areas/{id}` - Show
- `PUT /areas/{id}` - Update
- `DELETE /areas/{id}` - Delete

### Categories
- `GET /categories` - List all
- `POST /categories` - Create
- `GET /categories/{id}` - Show
- `PUT /categories/{id}` - Update
- `DELETE /categories/{id}` - Delete

### Users
- `GET /users` - List all
- `POST /users` - Create
- `GET /users/{id}` - Show
- `PUT /users/{id}` - Update
- `DELETE /users/{id}` - Delete

---

## 4. QR Codes

### GET /qr-codes/generate/{assetType}/{assetId}
Generate QR code for asset.

### POST /qr-codes/scan
**Request:**
```json
{
  "qr_code": "fire_hydrant:1",
  "latitude": -6.2088,
  "longitude": 106.8456
}
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "asset_type": "fire_hydrant",
    "asset_id": 1,
    "asset_name": "Hydrant A-101",
    "location": "Plant 1",
    "area": "Production Area"
  }
}
```

### GET /qr-codes

---

## 5. Fire Hydrant Inspections

### GET /fire-hydrants
### POST /fire-hydrants
**Request:**
```json
{
  "location_id": 1,
  "area_id": 1,
  "inspection_date": "2026-06-29",
  "inspector_id": 1,
  "items": [
    {
      "hydrant_number": "FH-001",
      "name": "Hydrant A",
      "hose_condition": 1,
      "nozzle_condition": 1,
      "coupling_condition": 1,
      "wrench_condition": 1,
      "valve_condition": 1,
      "remark": "All good"
    }
  ]
}
```

### GET /fire-hydrants/{id}
### PUT /fire-hydrants/{id}
### DELETE /fire-hydrants/{id}
### POST /fire-hydrants/{id}/checkin
**Request:**
```json
{
  "checkin_lat": -6.2088,
  "checkin_lng": 106.8456
}
```
### POST /fire-hydrants/{id}/sign

---

## 6. Fire Extinguisher Inspections

Same pattern as Fire Hydrant:
- `GET /fire-extinguishers`
- `POST /fire-extinguishers`
- `GET /fire-extinguishers/{id}`
- `PUT /fire-extinguishers/{id}`
- `DELETE /fire-extinguishers/{id}`
- `POST /fire-extinguishers/{id}/checkin`
- `POST /fire-extinguishers/{id}/sign`

---

## 7. Fire Alarm Inspections

Same pattern:
- `GET /fire-alarms`
- `POST /fire-alarms`
- `GET /fire-alarms/{id}`
- `PUT /fire-alarms/{id}`
- `DELETE /fire-alarms/{id}`
- `POST /fire-alarms/{id}/checkin`
- `POST /fire-alarms/{id}/sign`

---

## 8. ES/EW Inspections

Same pattern:
- `GET /es-ew`
- `POST /es-ew`
- `GET /es-ew/{id}`
- `PUT /es-ew/{id}`
- `DELETE /es-ew/{id}`
- `POST /es-ew/{id}/checkin`
- `POST /es-ew/{id}/sign`

---

## 9. Checklist Inspections

### GET /checklists
### POST /checklists
### GET /checklists/{id}
### PUT /checklists/{id}
### DELETE /checklists/{id}
### POST /checklists/{id}/checkin
### GET /checklist-questions/{categoryId}
### POST /checklist-questions

---

## 10. Incidents

### GET /incidents
### POST /incidents
**Request:**
```json
{
  "incident_type_id": 3,
  "incident_level_id": 2,
  "location_id": 1,
  "description": "Worker slipped on wet floor",
  "incident_date": "2026-06-29",
  "incident_time": "14:30:00",
  "latitude": "-6.2088",
  "longitude": "106.8456"
}
```

### GET /incidents/{id}
### PUT /incidents/{id}
### DELETE /incidents/{id}
### POST /incidents/{id}/images
### POST /incidents/{id}/investigate
### GET /incident-types
### GET /incident-levels

---

## 11. Medical Reports

- `GET /medical-reports`
- `POST /medical-reports`
- `GET /medical-reports/{id}`
- `PUT /medical-reports/{id}`
- `DELETE /medical-reports/{id}`

---

## 12. File Upload

### POST /upload
Multipart form-data with key `file`.
