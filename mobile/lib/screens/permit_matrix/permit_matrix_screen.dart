import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import 'permit_matrix_common.dart';
import 'permit_matrix_form_screen.dart';

class PermitMatrixScreen extends StatefulWidget {
  const PermitMatrixScreen({super.key, this.initialId});

  final String? initialId;

  @override
  State<PermitMatrixScreen> createState() => _PermitMatrixScreenState();
}

class _PermitMatrixScreenState extends State<PermitMatrixScreen> {
  final TextEditingController _searchController = TextEditingController();
  List<dynamic> _rows = [];
  bool _isLoading = true;
  bool _hasShownInitialDetail = false;
  String? _error;

  List<Map<String, dynamic>> get _filteredRows {
    final query = _searchController.text.trim().toLowerCase();
    final rows = _rows.map(permitMap).toList(growable: false);
    if (query.isEmpty) return rows;
    return rows.where((row) {
      final values = [
        row['permit_number'],
        permitInspectorName(row),
        permitRelationName(row['permit_type']),
        permitRelationName(row['supervision_area']),
        permitRelationName(row['main_area']),
        permitRelationName(row['sub_area']),
        row['section_equipment'],
        row['contractor_name'],
        row['permit_findings'],
      ];
      return values
          .any((value) => permitText(value).toLowerCase().contains(query));
    }).toList(growable: false);
  }

  @override
  void initState() {
    super.initState();
    _loadData();
    if (widget.initialId != null) _showDirectDetail(widget.initialId!);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final rows = await context.read<ApiService>().getPermitMatrix();
      if (!mounted) return;
      setState(() {
        _rows = rows;
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

  Future<void> _showDirectDetail(String value) async {
    final id = int.tryParse(value);
    if (id == null) return;
    try {
      final detail =
          await context.read<ApiService>().getPermitMatrixInspection(id);
      if (!mounted || _hasShownInitialDetail) return;
      _hasShownInitialDetail = true;
      _openDetailSheet(detail);
    } catch (_) {
      // Daftar yang dimuat akan digunakan sebagai fallback.
    }
  }

  void _showInitialFromList() {
    if (_hasShownInitialDetail || widget.initialId == null) return;
    final match = _rows.map(permitMap).where(
          (row) => row['id']?.toString() == widget.initialId,
        );
    if (match.isEmpty) return;
    _hasShownInitialDetail = true;
    _showDetail(match.first);
  }

  Future<void> _retry() async {
    setState(() => _isLoading = true);
    await _loadData();
  }

  Future<Map<String, dynamic>> _fetchDetail(
    Map<String, dynamic> row,
  ) async {
    final id = permitInt(row['id']);
    if (id == null) return row;
    try {
      return await context.read<ApiService>().getPermitMatrixInspection(id);
    } catch (_) {
      return row;
    }
  }

  Future<void> _showDetail(Map<String, dynamic> row) async {
    final detail = await _fetchDetail(row);
    if (mounted) _openDetailSheet(detail);
  }

  void _openDetailSheet(Map<String, dynamic> detail) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
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
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Permit ${permitText(detail['permit_number'])}',
                        style: Theme.of(context)
                            .textTheme
                            .titleLarge
                            ?.copyWith(fontWeight: FontWeight.w700),
                      ),
                    ),
                    _FindingBadge(hasFinding: permitHasFinding(detail)),
                  ],
                ),
                const SizedBox(height: 16),
                _DetailSection(
                  title: 'Data Permit',
                  rows: [
                    ('Tanggal Permit', permitDate(detail['permit_date'])),
                    ('No. Permit', permitText(detail['permit_number'])),
                    ('Nama Inspector', permitInspectorName(detail)),
                    (
                      'Type Permit',
                      permitRelationName(detail['permit_type']),
                    ),
                    (
                      'Area Pengawasan',
                      _supervisionName(detail['supervision_area']),
                    ),
                    ('Main Area', permitRelationName(detail['main_area'])),
                    ('Sub Area', permitRelationName(detail['sub_area'])),
                    (
                      'Section / Equipment',
                      permitText(detail['section_equipment']),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                _DetailSection(
                  title: 'Pelaksanaan Pekerjaan',
                  rows: [
                    ('Job Performance', permitText(detail['job_performance'])),
                    (
                      'Authorized Craftman',
                      permitText(detail['authorized_craftman']),
                    ),
                    (
                      'Authorized Facility',
                      permitText(detail['authorized_facility']),
                    ),
                    ('Nama Kontraktor', permitText(detail['contractor_name'])),
                    (
                      'Uraian Pekerjaan',
                      permitText(detail['work_description']),
                    ),
                    (
                      'Temuan Terkait Safe Work Permit',
                      permitText(detail['permit_findings']).isEmpty
                          ? 'Tidak ada temuan.'
                          : permitText(detail['permit_findings']),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => Navigator.pop(sheetContext),
                        icon: const Icon(Icons.close),
                        label: const Text('Tutup'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () {
                          Navigator.pop(sheetContext);
                          _openForm(detail);
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

  Future<void> _openForm([Map<String, dynamic>? inspection]) async {
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (_) => PermitMatrixFormScreen(inspection: inspection),
      ),
    );
    if (result != null && mounted) {
      setState(() => _isLoading = true);
      await _loadData();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result == 'deleted'
                ? 'Data Permit Matrix berhasil dihapus.'
                : 'Data Permit Matrix berhasil disimpan.',
          ),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> row) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Permit Matrix?'),
        content: Text(
          'No. Permit ${permitText(row['permit_number'])}. Tindakan ini tidak dapat dibatalkan.',
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
    final id = permitInt(row['id']);
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
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Data Permit Matrix berhasil dihapus.'),
        backgroundColor: Colors.green,
      ),
    );
    await _loadData();
  }

  @override
  Widget build(BuildContext context) {
    final rows = _filteredRows;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Permit Matrix'),
        actions: [
          IconButton(
            onPressed: _retry,
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openForm,
        tooltip: 'Tambah Permit Matrix',
        child: const Icon(Icons.add),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _PermitError(message: _error!, onRetry: _retry)
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                      child: TextField(
                        key: const ValueKey('permit_search'),
                        controller: _searchController,
                        decoration: InputDecoration(
                          hintText: 'Cari No. Permit, inspector, kontraktor...',
                          prefixIcon: const Icon(Icons.search),
                          suffixIcon: _searchController.text.isEmpty
                              ? null
                              : IconButton(
                                  onPressed: () {
                                    _searchController.clear();
                                    setState(() {});
                                  },
                                  icon: const Icon(Icons.close),
                                ),
                          border: const OutlineInputBorder(),
                        ),
                        onChanged: (_) => setState(() {}),
                      ),
                    ),
                    Expanded(
                      child: rows.isEmpty
                          ? const Center(child: Text('Data tidak ditemukan'))
                          : RefreshIndicator(
                              onRefresh: _retry,
                              child: ListView.builder(
                                padding:
                                    const EdgeInsets.fromLTRB(16, 8, 16, 88),
                                itemCount: rows.length,
                                itemBuilder: (context, index) {
                                  final row = rows[index];
                                  return _PermitCard(
                                    row: row,
                                    onTap: () => _showDetail(row),
                                    onLongPress: () => _confirmDelete(row),
                                  );
                                },
                              ),
                            ),
                    ),
                  ],
                ),
    );
  }
}

class _PermitCard extends StatelessWidget {
  const _PermitCard({
    required this.row,
    required this.onTap,
    required this.onLongPress,
  });

  final Map<String, dynamic> row;
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
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'No. Permit ${permitText(row['permit_number'])}',
                      style: Theme.of(context)
                          .textTheme
                          .titleMedium
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  _FindingBadge(hasFinding: permitHasFinding(row)),
                ],
              ),
              const SizedBox(height: 12),
              _CardRow(
                icon: Icons.calendar_today_outlined,
                value: permitDate(row['permit_date']),
              ),
              _CardRow(
                icon: Icons.person_outline,
                value: permitInspectorName(row),
              ),
              _CardRow(
                icon: Icons.assignment_outlined,
                value: permitRelationName(row['permit_type']),
              ),
              _CardRow(
                icon: Icons.business_outlined,
                value: permitText(row['contractor_name']),
              ),
              const SizedBox(height: 6),
              Text(
                '${permitRelationName(row['main_area'])} • ${permitRelationName(row['sub_area'])}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.black54),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CardRow extends StatelessWidget {
  const _CardRow({required this.icon, required this.value});

  final IconData icon;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Icon(icon, size: 17, color: Colors.black54),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _FindingBadge extends StatelessWidget {
  const _FindingBadge({required this.hasFinding});

  final bool hasFinding;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: hasFinding ? Colors.red.shade50 : Colors.green.shade50,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        hasFinding ? 'Ada Temuan' : 'Tidak Ada Temuan',
        style: TextStyle(
          color: hasFinding ? Colors.red.shade800 : Colors.green.shade800,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  const _DetailSection({required this.title, required this.rows});

  final String title;
  final List<(String, String)> rows;

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
              title,
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const Divider(height: 24),
            for (final row in rows)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      row.$1,
                      style: const TextStyle(
                        color: Colors.black54,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      row.$2.isEmpty ? '-' : row.$2,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _PermitError extends StatelessWidget {
  const _PermitError({required this.message, required this.onRetry});

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

String _supervisionName(dynamic relation) {
  final row = permitMap(relation);
  final code = permitText(row['code']);
  final name = permitText(row['name']);
  if (name.isEmpty || name == code) return code;
  return '$code - $name';
}
