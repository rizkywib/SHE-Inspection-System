import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';

class FireHydrantScreen extends StatefulWidget {
  const FireHydrantScreen({super.key, this.initialId});

  final String? initialId;

  @override
  State<FireHydrantScreen> createState() => _FireHydrantScreenState();
}

class _FireHydrantScreenState extends State<FireHydrantScreen> {
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
      final detail = await api.getFireHydrant(idInt);
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
      final data = await api.getFireHydrants();
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
        _safeReferenceLoad(api.getFireHydrantLocations),
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
        title: const Text('Fire Hydrant Inspection'),
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
        onPressed: () => _showInspectionForm(),
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
                          return Card(
                            child: ListTile(
                              title: Text(
                                (item['reference_no'] ?? 'No Reference')
                                    .toString(),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              subtitle: Text(_subtitle(item)),
                              trailing: Wrap(
                                spacing: 8,
                                crossAxisAlignment: WrapCrossAlignment.center,
                                children: [
                                  Chip(
                                    label: Text(
                                      (item['status'] ?? '').toString(),
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                  ),
                                  IconButton(
                                    onPressed: () =>
                                        _showInspectionForm(item: item),
                                    icon: const Icon(Icons.edit_outlined),
                                    tooltip: 'Edit',
                                  ),
                                ],
                              ),
                              onTap: () => _showInspectionDetail(item),
                              onLongPress: () => _confirmDelete(item),
                            ),
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

  String _subtitle(Map<String, dynamic> item) {
    final date = _dateOnly(item['inspection_date']) ?? '';
    final location =
        _relationName(item['location'], item['location_id'], 'Location');
    final inspector =
        _relationName(item['inspector'], item['inspector_id'], 'Inspector');
    final itemCount =
        (item['items'] is List) ? (item['items'] as List).length : 0;
    return [
      if (date.isNotEmpty) date,
      if (location.isNotEmpty) location,
      if (inspector.isNotEmpty) inspector,
      '$itemCount item',
    ].join(' - ');
  }

  Future<Map<String, dynamic>> _fetchDetail(Map<String, dynamic> item) async {
    final id = _intValue(item['id']);
    if (id == null) return item;
    try {
      return await context.read<ApiService>().getFireHydrant(id);
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
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        detail['reference_no']?.toString() ??
                            'Fire Hydrant Detail',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                    ),
                    IconButton(
                      onPressed: () {
                        Navigator.pop(context);
                        _showInspectionForm(item: detail);
                      },
                      icon: const Icon(Icons.edit_outlined),
                      tooltip: 'Edit',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _DetailSection(
                  title: 'Inspection Data',
                  children: [
                    _DetailRow(
                        label: 'Date',
                        value: _dateOnly(detail['inspection_date'])),
                    _DetailRow(label: 'Status', value: detail['status']),
                    _DetailRow(
                      label: 'Location',
                      value: _relationName(detail['location'],
                          detail['location_id'], 'Location'),
                    ),
                    _DetailRow(
                      label: 'Inspector',
                      value: _relationName(detail['inspector'],
                          detail['inspector_id'], 'Inspector'),
                    ),
                    _DetailRow(
                        label: 'Checked In At',
                        value: _dateTime(detail['checked_in_at'])),
                    _DetailRow(
                        label: 'Signed At',
                        value: _dateTime(detail['signed_at'])),
                    _DetailRow(label: 'Notes', value: detail['notes']),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  'Fire Hydrant Items',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                if (items.isEmpty)
                  const Text('No items')
                else
                  ...items.asMap().entries.map((entry) {
                    final data = _mapFrom(entry.value);
                    return _HydrantItemCard(index: entry.key + 1, item: data);
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
    final detail = await _fetchDetail(item);
    _openDetailSheet(detail);
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
    var status = (item?['status'] ?? 'new').toString();
    if (!['new', 'completed', 'signed'].contains(status)) status = 'new';

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
        .map((value) => _HydrantItemFormData.fromMap(_mapFrom(value)))
        .toList();
    if (!isEdit && itemForms.isEmpty) {
      itemForms.add(_HydrantItemFormData());
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
                      isEdit ? 'Edit Fire Hydrant' : 'Create Fire Hydrant',
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
                        DropdownMenuItem(value: 'new', child: Text('New')),
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
                            'Fire Hydrant Items',
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
                        return _HydrantItemEditor(
                          index: entry.key,
                          data: entry.value,
                          points: _points,
                          canRemove: isEdit && itemForms.length > 1,
                          onRemove: () {
                            setSheetState(() {
                              final removed = itemForms.removeAt(entry.key);
                              removed.dispose();
                            });
                          },
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

                              final payload = <String, dynamic>{
                                'inspection_date': dateController.text,
                                'location_id': locationId,
                                'inspector_id': inspectorId,
                                'status': status,
                                'notes': _nullIfEmpty(notesController.text),
                                'items': itemForms
                                    .map((form) => form.toPayload())
                                    .toList(),
                              };

                              final saved =
                                  await _saveInspection(item, payload);
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
        const SnackBar(content: Text('Fire Hydrant saved')),
      );
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  String? _validateItemForms(List<_HydrantItemFormData> itemForms) {
    for (var i = 0; i < itemForms.length; i++) {
      final item = itemForms[i];
      if (item.hydrantNumber.text.trim().isEmpty) {
        return 'Hydrant number item ${i + 1} wajib diisi';
      }
      if (item.name.text.trim().isEmpty) {
        return 'Name item ${i + 1} wajib diisi';
      }
    }
    return null;
  }

  Future<bool> _saveInspection(
      Map<String, dynamic>? item, Map<String, dynamic> payload) async {
    final api = context.read<ApiService>();
    final response = item == null
        ? await api.createFireHydrant(payload)
        : await api.updateFireHydrant(_intValue(item['id'])!, payload);

    if (!mounted) return false;
    if (response.containsKey('error')) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response['error'].toString()),
          backgroundColor: Colors.red,
        ),
      );
      return false;
    }

    return true;
  }

  Future<void> _confirmDelete(Map<String, dynamic> item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Delete Fire Hydrant?'),
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
    final response = await api.deleteFireHydrant(_intValue(item['id'])!);
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
      const SnackBar(content: Text('Fire Hydrant deleted')),
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

  String? _dateTime(dynamic value) {
    if (value == null) return null;
    final text = value.toString();
    if (text.isEmpty) return null;
    return text.replaceFirst('T', ' ').split('.').first;
  }
}

class _HydrantItemFormData {
  _HydrantItemFormData({
    String hydrantNumber = '',
    String name = '',
    String locationDetail = '',
    String remark = '',
    this.hoseCondition = true,
    this.nozzleCondition = true,
    this.couplingCondition = true,
    this.wrenchCondition = true,
    this.valveCondition = true,
    this.couplingExtraCondition = true,
  })  : hydrantNumber = TextEditingController(text: hydrantNumber),
        name = TextEditingController(text: name),
        locationDetail = TextEditingController(text: locationDetail),
        remark = TextEditingController(text: remark);

  factory _HydrantItemFormData.fromMap(Map<String, dynamic> item) {
    return _HydrantItemFormData(
      hydrantNumber: item['hydrant_number']?.toString() ?? '',
      name: item['name']?.toString() ?? '',
      locationDetail: item['location_detail']?.toString() ?? '',
      remark: item['remark']?.toString() ?? '',
      hoseCondition: _truthy(item['hose_condition']),
      nozzleCondition: _truthy(item['nozzle_condition']),
      couplingCondition: _truthy(item['coupling_condition']),
      wrenchCondition: _truthy(item['wrench_condition']),
      valveCondition: _truthy(item['valve_condition']),
      couplingExtraCondition: _truthy(item['coupling_extra_condition']),
    );
  }

  final TextEditingController hydrantNumber;
  final TextEditingController name;
  final TextEditingController locationDetail;
  final TextEditingController remark;
  bool hoseCondition;
  bool nozzleCondition;
  bool couplingCondition;
  bool wrenchCondition;
  bool valveCondition;
  bool couplingExtraCondition;

  Map<String, dynamic> toPayload() {
    final payload = <String, dynamic>{
      'hydrant_number': hydrantNumber.text.trim(),
      'name': name.text.trim(),
      'location_detail': _nullIfEmpty(locationDetail.text),
      'hose_condition': hoseCondition,
      'nozzle_condition': nozzleCondition,
      'coupling_condition': couplingCondition,
      'wrench_condition': wrenchCondition,
      'valve_condition': valveCondition,
      'coupling_extra_condition': couplingExtraCondition,
      'remark': _nullIfEmpty(remark.text),
    };
    return payload;
  }

  void applyPoint(Map<String, dynamic> point) {
    hydrantNumber.text = (point['name_point'] ?? point['id'] ?? '').toString();
    final pointName = point['ket1']?.toString() ?? '';
    final pointLocation = point['ket2']?.toString() ?? '';
    if (pointName.isNotEmpty) name.text = pointName;
    if (pointLocation.isNotEmpty) locationDetail.text = pointLocation;
  }

  void dispose() {
    hydrantNumber.dispose();
    name.dispose();
    locationDetail.dispose();
    remark.dispose();
  }
}

class _HydrantItemEditor extends StatelessWidget {
  const _HydrantItemEditor({
    required this.index,
    required this.data,
    required this.points,
    required this.canRemove,
    required this.onRemove,
    required this.onChanged,
  });

  final int index;
  final _HydrantItemFormData data;
  final List<dynamic> points;
  final bool canRemove;
  final VoidCallback onRemove;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    final selectedPointId = _selectedPointIdForHydrant(
      data.hydrantNumber.text,
      points,
    );

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        initiallyExpanded: index == 0,
        title: Text('Item ${index + 1}'),
        subtitle: Text(
          data.hydrantNumber.text.isEmpty
              ? 'Hydrant detail'
              : data.hydrantNumber.text,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: canRemove
            ? IconButton(
                onPressed: onRemove,
                icon: const Icon(Icons.delete_outline),
                tooltip: 'Remove item',
              )
            : null,
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        children: [
          DropdownButtonFormField<int?>(
            initialValue: selectedPointId,
            isExpanded: true,
            decoration: const InputDecoration(
              labelText: 'Hydrant Number',
              border: OutlineInputBorder(),
            ),
            items: [
              DropdownMenuItem<int?>(
                value: null,
                child: Text(
                  data.hydrantNumber.text.trim().isEmpty
                      ? '- Select Hydrant Number -'
                      : data.hydrantNumber.text.trim(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              ...points
                  .map(_mapFrom)
                  .where((point) => _intValue(point['id']) != null)
                  .map((point) {
                return DropdownMenuItem<int?>(
                  value: _intValue(point['id']),
                  child: Text(
                    _pointOption(point),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                );
              }),
            ],
            onChanged: (id) {
              final point = points.map(_mapFrom).firstWhere(
                    (value) => _intValue(value['id']) == id,
                    orElse: () => <String, dynamic>{},
                  );
              if (point.isNotEmpty) {
                data.applyPoint(point);
                onChanged();
              }
            },
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
                label: 'Hose',
                value: data.hoseCondition,
                onChanged: (v) {
                  data.hoseCondition = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Nozzle',
                value: data.nozzleCondition,
                onChanged: (v) {
                  data.nozzleCondition = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Coupling',
                value: data.couplingCondition,
                onChanged: (v) {
                  data.couplingCondition = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Wrench',
                value: data.wrenchCondition,
                onChanged: (v) {
                  data.wrenchCondition = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Valve',
                value: data.valveCondition,
                onChanged: (v) {
                  data.valveCondition = v;
                  onChanged();
                },
              ),
              _ConditionChip(
                label: 'Extra Coupling',
                value: data.couplingExtraCondition,
                onChanged: (v) {
                  data.couplingExtraCondition = v;
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
        ],
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

class _HydrantItemCard extends StatelessWidget {
  const _HydrantItemCard({required this.index, required this.item});

  final int index;
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
            Text('Item $index', style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            _DetailRow(label: 'Hydrant Number', value: item['hydrant_number']),
            _DetailRow(label: 'Name', value: item['name']),
            if (_hasValue(item['location_detail']))
              _DetailRow(
                  label: 'Location Detail', value: item['location_detail']),
            if (_hasValue(item['remark']))
              _DetailRow(label: 'Remark', value: item['remark']),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _StatusPill(
                    label: 'Hose', value: _truthy(item['hose_condition'])),
                _StatusPill(
                    label: 'Nozzle', value: _truthy(item['nozzle_condition'])),
                _StatusPill(
                    label: 'Coupling',
                    value: _truthy(item['coupling_condition'])),
                _StatusPill(
                    label: 'Wrench', value: _truthy(item['wrench_condition'])),
                _StatusPill(
                    label: 'Valve', value: _truthy(item['valve_condition'])),
                _StatusPill(
                    label: 'Extra Coupling',
                    value: _truthy(item['coupling_extra_condition'])),
              ],
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

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.label, required this.value});

  final String label;
  final bool value;

  @override
  Widget build(BuildContext context) {
    return Chip(
      avatar: Icon(
        value ? Icons.check_circle_outline : Icons.cancel_outlined,
        size: 18,
        color: value ? Colors.green : Colors.red,
      ),
      label: Text('$label: ${value ? 'OK' : 'Need Correction'}'),
    );
  }
}

class _DetailSection extends StatelessWidget {
  const _DetailSection({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            ...children,
          ],
        ),
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
            width: 130,
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

bool _hasValue(dynamic value) {
  if (value == null) return false;
  final text = value.toString().trim();
  return text.isNotEmpty && text != '-';
}

String? _mediaUrl(BuildContext context, dynamic path) {
  if (!_hasValue(path)) return null;
  final text = path.toString();
  if (text.startsWith('http://') || text.startsWith('https://')) {
    return text;
  }
  final apiBase = context.read<ApiService>().baseUrl;
  final appBase = apiBase.replaceFirst(RegExp(r'/api/?$'), '');
  final cleanBase = appBase.endsWith('/')
      ? appBase.substring(0, appBase.length - 1)
      : appBase;
  final cleanPath = text.startsWith('/') ? text.substring(1) : text;
  return '$cleanBase/$cleanPath';
}

List<dynamic> _listFrom(dynamic value) {
  if (value is List) return value;
  return <dynamic>[];
}

int? _intValue(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}

int? _selectedId(dynamic value, List<dynamic> options, String key) {
  final id = _intValue(value);
  if (id == null) return null;
  final exists =
      options.map(_mapFrom).any((item) => _intValue(item[key]) == id);
  return exists ? id : null;
}

int? _selectedPointIdForHydrant(String hydrantNumber, List<dynamic> points) {
  final selected = hydrantNumber.trim();
  if (selected.isEmpty) return null;

  for (final pointValue in points) {
    final point = _mapFrom(pointValue);
    final pointId = _intValue(point['id']);
    if (pointId == null) continue;

    final names = [
      point['name_point'],
      point['ket1'],
      point['id'],
    ].map((value) => value?.toString().trim()).whereType<String>();

    if (names.any((value) => value == selected)) return pointId;
  }

  return null;
}

String? _nullIfEmpty(String value) {
  final text = value.trim();
  return text.isEmpty ? null : text;
}

bool _truthy(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final text = value?.toString().toLowerCase();
  return text == '1' || text == 'true' || text == 'yes';
}

String _pointOption(Map<String, dynamic> point) {
  final id = point['id']?.toString() ?? '';
  final name = point['name_point']?.toString() ?? 'Point';
  final ket1 = point['ket1']?.toString() ?? '';
  if (ket1.isEmpty) return id.isEmpty ? name : '$id - $name';
  return id.isEmpty ? '$name - $ket1' : '$id - $name - $ket1';
}
