import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/connectivity_service.dart';
import '../../services/offline_storage_service.dart';

class IncidentFormScreen extends StatefulWidget {
  const IncidentFormScreen({super.key, this.inspection});

  final Map<String, dynamic>? inspection;

  @override
  State<IncidentFormScreen> createState() => _IncidentFormScreenState();
}

class _IncidentFormScreenState extends State<IncidentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _locationController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _imagePicker = ImagePicker();

  DateTime _date = DateTime.now();
  TimeOfDay _time = TimeOfDay.now();
  List<Map<String, dynamic>> _incidentTypes = [];
  int? _incidentTypeId;
  String _status = 'open';
  File? _image;
  File? _repairImage;
  Map<String, dynamic>? _existingFinding;
  Map<String, dynamic>? _existingRepair;
  bool _isLoading = true;
  bool _isSaving = false;
  String? _loadError;

  bool get _isEdit => widget.inspection != null;

  @override
  void initState() {
    super.initState();
    final inspection = widget.inspection ?? const <String, dynamic>{};
    final parsedDate =
        DateTime.tryParse(inspection['incident_date']?.toString() ?? '');
    if (parsedDate != null) _date = parsedDate;
    _time = _parseTime(inspection['incident_time']);
    _locationController.text = _string(inspection['location_text']);
    _descriptionController.text = _string(inspection['description']);
    _incidentTypeId = _asInt(inspection['incident_type_id']);
    final status = inspection['status']?.toString().toLowerCase();
    if (status == 'closed' || status == 'close') _status = 'close';

    final images = _listFrom(inspection['images']);
    for (final raw in images) {
      final image = _asMap(raw);
      if (image['kind']?.toString() == 'repair') {
        _existingRepair ??= image;
      } else {
        _existingFinding ??= image;
      }
    }
    _loadMasterData();
  }

  @override
  void dispose() {
    _locationController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _loadMasterData() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final api = context.read<ApiService>();
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> result;
      if (connectivity.isOnline) {
        result = await api.getIncidentTypes();
        unawaited(
          OfflineStorageService.instance.saveCache('incident_types', result),
        );
      } else {
        result = await OfflineStorageService.instance
                .readCache('incident_types') ??
            [];
      }
      if (!mounted) return;

      setState(() {
        _incidentTypes = result
            .map(_asMap)
            .where(
                (item) => item['is_active'] != false && item['is_active'] != 0)
            .toList();
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _loadError = _cleanError(error);
      });
    }
  }

  Future<void> _selectDate() async {
    final selected = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (selected != null && mounted) setState(() => _date = selected);
  }

  Future<void> _selectTime() async {
    final selected = await showTimePicker(context: context, initialTime: _time);
    if (selected != null && mounted) setState(() => _time = selected);
  }

  Future<void> _chooseImageSource(ValueChanged<File> onPicked) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Ambil dari kamera'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Pilih dari galeri'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;

    final selected = await _imagePicker.pickImage(
      source: source,
      imageQuality: 82,
      maxWidth: 1920,
    );
    if (selected != null && mounted) {
      setState(() => onPicked(File(selected.path)));
    }
  }

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;
    final hasFinding = _image != null || (_isEdit && _existingFinding != null);
    if (!hasFinding) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Foto temuan awal wajib dipilih.')),
      );
      return;
    }

    setState(() => _isSaving = true);
    final payload = {
      'incident_date': DateFormat('yyyy-MM-dd').format(_date),
      'incident_time':
          '${_time.hour.toString().padLeft(2, '0')}:${_time.minute.toString().padLeft(2, '0')}',
      'location_text': _locationController.text.trim(),
      'incident_type_id': _incidentTypeId.toString(),
      'description': _descriptionController.text.trim(),
      'status': _status,
    };
    final api = context.read<ApiService>();
    final connectivity = context.read<ConnectivityService>();

    if (!connectivity.isOnline) {
      final fileMap = <String, String>{
        if (_image != null) 'image': _image!.path,
        if (_repairImage != null) 'repair_photo': _repairImage!.path,
      };
      final id = _isEdit ? _asInt(widget.inspection!['id']) : null;
      await OfflineStorageService.instance.enqueueDraft(
        endpoint: id == null ? '/incidents' : '/incidents/$id',
        method: id == null ? 'POST' : 'PUT',
        fields: payload,
        files: fileMap,
        displayName: id == null ? 'Inspection (baru)' : 'Inspection (edit #$id)',
      );
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Offline: Inspection disimpan sebagai draft. '
            'Akan disinkronkan saat online.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      Navigator.pop(context, true);
      return;
    }

    final id = _isEdit ? _asInt(widget.inspection!['id']) : null;
    final fileMap = <String, String>{
      if (_image != null) 'image': _image!.path,
      if (_repairImage != null) 'repair_photo': _repairImage!.path,
    };
    Future<void> enqueueAsDraft() async {
      await OfflineStorageService.instance.enqueueDraft(
        endpoint: id == null ? '/incidents' : '/incidents/$id',
        method: id == null ? 'POST' : 'PUT',
        fields: payload,
        files: fileMap,
        displayName: id == null
            ? 'Inspection (baru)'
            : 'Inspection (edit #$id)',
      );
    }

    Map<String, dynamic> result;
    try {
      result = _isEdit
          ? await api.updateIncident(
              id!,
              payload,
              image: _image,
              repairPhoto: _repairImage,
            )
          : await api.createIncident(
              payload,
              image: _image,
              repairPhoto: _repairImage,
            );
    } catch (_) {
      // Koneksi terputus saat mengirim: simpan sebagai draft agar otomatis
      // disinkronkan saat koneksi pulih.
      await enqueueAsDraft();
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Gagal terkirim, data disimpan sebagai draft. '
            'Akan disinkronkan otomatis saat online.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      Navigator.pop(context, true);
      return;
    }

    if (!mounted) return;
    setState(() => _isSaving = false);
    if (result['error'] != null) {
      await enqueueAsDraft();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            '${result['error']}\nData disimpan sebagai draft, '
            'akan disinkronkan otomatis.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      Navigator.pop(context, true);
      return;
    }

    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        icon: const Icon(Icons.check_circle, color: Colors.green, size: 48),
        title: const Text('Berhasil'),
        content: Text(
          result['message']?.toString() ?? 'Inspection berhasil disimpan.',
          textAlign: TextAlign.center,
        ),
        actions: [
          FilledButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
    if (mounted) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(title: Text(_isEdit ? 'Edit Inspection' : 'Inspection')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _loadError != null
              ? _LoadError(message: _loadError!, onRetry: _loadMasterData)
              : Form(
                  key: _formKey,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _FormCard(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: _PickerField(
                                  label: 'Tanggal',
                                  value:
                                      DateFormat('dd MMM yyyy').format(_date),
                                  icon: Icons.calendar_today_outlined,
                                  onTap: _selectDate,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _PickerField(
                                  label: 'Jam',
                                  value: _time.format(context),
                                  icon: Icons.schedule_outlined,
                                  onTap: _selectTime,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _locationController,
                            textInputAction: TextInputAction.next,
                            maxLength: 255,
                            decoration: _decoration(
                              'Lokasi',
                              Icons.location_on_outlined,
                            ),
                            validator: (value) =>
                                value == null || value.trim().isEmpty
                                    ? 'Lokasi wajib diisi'
                                    : null,
                          ),
                          const SizedBox(height: 16),
                          DropdownButtonFormField<int>(
                            initialValue: _incidentTypeId,
                            isExpanded: true,
                            decoration: _decoration(
                              'Inspection Type',
                              Icons.category_outlined,
                            ),
                            items: _incidentTypes
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: _asInt(item['id']),
                                    child: Text(
                                      item['name']?.toString() ?? '-',
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) =>
                                setState(() => _incidentTypeId = value),
                            validator: (value) => value == null
                                ? 'Inspection Type wajib dipilih'
                                : null,
                          ),
                          const SizedBox(height: 16),
                          DropdownButtonFormField<String>(
                            initialValue: _status,
                            decoration: _decoration(
                              'Status',
                              Icons.flag_outlined,
                            ),
                            items: const [
                              DropdownMenuItem(
                                  value: 'open', child: Text('Open')),
                              DropdownMenuItem(
                                  value: 'close', child: Text('Close')),
                            ],
                            onChanged: (value) {
                              if (value != null) {
                                setState(() => _status = value);
                              }
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _descriptionController,
                            minLines: 4,
                            maxLines: 7,
                            maxLength: 5000,
                            decoration: _decoration(
                              'Keterangan',
                              Icons.notes_outlined,
                            ).copyWith(alignLabelWithHint: true),
                            validator: (value) =>
                                value == null || value.trim().isEmpty
                                    ? 'Keterangan wajib diisi'
                                    : null,
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _FormCard(
                        children: [
                          const Text(
                            'Foto Temuan Awal *',
                            style: TextStyle(fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 10),
                          if (_image != null)
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Image.file(
                                _image!,
                                height: 210,
                                width: double.infinity,
                                fit: BoxFit.cover,
                              ),
                            )
                          else if (_existingFinding != null)
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Image.network(
                                    _mediaUrl(context,
                                        _existingFinding!['image_path'])!,
                                    height: 210,
                                    width: double.infinity,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      height: 210,
                                      color: const Color(0xFFE2E8F0),
                                      child: const Icon(
                                        Icons.broken_image_outlined),
                                    ),
                                  ),
                                  const Padding(
                                    padding: EdgeInsets.only(top: 6),
                                    child: Text(
                                      'Foto temuan awal saat ini',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Color(0xFF64748B),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          if (_image != null ||
                              _existingFinding != null)
                            const SizedBox(height: 10),
                          OutlinedButton.icon(
                            onPressed: () => _chooseImageSource(
                              (file) => _image = file,
                            ),
                            icon: Icon(
                              _image == null &&
                                      _existingFinding == null
                                  ? Icons.add_a_photo_outlined
                                  : Icons.change_circle_outlined,
                            ),
                            label: Text(
                              _image == null &&
                                      _existingFinding == null
                                  ? 'Upload Gambar'
                                  : 'Ganti Gambar',
                            ),
                          ),
                          const Divider(height: 32),
                          const Text(
                            'Perbaikan',
                            style: TextStyle(fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Opsional - tidak wajib diisi.',
                            style: TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                          const SizedBox(height: 10),
                          if (_repairImage != null)
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Image.file(
                                _repairImage!,
                                height: 210,
                                width: double.infinity,
                                fit: BoxFit.cover,
                              ),
                            )
                          else if (_existingRepair != null)
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Image.network(
                                    _mediaUrl(context,
                                        _existingRepair!['image_path'])!,
                                    height: 210,
                                    width: double.infinity,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      height: 210,
                                      color: const Color(0xFFE2E8F0),
                                      child: const Icon(
                                        Icons.broken_image_outlined),
                                    ),
                                  ),
                                  const Padding(
                                    padding: EdgeInsets.only(top: 6),
                                    child: Text(
                                      'Foto perbaikan saat ini',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Color(0xFF64748B),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          if (_repairImage != null ||
                              _existingRepair != null)
                            const SizedBox(height: 10),
                          OutlinedButton.icon(
                            onPressed: () => _chooseImageSource(
                              (file) => _repairImage = file,
                            ),
                            icon: Icon(
                              _repairImage == null &&
                                      _existingRepair == null
                                  ? Icons.add_a_photo_outlined
                                  : Icons.change_circle_outlined,
                            ),
                            label: Text(
                              _repairImage == null &&
                                      _existingRepair == null
                                  ? 'Upload Perbaikan'
                                  : 'Ganti Perbaikan',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      FilledButton.icon(
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
                        label: Text(_isSaving ? 'Menyimpan...' : 'Simpan'),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(50),
                        ),
                      ),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
    );
  }
}

class _FormCard extends StatelessWidget {
  const _FormCard({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(children: children),
    );
  }
}

class _PickerField extends StatelessWidget {
  const _PickerField({
    required this.label,
    required this.value,
    required this.icon,
    required this.onTap,
  });

  final String label;
  final String value;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(4),
      child: InputDecorator(
        decoration: _decoration(label, icon),
        child: Text(value),
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
              label: const Text('Coba Lagi'),
            ),
          ],
        ),
      ),
    );
  }
}

InputDecoration _decoration(String label, IconData icon) {
  return InputDecoration(
    labelText: label,
    prefixIcon: Icon(icon),
    border: const OutlineInputBorder(),
  );
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

String _string(dynamic value) {
  return value?.toString().trim() ?? '';
}

List<dynamic> _listFrom(dynamic value) {
  return value is List ? value : <dynamic>[];
}

TimeOfDay _parseTime(dynamic value) {
  final text = value?.toString() ?? '';
  if (text.length < 5) return TimeOfDay.now();
  final parts = text.split(':');
  final hour = int.tryParse(parts[0]);
  final minute = parts.length > 1 ? int.tryParse(parts[1]) : null;
  if (hour == null || minute == null) return TimeOfDay.now();
  return TimeOfDay(hour: hour.clamp(0, 23), minute: minute.clamp(0, 59));
}

String? _mediaUrl(BuildContext context, dynamic path) {
  final text = path?.toString().trim() ?? '';
  if (text.isEmpty) return null;
  if (text.startsWith('http://') || text.startsWith('https://')) return text;

  final apiBase = context.read<ApiService>().baseUrl;
  final appBase = apiBase.replaceFirst(RegExp(r'/api/?$'), '');
  final cleanBase = appBase.endsWith('/')
      ? appBase.substring(0, appBase.length - 1)
      : appBase;
  final cleanPath = text.startsWith('/') ? text.substring(1) : text;
  return '$cleanBase/$cleanPath';
}

String _cleanError(Object error) {
  return error.toString().replaceFirst('Exception: ', '');
}
