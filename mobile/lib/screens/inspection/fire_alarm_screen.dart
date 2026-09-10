import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../services/connectivity_service.dart';
import '../../services/offline_storage_service.dart';
import '../../widgets/save_status_badge.dart';
import 'fire_alarm_create_flow.dart';

class FireAlarmScreen extends StatefulWidget {
  const FireAlarmScreen({super.key, this.initialId});

  final String? initialId;

  @override
  State<FireAlarmScreen> createState() => _FireAlarmScreenState();
}

class _FireAlarmScreenState extends State<FireAlarmScreen> {
  List<dynamic> _inspections = [];
  List<dynamic> _locations = [];
  List<dynamic> _users = [];
  List<dynamic> _points = [];
  bool _isLoading = true;
  bool _isLoadingReferences = false;
  String? _error;
  String? _initialId;
  bool _hasShownDetail = false;

  @override
  void initState() {
    super.initState();
    _initialId = widget.initialId;
    if (_initialId != null) {
      _showDirectDetail(_initialId!);
    }
    _loadInspections();
    _loadReferenceData();
  }

  Future<void> _showDirectDetail(String id) async {
    final idInt = int.tryParse(id);
    if (idInt == null) return;

    try {
      final api = context.read<ApiService>();
      final detail = await api.getFireAlarm(idInt);
      if (!mounted) return;
      _hasShownDetail = true;
      _openDetailSheet(detail);
    } catch (_) {
      // If direct fetch fails, will try from loaded list later
    }
  }

  Future<void> _loadInspections() async {
    try {
      final api = context.read<ApiService>();
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> data;
      if (connectivity.isOnline) {
        data = await api.getFireAlarms();
        unawaited(
          OfflineStorageService.instance.saveCache('fire_alarms', data),
        );
      } else {
        data = await OfflineStorageService.instance.readCache('fire_alarms') ??
            <dynamic>[];
      }
      data = [...data, ...await _alarmDraftRows()];
      if (!mounted) return;
      _inspections = data;
      _isLoading = false;
      _error = null;

      if (!_hasShownDetail && _initialId != null) {
        _showDetailForInitialId();
      }

      setState(() {});
    } catch (e) {
      if (!mounted) return;
      _isLoading = false;
      _error = e.toString();
      if (!_hasShownDetail && _initialId != null) {
        _showDetailForInitialId();
      }
      setState(() {});
    }
  }

  Future<List<Map<String, dynamic>>> _alarmDraftRows() async {
    final drafts = await OfflineStorageService.instance.getPendingDrafts();
    return drafts
        .where((d) => d['endpoint']?.toString().startsWith('/fire-alarms') == true)
        .map((d) => <String, dynamic>{
              'id': 'draft-${d['id']}',
              'is_draft': true,
              'location_text': d['display_name']?.toString() ?? 'Draft',
              'inspector_name': 'Menunggu sinkronisasi',
              'inspection_date': _dateOnly(d['created_at']?.toString()),
            })
        .toList();
  }

  void _showDetailForInitialId() {
    final id = _initialId;
    if (id == null) return;
    _hasShownDetail = true;
    final match = _inspections
        .map(_mapFrom)
        .where((item) => item['id']?.toString() == id)
        .firstOrNull;
    if (match != null) {
      _showInspectionDetail(match);
    }
  }

  Future<void> _loadReferenceData() async {
    if (_isLoadingReferences) return;
    setState(() => _isLoadingReferences = true);

    try {
      final api = context.read<ApiService>();
      final results = await Future.wait<List<dynamic>>([
        _safeReferenceLoad(api.getFireAlarmLocations),
        _safeReferenceLoad(api.getUsers),
        _safeReferenceLoad(api.getPoints),
      ]);
      if (!mounted) return;
      setState(() {
        _locations = results[0];
        _users = results[1];
        _points = results[2];
        _isLoadingReferences = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoadingReferences = false);
    }
  }

  Future<List<dynamic>> _safeReferenceLoad(
      Future<List<dynamic>> Function() loader) async {
    try {
      return await loader();
    } catch (_) {
      return [];
    }
  }

  Future<void> _ensureReferenceData() async {
    if (_locations.isEmpty || _users.isEmpty || _points.isEmpty) {
      await _loadReferenceData();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Fire Alarm Inspection'),
        actions: [
          IconButton(
            onPressed: () {
              setState(() => _isLoading = true);
              _loadInspections();
              _loadReferenceData();
            },
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _startNewInspection,
        child: const Icon(Icons.add),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _ErrorState(message: _error!, onRetry: _retry)
              : _inspections.isEmpty
                  ? const Center(child: Text('No inspections found'))
                  : RefreshIndicator(
                      onRefresh: _retry,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _inspections.length,
                        itemBuilder: (context, index) {
                          final item = _mapFrom(_inspections[index]);
                          final isDraft = item['is_draft'] == true;
                          return _FireAlarmListCard(
                            location: isDraft
                                ? (item['location_text']?.toString() ?? '-')
                                : _relationName(
                                    item['location'],
                                    item['location_id'],
                                    'Location',
                                  ),
                            inspector: isDraft
                                ? (item['inspector_name']?.toString() ?? '-')
                                : _relationName(
                                    item['inspector'],
                                    item['inspector_id'],
                                    'Inspector',
                                  ),
                            inspectionDate:
                                _dateOnly(item['inspection_date']) ?? '',
                            pendingSync: isDraft,
                            onTap: () => _showInspectionDetail(item),
                            onLongPress: isDraft
                                ? null
                                : () => _confirmDelete(item),
                          );
                        },
                      ),
                    ),
    );
  }

  Future<void> _retry() async {
    setState(() => _isLoading = true);
    await _loadInspections();
  }

  Future<void> _startNewInspection() async {
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => const FireAlarmSetupScreen(),
      ),
    );

    if (didSave == true && mounted) {
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  Future<Map<String, dynamic>> _fetchDetail(Map<String, dynamic> item) async {
    final id = _intValue(item['id']);
    if (id == null) return item;
    try {
      return await context.read<ApiService>().getFireAlarm(id);
    } catch (_) {
      return item;
    }
  }

  void _openDetailSheet(Map<String, dynamic> detail) {
    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final items = _listFrom(detail['items']);
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.86,
          minChildSize: 0.45,
          maxChildSize: 0.95,
          builder: (context, controller) {
            return ListView(
              controller: controller,
              padding: const EdgeInsets.all(20),
              children: [
                if (items.isEmpty)
                  const Text('No items')
                else
                  ...items.map((value) {
                    return _AlarmItemCard(item: _mapFrom(value));
                  }),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                        label: const Text('Close'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          _showInspectionForm(item: detail);
                        },
                        icon: const Icon(Icons.edit_outlined),
                        label: const Text('Edit'),
                      ),
                    ),
                  ],
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _showInspectionDetail(Map<String, dynamic> item) async {
    if (item['is_draft'] == true) {
      _showDraftDialog();
      return;
    }
    final detail = await _fetchDetail(item);
    _openDetailSheet(detail);
  }

  Future<void> _showDraftDialog() async {
    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        icon: const Icon(
          Icons.cloud_upload_outlined,
          color: Color(0xFFB66A13),
          size: 40,
        ),
        title: const Text('Draft Menunggu Sinkronisasi'),
        content: const Text(
          'Data ini disimpan secara offline. '
          'Data akan dikirim ke server saat koneksi tersedia.',
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
  }

  Future<void> _showInspectionForm({Map<String, dynamic>? item}) async {
    await _ensureReferenceData();
    if (!mounted) return;

    final isEdit = item != null;
    final currentUser = _mapFrom(context.read<AuthService>().user);
    final dateController = TextEditingController(
      text: _dateOnly(item?['inspection_date']) ?? _today(),
    );
    final notesController =
        TextEditingController(text: item?['notes']?.toString() ?? '');
    var status = (item?['status'] ?? 'draft').toString();
    if (!['draft', 'completed', 'signed'].contains(status)) status = 'draft';

    int? locationId =
        _selectedId(item?['location_id'], _locations, 'id_location');
    final inspectorId = _intValue(currentUser['id']) ??
        _selectedId(item?['inspector_id'], _users, 'id');
    final inspectorName = _genericOption(
      currentUser.isNotEmpty ? currentUser : _mapFrom(item?['inspector']),
      'Current User',
    );
    final referenceNo = item?['reference_no']?.toString();
    final itemForms = _listFrom(item?['items'])
        .map((value) => _AlarmItemFormData.fromMap(_mapFrom(value)))
        .toList();
    if (!isEdit && itemForms.isEmpty) {
      itemForms.add(_AlarmItemFormData());
    }
    var didSave = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return DraggableScrollableSheet(
              expand: false,
              initialChildSize: 0.9,
              minChildSize: 0.5,
              maxChildSize: 0.96,
              builder: (context, controller) {
                return ListView(
                  controller: controller,
                  padding: EdgeInsets.only(
                    left: 20,
                    right: 20,
                    top: 20,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 20,
                  ),
                  children: [
                    Text(
                      isEdit ? 'Edit Fire Alarm' : 'Create Fire Alarm',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    if (referenceNo != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        referenceNo,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                    const SizedBox(height: 16),
                    TextField(
                      controller: dateController,
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Inspection Date',
                        prefixIcon: Icon(Icons.calendar_today),
                        border: OutlineInputBorder(),
                      ),
                      onTap: () async {
                        final current =
                            DateTime.tryParse(dateController.text) ??
                                DateTime.now();
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: current,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          dateController.text =
                              picked.toIso8601String().split('T').first;
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      initialValue: locationId,
                      decoration: const InputDecoration(
                        labelText: 'Location',
                        prefixIcon: Icon(Icons.place_outlined),
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                            value: null, child: Text('- Select Location -')),
                        ..._locations.map((location) {
                          final data = _mapFrom(location);
                          return DropdownMenuItem<int?>(
                            value: _intValue(data['id_location']),
                            child: Text(_locationOption(data),
                                overflow: TextOverflow.ellipsis),
                          );
                        }),
                      ],
                      onChanged: (value) =>
                          setSheetState(() => locationId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      initialValue: inspectorName,
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Inspector',
                        prefixIcon: Icon(Icons.person_outline),
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: status,
                      decoration: const InputDecoration(
                        labelText: 'Status',
                        prefixIcon: Icon(Icons.flag_outlined),
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'draft', child: Text('Draft')),
                        DropdownMenuItem(
                            value: 'completed', child: Text('Completed')),
                        DropdownMenuItem(
                            value: 'signed', child: Text('Signed')),
                      ],
                      onChanged: (value) {
                        if (value != null) setSheetState(() => status = value);
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: notesController,
                      minLines: 3,
                      maxLines: 5,
                      decoration: const InputDecoration(
                        labelText: 'Notes',
                        prefixIcon: Icon(Icons.notes),
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            'Fire Alarm Items',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (itemForms.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Text('No items added'),
                      )
                    else
                      ...itemForms.asMap().entries.map((entry) {
                        return _AlarmItemEditor(
                          index: entry.key,
                          data: entry.value,
                          points: _points,
                          onChanged: () => setSheetState(() {}),
                        );
                      }),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        if (isEdit)
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () {
                                Navigator.pop(context);
                                _confirmDelete(item);
                              },
                              icon: const Icon(Icons.delete_outline),
                              label: const Text('Delete'),
                            ),
                          ),
                        if (isEdit) const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () async {
                              final validation = _validateItemForms(itemForms);
                              if (validation != null) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                      content: Text(validation),
                                      backgroundColor: Colors.red),
                                );
                                return;
                              }

                              final fields = <String, String>{
                                'inspection_date': dateController.text,
                                'location_id': locationId.toString(),
                                'inspector_id': inspectorId.toString(),
                                'status': status,
                                'notes': _nullIfEmpty(notesController.text) ?? '',
                                for (var i = 0; i < itemForms.length; i++)
                                  ..._alarmItemFields(itemForms[i], i),
                              };
                              final files = <String, File?>{
                                for (var i = 0; i < itemForms.length; i++)
                                  ..._alarmItemPhotoFields(itemForms[i], i),
                              };
                              final saved = await _saveInspectionWithPhotos(
                                item,
                                fields,
                                files,
                              );
                              if (!context.mounted) return;
                              if (saved) {
                                didSave = true;
                                Navigator.pop(context);
                              }
                            },
                            icon: const Icon(Icons.save),
                            label: const Text('Save'),
                          ),
                        ),
                      ],
                    ),
                  ],
                );
              },
            );
          },
        );
      },
    );

    dateController.dispose();
    notesController.dispose();
    for (final form in itemForms) {
      form.dispose();
    }

    if (didSave && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Fire Alarm saved')),
      );
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  String? _validateItemForms(List<_AlarmItemFormData> itemForms) {
    for (var i = 0; i < itemForms.length; i++) {
      final item = itemForms[i];
      if (item.name.text.trim().isEmpty) {
        return 'Name item ${i + 1} wajib diisi';
      }
    }
    return null;
  }

  Future<bool> _saveInspectionWithPhotos(
    Map<String, dynamic>? item,
    Map<String, String> fields,
    Map<String, File?> files,
  ) async {
    final connectivity = context.read<ConnectivityService>();
    final api = context.read<ApiService>();

    if (!connectivity.isOnline) {
      final fileMap = <String, String>{
        for (final entry in files.entries)
          if (entry.value != null) entry.key: entry.value!.path,
      };
      final id = item == null ? null : _intValue(item['id']);
      await OfflineStorageService.instance.enqueueDraft(
        endpoint: id == null ? '/fire-alarms' : '/fire-alarms/$id',
        method: id == null ? 'POST' : 'PUT',
        fields: fields,
        files: fileMap,
        displayName: id == null ? 'Fire Alarm (baru)' : 'Fire Alarm (edit #$id)',
      );
      if (!mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Offline: inspeksi Fire Alarm disimpan sebagai draft. '
            'Akan disinkronkan saat online.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return true;
    }

    final id = item == null ? null : _intValue(item['id']);

    Future<void> saveAsDraft() async {
      final fileMap = <String, String>{
        for (final entry in files.entries)
          if (entry.value != null) entry.key: entry.value!.path,
      };
      await OfflineStorageService.instance.enqueueDraft(
        endpoint: id == null ? '/fire-alarms' : '/fire-alarms/$id',
        method: id == null ? 'POST' : 'PUT',
        fields: fields,
        files: fileMap,
        displayName:
            id == null ? 'Fire Alarm (baru)' : 'Fire Alarm (edit #$id)',
      );
    }

    try {
      final response = item == null
          ? await api.createFireAlarmWithPhotos(fields, files)
          : await api.updateFireAlarmWithPhotos(id!, fields, files);

      if (!mounted) return false;
      if (response.containsKey('error')) {
        await saveAsDraft();
        if (!mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${response['error']}\nData disimpan sebagai draft, '
              'akan disinkronkan otomatis.',
            ),
            backgroundColor: Colors.orange,
          ),
        );
        return true;
      }
      return true;
    } catch (_) {
      await saveAsDraft();
      if (!mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Gagal terkirim, data disimpan sebagai draft. '
            'Akan disinkronkan otomatis.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return true;
    }
  }

  Map<String, String> _alarmItemFields(_AlarmItemFormData form, int index) {
    return {
      'items[$index][name]': form.name.text.trim(),
      'items[$index][alarm_number]':
          _nullIfEmpty(form.alarmNumber.text) ?? '',
      'items[$index][type]': form.type,
      'items[$index][location_detail]': form.locationDetail.text,
      'items[$index][condition_good]': form.conditionGood ? '1' : '0',
      'items[$index][correction_needed]': form.correctionNeeded ? '1' : '0',
      'items[$index][remark]': _nullIfEmpty(form.remark.text) ?? '',
    };
  }

  Map<String, File?> _alarmItemPhotoFields(_AlarmItemFormData form, int index) {
    return {
      'items[$index][photo_before]': form.newPhotoBefore,
      'items[$index][photo_after]': form.newPhotoAfter,
    };
  }

  Future<void> _confirmDelete(Map<String, dynamic> item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Delete Fire Alarm?'),
          content: Text(
              item['reference_no']?.toString() ?? 'Delete this inspection?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Delete'),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) return;

    final api = context.read<ApiService>();
    final response = await api.deleteFireAlarm(_intValue(item['id'])!);
    if (!mounted) return;
    if (response.containsKey('error')) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response['error'].toString()),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Fire Alarm deleted')),
    );
    setState(() => _isLoading = true);
    await _loadInspections();
  }

  String _today() => DateTime.now().toIso8601String().split('T').first;

  String _locationOption(Map<String, dynamic> location) {
    final id = location['id_location']?.toString() ?? '';
    final name = location['name']?.toString() ?? 'Location';
    return id.isEmpty ? name : '$id - $name';
  }

  String _genericOption(Map<String, dynamic> value, String fallback) {
    final id =
        value['id']?.toString() ?? value['id_location']?.toString() ?? '';
    final name = value['name'] ??
        value['username'] ??
        value['name_point'] ??
        value['asset_name'] ??
        fallback;
    return id.isEmpty ? name.toString() : '$id - $name';
  }

  String _relationName(dynamic relation, dynamic fallbackId, String label) {
    final data = _mapFrom(relation);
    if (data.isNotEmpty) {
      final id = data['id'] ?? data['id_location'];
      final name = data['name'] ??
          data['username'] ??
          data['name_point'] ??
          data['description'];
      if (id != null && name != null) return '$id - $name';
      if (name != null) return name.toString();
    }
    return fallbackId == null ? '' : '$label #$fallbackId';
  }

  String? _dateOnly(dynamic value) {
    if (value == null) return null;
    final text = value.toString();
    if (text.isEmpty) return null;
    return text.split('T').first.split(' ').first;
  }
}

class _FireAlarmListCard extends StatelessWidget {
  const _FireAlarmListCard({
    required this.location,
    required this.inspector,
    required this.inspectionDate,
    required this.pendingSync,
    required this.onTap,
    this.onLongPress,
  });

  final String location;
  final String inspector;
  final String inspectionDate;
  final bool pendingSync;
  final VoidCallback onTap;
  final VoidCallback? onLongPress;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        onLongPress: onLongPress,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Location',
                style: TextStyle(
                  color: Colors.black54,
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 3),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      location.isEmpty ? '-' : location,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SaveStatusBadge(isPending: pendingSync),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _FireAlarmListValue(
                      label: 'Inspected By',
                      value: inspector,
                    ),
                  ),
                  const SizedBox(width: 16),
                  _FireAlarmListValue(
                    label: 'Inspection Date',
                    value: inspectionDate,
                    alignEnd: true,
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

class _FireAlarmListValue extends StatelessWidget {
  const _FireAlarmListValue({
    required this.label,
    required this.value,
    this.alignEnd = false,
  });

  final String label;
  final String value;
  final bool alignEnd;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment:
          alignEnd ? CrossAxisAlignment.end : CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Colors.black54,
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          value.isEmpty ? '-' : value,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          textAlign: alignEnd ? TextAlign.end : TextAlign.start,
          style: const TextStyle(
            color: Color(0xFF334155),
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _AlarmItemFormData {
  _AlarmItemFormData({
    String name = '',
    String alarmNumber = '',
    this.type = '',
    String locationDetail = '',
    String remark = '',
    this.conditionGood = true,
    this.correctionNeeded = false,
  })  : name = TextEditingController(text: name),
      alarmNumber = TextEditingController(text: alarmNumber),
      locationDetail = TextEditingController(text: locationDetail),
      remark = TextEditingController(text: remark);

  factory _AlarmItemFormData.fromMap(Map<String, dynamic> item) {
    return _AlarmItemFormData(
      name: item['name']?.toString() ?? '',
      alarmNumber: item['alarm_number']?.toString() ?? '',
      type: item['type']?.toString() ?? '',
      locationDetail: item['location_detail']?.toString() ?? '',
      remark: item['remark']?.toString() ?? '',
      conditionGood: _truthy(item['condition_good']),
      correctionNeeded: _truthy(item['correction_needed']),
    )..existingPhotoBefore = item['photo_before']?.toString()
      ..existingPhotoAfter = item['photo_after']?.toString();
  }

  final TextEditingController name;
  final TextEditingController alarmNumber;
  final TextEditingController locationDetail;
  final TextEditingController remark;
  String type;
  bool conditionGood;
  bool correctionNeeded;
  String? existingPhotoBefore;
  String? existingPhotoAfter;
  File? newPhotoBefore;
  File? newPhotoAfter;

  void applyPoint(Map<String, dynamic> point) {
    name.text = (point['name_point'] ?? point['id'] ?? '').toString();
    type = point['ket1']?.toString() ?? '';
    locationDetail.text = point['ket2']?.toString() ?? '';
  }

  void dispose() {
    name.dispose();
    alarmNumber.dispose();
    locationDetail.dispose();
    remark.dispose();
  }
}

class _AlarmItemEditor extends StatelessWidget {
  const _AlarmItemEditor({
    required this.index,
    required this.data,
    required this.points,
    required this.onChanged,
  });

  final int index;
  final _AlarmItemFormData data;
  final List<dynamic> points;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        initiallyExpanded: index == 0,
        title: Text('Item ${index + 1}'),
        subtitle: Text(
          data.name.text.isEmpty ? 'Alarm detail' : data.name.text,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        children: [
          _SearchableAlarmDropdown(
            points: points,
            selectedLabel: data.name.text,
            onSelected: (point) {
              data.applyPoint(point);
              onChanged();
            },
          ),
          const SizedBox(height: 12),
          TextField(
            controller: data.alarmNumber,
            decoration: const InputDecoration(
              labelText: 'Alarm Number',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: data.name,
            readOnly: true,
            decoration: const InputDecoration(
              labelText: 'Name',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            key: ValueKey('type-${data.type}'),
            initialValue: data.type,
            readOnly: true,
            decoration: const InputDecoration(
              labelText: 'Type',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: data.locationDetail,
            readOnly: true,
            decoration: const InputDecoration(
              labelText: 'Location Detail',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            children: [
              _ConditionChip(
                label: 'Condition Good',
                value: data.conditionGood,
                onChanged: (v) {
                  data.conditionGood = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Correction Needed',
                value: data.correctionNeeded,
                onChanged: (v) {
                  data.correctionNeeded = v;
                  onChanged();
                },
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextField(
            controller: data.remark,
            minLines: 2,
            maxLines: 4,
            decoration: const InputDecoration(
              labelText: 'Remark',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Photos',
            style: Theme.of(context).textTheme.titleSmall,
          ),
          const SizedBox(height: 8),
          _AlarmPhotoPicker(
            label: 'Foto Sebelum (Before)',
            existingPath: data.existingPhotoBefore,
            file: data.newPhotoBefore,
            onPick: () async {
              final selected = await ImagePicker().pickImage(
                source: ImageSource.camera,
                imageQuality: 75,
                maxWidth: 1600,
              );
              if (selected == null) return;
              data.newPhotoBefore = File(selected.path);
              onChanged();
            },
            onClear: () {
              data.newPhotoBefore = null;
              onChanged();
            },
          ),
          const SizedBox(height: 10),
          _AlarmPhotoPicker(
            label: 'Foto Sesudah (After)',
            existingPath: data.existingPhotoAfter,
            file: data.newPhotoAfter,
            onPick: () async {
              final selected = await ImagePicker().pickImage(
                source: ImageSource.camera,
                imageQuality: 75,
                maxWidth: 1600,
              );
              if (selected == null) return;
              data.newPhotoAfter = File(selected.path);
              onChanged();
            },
            onClear: () {
              data.newPhotoAfter = null;
              onChanged();
            },
          ),
        ],
      ),
    );
  }
}

class _SearchableAlarmDropdown extends StatelessWidget {
  const _SearchableAlarmDropdown({
    required this.points,
    required this.selectedLabel,
    required this.onSelected,
  });

  final List<dynamic> points;
  final String selectedLabel;
  final ValueChanged<Map<String, dynamic>> onSelected;

  @override
  Widget build(BuildContext context) {
    final items = points
        .map(_mapFrom)
        .where((point) => _intValue(point['id']) != null)
        .toList(growable: false);
    final hasSelection = selectedLabel.trim().isNotEmpty;

    return SearchAnchor(
      builder: (context, controller) {
        return InkWell(
          onTap: controller.openView,
          borderRadius: BorderRadius.circular(4),
          child: InputDecorator(
            decoration: InputDecoration(
              labelText: 'Name',
              border: const OutlineInputBorder(),
              suffixIcon: IconButton(
                icon: const Icon(Icons.search),
                tooltip: 'Search Name',
                onPressed: controller.openView,
              ),
            ),
            child: Text(
              hasSelection ? selectedLabel.trim() : '- Select Name -',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: hasSelection
                    ? const Color(0xFF0F172A)
                    : Theme.of(context).hintColor,
              ),
            ),
          ),
        );
      },
      suggestionsBuilder: (context, controller) {
        final query = controller.text.trim().toLowerCase();
        final filtered = items.where((point) {
          if (query.isEmpty) return true;
          return _pointSearchText(point).toLowerCase().contains(query);
        }).toList(growable: false);

        if (filtered.isEmpty) {
          return const [
            ListTile(
              leading: Icon(Icons.search_off),
              title: Text('No point found'),
            ),
          ];
        }

        return filtered.map((point) {
          final ket2 = point['ket2']?.toString().trim() ?? '';
          return ListTile(
            leading: const Icon(Icons.notifications_active_outlined),
            title: Text(_pointOption(point)),
            subtitle: ket2.isEmpty ? null : Text(ket2),
            onTap: () {
              controller.closeView(_pointOption(point));
              onSelected(point);
            },
          );
        }).toList(growable: false);
      },
    );
  }
}

class _AlarmPhotoPicker extends StatelessWidget {
  const _AlarmPhotoPicker({
    required this.label,
    required this.existingPath,
    required this.file,
    required this.onPick,
    required this.onClear,
  });

  final String label;
  final String? existingPath;
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
                height: 120,
                width: double.infinity,
                fit: BoxFit.cover,
              ),
            ),
          ] else if (_hasValue(existingPath)) ...[
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: _existingPhoto(context, existingPath!),
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
                  tooltip: 'Remove new photo',
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _existingPhoto(BuildContext context, String path) {
    final url = _mediaUrl(context, path);
    return Container(
      height: 120,
      width: double.infinity,
      color: const Color(0xFFE2E8F0),
      child: url == null
          ? const Icon(Icons.image_not_supported_outlined, color: Colors.black45)
          : Image.network(
              url,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => const Icon(
                Icons.broken_image_outlined,
                color: Colors.black45,
              ),
            ),
    );
  }
}

class _ConditionChip extends StatelessWidget {
  const _ConditionChip({
    required this.label,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(label),
      selected: value,
      onSelected: onChanged,
    );
  }
}

class _AlarmItemCard extends StatelessWidget {
  const _AlarmItemCard({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              item['name']?.toString() ?? 'Fire Alarm Item',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            if (_hasValue(item['alarm_number']))
              _DetailRow(label: 'Alarm Number', value: item['alarm_number']),
            if (_hasValue(item['type']))
              _DetailRow(label: 'Type', value: item['type']),
            if (_hasValue(item['location_detail']))
              _DetailRow(
                  label: 'Location Detail', value: item['location_detail']),
            if (_hasValue(item['remark']))
              _DetailRow(label: 'Remark', value: item['remark']),
            const SizedBox(height: 12),
            const Text(
              'Checklist',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: Color(0xFF475569),
              ),
            ),
            const SizedBox(height: 8),
            LayoutBuilder(
              builder: (context, constraints) {
                final itemWidth = (constraints.maxWidth - 8) / 2;
                return Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _ConditionChecklistItem(
                      width: itemWidth,
                      label: 'Condition Good',
                      value: _truthy(item['condition_good']),
                    ),
                    _ConditionChecklistItem(
                      width: itemWidth,
                      label: 'Correction Needed',
                      value: _truthy(item['correction_needed']),
                    ),
                  ],
                );
              },
            ),
            if (item['photo_before'] != null ||
                item['photo_after'] != null) ...[
              const SizedBox(height: 12),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  if (_hasValue(item['photo_before']))
                    _SmallPhotoPreview(
                        label: 'Before', path: item['photo_before']),
                  if (_hasValue(item['photo_after']))
                    _SmallPhotoPreview(
                        label: 'After', path: item['photo_after']),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SmallPhotoPreview extends StatelessWidget {
  const _SmallPhotoPreview({required this.label, required this.path});

  final String label;
  final dynamic path;

  @override
  Widget build(BuildContext context) {
    final url = _mediaUrl(context, path);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: Colors.black54),
        ),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: Container(
            width: 72,
            height: 72,
            color: const Color(0xFFE2E8F0),
            child: url == null
                ? const Icon(Icons.image_not_supported_outlined,
                    color: Colors.black45)
                : Image.network(
                    url,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const Icon(
                      Icons.broken_image_outlined,
                      color: Colors.black45,
                    ),
                  ),
          ),
        ),
      ],
    );
  }
}

class _ConditionChecklistItem extends StatelessWidget {
  const _ConditionChecklistItem({
    required this.width,
    required this.label,
    required this.value,
  });

  final double width;
  final String label;
  final bool value;

  @override
  Widget build(BuildContext context) {
    final color = value ? const Color(0xFF15803D) : const Color(0xFFDC2626);
    final background =
        value ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2);
    return Container(
      width: width,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.24)),
      ),
      child: Row(
        children: [
          Icon(
            value ? Icons.check_box_rounded : Icons.cancel_outlined,
            color: color,
            size: 20,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});

  final String label;
  final dynamic value;

  @override
  Widget build(BuildContext context) {
    final text = value?.toString().trim();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 125,
            child: Text(label, style: Theme.of(context).textTheme.bodySmall),
          ),
          Expanded(child: Text(text == null || text.isEmpty ? '-' : text)),
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});

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

Map<String, dynamic> _mapFrom(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

List<dynamic> _listFrom(dynamic value) {
  if (value is List) return value;
  return <dynamic>[];
}

int? _intValue(dynamic value) {
  if (value is int) return value;
  return value == null ? null : int.tryParse(value.toString());
}

bool _truthy(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final text = value?.toString().toLowerCase();
  return text == '1' || text == 'true' || text == 'yes';
}

bool _hasValue(dynamic value) {
  if (value == null) return false;
  final text = value.toString().trim();
  return text.isNotEmpty && text != '-';
}

int? _selectedId(dynamic value, List<dynamic> options, String key) {
  final id = _intValue(value);
  if (id == null) return null;
  return options.map(_mapFrom).any((item) => _intValue(item[key]) == id)
      ? id
      : null;
}

String _pointOption(Map<String, dynamic> point) {
  final id = point['id']?.toString() ?? '';
  final name = point['name_point']?.toString() ?? 'Point';
  return id.isEmpty ? name : '$id - $name';
}

String _pointSearchText(Map<String, dynamic> point) {
  return '${point['name_point']?.toString() ?? ''} '
      '${point['ket1']?.toString() ?? ''} '
      '${point['ket2']?.toString() ?? ''} '
      '${point['id']?.toString() ?? ''}';
}

String? _nullIfEmpty(String value) {
  final text = value.trim();
  return text.isEmpty ? null : text;
}

String? _mediaUrl(BuildContext context, dynamic path) {
  if (path == null) return null;
  final text = path.toString().trim();
  if (text.isEmpty) return null;
  if (text.startsWith('http')) return text;
  final base = context.read<ApiService>().baseUrl;
  return '${base.replaceFirst(RegExp(r'/+$'), '')}/$text';
}