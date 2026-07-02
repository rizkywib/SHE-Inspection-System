import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api_service.dart';

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
      // Get current location
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      final api = context.read<ApiService>();
      final result = await api.scanQrCode(qrData);

      if (!mounted) return;

      if (result['status'] == 'success') {
        final data = result['data'];
        final assetType = data['asset_type'];
        final assetId = data['asset_id'];

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

        Navigator.pushNamed(context, route, arguments: {
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