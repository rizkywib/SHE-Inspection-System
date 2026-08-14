import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import 'fire_extinguisher_create_screen.dart';

class FireExtinguisherScreen extends StatefulWidget {
  const FireExtinguisherScreen({super.key});

  @override
  State<FireExtinguisherScreen> createState() => _FireExtinguisherScreenState();
}

class _FireExtinguisherScreenState extends State<FireExtinguisherScreen> {
  List<dynamic> _inspections = [];
  List<dynamic> _locations = [];
  List<dynamic> _users = [];
  List<dynamic> _points = [];
  bool _isLoading = true;
  bool _isLoadingReferences = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadInspections();
    _loadReferenceData();
  }

  Future<void> _loadInspections() async {
    try {
      final data = await context.read<ApiService>().getFireExtinguishers();
      if (!mounted) return;
      setState(() {
        _inspections = data;
        _isLoading = false;
        _error = null;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  Future<void> _loadReferenceData() async {
    if (_isLoadingReferences) return;
    setState(() => _isLoadingReferences = true);
    final api = context.read<ApiService>();
    final results = await Future.wait<List<dynamic>>([
      _safeReferenceLoad(api.getFireExtinguisherLocations),
      _safeReferenceLoad(api.getUsers),
      _safeReferenceLoad(api.getPoints),
    ]);
    if (!mounted) return;
    setState(() {
      _locations = results[0];
      _users = results[1];
      _points = results[2]
          .map(_mapFrom)
          .where(_isFireExtinguisherPoint)
          .toList(growable: false);
      _isLoadingReferences = false;
    });
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

  Future<void> _retry() async {
    setState(() => _isLoading = true);
    await Future.wait([_loadInspections(), _loadReferenceData()]);
  }

  Future<void> _startNewInspection() async {
    final didSave = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => const FireExtinguisherCreateScreen(),
      ),
    );
    if (didSave == true && mounted) {
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  Future<Map<String, dynamic>> _fetchDetail(
      Map<String, dynamic> inspection) async {
    final id = _intValue(inspection['id']);
    if (id == null) return inspection;
    try {
      return await context.read<ApiService>().getFireExtinguisher(id);
    } catch (_) {
      return inspection;
    }
  }

  Future<void> _showInspectionDetail(Map<String, dynamic> inspection) async {
    final detail = await _fetchDetail(inspection);
    if (!mounted) return;
    _openDetailSheet(detail);
  }

  void _openDetailSheet(Map<String, dynamic> detail) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
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
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.primaryContainer,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        Icons.fire_extinguisher,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            detail['reference_no']?.toString() ??
                                'Fire Extinguisher',
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 2),
                          Text(_dateOnly(detail['inspection_date']) ?? '-'),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        _DetailRow(
                          label: 'Location',
                          value: _relationName(
                            detail['location'],
                            detail['location_id'],
                            'Location',
                          ),
                        ),
                        _DetailRow(
                          label: 'Inspector',
                          value: _relationName(
                            detail['inspector'],
                            detail['inspector_id'],
                            'Inspector',
                          ),
                        ),
                        if (_hasValue(detail['checked_in_at']))
                          _DetailRow(
                            label: 'Check-in',
                            value: _dateTime(detail['checked_in_at']),
                          ),
                        if (_hasValue(detail['signed_at']))
                          _DetailRow(
                            label: 'Signed',
                            value: _dateTime(detail['signed_at']),
                          ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  const Text('No items')
                else
                  ...items.map((value) {
                    return _ExtinguisherItemCard(item: _mapFrom(value));
                  }),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => Navigator.pop(sheetContext),
                        icon: const Icon(Icons.close),
                        label: const Text('Close'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(sheetContext);
                          _showEditForm(detail);
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

  Future<void> _showEditForm(Map<String, dynamic> inspection) async {
    await _ensureReferenceData();
    if (!mounted) return;

    final currentUser = _mapFrom(context.read<AuthService>().user);
    final item = _listFrom(inspection['items']).map(_mapFrom).firstOrNull;
    final referenceController = TextEditingController(
      text: inspection['reference_no']?.toString() ?? '',
    );
    final dateController = TextEditingController(
      text: _dateOnly(inspection['inspection_date']) ?? _today(),
    );
    final remarkController = TextEditingController(
      text: item?['remark']?.toString() ?? '',
    );
    final expiryController = TextEditingController(
      text: _dateOnly(item?['expiry_date']) ?? '',
    );

    int? locationId =
        _selectedId(inspection['location_id'], _locations, 'id_location');
    final inspectorId =
        _intValue(inspection['inspector_id']) ?? _intValue(currentUser['id']);
    int? pointId = _selectedPointIdForItem(item, _points);
    bool pressureCondition = _truthy(item?['pressure_condition']);
    bool sealCondition = _truthy(item?['seal_condition']);
    bool nozzleCondition = _truthy(item?['nozzle_condition']);
    File? newPhotoBefore;
    File? newPhotoAfter;
    var didSave = false;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final selectedPoint = _pointById(pointId, _points);
            final existingName = item?['name']?.toString() ?? '';
            return DraggableScrollableSheet(
              expand: false,
              initialChildSize: 0.92,
              minChildSize: 0.55,
              maxChildSize: 0.97,
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
                      'Edit Fire Extinguisher',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: referenceController,
                      decoration: const InputDecoration(
                        labelText: 'Reference Number',
                        prefixIcon: Icon(Icons.tag_outlined),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: dateController,
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Inspection Date',
                        prefixIcon: Icon(Icons.calendar_today_outlined),
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
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'Location',
                        prefixIcon: Icon(Icons.location_on_outlined),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('- Select Location -'),
                        ),
                        ..._locations.map(_mapFrom).map((location) {
                          return DropdownMenuItem<int?>(
                            value: _intValue(location['id_location']),
                            child: Text(
                              _locationOption(location),
                              overflow: TextOverflow.ellipsis,
                            ),
                          );
                        }),
                      ],
                      onChanged: (value) =>
                          setSheetState(() => locationId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      initialValue: _relationName(
                        inspection['inspector'],
                        inspectorId,
                        'Inspector',
                      ),
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Inspector',
                        prefixIcon: Icon(Icons.badge_outlined),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Text(
                      'Fire Extinguisher Item',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<int?>(
                      initialValue: pointId,
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'APAR Number',
                        prefixIcon: Icon(Icons.fire_extinguisher),
                      ),
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(
                            existingName.isEmpty
                                ? '- Select APAR -'
                                : '$existingName (select point)',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        ..._points.map(_mapFrom).map((point) {
                          return DropdownMenuItem<int?>(
                            value: _intValue(point['id']),
                            child: Text(
                              _pointOption(point),
                              overflow: TextOverflow.ellipsis,
                            ),
                          );
                        }),
                      ],
                      onChanged: (value) =>
                          setSheetState(() => pointId = value),
                    ),
                    if (selectedPoint.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Theme.of(context)
                              .colorScheme
                              .primaryContainer
                              .withValues(alpha: 0.45),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Type: ${selectedPoint['ket1'] ?? '-'}'),
                            const SizedBox(height: 3),
                            Text(
                                'Location Detail: ${selectedPoint['ket2'] ?? '-'}'),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 16),
                    Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      children: [
                        _ConditionChip(
                          label: 'Pressure',
                          value: pressureCondition,
                          onChanged: (value) =>
                              setSheetState(() => pressureCondition = value),
                        ),
                        _ConditionChip(
                          label: 'Seal',
                          value: sealCondition,
                          onChanged: (value) =>
                              setSheetState(() => sealCondition = value),
                        ),
                        _ConditionChip(
                          label: 'Nozzle',
                          value: nozzleCondition,
                          onChanged: (value) =>
                              setSheetState(() => nozzleCondition = value),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: expiryController,
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Expiry Date',
                        prefixIcon: Icon(Icons.event_outlined),
                      ),
                      onTap: () async {
                        final current =
                            DateTime.tryParse(expiryController.text) ??
                                DateTime(DateTime.now().year + 1);
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: current,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          expiryController.text =
                              picked.toIso8601String().split('T').first;
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: remarkController,
                      minLines: 2,
                      maxLines: 4,
                      decoration: const InputDecoration(labelText: 'Remark'),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Photos',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 10),
                    _EditPhotoPicker(
                      label: 'Foto Sebelum (Before)',
                      existingPath: item?['photo_before']?.toString(),
                      file: newPhotoBefore,
                      onPick: () async {
                        final selected = await ImagePicker().pickImage(
                          source: ImageSource.camera,
                          imageQuality: 75,
                          maxWidth: 1600,
                        );
                        if (selected == null) return;
                        setSheetState(() => newPhotoBefore = File(selected.path));
                      },
                      onClear: () =>
                          setSheetState(() => newPhotoBefore = null),
                    ),
                    const SizedBox(height: 12),
                    _EditPhotoPicker(
                      label: 'Foto Sesudah (After)',
                      existingPath: item?['photo_after']?.toString(),
                      file: newPhotoAfter,
                      onPick: () async {
                        final selected = await ImagePicker().pickImage(
                          source: ImageSource.camera,
                          imageQuality: 75,
                          maxWidth: 1600,
                        );
                        if (selected == null) return;
                        setSheetState(() => newPhotoAfter = File(selected.path));
                      },
                      onClear: () =>
                          setSheetState(() => newPhotoAfter = null),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.pop(sheetContext);
                              _confirmDelete(inspection);
                            },
                            icon: const Icon(Icons.delete_outline),
                            label: const Text('Delete'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () async {
                              if (referenceController.text.trim().isEmpty ||
                                  locationId == null ||
                                  pointId == null) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                      'Reference, location, dan APAR wajib diisi.',
                                    ),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                                return;
                              }

                              final fields = <String, String>{
                                'reference_no':
                                    referenceController.text.trim(),
                                'inspection_date': dateController.text,
                                'location_id': locationId.toString(),
                                if (inspectorId != null)
                                  'inspector_id': inspectorId.toString(),
                                'point_id': pointId.toString(),
                                'item[pressure_condition]':
                                    pressureCondition ? '1' : '0',
                                'item[seal_condition]': sealCondition ? '1' : '0',
                                'item[nozzle_condition]':
                                    nozzleCondition ? '1' : '0',
                                'item[remark]':
                                    _nullIfEmpty(remarkController.text) ?? '',
                                'item[expiry_date]':
                                    _nullIfEmpty(expiryController.text) ?? '',
                              };
                              final id = _intValue(inspection['id']);
                              if (id == null) return;
                              final response = await context
                                  .read<ApiService>()
                                  .updateFireExtinguisherWithPhotos(
                                    id,
                                    fields,
                                    photoBefore: newPhotoBefore,
                                    photoAfter: newPhotoAfter,
                                  );
                              if (!context.mounted) return;
                              if (response.containsKey('error')) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content:
                                        Text(response['error'].toString()),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                                return;
                              }
                              didSave = true;
                              Navigator.pop(sheetContext);
                            },
                            icon: const Icon(Icons.save_outlined),
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

    referenceController.dispose();
    dateController.dispose();
    remarkController.dispose();
    expiryController.dispose();

    if (didSave && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Fire Extinguisher berhasil diperbarui.')),
      );
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> inspection) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Delete Fire Extinguisher?'),
          content: Text(
            inspection['reference_no']?.toString() ?? 'Delete this inspection?',
          ),
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

    final id = _intValue(inspection['id']);
    if (id == null) return;
    final response =
        await context.read<ApiService>().deleteFireExtinguisher(id);
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
      const SnackBar(content: Text('Fire Extinguisher berhasil dihapus.')),
    );
    setState(() => _isLoading = true);
    await _loadInspections();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Fire Extinguisher Inspection'),
        actions: [
          IconButton(
            onPressed: _retry,
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
                          final inspection = _mapFrom(_inspections[index]);
                          return _ExtinguisherListCard(
                            location: _relationName(
                              inspection['location'],
                              inspection['location_id'],
                              'Location',
                            ),
                            inspector: _relationName(
                              inspection['inspector'],
                              inspection['inspector_id'],
                              'Inspector',
                            ),
                            inspectionDate:
                                _dateOnly(inspection['inspection_date']) ?? '',
                            onTap: () => _showInspectionDetail(inspection),
                            onLongPress: () => _confirmDelete(inspection),
                          );
                        },
                      ),
                    ),
    );
  }
}

class _ExtinguisherListCard extends StatelessWidget {
  const _ExtinguisherListCard({
    required this.location,
    required this.inspector,
    required this.inspectionDate,
    required this.onTap,
    required this.onLongPress,
  });

  final String location;
  final String inspector;
  final String inspectionDate;
  final VoidCallback onTap;
  final VoidCallback onLongPress;

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
              Text(
                location.isEmpty ? '-' : location,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _ListValue(
                      label: 'Inspected By',
                      value: inspector,
                    ),
                  ),
                  const SizedBox(width: 16),
                  _ListValue(
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

class _ListValue extends StatelessWidget {
  const _ListValue({
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
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

class _ExtinguisherItemCard extends StatelessWidget {
  const _ExtinguisherItemCard({required this.item});

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
              item['name']?.toString() ?? 'Fire Extinguisher Item',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            _DetailRow(label: 'Type', value: item['type']),
            _DetailRow(
                label: 'Location Detail', value: item['location_detail']),
            if (_hasValue(item['expiry_date']))
              _DetailRow(
                  label: 'Expiry Date', value: _dateOnly(item['expiry_date'])),
            if (_hasValue(item['remark']))
              _DetailRow(label: 'Remark', value: item['remark']),
            const SizedBox(height: 12),
            LayoutBuilder(
              builder: (context, constraints) {
                final width = (constraints.maxWidth - 8) / 2;
                return Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _ConditionItem(
                      width: width,
                      label: 'Pressure',
                      value: _truthy(item['pressure_condition']),
                    ),
                    _ConditionItem(
                      width: width,
                      label: 'Seal',
                      value: _truthy(item['seal_condition']),
                    ),
                    _ConditionItem(
                      width: width,
                      label: 'Nozzle',
                      value: _truthy(item['nozzle_condition']),
                    ),
                  ],
                );
              },
            ),
            if (_hasValue(item['photo_before']) ||
                _hasValue(item['photo_after'])) ...[
              const SizedBox(height: 12),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  if (_hasValue(item['photo_before']))
                    _PhotoPreview(label: 'Before', path: item['photo_before']),
                  if (_hasValue(item['photo_after']))
                    _PhotoPreview(label: 'After', path: item['photo_after']),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _EditPhotoPicker extends StatelessWidget {
  const _EditPhotoPicker({
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
              child: _networkPhoto(context, existingPath!),
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

  Widget _networkPhoto(BuildContext context, String path) {
    final url = _mediaUrl(context, path);
    return Container(
      height: 120,
      width: double.infinity,
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: url == null
          ? const Icon(Icons.image_not_supported_outlined)
          : Image.network(
              url,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) =>
                  const Icon(Icons.broken_image_outlined),
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

class _ConditionItem extends StatelessWidget {
  const _ConditionItem({
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

class _PhotoPreview extends StatelessWidget {
  const _PhotoPreview({required this.label, required this.path});

  final String label;
  final dynamic path;

  @override
  Widget build(BuildContext context) {
    final url = _mediaUrl(context, path);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Container(
            width: 80,
            height: 80,
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            child: url == null
                ? const Icon(Icons.image_not_supported_outlined)
                : Image.network(
                    url,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) =>
                        const Icon(Icons.broken_image_outlined),
                  ),
          ),
        ),
      ],
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

String? _dateOnly(dynamic value) {
  if (!_hasValue(value)) return null;
  return value.toString().split('T').first.split(' ').first;
}

String _dateTime(dynamic value) {
  if (!_hasValue(value)) return '-';
  return value.toString().replaceFirst('T', ' ').split('.').first;
}

String _today() => DateTime.now().toIso8601String().split('T').first;

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

int? _selectedId(dynamic value, List<dynamic> options, String key) {
  final id = _intValue(value);
  if (id == null) return null;
  return options.map(_mapFrom).any((item) => _intValue(item[key]) == id)
      ? id
      : null;
}

int? _selectedPointIdForItem(Map<String, dynamic>? item, List<dynamic> points) {
  if (item == null) return null;
  final name = item['name']?.toString().trim() ?? '';
  final type = item['type']?.toString().trim() ?? '';
  final detail = item['location_detail']?.toString().trim() ?? '';
  if (name.isEmpty) return null;

  final mapped = points.map(_mapFrom).toList();
  for (final point in mapped) {
    if (point['name_point']?.toString().trim() == name &&
        point['ket1']?.toString().trim() == type &&
        point['ket2']?.toString().trim() == detail) {
      return _intValue(point['id']);
    }
  }
  for (final point in mapped) {
    if (point['name_point']?.toString().trim() == name) {
      return _intValue(point['id']);
    }
  }
  return null;
}

Map<String, dynamic> _pointById(int? id, List<dynamic> points) {
  if (id == null) return <String, dynamic>{};
  return points.map(_mapFrom).firstWhere(
        (point) => _intValue(point['id']) == id,
        orElse: () => <String, dynamic>{},
      );
}

String _locationOption(Map<String, dynamic> location) {
  final id = (location['id_location'] ?? location['id'])?.toString() ?? '';
  final name =
      (location['name'] ?? location['location_name'] ?? 'Location').toString();
  return id.isEmpty ? name : '$id - $name';
}

String _pointOption(Map<String, dynamic> point) {
  final id = point['id']?.toString() ?? '';
  final name = point['name_point']?.toString() ?? 'APAR';
  final type = point['ket1']?.toString() ?? '';
  return type.isEmpty ? '$id - $name' : '$id - $name - $type';
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

String? _nullIfEmpty(String value) {
  final text = value.trim();
  return text.isEmpty ? null : text;
}

String? _mediaUrl(BuildContext context, dynamic path) {
  if (!_hasValue(path)) return null;
  final text = path.toString();
  if (text.startsWith('http://') || text.startsWith('https://')) return text;
  final apiBase = context.read<ApiService>().baseUrl;
  final appBase = apiBase.replaceFirst(RegExp(r'/api/?$'), '');
  final cleanBase = appBase.endsWith('/')
      ? appBase.substring(0, appBase.length - 1)
      : appBase;
  final cleanPath = text.startsWith('/') ? text.substring(1) : text;
  return '$cleanBase/$cleanPath';
}
