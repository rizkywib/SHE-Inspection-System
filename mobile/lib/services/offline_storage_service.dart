import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:hive/hive.dart';
import 'package:path_provider/path_provider.dart' as path_provider;

/// Penyimpanan lokal offline berbasis Hive.
///
/// Menyimpan:
///  - cache master data (points, lokasi, dll) agar QR scan tetap bisa berfungsi
///    saat offline;
///  - antrian draft inspeksi (pending) yang akan disinkronkan saat online.
class OfflineStorageService {
  OfflineStorageService._();

  static final OfflineStorageService instance = OfflineStorageService._();

  static const _cacheBoxName = 'offline_cache';
  static const _queueBoxName = 'offline_queue';

  late Box<dynamic> _cacheBox;
  late Box<dynamic> _queueBox;
  bool _initialized = false;

  Future<void> init() async {
    if (_initialized) return;

    final dir = await _resolveStorageDirectory();
    Hive.init(dir.path);

    _cacheBox = await Hive.openBox<dynamic>(_cacheBoxName);
    _queueBox = await Hive.openBox<dynamic>(_queueBoxName);
    _initialized = true;
    debugPrint('[OfflineStorage] initialized.');
  }

  Future<Directory> _resolveStorageDirectory() async {
    try {
      return await path_provider.getApplicationDocumentsDirectory();
    } catch (_) {
      return Directory.systemTemp;
    }
  }

  // ---------------------------------------------------------------------
  // Cache Master Data
  // ---------------------------------------------------------------------

  Future<void> saveCache(String key, List<dynamic> data) async {
    if (!_initialized) return;
    final json = jsonEncode(data);
    await _cacheBox.put('$_cacheKeyPrefix$key', json);
  }

  String get _cacheKeyPrefix => 'data:';

  Future<List<dynamic>?> readCache(String key) async {
    if (!_initialized) return null;
    final raw = _cacheBox.get('$_cacheKeyPrefix$key');
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw.toString());
      return decoded is List ? decoded : null;
    } catch (_) {
      return null;
    }
  }

  Future<Map<String, dynamic>?> readCacheSingle(String key) async {
    if (!_initialized) return null;
    final raw = _cacheBox.get('$_cacheKeyPrefix$key');
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw.toString());
      if (decoded is Map) return Map<String, dynamic>.from(decoded);
      return {'data': decoded};
    } catch (_) {
      return null;
    }
  }

  /// Menyimpan data master sebagai objek tunggal (misal user profile).
  Future<void> saveCacheJson(String key, Map<String, dynamic> data) async {
    if (!_initialized) return;
    await _cacheBox.put('$_cacheKeyPrefix$key', jsonEncode(data));
  }

  // ---------------------------------------------------------------------
  // Antrian Draft Inspeksi (Pending)
  // ---------------------------------------------------------------------

  /// Menambahkan draft inspeksi ke antrian untuk disinkronkan nanti.
  ///
  /// [endpoint] contoh: `/fire-extinguishers`
  /// [method] contoh: `POST` atau `PUT`
  /// [fields] data form yang dikirim ke backend
  /// [files] nama field -> path file lokal (foto)
  /// [displayName] nama untuk ditampilkan di daftar pending
  Future<String> enqueueDraft({
    required String endpoint,
    required String method,
    required Map<String, String> fields,
    Map<String, String>? files,
    String displayName = 'Inspeksi',
  }) async {
    if (!_initialized) return '';
    final id = DateTime.now().millisecondsSinceEpoch.toString();
    final entry = <String, dynamic>{
      'id': id,
      'endpoint': endpoint,
      'method': method,
      'fields': fields,
      'files': files ?? <String, String>{},
      'display_name': displayName,
      'status': 'pending',
      'created_at': DateTime.now().toIso8601String(),
      'attempts': 0,
    };
    await _queueBox.put(_queueKey(id), entry);
    return id;
  }

  String _queueKey(String id) => 'queue:$id';

  Future<List<Map<String, dynamic>>> getPendingDrafts() async {
    if (!_initialized) return [];
    final results = <Map<String, dynamic>>[];
    for (final key in _queueBox.keys) {
      final raw = _queueBox.get(key);
      if (raw is Map) {
        final map = Map<String, dynamic>.from(raw);
        if (map['status'] == 'pending') results.add(map);
      }
    }
    results.sort(
      (a, b) =>
          (a['created_at']?.toString() ?? '').compareTo(
              b['created_at']?.toString() ?? ''),
    );
    return results;
  }

  Future<void> updateDraftStatus(
    String id, {
    required String status,
    Object? result,
  }) async {
    if (!_initialized) return;
    final raw = _queueBox.get(_queueKey(id));
    if (raw is Map) {
      final map = Map<String, dynamic>.from(raw);
      map['status'] = status;
      if (result != null) map['result'] = result;
      await _queueBox.put(_queueKey(id), map);
    }
  }

  Future<void> incrementAttempt(String id) async {
    if (!_initialized) return;
    final raw = _queueBox.get(_queueKey(id));
    if (raw is Map) {
      final map = Map<String, dynamic>.from(raw);
      map['attempts'] = ((map['attempts'] ?? 0) as num).toInt() + 1;
      await _queueBox.put(_queueKey(id), map);
    }
  }

  Future<void> removeDraft(String id) async {
    if (!_initialized) return;
    await _queueBox.delete(_queueKey(id));
  }

  Future<void> clearAllDrafts() async {
    if (!_initialized) return;
    final keys = _queueBox.keys
        .where((k) => k.toString().startsWith('queue:'))
        .toList();
    await _queueBox.deleteAll(keys);
  }
}
