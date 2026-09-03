import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

/// Menyediakan status konektivitas jaringan dan mengekspos perubahan
/// online/offline secara real-time ke seluruh aplikasi.
class ConnectivityService extends ChangeNotifier {
  final Connectivity _connectivity = Connectivity();
  StreamSubscription<List<ConnectivityResult>>? _subscription;

  bool _isOnline = true;
  bool _hasNetwork = true;

  /// Apakah perangkat terhubung ke jaringan (wi-fi / seluler).
  bool get isOnline => _isOnline;

  /// Apakah ada antarmuka jaringan aktif (bukan sekedar status online).
  bool get hasNetwork => _hasNetwork;

  Future<void> init() async {
    try {
      final results = await _connectivity.checkConnectivity();
      _applyResults(results);

      _subscription = _connectivity.onConnectivityChanged.listen(_applyResults);
    } catch (_) {
      // Jika plugin gagal, asumsikan online agar tidak memblokir aplikasi.
      _isOnline = true;
      _hasNetwork = true;
    }
    notifyListeners();
  }

  void _applyResults(List<ConnectivityResult> results) {
    final hasNetwork = results.any(
      (r) => r != ConnectivityResult.none,
    );
    // Aplikasi dianggap online hanya jika benar-benar ada antarmuka jaringan.
    _hasNetwork = hasNetwork;
    _isOnline = hasNetwork;
    notifyListeners();
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }
}
