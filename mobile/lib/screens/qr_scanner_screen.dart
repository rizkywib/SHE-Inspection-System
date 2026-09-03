import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api_service.dart';
import '../services/offline_storage_service.dart';
import '../services/connectivity_service.dart';

class QrScannerScreen extends StatefulWidget {
  const QrScannerScreen({super.key});

  @override
  State<QrScannerScreen> createState() => _QrScannerScreenState();
}

class _QrScannerScreenState extends State<QrScannerScreen> {
  bool _isScanning = false;
  bool _hasPermission = false;

  @override
  void initState() {
    super.initState();
    _checkPermissions();
  }

  Future<void> _checkPermissions() async {
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    setState(() {
      _hasPermission = permission == LocationPermission.always ||
          permission == LocationPermission.whileInUse;
    });
  }

  Future<void> _onQrScanned(String qrData) async {
    if (_isScanning) return;
    _isScanning = true;

    try {
      final connectivity = context.read<ConnectivityService>();
      final navigator = Navigator.of(context);
      final api = context.read<ApiService>();

      // Get current location
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      Map<String, dynamic>? result;

      if (connectivity.isOnline) {
        result = await api.scanQrCode(qrData);
      } else {
        // Offline: resolve QR dari cache points lokal.
        result = await _resolveOfflineQr(qrData);
      }

      if (!mounted) return;

      if (result == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Gagal memproses QR Code (hasil kosong).'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      if (result['status'] == 'success') {
        final data = result['data'];
        final assetType = data['asset_type'];

        // Navigate to appropriate inspection form
        String route;
        switch (assetType) {
          case 'fire_hydrant':
            route = '/fire-hydrant';
            break;
          case 'fire_extinguisher':
            route = '/fire-extinguisher';
            break;
          case 'fire_alarm':
            route = '/fire-alarm';
            break;
          case 'es_ew':
            route = '/es-ew';
            break;
          default:
            route = '/checklist';
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Asset found: ${data['asset_name']}'),
            backgroundColor: Colors.green,
          ),
        );

        navigator.pushNamed(route, arguments: {
          'qr_data': data,
          'latitude': position.latitude,
          'longitude': position.longitude,
        });
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'QR Code not recognized'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      _isScanning = false;
    }
  }

  /// Resolusi QR offline menggunakan cache point lokal.
  Future<Map<String, dynamic>?> _resolveOfflineQr(String qrData) async {
    final points = await OfflineStorageService.instance.readCache('points');
    if (points == null || points.isEmpty) {
      return {
        'status': 'error',
        'message': 'Offline: data point belum di-cache. '
            'Hubungkan ke internet lalu buka aplikasi sekali untuk meng-cache.',
      };
    }

    final normalized = qrData.trim().toUpperCase();
    for (final raw in points) {
      final point = raw is Map ? Map<String, dynamic>.from(raw) : null;
      if (point == null) continue;
      final qr = point['qr_code']?.toString().trim().toUpperCase() ?? '';
      if (qr.isNotEmpty && qr == normalized) {
        return {
          'status': 'success',
          'data': {
            'asset_type': _assetTypeFor(point),
            'asset_id': point['id'],
            'asset_name': point['name_point'],
            'point': point,
          },
        };
      }
    }
    return {
      'status': 'error',
      'message': 'QR Code tidak terdaftar di cache local.',
    };
  }

  String _assetTypeFor(Map<String, dynamic> point) {
    final type = point['ket1']?.toString().trim().toUpperCase() ?? '';
    if (type.contains('KG') ||
        type.contains('APAR') ||
        RegExp(r'^(?:DC|CO2?|CA|HF)(?:\s|[-–])').hasMatch(type)) {
      return 'fire_extinguisher';
    }
    if (point['name_point']?.toString().toLowerCase().contains('hydrant') ==
        true) {
      return 'fire_hydrant';
    }
    if (point['name_point']?.toString().toLowerCase().contains('alarm') ==
        true) {
      return 'fire_alarm';
    }
    return 'checklist';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan QR Code'),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (!_hasPermission)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        const Icon(Icons.location_off, size: 48, color: Colors.red),
                        const SizedBox(height: 12),
                        const Text(
                          'Location Permission Required',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'GPS is needed to check-in at inspection points',
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _checkPermissions,
                          child: const Text('Grant Permission'),
                        ),
                      ],
                    ),
                  ),
                )
              else
                Card(
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () => _simulateScan(),
                    child: Container(
                      width: 250,
                      height: 250,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: const Color(0xFF1A56DB),
                          width: 2,
                        ),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.qr_code_scanner,
                            size: 80,
                            color: Color(0xFF1A56DB),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            _isScanning ? 'Scanning...' : 'Tap to Scan',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Position ready: GPS active',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  // For demo/development - replace with actual camera QR scanner
  void _simulateScan() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Simulate QR Scan',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            _qrOption(context, 'Fire Hydrant - FH-001', 'fire_hydrant', 1),
            _qrOption(context, 'Fire Extinguisher - APAR-001', 'fire_extinguisher', 1),
            _qrOption(context, 'Fire Alarm - ALM-001', 'fire_alarm', 1),
            _qrOption(context, 'Emergency Shower - ES-001', 'es_ew', 1),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _qrOption(BuildContext context, String label, String type, int id) {
    return ListTile(
      leading: const Icon(Icons.qr_code),
      title: Text(label),
      onTap: () {
        Navigator.pop(context);
        _onQrScanned('$type:$id');
      },
    );
  }
}