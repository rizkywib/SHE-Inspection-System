import 'dart:async';
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
import 'es_ew_common.dart';

class EsEwSetupScreen extends StatefulWidget {
  const EsEwSetupScreen({super.key});

  @override
  State<EsEwSetupScreen> createState() => _EsEwSetupScreenState();
}

class _EsEwSetupScreenState extends State<EsEwSetupScreen> {
  late final String _inspectionDate;
  List<dynamic> _areas = [];
  int? _areaId;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _inspectionDate = DateTime.now().toIso8601String().split('T').first;
    _loadMasterData();
  }

  Future<void> _loadMasterData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final connectivity = context.read<ConnectivityService>();
      Map<String, dynamic> master;
      if (connectivity.isOnline) {
        master = await context.read<ApiService>().getEsEwMasterData();
        unawaited(OfflineStorageService.instance
            .saveCacheJson('es_ew_master', master));
      } else {
        master = await OfflineStorageService.instance
                .readCacheSingle('es_ew_master') ??
            {};
      }
      if (!mounted) return;
      setState(() {
        _areas = esEwList(master['areas']);
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
    final areaId = _areaId;
    if (areaId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih area terlebih dahulu.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final area = _areas.map(esEwMap).firstWhere(
          (value) => esEwInt(value['id']) == areaId,
          orElse: () => <String, dynamic>{},
        );
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => EsEwQrScannerScreen(
          inspectionDate: _inspectionDate,
          areaId: areaId,
          areaName: esEwText(area['name']),
        ),
      ),
    );
    if (didSave == true && mounted) Navigator.pop(context, true);
  }

  Future<void> _openManualForm() async {
    final areaId = _areaId;
    if (areaId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih area terlebih dahulu.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final area = _areas.map(esEwMap).firstWhere(
          (value) => esEwInt(value['id']) == areaId,
          orElse: () => <String, dynamic>{},
        );
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => EsEwCreateScreen(
          inspectionDate: _inspectionDate,
          areaId: areaId,
          areaName: esEwText(area['name']),
          isManual: true,
        ),
      ),
    );
    if (didSave == true && mounted) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New ES/EW Inspection')),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _LoadError(message: _error!, onRetry: _loadMasterData)
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
                            _ReadOnlyField(
                              label: 'Inspection Date',
                              value: _inspectionDate,
                              icon: Icons.calendar_today_outlined,
                            ),
                            const SizedBox(height: 16),
                            DropdownButtonFormField<int>(
                              initialValue: _areaId,
                              isExpanded: true,
                              decoration: const InputDecoration(
                                labelText: 'Area',
                                prefixIcon: Icon(Icons.map_outlined),
                                border: OutlineInputBorder(),
                              ),
                              hint: const Text('- Select Area -'),
                              items: _areas.map(esEwMap).map((area) {
                                final id = esEwInt(area['id']);
                                return DropdownMenuItem<int>(
                                  value: id,
                                  child: Text(
                                    esEwText(area['name']),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                );
                              }).toList(),
                              onChanged: (value) =>
                                  setState(() => _areaId = value),
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

class EsEwQrScannerScreen extends StatefulWidget {
  const EsEwQrScannerScreen({
    super.key,
    required this.inspectionDate,
    required this.areaId,
    required this.areaName,
  });

  final String inspectionDate;
  final int areaId;
  final String areaName;

  @override
  State<EsEwQrScannerScreen> createState() => _EsEwQrScannerScreenState();
}

class _EsEwQrScannerScreenState extends State<EsEwQrScannerScreen> {
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
        _points = points.where(isEsEwPoint).toList(growable: false);
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
    final point = findEsEwPointByQr(value, _points);
    if (point == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('QR Code tidak terdaftar sebagai titik ES/EW.'),
            backgroundColor: Colors.red,
          ),
        );
        _isHandlingScan = false;
        await _scannerController.start();
      }
      return;
    }

    if (!mounted) return;
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => EsEwCreateScreen(
          inspectionDate: widget.inspectionDate,
          areaId: widget.areaId,
          areaName: widget.areaName,
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
        title: const Text('Scan QR ES/EW'),
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
                              'Arahkan kamera ke QR Code ES/EW',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${widget.inspectionDate} • ${widget.areaName}',
                              textAlign: TextAlign.center,
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

class EsEwCreateScreen extends StatefulWidget {
  const EsEwCreateScreen({
    super.key,
    required this.inspectionDate,
    required this.areaId,
    required this.areaName,
    this.point,
    this.scannedQr,
    this.isManual = false,
  });

  final String inspectionDate;
  final int areaId;
  final String areaName;
  final Map<String, dynamic>? point;
  final String? scannedQr;
  final bool isManual;

  @override
  State<EsEwCreateScreen> createState() => _EsEwCreateScreenState();
}

class _EsEwCreateScreenState extends State<EsEwCreateScreen> {
  final ImagePicker _picker = ImagePicker();
  late final TextEditingController _remarkController;
  late final Map<String, bool> _conditions;
  List<dynamic> _points = [];
  Map<String, dynamic>? _selectedPoint;
  bool _isLoadingPoints = false;
  File? _eyeWashPhoto;
  File? _emergencyShowerPhoto;
  bool _isSaving = false;

  Map<String, dynamic> get _activePoint =>
      _selectedPoint ?? widget.point ?? <String, dynamic>{};

  @override
  void initState() {
    super.initState();
    _remarkController = TextEditingController();
    _conditions = {
      for (final field in esEwConditionLabels.keys) field: true,
    };
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
        _points = points.where(isEsEwPoint).toList(growable: false);
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

  Future<void> _pickPhoto(bool isEyeWash) async {
    final selected = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 75,
      maxWidth: 1600,
    );
    if (selected == null || !mounted) return;
    setState(() {
      if (isEyeWash) {
        _eyeWashPhoto = File(selected.path);
      } else {
        _emergencyShowerPhoto = File(selected.path);
      }
    });
  }

  Future<void> _save() async {
    final pointId = esEwInt(_activePoint['id']);
    if (pointId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.isManual
                ? 'Pilih ES/EW Name terlebih dahulu.'
                : 'Point ES/EW dari QR Code tidak valid.',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isSaving = true);
    final currentUser = esEwMap(context.read<AuthService>().user);
    final inspectorId = esEwInt(currentUser['id']);
    final fields = <String, String>{
      'inspection_date': widget.inspectionDate,
      'area_id': widget.areaId.toString(),
      'point_id': pointId.toString(),
      if (inspectorId != null) 'inspector_id': inspectorId.toString(),
      'items[0][remark]': _remarkController.text.trim(),
      for (final entry in _conditions.entries)
        'items[0][${entry.key}]': entry.value ? '1' : '0',
    };

    try {
      final connectivity = context.read<ConnectivityService>();
      final api = context.read<ApiService>();

      if (!connectivity.isOnline) {
        final fileMap = <String, String>{
          if (_eyeWashPhoto != null)
            'items[0][photo_before]': _eyeWashPhoto!.path,
          if (_emergencyShowerPhoto != null)
            'items[0][photo_after]': _emergencyShowerPhoto!.path,
        };
        await OfflineStorageService.instance.enqueueDraft(
          endpoint: '/es-ew',
          method: 'POST',
          fields: fields,
          files: fileMap,
          displayName: 'ES/EW ${esEwText(_activePoint['name_point'])}',
        );
        if (!mounted) return;
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Offline: inspeksi ES/EW disimpan sebagai draft. '
              'Akan disinkronkan saat online.',
            ),
            backgroundColor: Colors.orange,
          ),
        );
        Navigator.pop(context, true);
        return;
      }

      final response = await api.createEsEw(
            fields,
            eyeWashPhoto: _eyeWashPhoto,
            emergencyShowerPhoto: _emergencyShowerPhoto,
          );
      if (!mounted) return;
      if (response.containsKey('error')) {
        await _enqueueRetry(fields);
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
          content: Text('ES/EW inspection berhasil disimpan.'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context, true);
    } catch (error) {
      await _enqueueRetry(fields);
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

  Future<void> _enqueueRetry(Map<String, String> fields) async {
    final fileMap = <String, String>{
      if (_eyeWashPhoto != null)
        'items[0][photo_before]': _eyeWashPhoto!.path,
      if (_emergencyShowerPhoto != null)
        'items[0][photo_after]': _emergencyShowerPhoto!.path,
    };
    await OfflineStorageService.instance.enqueueDraft(
      endpoint: '/es-ew',
      method: 'POST',
      fields: fields,
      files: fileMap,
      displayName: 'ES/EW ${esEwText(_activePoint['name_point'])}',
    );
  }

  @override
  Widget build(BuildContext context) {
    final currentUser = esEwMap(context.watch<AuthService>().user);
    final inspectorName = esEwText(
      currentUser['name'] ??
          currentUser['username'] ??
          currentUser['email'] ??
          'Current User',
    );
    return Scaffold(
      appBar: AppBar(title: const Text('Create ES/EW Inspection')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
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
                      label: 'Area',
                      value: widget.areaName,
                      icon: Icons.map_outlined,
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
                title: 'ES/EW Detail',
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
                        label: 'ES/EW Name',
                        points: _points,
                        selectedId: esEwInt(_selectedPoint?['id']),
                        isLoading: _isLoadingPoints,
                        icon: Icons.shower_outlined,
                        optionBuilder: esEwPointOption,
                        onChanged: (point) =>
                            setState(() => _selectedPoint = point),
                      )
                    else
                      _ReadOnlyField(
                        label: 'Name',
                        value: esEwText(_activePoint['name_point']),
                        icon: Icons.shower_outlined,
                      ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Location',
                      value: esEwText(_activePoint['ket1']),
                      icon: Icons.place_outlined,
                    ),
                    const SizedBox(height: 12),
                    _ReadOnlyField(
                      label: 'Section',
                      value: esEwText(_activePoint['ket2']),
                      icon: Icons.account_tree_outlined,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _FormSection(
                title: 'Inspection Condition',
                child: Column(
                  children: [
                    for (final entry in esEwConditionLabels.entries)
                      _ConditionYesNo(
                        label: entry.value,
                        value: _conditions[entry.key]!,
                        onChanged: (value) =>
                            setState(() => _conditions[entry.key] = value),
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
                      label: 'Eye Wash',
                      file: _eyeWashPhoto,
                      onPick: () => _pickPhoto(true),
                      onClear: () => setState(() => _eyeWashPhoto = null),
                    ),
                    const SizedBox(height: 12),
                    _PhotoPicker(
                      label: 'Emergency Shower',
                      file: _emergencyShowerPhoto,
                      onPick: () => _pickPhoto(false),
                      onClear: () =>
                          setState(() => _emergencyShowerPhoto = null),
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

class _ScannerOverlay extends StatelessWidget {
  const _ScannerOverlay();

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(child: CustomPaint(painter: _ScannerOverlayPainter()));
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
