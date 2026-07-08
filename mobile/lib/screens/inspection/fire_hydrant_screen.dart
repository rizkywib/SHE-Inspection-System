import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';

class FireHydrantScreen extends StatefulWidget {
  const FireHydrantScreen({super.key});

  @override
  State<FireHydrantScreen> createState() => _FireHydrantScreenState();
}

class _FireHydrantScreenState extends State<FireHydrantScreen> {
  List<dynamic> _inspections = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadInspections();
  }

  Future<void> _loadInspections() async {
    try {
      final api = context.read<ApiService>();
      final data = await api.getFireHydrants();
      setState(() {
        _inspections = data;
        _isLoading = false;
        _error = null;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = e.toString();
      });
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
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.error_outline,
                            color: Colors.red, size: 48),
                        const SizedBox(height: 12),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () {
                            setState(() => _isLoading = true);
                            _loadInspections();
                          },
                          icon: const Icon(Icons.refresh),
                          label: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : _inspections.isEmpty
                  ? const Center(child: Text('No inspections found'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _inspections.length,
                  itemBuilder: (context, index) {
                    final item =
                        Map<String, dynamic>.from(_inspections[index] as Map);
                    return Card(
                      child: ListTile(
                        title:
                            Text((item['reference_no'] ?? 'No Reference').toString()),
                        subtitle: Text(_subtitle(item)),
                        trailing: Chip(
                          label: Text(
                            item['status'] ?? '',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                        onTap: () => _showInspectionForm(item: item),
                        onLongPress: () => _confirmDelete(item),
                      ),
                    );
                  },
                ),
    );
  }

  String _subtitle(dynamic item) {
    final location = item['location'];
    final locationName = location is Map
        ? (location['name'] ??
            location['location_name'] ??
            location['description'] ??
            '')
        : '';
    final date = (item['inspection_date'] ?? '').toString().split('T').first;
    final notes = item['notes'] ?? '';
    return [
      if (date.isNotEmpty) date,
      if (locationName.toString().isNotEmpty) locationName,
      if (notes.toString().isNotEmpty) notes,
    ].join(' - ');
  }

  Future<void> _showInspectionForm({Map<String, dynamic>? item}) async {
    final isEdit = item != null;
    final dateController = TextEditingController(
      text: _dateOnly(item?['inspection_date']) ?? _today(),
    );
    final notesController = TextEditingController(text: item?['notes'] ?? '');
    var status = (item?['status'] ?? 'new').toString();
    if (!['new', 'completed', 'signed'].contains(status)) {
      status = 'new';
    }

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
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
                      isEdit ? 'Edit Fire Hydrant' : 'Create Fire Hydrant',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
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
                    DropdownButtonFormField<String>(
                      value: status,
                      decoration: const InputDecoration(
                        labelText: 'Status',
                        prefixIcon: Icon(Icons.flag_outlined),
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'new', child: Text('New')),
                        DropdownMenuItem(
                            value: 'completed', child: Text('Completed')),
                        DropdownMenuItem(value: 'signed', child: Text('Signed')),
                      ],
                      onChanged: (value) {
                        if (value != null) {
                          setSheetState(() => status = value);
                        }
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
                              final payload = {
                                'inspection_date': dateController.text,
                                'status': status,
                                'notes': notesController.text.trim(),
                              };
                              await _saveInspection(item, payload);
                              if (mounted) Navigator.pop(context);
                            },
                            icon: const Icon(Icons.save),
                            label: const Text('Save'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    dateController.dispose();
    notesController.dispose();
  }

  Future<void> _saveInspection(
      Map<String, dynamic>? item, Map<String, dynamic> payload) async {
    final api = context.read<ApiService>();
    final response = item == null
        ? await api.createFireHydrant(payload)
        : await api.updateFireHydrant(item['id'], payload);

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
      const SnackBar(content: Text('Fire Hydrant saved')),
    );
    setState(() => _isLoading = true);
    await _loadInspections();
  }

  Future<void> _confirmDelete(Map<String, dynamic> item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Delete Fire Hydrant?'),
          content: Text(item['reference_no'] ?? 'Delete this inspection?'),
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
    final response = await api.deleteFireHydrant(item['id']);
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

  String? _dateOnly(dynamic value) {
    if (value == null) return null;
    final text = value.toString();
    if (text.isEmpty) return null;
    return text.split('T').first.split(' ').first;
  }
}
