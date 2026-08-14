import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';

class FireExtinguisherCreateScreen extends StatefulWidget {
  const FireExtinguisherCreateScreen({super.key});

  @override
  State<FireExtinguisherCreateScreen> createState() =>
      _FireExtinguisherCreateScreenState();
}

class _FireExtinguisherCreateScreenState
    extends State<FireExtinguisherCreateScreen> {
  late final String _inspectionDate;
  List<dynamic> _locations = [];
  int? _locationId;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _inspectionDate = DateTime.now().toIso8601String().split('T').first;
    _loadLocations();
  }

  Future<void> _loadLocations() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final locations =
          await context.read<ApiService>().getFireExtinguisherLocations();
      if (!mounted) return;
      setState(() {
        _locations = locations;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  Future<void> _openScanner() async {
    final locationId = _locationId;
    if (locationId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih lokasi APAR terlebih dahulu.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final location = _locations.map(_asMap).firstWhere(
          (item) => _asInt(item['id_location'] ?? item['id']) == locationId,
          orElse: () => <String, dynamic>{},
        );

    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => FireExtinguisherQrScannerScreen(
          inspectionDate: _inspectionDate,
          locationId: locationId,
          locationName: _locationName(location),
        ),
      ),
    );

    if (didSave == true && mounted) {
      Navigator.pop(context, true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New Fire Extinguisher')),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _LoadError(message: _error!, onRetry: _loadLocations)
                : SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Inspection Data',
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 16),
                            TextFormField(
                              key: ValueKey(_inspectionDate),
                              initialValue: _inspectionDate,
                              readOnly: true,
                              decoration: const InputDecoration(
                                labelText: 'Inspection Date',
                                helperText: 'Tanggal inspeksi hari ini',
                                prefixIcon: Icon(Icons.calendar_today_outlined),
                              ),
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<int>(
                              initialValue: _locationId,
                              isExpanded: true,
                              decoration: const InputDecoration(
                                labelText: 'Location',
                                prefixIcon: Icon(Icons.location_on_outlined),
                              ),
                              hint: const Text('- Select Location -'),
                              items: _locations
                                  .map(_asMap)
                                  .map((location) {
                                    final id = _asInt(location['id_location'] ??
                                        location['id']);
                                    if (id == null) return null;
                                    return DropdownMenuItem<int>(
                                      value: id,
                                      child: Text(
                                        _locationOption(location),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    );
                                  })
                                  .whereType<DropdownMenuItem<int>>()
                                  .toList(),
                              onChanged: (value) =>
                                  setState(() => _locationId = value),
                            ),
                            const SizedBox(height: 24),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                onPressed: _openScanner,
                                icon: const Icon(Icons.qr_code_scanner),
                                label: const Text('Scan QR Code'),
                                style: FilledButton.styleFrom(
                                  minimumSize: const Size.fromHeight(50),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
      ),
    );
  }
}

class FireExtinguisherQrScannerScreen extends StatefulWidget {
  const FireExtinguisherQrScannerScreen({
    super.key,
    required this.inspectionDate,
    required this.locationId,
    required this.locationName,
  });

  final String inspectionDate;
  final int locationId;
  final String locationName;

  @override
  State<FireExtinguisherQrScannerScreen> createState() =>
      _FireExtinguisherQrScannerScreenState();
}

class _FireExtinguisherQrScannerScreenState
    extends State<FireExtinguisherQrScannerScreen> {
  final MobileScannerController _scannerController = MobileScannerController(
    autoStart: false,
    detectionSpeed: DetectionSpeed.noDuplicates,
    formats: const [BarcodeFormat.qrCode],
  );

  List<Map<String, dynamic>> _points = [];
  bool _isLoading = true;
  bool _isHandlingScan = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPointsAndStart();
  }

  Future<void> _loadPointsAndStart() async {
    try {
      final points = await context.read<ApiService>().getPoints();
      final extinguisherPoints = points
          .map(_asMap)
          .where(_isFireExtinguisherPoint)
          .toList(growable: false);
      if (!mounted) return;
      setState(() {
        _points = extinguisherPoints;
        _isLoading = false;
        _error = extinguisherPoints.isEmpty
            ? 'Data point Fire Extinguisher tidak ditemukan.'
            : null;
      });
      if (_error == null) await _scannerController.start();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  Future<void> _handleDetection(BarcodeCapture capture) async {
    if (_isHandlingScan || _isLoading) return;

    final value = capture.barcodes
        .map((barcode) => barcode.rawValue?.trim())
        .whereType<String>()
        .where((text) => text.isNotEmpty)
        .firstOrNull;
    if (value == null) return;

    _isHandlingScan = true;
    await _scannerController.stop();

    final point = _findPoint(value);
    if (point == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'QR Code tidak terdaftar sebagai point Fire Extinguisher.',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
      _isHandlingScan = false;
      if (mounted) await _scannerController.start();
      return;
    }

    if (!mounted) return;
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => FireExtinguisherQrFormScreen(
          inspectionDate: widget.inspectionDate,
          locationId: widget.locationId,
          locationName: widget.locationName,
          point: point,
          scannedQr: value,
        ),
      ),
    );

    if (!mounted) return;
    if (didSave == true) {
      Navigator.pop(context, true);
      return;
    }

    _isHandlingScan = false;
    await _scannerController.start();
  }

  Map<String, dynamic>? _findPoint(String qrValue) {
    final normalized = qrValue.trim().toUpperCase();

    for (final point in _points) {
      final storedQr = point['qr_code']?.toString().trim().toUpperCase();
      if (storedQr != null && storedQr.isNotEmpty && storedQr == normalized) {
        return point;
      }
    }

    int? pointId;
    try {
      final decoded = jsonDecode(qrValue);
      if (decoded is Map) {
        final data = Map<String, dynamic>.from(decoded);
        pointId = _asInt(
          data['point_id'] ??
              data['asset_id'] ??
              data['id_point'] ??
              data['id'],
        );
        final embeddedQr = data['qr_code']?.toString().trim().toUpperCase();
        if (embeddedQr != null && embeddedQr.isNotEmpty) {
          for (final point in _points) {
            if (point['qr_code']?.toString().trim().toUpperCase() ==
                embeddedQr) {
              return point;
            }
          }
        }
      }
    } catch (_) {
      // QR point dari backend dapat berupa plain text.
    }

    final pointMatch = RegExp(r'^POINT-(\d+)(?:-|$)', caseSensitive: false)
        .firstMatch(qrValue);
    pointId ??= pointMatch == null ? null : int.tryParse(pointMatch.group(1)!);

    final legacyMatch = RegExp(
      r'^(?:FIRE[_-]?EXTINGUISHER|APAR):(\d+)$',
      caseSensitive: false,
    ).firstMatch(qrValue);
    pointId ??=
        legacyMatch == null ? null : int.tryParse(legacyMatch.group(1)!);

    if (pointId == null) return null;
    for (final point in _points) {
      if (_asInt(point['id']) == pointId) return point;
    }
    return null;
  }

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('Scan QR Fire Extinguisher'),
        actions: [
          IconButton(
            onPressed: _isLoading ? null : _scannerController.toggleTorch,
            icon: const Icon(Icons.flashlight_on_outlined),
            tooltip: 'Flash',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ColoredBox(
                  color: Theme.of(context).scaffoldBackgroundColor,
                  child: _LoadError(
                    message: _error!,
                    onRetry: () {
                      setState(() {
                        _isLoading = true;
                        _error = null;
                      });
                      _loadPointsAndStart();
                    },
                  ),
                )
              : Stack(
                  fit: StackFit.expand,
                  children: [
                    MobileScanner(
                      controller: _scannerController,
                      onDetect: _handleDetection,
                    ),
                    const _ScannerOverlay(),
                    Positioned(
                      left: 20,
                      right: 20,
                      bottom: 28,
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.68),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Text(
                              'Arahkan kamera ke QR Code Fire Extinguisher',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${widget.inspectionDate} • '
                              '${widget.locationName}',
                              textAlign: TextAlign.center,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

class FireExtinguisherQrFormScreen extends StatefulWidget {
  const FireExtinguisherQrFormScreen({
    super.key,
    required this.inspectionDate,
    required this.locationId,
    required this.locationName,
    required this.point,
    required this.scannedQr,
  });

  final String inspectionDate;
  final int locationId;
  final String locationName;
  final Map<String, dynamic> point;
  final String scannedQr;

  @override
  State<FireExtinguisherQrFormScreen> createState() =>
      _FireExtinguisherQrFormScreenState();
}

class _FireExtinguisherQrFormScreenState
    extends State<FireExtinguisherQrFormScreen> {
  final ImagePicker _picker = ImagePicker();
  late final TextEditingController _remarkController;
  late final TextEditingController _expiryDateController;
  bool _pressureCondition = true;
  bool _sealCondition = true;
  bool _nozzleCondition = true;
  bool _isSaving = false;
  File? _photoBefore;
  File? _photoAfter;

  String get _assetName =>
      (widget.point['name_point'] ?? widget.point['id'] ?? '').toString();
  String get _assetType => widget.point['ket1']?.toString().trim() ?? '';
  String get _locationDetail => widget.point['ket2']?.toString().trim() ?? '';

  @override
  void initState() {
    super.initState();
    _remarkController = TextEditingController();
    _expiryDateController = TextEditingController();
  }

  @override
  void dispose() {
    _remarkController.dispose();
    _expiryDateController.dispose();
    super.dispose();
  }

  Future<void> _pickExpiryDate() async {
    final current = DateTime.tryParse(_expiryDateController.text) ??
        DateTime(DateTime.now().year + 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      _expiryDateController.text = picked.toIso8601String().split('T').first;
    }
  }

  Future<void> _pickPhoto(bool isBefore) async {
    final selected = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 75,
      maxWidth: 1600,
    );
    if (selected == null || !mounted) return;
    setState(() {
      if (isBefore) {
        _photoBefore = File(selected.path);
      } else {
        _photoAfter = File(selected.path);
      }
    });
  }

  Future<void> _save() async {
    final pointId = _asInt(widget.point['id']);
    if (pointId == null || _assetName.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Detail APAR dari QR Code tidak lengkap.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isSaving = true);
    final currentUser = _asMap(context.read<AuthService>().user);
    final inspectorId = _asInt(currentUser['id']);
    final fields = <String, String>{
      'inspection_date': widget.inspectionDate,
      'location_id': widget.locationId.toString(),
      if (inspectorId != null) 'inspector_id': inspectorId.toString(),
      'point_id': pointId.toString(),
      'item[pressure_condition]': _pressureCondition ? '1' : '0',
      'item[seal_condition]': _sealCondition ? '1' : '0',
      'item[nozzle_condition]': _nozzleCondition ? '1' : '0',
      'item[remark]': _nullIfEmpty(_remarkController.text) ?? '',
      'item[expiry_date]': _nullIfEmpty(_expiryDateController.text) ?? '',
    };

    try {
      final response =
          await context.read<ApiService>().createFireExtinguisherWithPhotos(
                fields,
                photoBefore: _photoBefore,
                photoAfter: _photoAfter,
              );
      if (!mounted) return;
      if (response.containsKey('error')) {
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['error'].toString()),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Fire Extinguisher berhasil disimpan.')),
      );
      Navigator.pop(context, true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Gagal menyimpan inspeksi: $error'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentUser = _asMap(context.watch<AuthService>().user);
    final inspectorName = (currentUser['name'] ??
            currentUser['username'] ??
            currentUser['email'] ??
            'Current User')
        .toString();

    return Scaffold(
      appBar: AppBar(title: const Text('Create Fire Extinguisher')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _FormSection(
                title: 'Inspection Data',
                child: Column(
                  children: [
                    _ReadOnlyField(
                      label: 'Inspection Date',
                      value: widget.inspectionDate,
                      icon: Icons.calendar_today_outlined,
                    ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Location',
                      value: widget.locationName,
                      icon: Icons.location_on_outlined,
                    ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Inspector',
                      value: inspectorName,
                      icon: Icons.badge_outlined,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _FormSection(
                title: 'Fire Extinguisher Detail',
                trailing: const Chip(
                  avatar: Icon(Icons.qr_code_2, size: 18),
                  label: Text('QR Scanned'),
                ),
                child: Column(
                  children: [
                    _ReadOnlyField(
                      label: 'APAR Number',
                      value: _assetName,
                      icon: Icons.numbers,
                    ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Type',
                      value: _assetType,
                      icon: Icons.fire_extinguisher,
                    ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Location Detail',
                      value: _locationDetail,
                      icon: Icons.place_outlined,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _FormSection(
                title: 'Fire Extinguisher Condition',
                child: Column(
                  children: [
                    _ConditionYesNo(
                      label: 'Pressure',
                      value: _pressureCondition,
                      onChanged: (value) =>
                          setState(() => _pressureCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Seal',
                      value: _sealCondition,
                      onChanged: (value) =>
                          setState(() => _sealCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Nozzle',
                      value: _nozzleCondition,
                      onChanged: (value) =>
                          setState(() => _nozzleCondition = value),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _expiryDateController,
                      readOnly: true,
                      onTap: _pickExpiryDate,
                      decoration: const InputDecoration(
                        labelText: 'Expiry Date',
                        prefixIcon: Icon(Icons.event_outlined),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _remarkController,
                      minLines: 2,
                      maxLines: 4,
                      decoration: const InputDecoration(labelText: 'Remark'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _FormSection(
                title: 'Photos',
                child: Column(
                  children: [
                    _PhotoPicker(
                      label: 'Foto Sebelum (Before)',
                      file: _photoBefore,
                      onPick: () => _pickPhoto(true),
                      onClear: () => setState(() => _photoBefore = null),
                    ),
                    const SizedBox(height: 12),
                    _PhotoPicker(
                      label: 'Foto Sesudah (After)',
                      file: _photoAfter,
                      onPick: () => _pickPhoto(false),
                      onClear: () => setState(() => _photoAfter = null),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed:
                          _isSaving ? null : () => Navigator.pop(context),
                      icon: const Icon(Icons.qr_code_scanner),
                      label: const Text('Scan Ulang'),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(50),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _isSaving ? null : _save,
                      icon: _isSaving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.save_outlined),
                      label: Text(_isSaving ? 'Saving...' : 'Save'),
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(50),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ScannerOverlay extends StatelessWidget {
  const _ScannerOverlay();

  @override
  Widget build(BuildContext context) {
    return const IgnorePointer(child: CustomPaint(painter: _ScannerPainter()));
  }
}

class _ScannerPainter extends CustomPainter {
  const _ScannerPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final scanSize = size.width.clamp(220.0, 280.0);
    final rect = Rect.fromCenter(
      center: Offset(size.width / 2, size.height / 2 - 30),
      width: scanSize,
      height: scanSize,
    );
    final overlay = Path()
      ..addRect(Offset.zero & size)
      ..addRRect(RRect.fromRectAndRadius(rect, const Radius.circular(18)))
      ..fillType = PathFillType.evenOdd;
    canvas.drawPath(
      overlay,
      Paint()..color = Colors.black.withValues(alpha: 0.52),
    );
    canvas.drawRRect(
      RRect.fromRectAndRadius(rect, const Radius.circular(18)),
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.stroke
        ..strokeWidth = 3,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _FormSection extends StatelessWidget {
  const _FormSection({
    required this.title,
    required this.child,
    this.trailing,
  });

  final String title;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                if (trailing != null) trailing!,
              ],
            ),
            const SizedBox(height: 16),
            child,
          ],
        ),
      ),
    );
  }
}

class _ReadOnlyField extends StatelessWidget {
  const _ReadOnlyField({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      key: ValueKey('$label-$value'),
      initialValue: value.isEmpty ? '-' : value,
      readOnly: true,
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
    );
  }
}

class _ConditionYesNo extends StatelessWidget {
  const _ConditionYesNo({
    required this.label,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          SegmentedButton<bool>(
            segments: const [
              ButtonSegment(value: true, label: Text('YES')),
              ButtonSegment(value: false, label: Text('NO')),
            ],
            selected: {value},
            showSelectedIcon: false,
            onSelectionChanged: (selection) => onChanged(selection.first),
          ),
        ],
      ),
    );
  }
}

class _PhotoPicker extends StatelessWidget {
  const _PhotoPicker({
    required this.label,
    required this.file,
    required this.onPick,
    required this.onClear,
  });

  final String label;
  final File? file;
  final VoidCallback onPick;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).dividerColor),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w700)),
          if (file != null) ...[
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.file(
                file!,
                height: 130,
                width: double.infinity,
                fit: BoxFit.cover,
              ),
            ),
          ],
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onPick,
                  icon: const Icon(Icons.camera_alt_outlined),
                  label: Text(file == null ? 'Take Photo' : 'Retake'),
                ),
              ),
              if (file != null) ...[
                const SizedBox(width: 8),
                IconButton(
                  onPressed: onClear,
                  icon: const Icon(Icons.delete_outline),
                  tooltip: 'Remove photo',
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _LoadError extends StatelessWidget {
  const _LoadError({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 48),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

bool _isFireExtinguisherPoint(Map<String, dynamic> point) {
  final type = point['ket1']?.toString().trim().toUpperCase() ?? '';
  if (type.isEmpty) return false;
  if (type.contains('FIRE EXTINGUISHER') ||
      type.contains('CLEAN AGENT') ||
      type.contains('CLEANT AGENT') ||
      type.contains('HALOTRON') ||
      type.contains('POWDER') ||
      type.contains('FOAM') ||
      type.contains(' KG') ||
      type.endsWith('KG')) {
    return true;
  }
  return RegExp(r'^(?:DC|CO2?|CA|HF)(?:\s|[-–])').hasMatch(type);
}

Map<String, dynamic> _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

int? _asInt(dynamic value) {
  if (value is int) return value;
  return value == null ? null : int.tryParse(value.toString());
}

String _locationName(Map<String, dynamic> location) {
  return (location['name'] ?? location['location_name'] ?? 'Location')
      .toString();
}

String _locationOption(Map<String, dynamic> location) {
  final id = (location['id_location'] ?? location['id'])?.toString() ?? '';
  final name = _locationName(location);
  return id.isEmpty ? name : '$id - $name';
}

String? _nullIfEmpty(String value) {
  final text = value.trim();
  return text.isEmpty ? null : text;
}
