import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../services/connectivity_service.dart';
import '../../services/offline_storage_service.dart';
import '../../widgets/point_dropdown.dart';

class FireHydrantSetupScreen extends StatefulWidget {
  const FireHydrantSetupScreen({super.key});

  @override
  State<FireHydrantSetupScreen> createState() => _FireHydrantSetupScreenState();
}

class _FireHydrantSetupScreenState extends State<FireHydrantSetupScreen> {
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
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> locations;
      if (connectivity.isOnline) {
        locations =
            await context.read<ApiService>().getFireHydrantLocations();
        unawaited(OfflineStorageService.instance
            .saveCache('hydrant_locations', locations));
      } else {
        locations = await OfflineStorageService.instance
                .readCache('hydrant_locations') ??
            [];
      }
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
          content: Text('Pilih location terlebih dahulu.'),
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
        builder: (_) => FireHydrantQrScannerScreen(
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

  Future<void> _openManualForm() async {
    final locationId = _locationId;
    if (locationId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih location terlebih dahulu.'),
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
        builder: (_) => FireHydrantCreateScreen(
          inspectionDate: _inspectionDate,
          locationId: locationId,
          locationName: _locationName(location),
          isManual: true,
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
      appBar: AppBar(title: const Text('New Fire Hydrant Inspection')),
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
                                border: OutlineInputBorder(),
                              ),
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<int>(
                              initialValue: _locationId,
                              isExpanded: true,
                              decoration: const InputDecoration(
                                labelText: 'Location',
                                prefixIcon: Icon(Icons.location_on_outlined),
                                border: OutlineInputBorder(),
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
                                icon: const Icon(Icons.add),
                                label: const Text('Add Form'),
                                style: FilledButton.styleFrom(
                                  minimumSize: const Size.fromHeight(50),
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton.icon(
                                onPressed: _openManualForm,
                                icon: const Icon(Icons.edit_note_outlined),
                                label: const Text('Add Manual Form'),
                                style: OutlinedButton.styleFrom(
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

class FireHydrantQrScannerScreen extends StatefulWidget {
  const FireHydrantQrScannerScreen({
    super.key,
    required this.inspectionDate,
    required this.locationId,
    required this.locationName,
  });

  final String inspectionDate;
  final int locationId;
  final String locationName;

  @override
  State<FireHydrantQrScannerScreen> createState() =>
      _FireHydrantQrScannerScreenState();
}

class _FireHydrantQrScannerScreenState
    extends State<FireHydrantQrScannerScreen> {
  final MobileScannerController _scannerController = MobileScannerController(
    autoStart: false,
    detectionSpeed: DetectionSpeed.noDuplicates,
    formats: const [BarcodeFormat.qrCode],
  );

  List<dynamic> _points = [];
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
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> points;
      if (connectivity.isOnline) {
        points = await context.read<ApiService>().getPoints();
        unawaited(OfflineStorageService.instance.saveCache('points', points));
      } else {
        points = await OfflineStorageService.instance.readCache('points') ?? [];
      }
      if (!mounted) return;
      if (!connectivity.isOnline && points.isEmpty) {
        setState(() {
          _isLoading = false;
          _error = 'Mode offline: data titik belum di-cache. '
              'Hubungkan ke internet lalu buka aplikasi sekali '
              'agar data QR Code tersimpan lokal.';
        });
        return;
      }
      setState(() {
        _points = points;
        _isLoading = false;
        _error = null;
      });
      await _scannerController.start();
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
              'QR Code tidak terdaftar sebagai titik Fire Hydrant.',
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
        builder: (_) => FireHydrantCreateScreen(
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
    final points = _points.map(_asMap).toList();

    for (final point in points) {
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
        if (embeddedQr != null) {
          for (final point in points) {
            if (point['qr_code']?.toString().trim().toUpperCase() ==
                embeddedQr) {
              return point;
            }
          }
        }
      }
    } catch (_) {
      // QR point yang dibuat backend berupa plain text, bukan JSON.
    }

    final pointMatch = RegExp(r'^POINT-(\d+)(?:-|$)', caseSensitive: false)
        .firstMatch(qrValue);
    pointId ??= pointMatch == null ? null : int.tryParse(pointMatch.group(1)!);

    final legacyMatch =
        RegExp(r'^FIRE[_-]?HYDRANT:(\d+)$', caseSensitive: false)
            .firstMatch(qrValue);
    pointId ??=
        legacyMatch == null ? null : int.tryParse(legacyMatch.group(1)!);

    if (pointId == null) return null;
    for (final point in points) {
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
        title: const Text('Scan QR Code'),
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
                              'Arahkan kamera ke QR Code Fire Hydrant',
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

class FireHydrantCreateScreen extends StatefulWidget {
  const FireHydrantCreateScreen({
    super.key,
    required this.inspectionDate,
    required this.locationId,
    required this.locationName,
    this.point,
    this.scannedQr,
    this.isManual = false,
  });

  final String inspectionDate;
  final int locationId;
  final String locationName;
  final Map<String, dynamic>? point;
  final String? scannedQr;
  final bool isManual;

  @override
  State<FireHydrantCreateScreen> createState() =>
      _FireHydrantCreateScreenState();
}

class _FireHydrantCreateScreenState extends State<FireHydrantCreateScreen> {
  final ImagePicker _picker = ImagePicker();
  late final TextEditingController _remarkController;
  bool _hoseCondition = true;
  bool _nozzleCondition = true;
  bool _couplingCondition = true;
  bool _wrenchCondition = true;
  bool _valveCondition = true;
  bool _couplingExtraCondition = true;
  bool _isSaving = false;
  bool _isLoadingPoints = false;
  List<dynamic> _points = [];
  Map<String, dynamic>? _selectedPoint;
  File? _photoBefore;
  File? _photoAfter;

  Map<String, dynamic> get _activePoint =>
      _selectedPoint ?? widget.point ?? <String, dynamic>{};

  String get _hydrantNumber =>
      (_activePoint['name_point'] ?? _activePoint['id'] ?? '').toString();

  String get _hydrantName {
    final value = _activePoint['ket1']?.toString().trim() ?? '';
    return value.isEmpty ? _hydrantNumber : value;
  }

  String get _locationDetail =>
      _activePoint['ket2']?.toString().trim() ?? '';

  @override
  void initState() {
    super.initState();
    _remarkController = TextEditingController();
    if (widget.isManual) {
      _loadPoints();
    }
  }

  Future<void> _loadPoints() async {
    setState(() => _isLoadingPoints = true);
    try {
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> points;
      if (connectivity.isOnline) {
        points = await context.read<ApiService>().getPoints();
        unawaited(OfflineStorageService.instance.saveCache('points', points));
      } else {
        points = await OfflineStorageService.instance.readCache('points') ?? [];
      }
      if (!mounted) return;
      setState(() {
        _points = points;
        _isLoadingPoints = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoadingPoints = false);
    }
  }

  @override
  void dispose() {
    _remarkController.dispose();
    super.dispose();
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
    if (_hydrantNumber.trim().isEmpty || _hydrantName.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.isManual
                ? 'Pilih Hydrant Number terlebih dahulu.'
                : 'Detail hydrant dari QR Code tidak lengkap.',
          ),
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
      'items[0][hydrant_number]': _hydrantNumber,
      'items[0][name]': _hydrantName,
      'items[0][location_detail]': _nullIfEmpty(_locationDetail) ?? '',
      'items[0][hose_condition]': _hoseCondition ? '1' : '0',
      'items[0][nozzle_condition]': _nozzleCondition ? '1' : '0',
      'items[0][coupling_condition]': _couplingCondition ? '1' : '0',
      'items[0][wrench_condition]': _wrenchCondition ? '1' : '0',
      'items[0][valve_condition]': _valveCondition ? '1' : '0',
      'items[0][coupling_extra_condition]':
          _couplingExtraCondition ? '1' : '0',
      'items[0][remark]': _nullIfEmpty(_remarkController.text) ?? '',
    };
    final files = <String, File?>{
      'items[0][photo_before]': _photoBefore,
      'items[0][photo_after]': _photoAfter,
    };

    try {
      final connectivity = context.read<ConnectivityService>();
      final api = context.read<ApiService>();

      if (!connectivity.isOnline) {
        final fileMap = <String, String>{
          if (_photoBefore != null) 'items[0][photo_before]': _photoBefore!.path,
          if (_photoAfter != null) 'items[0][photo_after]': _photoAfter!.path,
        };
        await OfflineStorageService.instance.enqueueDraft(
          endpoint: '/fire-hydrants',
          method: 'POST',
          fields: fields,
          files: fileMap,
          displayName: 'Fire Hydrant $_hydrantNumber',
        );
        if (!mounted) return;
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Offline: inspeksi Fire Hydrant disimpan sebagai draft. '
              'Akan disinkronkan saat online.',
            ),
            backgroundColor: Colors.orange,
          ),
        );
        Navigator.pop(context, true);
        return;
      }

      final response = await api.createFireHydrantWithPhotos(
            fields,
            files,
          );
      if (!mounted) return;

      if (response.containsKey('error')) {
        debugPrint('[HydrantCreate] Server error: ${response['error']}');
        await _enqueueRetry(fields, files);
        if (!mounted) return;
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${response['error']}\nData disimpan sebagai draft, '
              'akan disinkronkan otomatis.',
            ),
            backgroundColor: Colors.orange,
          ),
        );
        Navigator.pop(context, true);
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Fire Hydrant inspection berhasil disimpan.'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context, true);
    } catch (error) {
      debugPrint('[HydrantCreate] Exception: $error');
      await _enqueueRetry(fields, files);
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Gagal terkirim ($error), data disimpan sebagai draft. '
            'Akan disinkronkan otomatis saat online.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      Navigator.pop(context, true);
    }
  }

  Future<void> _enqueueRetry(
    Map<String, String> fields,
    Map<String, File?> files,
  ) async {
    final fileMap = <String, String>{
      for (final entry in files.entries)
        if (entry.value != null) entry.key: entry.value!.path,
    };
    await OfflineStorageService.instance.enqueueDraft(
      endpoint: '/fire-hydrants',
      method: 'POST',
      fields: fields,
      files: fileMap,
      displayName: 'Fire Hydrant $_hydrantNumber',
    );
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
      appBar: AppBar(title: const Text('Create Fire Hydrant')),
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
                      icon: Icons.person_outline,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _FormSection(
                title: 'Hydrant Detail',
                trailing: Chip(
                  avatar: Icon(
                    widget.isManual ? Icons.edit_note : Icons.qr_code_2,
                    size: 18,
                  ),
                  label: Text(widget.isManual ? 'Manual' : 'QR Scanned'),
                ),
                child: Column(
                  children: [
                    if (widget.isManual)
                      PointDropdown(
                        label: 'Hydrant Number',
                        points: _points,
                        selectedId: _asInt(_selectedPoint?['id']),
                        isLoading: _isLoadingPoints,
                        onChanged: (point) =>
                            setState(() => _selectedPoint = point),
                      )
                    else
                      _ReadOnlyField(
                        label: 'Hydrant Number',
                        value: _hydrantNumber,
                        icon: Icons.numbers,
                      ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Name',
                      value: _hydrantName,
                      icon: Icons.fire_hydrant_alt_outlined,
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
                title: 'Hydrant Condition',
                child: Column(
                  children: [
                    _ConditionYesNo(
                      label: 'Hose',
                      value: _hoseCondition,
                      onChanged: (value) =>
                          setState(() => _hoseCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Nozzle',
                      value: _nozzleCondition,
                      onChanged: (value) =>
                          setState(() => _nozzleCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Coupling',
                      value: _couplingCondition,
                      onChanged: (value) =>
                          setState(() => _couplingCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Wrench',
                      value: _wrenchCondition,
                      onChanged: (value) =>
                          setState(() => _wrenchCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Valve',
                      value: _valveCondition,
                      onChanged: (value) =>
                          setState(() => _valveCondition = value),
                    ),
                    _ConditionYesNo(
                      label: 'Extra Coupling',
                      value: _couplingExtraCondition,
                      onChanged: (value) =>
                          setState(() => _couplingExtraCondition = value),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _remarkController,
                      minLines: 2,
                      maxLines: 4,
                      decoration: const InputDecoration(
                        labelText: 'Remark',
                        border: OutlineInputBorder(),
                      ),
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
                  if (!widget.isManual) ...[
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
                  ],
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
    return IgnorePointer(
      child: CustomPaint(
        painter: _ScannerOverlayPainter(),
      ),
    );
  }
}

class _ScannerOverlayPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final scanSize = size.width.clamp(220.0, 280.0);
    final rect = Rect.fromCenter(
      center: Offset(size.width / 2, size.height * 0.42),
      width: scanSize,
      height: scanSize,
    );
    final overlay = Path()
      ..addRect(Offset.zero & size)
      ..addRRect(RRect.fromRectAndRadius(rect, const Radius.circular(18)))
      ..fillType = PathFillType.evenOdd;
    canvas.drawPath(
      overlay,
      Paint()..color = Colors.black.withValues(alpha: 0.5),
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
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        filled: true,
        fillColor: const Color(0xFFF8FAFC),
        border: const OutlineInputBorder(),
      ),
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
      padding: const EdgeInsets.symmetric(vertical: 6),
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
              ButtonSegment<bool>(value: true, label: Text('YES')),
              ButtonSegment<bool>(value: false, label: Text('NO')),
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
            FilledButton.icon(
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

Map<String, dynamic> _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

int? _asInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '');
}

String _locationName(Map<String, dynamic> location) {
  return (location['name'] ??
          location['location_name'] ??
          location['description'] ??
          'Location')
      .toString();
}

String _locationOption(Map<String, dynamic> location) {
  final id = location['id_location'] ?? location['id'];
  final name = _locationName(location);
  return id == null ? name : '$id - $name';
}

dynamic _nullIfEmpty(String value) {
  final text = value.trim();
  return text.isEmpty ? null : text;
}
