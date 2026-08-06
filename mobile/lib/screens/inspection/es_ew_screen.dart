import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import 'es_ew_common.dart';
import 'es_ew_create_flow.dart';

class EsEwScreen extends StatefulWidget {
  const EsEwScreen({super.key, this.initialId});

  final String? initialId;

  @override
  State<EsEwScreen> createState() => _EsEwScreenState();
}

class _EsEwScreenState extends State<EsEwScreen> {
  List<dynamic> _inspections = [];
  Map<String, dynamic> _master = {};
  bool _isLoading = true;
  bool _isLoadingMaster = false;
  bool _hasShownInitialDetail = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadInspections();
    _loadMasterData();
    if (widget.initialId != null) _showDirectDetail(widget.initialId!);
  }

  Future<void> _loadInspections() async {
    try {
      final values = await context.read<ApiService>().getEsEw();
      if (!mounted) return;
      setState(() {
        _inspections = values;
        _isLoading = false;
        _error = null;
      });
      _showInitialFromList();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  Future<void> _loadMasterData() async {
    if (_isLoadingMaster) return;
    _isLoadingMaster = true;
    try {
      final value = await context.read<ApiService>().getEsEwMasterData();
      if (!mounted) return;
      setState(() {
        _master = value;
        _isLoadingMaster = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoadingMaster = false);
    }
  }

  Future<void> _ensureMasterData() async {
    if (_master.isEmpty) await _loadMasterData();
  }

  Future<void> _showDirectDetail(String value) async {
    final id = int.tryParse(value);
    if (id == null) return;
    try {
      final detail = await context.read<ApiService>().getEsEwInspection(id);
      if (!mounted || _hasShownInitialDetail) return;
      _hasShownInitialDetail = true;
      _openDetailSheet(detail);
    } catch (_) {
      // List yang dimuat akan menjadi fallback.
    }
  }

  void _showInitialFromList() {
    if (_hasShownInitialDetail || widget.initialId == null) return;
    final match = _inspections.map(esEwMap).where(
          (item) => item['id']?.toString() == widget.initialId,
        );
    if (match.isEmpty) return;
    _hasShownInitialDetail = true;
    _showInspectionDetail(match.first);
  }

  Future<void> _retry() async {
    setState(() => _isLoading = true);
    await Future.wait([_loadInspections(), _loadMasterData()]);
  }

  Future<void> _startNewInspection() async {
    final saved = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const EsEwSetupScreen()),
    );
    if (saved == true && mounted) {
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  Future<Map<String, dynamic>> _fetchDetail(
    Map<String, dynamic> inspection,
  ) async {
    final id = esEwInt(inspection['id']);
    if (id == null) return inspection;
    try {
      return await context.read<ApiService>().getEsEwInspection(id);
    } catch (_) {
      return inspection;
    }
  }

  Future<void> _showInspectionDetail(
    Map<String, dynamic> inspection,
  ) async {
    final detail = await _fetchDetail(inspection);
    if (mounted) _openDetailSheet(detail);
  }

  void _openDetailSheet(Map<String, dynamic> detail) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
        final item = esEwList(detail['items']).map(esEwMap).firstOrNull ?? {};
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.9,
          minChildSize: 0.5,
          maxChildSize: 0.96,
          builder: (context, controller) {
            return ListView(
              controller: controller,
              padding: const EdgeInsets.all(20),
              children: [
                Text(
                  esEwText(detail['reference_no']).isEmpty
                      ? 'ES/EW Inspection'
                      : esEwText(detail['reference_no']),
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 12),
                _DetailHeader(
                  area: _relationName(
                    detail['area'],
                    detail['area_id'],
                    'Area',
                  ),
                  inspector: _relationName(
                    detail['inspector'],
                    detail['inspector_id'],
                    'Inspector',
                  ),
                  date: _dateOnly(detail['inspection_date']),
                  status: esEwText(detail['status']),
                ),
                const SizedBox(height: 16),
                if (item.isEmpty)
                  const Text('No items')
                else
                  _EsEwItemCard(
                    item: item,
                    apiBaseUrl: context.read<ApiService>().baseUrl,
                  ),
                const SizedBox(height: 16),
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
                          _openEdit(detail);
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

  Future<void> _openEdit(Map<String, dynamic> detail) async {
    await _ensureMasterData();
    if (!mounted) return;
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (_) => EsEwEditScreen(
          inspection: detail,
          masterData: _master,
        ),
      ),
    );
    if (result != null && mounted) {
      setState(() => _isLoading = true);
      await _loadInspections();
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> inspection) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete ES/EW Inspection?'),
        content: Text(
          esEwText(inspection['reference_no']).isEmpty
              ? _relationName(
                  inspection['area'],
                  inspection['area_id'],
                  'Area',
                )
              : esEwText(inspection['reference_no']),
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
      ),
    );
    if (confirmed != true || !mounted) return;

    final id = esEwInt(inspection['id']);
    if (id == null) return;
    final response = await context.read<ApiService>().deleteEsEw(id);
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
      const SnackBar(
        content: Text('ES/EW inspection berhasil dihapus.'),
        backgroundColor: Colors.green,
      ),
    );
    await _loadInspections();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ES/EW Inspection'),
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
                          final inspection = esEwMap(_inspections[index]);
                          return _EsEwListCard(
                            area: _relationName(
                              inspection['area'],
                              inspection['area_id'],
                              'Area',
                            ),
                            inspector: _relationName(
                              inspection['inspector'],
                              inspection['inspector_id'],
                              'Inspector',
                            ),
                            inspectionDate:
                                _dateOnly(inspection['inspection_date']),
                            onTap: () => _showInspectionDetail(inspection),
                            onLongPress: () => _confirmDelete(inspection),
                          );
                        },
                      ),
                    ),
    );
  }
}

class EsEwEditScreen extends StatefulWidget {
  const EsEwEditScreen({
    super.key,
    required this.inspection,
    required this.masterData,
  });

  final Map<String, dynamic> inspection;
  final Map<String, dynamic> masterData;

  @override
  State<EsEwEditScreen> createState() => _EsEwEditScreenState();
}

class _EsEwEditScreenState extends State<EsEwEditScreen> {
  final ImagePicker _picker = ImagePicker();
  late final TextEditingController _dateController;
  late final TextEditingController _remarkController;
  late final Map<String, bool> _conditions;
  late final List<dynamic> _areas;
  late final List<dynamic> _points;
  late final Map<String, dynamic> _item;
  int? _areaId;
  int? _pointId;
  File? _eyeWashPhoto;
  File? _emergencyShowerPhoto;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _item = esEwList(widget.inspection['items']).map(esEwMap).firstOrNull ?? {};
    _areas = esEwList(widget.masterData['areas']);
    _points = esEwList(widget.masterData['points'])
        .where(isEsEwPoint)
        .toList(growable: false);
    _areaId = esEwInt(widget.inspection['area_id']);
    _pointId = matchEsEwPointId(_item, _points);
    _dateController = TextEditingController(
      text: _dateOnly(widget.inspection['inspection_date']),
    );
    _remarkController = TextEditingController(
      text: esEwText(_item['remark']),
    );
    _conditions = {
      for (final field in esEwConditionLabels.keys)
        field: esEwBool(_item[field]),
    };
  }

  @override
  void dispose() {
    _dateController.dispose();
    _remarkController.dispose();
    super.dispose();
  }

  Map<String, dynamic> get _selectedPoint => _points.map(esEwMap).firstWhere(
        (point) => esEwInt(point['id']) == _pointId,
        orElse: () => <String, dynamic>{},
      );

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
    final id = esEwInt(widget.inspection['id']);
    if (id == null || _areaId == null || _pointId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Area dan Point ES/EW wajib diisi.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    setState(() => _isSaving = true);
    final currentUser = esEwMap(context.read<AuthService>().user);
    final inspectorId = esEwInt(widget.inspection['inspector_id']) ??
        esEwInt(currentUser['id']);
    final fields = <String, String>{
      if (esEwText(widget.inspection['reference_no']).isNotEmpty)
        'reference_no': esEwText(widget.inspection['reference_no']),
      'inspection_date': _dateController.text,
      'area_id': _areaId.toString(),
      'point_id': _pointId.toString(),
      if (inspectorId != null) 'inspector_id': inspectorId.toString(),
      'items[0][remark]': _remarkController.text.trim(),
      for (final entry in _conditions.entries)
        'items[0][${entry.key}]': entry.value ? '1' : '0',
    };
    try {
      final response = await context.read<ApiService>().updateEsEw(
            id,
            fields,
            eyeWashPhoto: _eyeWashPhoto,
            emergencyShowerPhoto: _emergencyShowerPhoto,
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
      Navigator.pop(context, 'saved');
    } catch (error) {
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Gagal memperbarui inspeksi: $error'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete ES/EW Inspection?'),
        content: Text(esEwText(widget.inspection['reference_no'])),
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
      ),
    );
    if (confirmed != true || !mounted) return;
    final id = esEwInt(widget.inspection['id']);
    if (id == null) return;
    final response = await context.read<ApiService>().deleteEsEw(id);
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
    Navigator.pop(context, 'deleted');
  }

  @override
  Widget build(BuildContext context) {
    final selectedPoint = _selectedPoint;
    final inspector = _relationName(
      widget.inspection['inspector'],
      widget.inspection['inspector_id'],
      'Inspector',
    );
    return Scaffold(
      appBar: AppBar(title: const Text('Edit ES/EW Inspection')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: _dateController,
                readOnly: true,
                decoration: const InputDecoration(
                  labelText: 'Inspection Date',
                  prefixIcon: Icon(Icons.calendar_today_outlined),
                  border: OutlineInputBorder(),
                ),
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: DateTime.tryParse(_dateController.text) ??
                        DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime(2100),
                  );
                  if (picked != null) {
                    _dateController.text =
                        picked.toIso8601String().split('T').first;
                  }
                },
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                initialValue: _areaId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Area',
                  prefixIcon: Icon(Icons.map_outlined),
                  border: OutlineInputBorder(),
                ),
                items: _areas.map(esEwMap).map((area) {
                  return DropdownMenuItem<int>(
                    value: esEwInt(area['id']),
                    child: Text(esEwText(area['name'])),
                  );
                }).toList(),
                onChanged: (value) => setState(() => _areaId = value),
              ),
              const SizedBox(height: 12),
              TextFormField(
                initialValue: inspector,
                readOnly: true,
                decoration: const InputDecoration(
                  labelText: 'Inspector',
                  prefixIcon: Icon(Icons.person_outline),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                initialValue: _pointId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'ES/EW Point',
                  prefixIcon: Icon(Icons.shower_outlined),
                  border: OutlineInputBorder(),
                ),
                hint: Text(
                  esEwText(_item['name']).isEmpty
                      ? '- Select Point -'
                      : '${esEwText(_item['name'])} (select point)',
                ),
                items: _points.map(esEwMap).map((point) {
                  return DropdownMenuItem<int>(
                    value: esEwInt(point['id']),
                    child: Text(
                      esEwPointOption(point),
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList(),
                onChanged: (value) => setState(() => _pointId = value),
              ),
              if (selectedPoint.isNotEmpty) ...[
                const SizedBox(height: 10),
                _PointSummary(point: selectedPoint),
              ],
              const SizedBox(height: 20),
              Text(
                'Inspection Condition',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              for (final entry in esEwConditionLabels.entries)
                _EditCondition(
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
              const SizedBox(height: 20),
              _EditPhoto(
                label: 'Eye Wash',
                existingPath: esEwText(_item['photo_before']),
                replacement: _eyeWashPhoto,
                onPick: () => _pickPhoto(true),
              ),
              const SizedBox(height: 12),
              _EditPhoto(
                label: 'Emergency Shower',
                existingPath: esEwText(_item['photo_after']),
                replacement: _emergencyShowerPhoto,
                onPick: () => _pickPhoto(false),
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _isSaving ? null : _delete,
                      icon: const Icon(Icons.delete_outline),
                      label: const Text('Delete'),
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

class _EsEwListCard extends StatelessWidget {
  const _EsEwListCard({
    required this.area,
    required this.inspector,
    required this.inspectionDate,
    required this.onTap,
    required this.onLongPress,
  });

  final String area;
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
                'Area',
                style: TextStyle(
                  color: Colors.black54,
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                area.isEmpty ? '-' : area,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _ListValue(label: 'Inspected By', value: inspector),
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
          style: const TextStyle(color: Colors.black54, fontSize: 11),
        ),
        const SizedBox(height: 3),
        Text(
          value.isEmpty ? '-' : value,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

class _DetailHeader extends StatelessWidget {
  const _DetailHeader({
    required this.area,
    required this.inspector,
    required this.date,
    required this.status,
  });

  final String area;
  final String inspector;
  final String date;
  final String status;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primaryContainer.withValues(
              alpha: 0.4,
            ),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          _DetailRow(label: 'Area', value: area),
          _DetailRow(label: 'Inspector', value: inspector),
          _DetailRow(label: 'Inspection Date', value: date),
          _DetailRow(label: 'Status', value: status.isEmpty ? '-' : status),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(label, style: const TextStyle(color: Colors.black54)),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}

class _EsEwItemCard extends StatelessWidget {
  const _EsEwItemCard({required this.item, required this.apiBaseUrl});

  final Map<String, dynamic> item;
  final String apiBaseUrl;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              esEwText(item['name']),
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text('Location: ${esEwText(item['type'])}'),
            Text('Section: ${esEwText(item['location_detail'])}'),
            const Divider(height: 24),
            for (final entry in esEwConditionLabels.entries)
              _ConditionResult(
                label: entry.value,
                value: esEwBool(item[entry.key]),
              ),
            if (esEwText(item['remark']).isNotEmpty) ...[
              const Divider(height: 24),
              const Text('Remark',
                  style: TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Text(esEwText(item['remark'])),
            ],
            if (esEwText(item['photo_before']).isNotEmpty ||
                esEwText(item['photo_after']).isNotEmpty) ...[
              const Divider(height: 24),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  if (esEwText(item['photo_before']).isNotEmpty)
                    _NetworkPhoto(
                      label: 'Eye Wash',
                      url: _photoUrl(apiBaseUrl, item['photo_before']),
                    ),
                  if (esEwText(item['photo_after']).isNotEmpty)
                    _NetworkPhoto(
                      label: 'Emergency Shower',
                      url: _photoUrl(apiBaseUrl, item['photo_after']),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ConditionResult extends StatelessWidget {
  const _ConditionResult({required this.label, required this.value});

  final String label;
  final bool value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: value ? Colors.green.shade50 : Colors.red.shade50,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              value ? 'YES' : 'NO',
              style: TextStyle(
                color: value ? Colors.green.shade800 : Colors.red.shade800,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _NetworkPhoto extends StatelessWidget {
  const _NetworkPhoto({required this.label, required this.url});

  final String label;
  final String url;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 145,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Image.network(
              url,
              height: 100,
              width: 145,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => const SizedBox(
                height: 100,
                child: Center(child: Icon(Icons.broken_image_outlined)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PointSummary extends StatelessWidget {
  const _PointSummary({required this.point});

  final Map<String, dynamic> point;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primaryContainer.withValues(
              alpha: 0.4,
            ),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Location: ${esEwText(point['ket1'])}'),
          Text('Section: ${esEwText(point['ket2'])}'),
        ],
      ),
    );
  }
}

class _EditCondition extends StatelessWidget {
  const _EditCondition({
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
            child: Text(label,
                style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
          SegmentedButton<bool>(
            segments: const [
              ButtonSegment(value: true, label: Text('YES')),
              ButtonSegment(value: false, label: Text('NO')),
            ],
            selected: {value},
            showSelectedIcon: false,
            onSelectionChanged: (values) => onChanged(values.first),
          ),
        ],
      ),
    );
  }
}

class _EditPhoto extends StatelessWidget {
  const _EditPhoto({
    required this.label,
    required this.existingPath,
    required this.replacement,
    required this.onPick,
  });

  final String label;
  final String existingPath;
  final File? replacement;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).dividerColor),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.image_outlined),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              replacement != null
                  ? '$label: new photo selected'
                  : existingPath.isNotEmpty
                      ? '$label: current photo available'
                      : '$label: no photo',
            ),
          ),
          TextButton.icon(
            onPressed: onPick,
            icon: const Icon(Icons.camera_alt_outlined),
            label: Text(replacement == null ? 'Take' : 'Retake'),
          ),
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

String _relationName(dynamic relation, dynamic fallbackId, String label) {
  final data = esEwMap(relation);
  if (data.isNotEmpty) {
    final name = data['name'] ?? data['location_name'] ?? data['description'];
    final id = data['id_location'] ?? data['id'];
    if (id != null && name != null && label == 'Area') return name.toString();
    if (name != null) return name.toString();
  }
  return fallbackId == null ? '' : '$label #$fallbackId';
}

String _dateOnly(dynamic value) {
  final text = value?.toString() ?? '';
  return text.isEmpty ? '' : text.split('T').first.split(' ').first;
}

String _photoUrl(String baseUrl, dynamic path) {
  final cleanBase = baseUrl.replaceFirst(RegExp(r'/api/?$'), '');
  final cleanPath = esEwText(path).replaceFirst(RegExp(r'^/+'), '');
  return '$cleanBase/$cleanPath';
}
