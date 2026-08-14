import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';

class MasterDataScreen extends StatefulWidget {
  const MasterDataScreen({
    super.key,
    required this.title,
    required this.icon,
    required this.loader,
    required this.creater,
    required this.updater,
    required this.deleter,
    required this.fields,
    required this.idKey,
  });

  final String title;
  final IconData icon;
  final Future<List<dynamic>> Function(ApiService api) loader;
  final Future<Map<String, dynamic>> Function(ApiService api, Map<String, dynamic> data)
      creater;
  final Future<Map<String, dynamic>> Function(
      ApiService api, int id, Map<String, dynamic> data) updater;
  final Future<Map<String, dynamic>> Function(ApiService api, int id) deleter;
  final List<FieldConfig> fields;
  final String idKey;

  @override
  State<MasterDataScreen> createState() => _MasterDataScreenState();
}

class FieldConfig {
  const FieldConfig({
    required this.key,
    required this.label,
    this.required = true,
    this.numeric = false,
    this.multiline = false,
    this.dropdownItems,
  });

  final String key;
  final String label;
  final bool required;
  final bool numeric;
  final bool multiline;
  final List<String>? dropdownItems;
}

class _MasterDataScreenState extends State<MasterDataScreen> {
  List<dynamic> _rows = [];
  bool _isLoading = true;
  String? _error;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final rows = await widget.loader(context.read<ApiService>());
      if (!mounted) return;
      setState(() {
        _rows = rows;
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

  List<Map<String, dynamic>> get _filteredRows {
    final query = _search.trim().toLowerCase();
    return _rows.map(_asMap).where((row) {
      if (query.isEmpty) return true;
      return widget.fields.any((field) {
        final value = row[field.key]?.toString().toLowerCase() ?? '';
        return value.contains(query);
      });
    }).toList();
  }

  Future<void> _openForm([Map<String, dynamic>? existing]) async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => _MasterDataFormSheet(
        title: existing == null ? 'Add ${widget.title}' : 'Edit ${widget.title}',
        fields: widget.fields,
        initialValues: existing ?? <String, dynamic>{},
      ),
    );
    if (result == null || !mounted) return;

    final api = context.read<ApiService>();
    final isEdit = existing != null;
    final response = isEdit
        ? await widget.updater(
            api,
            _intValue(existing[widget.idKey])!,
            result,
          )
        : await widget.creater(api, result);

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
      SnackBar(
        content: Text('${widget.title} berhasil disimpan.'),
        backgroundColor: Colors.green,
      ),
    );
    await _load();
  }

  Future<void> _confirmDelete(Map<String, dynamic> row) async {
    final id = _intValue(row[widget.idKey]);
    if (id == null) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete?'),
        content: Text('Hapus ${widget.title} ini?'),
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

    final response = await widget.deleter(context.read<ApiService>(), id);
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
      SnackBar(
        content: Text('${widget.title} berhasil dihapus.'),
        backgroundColor: Colors.green,
      ),
    );
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final rows = _filteredRows;
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(),
        icon: const Icon(Icons.add),
        label: const Text('Add'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _ErrorState(message: _error!, onRetry: _load)
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: TextField(
                        onChanged: (value) => setState(() => _search = value),
                        decoration: InputDecoration(
                          hintText: 'Cari ${widget.title.toLowerCase()}...',
                          prefixIcon: const Icon(Icons.search),
                          border: const OutlineInputBorder(),
                          isDense: true,
                        ),
                      ),
                    ),
                    Expanded(
                      child: rows.isEmpty
                          ? const Center(child: Text('No data found'))
                          : ListView.separated(
                              padding: const EdgeInsets.fromLTRB(12, 0, 12, 80),
                              itemCount: rows.length,
                              separatorBuilder: (_, __) =>
                                  const SizedBox(height: 8),
                              itemBuilder: (context, index) {
                                final row = rows[index];
                                return Card(
                                  child: ListTile(
                                    leading: Icon(
                                      widget.icon,
                                      color: Theme.of(context)
                                          .colorScheme
                                          .primary,
                                    ),
                                    title: Text(_rowTitle(row)),
                                    subtitle: _rowSubtitle(row) == null
                                        ? null
                                        : Text(_rowSubtitle(row)!),
                                    trailing: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        IconButton(
                                          icon: const Icon(Icons.edit_outlined),
                                          tooltip: 'Edit',
                                          onPressed: () => _openForm(row),
                                        ),
                                        IconButton(
                                          icon: const Icon(
                                            Icons.delete_outline,
                                            color: Colors.red,
                                          ),
                                          tooltip: 'Delete',
                                          onPressed: () => _confirmDelete(row),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                    ),
                  ],
                ),
    );
  }

  String _rowTitle(Map<String, dynamic> row) {
    for (final field in widget.fields) {
      final value = row[field.key]?.toString().trim() ?? '';
      if (value.isNotEmpty) return value;
    }
    return (row[widget.idKey] ?? '-').toString();
  }

  String? _rowSubtitle(Map<String, dynamic> row) {
    final parts = widget.fields
        .skip(1)
        .map((field) => row[field.key]?.toString().trim())
        .where((value) => value != null && value.isNotEmpty)
        .toList();
    if (parts.isEmpty) return null;
    return parts.join(' • ');
  }
}

class _MasterDataFormSheet extends StatefulWidget {
  const _MasterDataFormSheet({
    required this.title,
    required this.fields,
    required this.initialValues,
  });

  final String title;
  final List<FieldConfig> fields;
  final Map<String, dynamic> initialValues;

  @override
  State<_MasterDataFormSheet> createState() => _MasterDataFormSheetState();
}

class _MasterDataFormSheetState extends State<_MasterDataFormSheet> {
  late final Map<String, TextEditingController> _controllers;
  late final Map<String, String> _dropdowns;

  @override
  void initState() {
    super.initState();
    _controllers = {
      for (final field in widget.fields)
        field.key: TextEditingController(
          text: widget.initialValues[field.key]?.toString() ?? '',
        ),
    };
    _dropdowns = {
      for (final field in widget.fields)
        if (field.dropdownItems != null)
          field.key: widget.initialValues[field.key]?.toString() ?? '',
    };
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  void _submit() {
    for (final field in widget.fields) {
      final value = _controllers[field.key]!.text.trim();
      if (field.required && value.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${field.label} wajib diisi.'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
    }

    final data = <String, dynamic>{
      for (final field in widget.fields)
        if (field.dropdownItems != null)
          field.key: _dropdowns[field.key]
        else if (field.numeric)
          field.key: _numOrNull(_controllers[field.key]!.text)
        else
          field.key: _nullIfEmpty(_controllers[field.key]!.text),
    };
    Navigator.pop(context, data);
  }

  dynamic _numOrNull(String value) {
    final text = value.trim();
    if (text.isEmpty) return null;
    return num.tryParse(text);
  }

  String? _nullIfEmpty(String value) {
    final text = value.trim();
    return text.isEmpty ? null : text;
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.title,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),
            for (final field in widget.fields) ...[
              if (field.dropdownItems != null)
                DropdownButtonFormField<String>(
                  initialValue: _dropdowns[field.key]!.isEmpty
                      ? null
                      : _dropdowns[field.key],
                  decoration: InputDecoration(
                    labelText: field.label,
                    border: const OutlineInputBorder(),
                  ),
                  items: field.dropdownItems!
                      .map((value) => DropdownMenuItem(
                            value: value,
                            child: Text(value),
                          ))
                      .toList(),
                  onChanged: (value) =>
                      setState(() => _dropdowns[field.key] = value ?? ''),
                )
              else
                TextField(
                  controller: _controllers[field.key],
                  keyboardType:
                      field.numeric ? TextInputType.number : TextInputType.text,
                  minLines: field.multiline ? 2 : 1,
                  maxLines: field.multiline ? 4 : 1,
                  decoration: InputDecoration(
                    labelText: field.label,
                    border: const OutlineInputBorder(),
                  ),
                ),
              const SizedBox(height: 12),
            ],
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _submit,
                icon: const Icon(Icons.save_outlined),
                label: const Text('Save'),
              ),
            ),
          ],
        ),
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

Map<String, dynamic> _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

int? _intValue(dynamic value) {
  if (value is int) return value;
  return value == null ? null : int.tryParse(value.toString());
}
