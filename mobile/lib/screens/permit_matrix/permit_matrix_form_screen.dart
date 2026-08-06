import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import 'permit_matrix_common.dart';

class PermitMatrixFormScreen extends StatefulWidget {
  const PermitMatrixFormScreen({super.key, this.inspection});

  final Map<String, dynamic>? inspection;

  @override
  State<PermitMatrixFormScreen> createState() => _PermitMatrixFormScreenState();
}

class _PermitMatrixFormScreenState extends State<PermitMatrixFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController;
  late final TextEditingController _permitNumberController;
  late final TextEditingController _sectionEquipmentController;
  late final TextEditingController _jobPerformanceController;
  late final TextEditingController _authorizedCraftmanController;
  late final TextEditingController _authorizedFacilityController;
  late final TextEditingController _contractorNameController;
  late final TextEditingController _workDescriptionController;
  late final TextEditingController _permitFindingsController;

  List<dynamic> _inspectors = [];
  List<dynamic> _permitTypes = [];
  List<dynamic> _supervisionAreas = [];
  List<dynamic> _mainAreas = [];
  List<dynamic> _subAreas = [];
  int? _inspectorId;
  int? _permitTypeId;
  int? _supervisionAreaId;
  int? _mainAreaId;
  int? _subAreaId;
  bool _isLoading = true;
  bool _isSaving = false;
  String? _error;

  bool get _isEdit => widget.inspection != null;

  @override
  void initState() {
    super.initState();
    final inspection = widget.inspection ?? const <String, dynamic>{};
    _dateController = TextEditingController(
      text: permitDate(inspection['permit_date']).isEmpty
          ? DateTime.now().toIso8601String().split('T').first
          : permitDate(inspection['permit_date']),
    );
    _permitNumberController =
        TextEditingController(text: permitText(inspection['permit_number']));
    _sectionEquipmentController = TextEditingController(
      text: permitText(inspection['section_equipment']),
    );
    _jobPerformanceController = TextEditingController(
      text: permitText(inspection['job_performance']),
    );
    _authorizedCraftmanController = TextEditingController(
      text: permitText(inspection['authorized_craftman']),
    );
    _authorizedFacilityController = TextEditingController(
      text: permitText(inspection['authorized_facility']),
    );
    _contractorNameController = TextEditingController(
      text: permitText(inspection['contractor_name']),
    );
    _workDescriptionController = TextEditingController(
      text: permitText(inspection['work_description']),
    );
    _permitFindingsController = TextEditingController(
      text: permitText(inspection['permit_findings']),
    );
    _loadMasterData();
  }

  @override
  void dispose() {
    _dateController.dispose();
    _permitNumberController.dispose();
    _sectionEquipmentController.dispose();
    _jobPerformanceController.dispose();
    _authorizedCraftmanController.dispose();
    _authorizedFacilityController.dispose();
    _contractorNameController.dispose();
    _workDescriptionController.dispose();
    _permitFindingsController.dispose();
    super.dispose();
  }

  Future<void> _loadMasterData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final master =
          await context.read<ApiService>().getPermitMatrixMasterData();
      if (!mounted) return;
      final inspection = widget.inspection ?? const <String, dynamic>{};
      final currentUser = permitMap(context.read<AuthService>().user);
      setState(() {
        _inspectors = permitList(master['inspectors']);
        _permitTypes = permitList(master['permit_types']);
        _supervisionAreas = permitList(master['supervision_areas']);
        _mainAreas = permitList(master['main_areas']);
        _subAreas = permitList(master['sub_areas']);
        _inspectorId = permitInt(inspection['inspector_id']) ??
            _availableId(currentUser['id'], _inspectors);
        _permitTypeId = permitInt(inspection['permit_type_id']);
        _supervisionAreaId = permitInt(inspection['supervision_area_id']);
        _mainAreaId = permitInt(inspection['main_area_id']);
        _subAreaId = permitInt(inspection['sub_area_id']);
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

  int? _availableId(dynamic value, List<dynamic> rows) {
    final id = permitInt(value);
    if (id == null) return null;
    return rows.map(permitMap).any((row) => permitInt(row['id']) == id)
        ? id
        : null;
  }

  List<Map<String, dynamic>> get _visibleSubAreas {
    final rows = _subAreas.map(permitMap).toList(growable: false);
    final hasMappedRows = rows.any((row) => row['main_area_id'] != null);
    if (!hasMappedRows) return rows;
    return rows
        .where((row) =>
            row['main_area_id'] == null ||
            permitInt(row['main_area_id']) == _mainAreaId)
        .toList(growable: false);
  }

  void _selectMainArea(int? value) {
    setState(() {
      _mainAreaId = value;
      if (!_visibleSubAreas.any((row) => permitInt(row['id']) == _subAreaId)) {
        _subAreaId = null;
      }
    });
  }

  String? _requiredText(String? value) {
    return value == null || value.trim().isEmpty
        ? 'Field ini wajib diisi.'
        : null;
  }

  String? _requiredId(int? value) {
    return value == null ? 'Field ini wajib dipilih.' : null;
  }

  Future<void> _pickDate() async {
    final initial = DateTime.tryParse(_dateController.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      _dateController.text = selected.toIso8601String().split('T').first;
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isSaving = true);
    final payload = <String, dynamic>{
      'permit_date': _dateController.text,
      'inspector_id': _inspectorId,
      'permit_number': _permitNumberController.text.trim(),
      'permit_type_id': _permitTypeId,
      'supervision_area_id': _supervisionAreaId,
      'main_area_id': _mainAreaId,
      'sub_area_id': _subAreaId,
      'section_equipment': _sectionEquipmentController.text.trim(),
      'job_performance': _jobPerformanceController.text.trim(),
      'authorized_craftman': _authorizedCraftmanController.text.trim(),
      'authorized_facility': _authorizedFacilityController.text.trim(),
      'contractor_name': _contractorNameController.text.trim(),
      'work_description': _workDescriptionController.text.trim(),
      'permit_findings': _permitFindingsController.text.trim().isEmpty
          ? null
          : _permitFindingsController.text.trim(),
    };

    try {
      final api = context.read<ApiService>();
      final response = _isEdit
          ? await api.updatePermitMatrix(
              permitInt(widget.inspection!['id'])!,
              payload,
            )
          : await api.createPermitMatrix(payload);
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
          content: Text('Data gagal disimpan: $error'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Permit Matrix?'),
        content: Text(
          'No. Permit ${_permitNumberController.text}. Tindakan ini tidak dapat dibatalkan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    final id = permitInt(widget.inspection?['id']);
    if (id == null) return;
    final response = await context.read<ApiService>().deletePermitMatrix(id);
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
    return Scaffold(
      appBar: AppBar(
        title: Text(_isEdit ? 'Edit Permit Matrix' : 'Tambah Permit Matrix'),
      ),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _FormError(message: _error!, onRetry: _loadMasterData)
                : Form(
                    key: _formKey,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Field bertanda * wajib diisi.',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            key: const ValueKey('permit_date'),
                            controller: _dateController,
                            readOnly: true,
                            decoration: const InputDecoration(
                              labelText: 'Tanggal Permit *',
                              prefixIcon: Icon(Icons.calendar_today_outlined),
                              border: OutlineInputBorder(),
                            ),
                            validator: _requiredText,
                            onTap: _pickDate,
                          ),
                          const SizedBox(height: 12),
                          _DropdownField(
                            key: const ValueKey('inspector_id'),
                            label: 'Nama Inspector *',
                            icon: Icons.person_outline,
                            value: _inspectorId,
                            rows: _inspectors,
                            itemLabel: (row) => permitText(row['name']),
                            onChanged: (value) =>
                                setState(() => _inspectorId = value),
                            validator: _requiredId,
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            key: const ValueKey('permit_number'),
                            controller: _permitNumberController,
                            maxLength: 255,
                            decoration: const InputDecoration(
                              labelText: 'No. Permit *',
                              prefixIcon:
                                  Icon(Icons.confirmation_number_outlined),
                              border: OutlineInputBorder(),
                              counterText: '',
                            ),
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _DropdownField(
                            key: const ValueKey('permit_type_id'),
                            label: 'Type Permit *',
                            icon: Icons.assignment_outlined,
                            value: _permitTypeId,
                            rows: _permitTypes,
                            itemLabel: (row) => permitText(row['name']),
                            onChanged: (value) =>
                                setState(() => _permitTypeId = value),
                            validator: _requiredId,
                          ),
                          const SizedBox(height: 12),
                          _DropdownField(
                            key: const ValueKey('supervision_area_id'),
                            label: 'Area Pengawasan *',
                            icon: Icons.visibility_outlined,
                            value: _supervisionAreaId,
                            rows: _supervisionAreas,
                            itemLabel: _supervisionLabel,
                            onChanged: (value) =>
                                setState(() => _supervisionAreaId = value),
                            validator: _requiredId,
                          ),
                          const SizedBox(height: 12),
                          _DropdownField(
                            key: const ValueKey('main_area_id'),
                            label: 'Main Area *',
                            icon: Icons.map_outlined,
                            value: _mainAreaId,
                            rows: _mainAreas,
                            itemLabel: (row) => permitText(row['name']),
                            onChanged: _selectMainArea,
                            validator: _requiredId,
                          ),
                          const SizedBox(height: 12),
                          _DropdownField(
                            key: ValueKey(
                                'sub_area_id-${_mainAreaId ?? 'none'}'),
                            label: 'Sub Area *',
                            icon: Icons.place_outlined,
                            value: _subAreaId,
                            rows: _visibleSubAreas,
                            itemLabel: (row) => permitText(row['name']),
                            onChanged: (value) =>
                                setState(() => _subAreaId = value),
                            validator: _requiredId,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('section_equipment'),
                            controller: _sectionEquipmentController,
                            label: 'Section / Equipment *',
                            icon: Icons.precision_manufacturing_outlined,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('job_performance'),
                            controller: _jobPerformanceController,
                            label: 'Job Performance *',
                            icon: Icons.engineering_outlined,
                            minLines: 3,
                            maxLines: 5,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('authorized_craftman'),
                            controller: _authorizedCraftmanController,
                            label: 'Authorized Craftman *',
                            icon: Icons.badge_outlined,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('authorized_facility'),
                            controller: _authorizedFacilityController,
                            label: 'Authorized Facility *',
                            icon: Icons.domain_verification_outlined,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('contractor_name'),
                            controller: _contractorNameController,
                            label: 'Nama Kontraktor *',
                            icon: Icons.business_outlined,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('work_description'),
                            controller: _workDescriptionController,
                            label: 'Uraian Pekerjaan *',
                            icon: Icons.description_outlined,
                            minLines: 3,
                            maxLines: 5,
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 12),
                          _TextField(
                            key: const ValueKey('permit_findings'),
                            controller: _permitFindingsController,
                            label: 'Temuan Terkait Safe Work Permit',
                            icon: Icons.report_problem_outlined,
                            minLines: 3,
                            maxLines: 5,
                          ),
                          const SizedBox(height: 24),
                          Row(
                            children: [
                              if (_isEdit) ...[
                                Expanded(
                                  child: OutlinedButton.icon(
                                    key: const ValueKey('permit_delete'),
                                    onPressed: _isSaving ? null : _delete,
                                    icon: const Icon(Icons.delete_outline),
                                    label: const Text('Hapus'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                              ],
                              Expanded(
                                child: FilledButton.icon(
                                  key: const ValueKey('permit_save'),
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
                                  label: Text(
                                    _isSaving ? 'Menyimpan...' : 'Simpan',
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
      ),
    );
  }

  String _supervisionLabel(Map<String, dynamic> row) {
    final code = permitText(row['code']);
    final name = permitText(row['name']);
    if (name.isEmpty || name == code) return code;
    return '$code - $name';
  }
}

class _DropdownField extends StatelessWidget {
  const _DropdownField({
    super.key,
    required this.label,
    required this.icon,
    required this.value,
    required this.rows,
    required this.itemLabel,
    required this.onChanged,
    required this.validator,
  });

  final String label;
  final IconData icon;
  final int? value;
  final List<dynamic> rows;
  final String Function(Map<String, dynamic>) itemLabel;
  final ValueChanged<int?> onChanged;
  final FormFieldValidator<int> validator;

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<int>(
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        border: const OutlineInputBorder(),
      ),
      hint: Text('Pilih ${label.replaceAll(' *', '')}'),
      items: rows.map(permitMap).map((row) {
        return DropdownMenuItem<int>(
          value: permitInt(row['id']),
          child: Text(itemLabel(row), overflow: TextOverflow.ellipsis),
        );
      }).toList(),
      onChanged: onChanged,
      validator: validator,
    );
  }
}

class _TextField extends StatelessWidget {
  const _TextField({
    super.key,
    required this.controller,
    required this.label,
    required this.icon,
    this.minLines = 1,
    this.maxLines = 1,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final int minLines;
  final int maxLines;
  final FormFieldValidator<String>? validator;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      minLines: minLines,
      maxLines: maxLines,
      maxLength: maxLines == 1 ? 255 : 65535,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        border: const OutlineInputBorder(),
        counterText: '',
        alignLabelWithHint: maxLines > 1,
      ),
      validator: validator,
    );
  }
}

class _FormError extends StatelessWidget {
  const _FormError({required this.message, required this.onRetry});

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
            const Icon(Icons.lock_outline, color: Colors.red, size: 48),
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
