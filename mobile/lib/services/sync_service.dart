import 'dart:io';

import 'package:flutter/foundation.dart';

import 'api_service.dart';
import 'connectivity_service.dart';
import 'offline_storage_service.dart';

/// Mengelola sinkronisasi data offline.
///
/// Bertanggung jawab untuk:
///  - meng-cache master data saat online agar QR scan & form tetap berfungsi
///    saat offline;
///  - mengirim antrian draft inspeksi (pending) ke server saat koneksi pulih.
class SyncService extends ChangeNotifier {
  SyncService({required this.api, required this.connectivity, this.isAutoSyncEnabled = true});

  final ApiService api;
  final ConnectivityService connectivity;
  final bool isAutoSyncEnabled;

  final OfflineStorageService _storage = OfflineStorageService.instance;

  bool _isSyncing = false;

  bool get isSyncing => _isSyncing;

  Future<int> pendingCount() async {
    return (await _storage.getPendingDrafts()).length;
  }

  Future<void> init() async {
    if (!isAutoSyncEnabled) return;
    if (connectivity.isOnline) {
      await cacheMasterData();
      await syncAll();
    }
  }

  /// Sinkronisasi seluruh antrian draft tertunda ke server (jika online).
  Future<void> syncAll() async {
    if (!connectivity.isOnline || _isSyncing) return;

    _isSyncing = true;
    notifyListeners();
    try {
      final drafts = await _storage.getPendingDrafts();
      for (final draft in drafts) {
        if (!connectivity.isOnline) break; // hentikan bila koneksi putus
        await _syncOne(draft); // lanjutkan walau ada draft yang gagal
      }
    } finally {
      _isSyncing = false;
      notifyListeners();
    }
  }

  Future<bool> _syncOne(Map<String, dynamic> draft) async {
    final id = draft['id'].toString();
    final endpoint = draft['endpoint'].toString();
    final method = draft['method'].toString();
    final fields = _asStrMap(draft['fields']);
    final files = _asStrMap(draft['files']);

    try {
      final result = await _dispatch(endpoint, method, fields, files);
      final ok = result is Map && !result.containsKey('error');
      if (ok) {
        await _storage.removeDraft(id);
        debugPrint('[Sync] Berhasil: $endpoint ($id)');
        return true;
      }
      await _storage.updateDraftStatus(id, status: 'pending', result: result);
      await _storage.incrementAttempt(id);
      return false;
    } catch (error) {
      debugPrint('[Sync] Gagal $endpoint: $error');
      await _storage.incrementAttempt(id);
      return false;
    }
  }

  /// Menentukan pemanggilan ApiService berdasarkan endpoint & method.
  Future<Object?> _dispatch(
    String endpoint,
    String method,
    Map<String, String> fields,
    Map<String, String> files,
  ) async {
    // --- Fire Extinguisher (APAR) ---
    if (endpoint.startsWith('/fire-extinguishers') && !endpoint.endsWith('/checkin')) {
      final id = _extractId(endpoint);
      final photoBefore = files['item[photo_before]'] == null
          ? null
          : File(files['item[photo_before]']!);
      final photoAfter = files['item[photo_after]'] == null
          ? null
          : File(files['item[photo_after]']!);
      if (id != null) {
        return api.updateFireExtinguisherWithPhotos(
          id,
          fields,
          photoBefore: photoBefore,
          photoAfter: photoAfter,
        );
      }
      return api.createFireExtinguisherWithPhotos(
        fields,
        photoBefore: photoBefore,
        photoAfter: photoAfter,
      );
    }

    // --- Fire Hydrant ---
    if (endpoint.startsWith('/fire-hydrants') && !endpoint.endsWith('/checkin')) {
      final id = _extractId(endpoint);
      final fileMap = _asFileMap(files);
      if (id != null) {
        return api.updateFireHydrantWithPhotos(id, fields, fileMap);
      }
      return api.createFireHydrantWithPhotos(fields, fileMap);
    }

    // --- ES/EW ---
    if (endpoint.startsWith('/es-ew')) {
      final id = _extractId(endpoint);
      final eyeWashPhoto = files['items[0][photo_before]'] == null
          ? null
          : File(files['items[0][photo_before]']!);
      final emergencyShowerPhoto = files['items[0][photo_after]'] == null
          ? null
          : File(files['items[0][photo_after]']!);
      if (id != null) {
        return api.updateEsEw(
          id,
          fields,
          eyeWashPhoto: eyeWashPhoto,
          emergencyShowerPhoto: emergencyShowerPhoto,
        );
      }
      return api.createEsEw(
        fields,
        eyeWashPhoto: eyeWashPhoto,
        emergencyShowerPhoto: emergencyShowerPhoto,
      );
    }

    // --- Inspection (Incident) ---
    if (endpoint.startsWith('/incidents')) {
      final id = _extractId(endpoint);
      final image = files['image'] == null ? null : File(files['image']!);
      final repairPhoto = files['repair_photo'] == null
          ? null
          : File(files['repair_photo']!);
      if (id != null) {
        return api.updateIncident(
          id,
          fields,
          image: image,
          repairPhoto: repairPhoto,
        );
      }
      return api.createIncident(
        fields,
        image: image,
        repairPhoto: repairPhoto,
      );
    }

    return {'error': 'Endpoint tidak didukung untuk sinkronisasi: $endpoint'};
  }

  // ---------------------------------------------------------------------
  // Cache Master Data
  // ---------------------------------------------------------------------

  /// Mengambil & meng-cache master data yang dibutuhkan untuk mode offline.
  Future<void> cacheMasterData() async {
    if (!connectivity.isOnline) return;
    try {
      final points = await api.getPoints();
      await _storage.saveCache('points', points);
      debugPrint('[Sync] cache points: ${points.length}');

      final extinguisherLocations =
          await api.getFireExtinguisherLocations();
      await _storage.saveCache('extinguisher_locations', extinguisherLocations);

      final hydrantLocations = await api.getFireHydrantLocations();
      await _storage.saveCache('hydrant_locations', hydrantLocations);

      final fireAlarmLocations = await api.getFireAlarmLocations();
      await _storage.saveCache('fire_alarm_locations', fireAlarmLocations);

      final esEwMaster = await api.getEsEwMasterData();
      await _storage.saveCacheJson('es_ew_master', esEwMaster);

      final incidentTypes = await api.getIncidentTypes();
      await _storage.saveCache('incident_types', incidentTypes);

      final incidents = await api.getIncidents();
      await _storage.saveCache('incidents', incidents);

      final hydrants = await api.getFireHydrants();
      await _storage.saveCache('hydrants', hydrants);

      final extinguishers = await api.getFireExtinguishers();
      await _storage.saveCache('extinguishers', extinguishers);

      final esEw = await api.getEsEw();
      await _storage.saveCache('es_ew', esEw);
    } catch (error) {
      debugPrint('[Sync] Gagal cache master data: $error');
    }
  }

  Map<String, String> _asStrMap(dynamic value) {
    if (value is Map) {
      return value.map((k, v) => MapEntry(k.toString(), v?.toString() ?? ''));
    }
    return <String, String>{};
  }

  Map<String, File?> _asFileMap(Map<String, String> files) {
    final result = <String, File?>{};
    for (final entry in files.entries) {
      final path = entry.value.trim();
      if (path.isNotEmpty) result[entry.key] = File(path);
    }
    return result;
  }

  int? _extractId(String endpoint) {
    final pattern = RegExp(r'^/[^/]+/(\d+)$');
    final match = pattern.firstMatch(endpoint);
    return match == null ? null : int.tryParse(match.group(1)!);
  }
}
