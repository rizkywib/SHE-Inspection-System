import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../services/connectivity_service.dart';
import '../../services/offline_storage_service.dart';
import '../../widgets/save_status_badge.dart';

class IncidentListScreen extends StatefulWidget {
  const IncidentListScreen({super.key});

  @override
  State<IncidentListScreen> createState() => _IncidentListScreenState();
}

class _IncidentListScreenState extends State<IncidentListScreen> {
  List<Map<String, dynamic>> _inspections = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadInspections();
  }

  Future<void> _loadInspections() async {
    setState(() {
      _isLoading = _inspections.isEmpty;
      _error = null;
    });

    try {
      final api = context.read<ApiService>();
      final connectivity = context.read<ConnectivityService>();
      List<dynamic> rows;
      if (connectivity.isOnline) {
        rows = await api.getIncidents();
        unawaited(
          OfflineStorageService.instance.saveCache('incidents', rows),
        );
      } else {
        rows = await OfflineStorageService.instance.readCache('incidents') ?? [];
      }
      rows = [...rows, ...await _incidentDraftRows()];
      if (!mounted) return;
      setState(() {
        _inspections = rows.map(_mapFrom).toList();
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = _errorMessage(error);
      });
    }
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

  Future<void> _createInspection() async {
    final created = await Navigator.pushNamed(context, '/incident-form');
    if (created == true && mounted) {
      await _loadInspections();
    }
  }

  Future<List<Map<String, dynamic>>> _incidentDraftRows() async {
    final drafts = await OfflineStorageService.instance.getPendingDrafts();
    return drafts
        .where(
            (d) => d['endpoint']?.toString().startsWith('/incidents') == true)
        .map((d) => <String, dynamic>{
              'id': 'draft-${d['id']}',
              'is_draft': true,
              'incident_type': {'name': 'Draft'},
              'incident_date': _dateOnly(d['created_at']?.toString()),
              'incident_time': '',
              'location_text': d['display_name']?.toString() ?? 'Draft',
              'description': 'Menunggu sinkronisasi',
            })
        .toList();
  }

  Future<void> _editInspection(Map<String, dynamic> inspection) async {
    final edited = await Navigator.pushNamed(
      context,
      '/incident-form',
      arguments: inspection,
    );
    if (edited == true && mounted) {
      await _loadInspections();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inspection')),
      floatingActionButton: FloatingActionButton(
        onPressed: _createInspection,
        tooltip: 'Inspection Baru',
        child: const Icon(Icons.add),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _ErrorState(message: _error!, onRetry: _loadInspections)
              : RefreshIndicator(
                  onRefresh: _loadInspections,
                  child: _inspections.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(24),
                          children: const [
                            SizedBox(height: 120),
                            Icon(
                              Icons.assignment_outlined,
                              size: 56,
                              color: Colors.black38,
                            ),
                            SizedBox(height: 12),
                            Center(
                              child: Text(
                                'Belum ada data inspection',
                                style: TextStyle(fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.only(bottom: 88),
                          itemCount: _inspections.length,
                          separatorBuilder: (_, __) => const Divider(height: 1),
                          itemBuilder: (context, index) {
                            final inspection = _inspections[index];
                            return _InspectionListItem(
                              inspection: inspection,
                              pendingSync: inspection['is_draft'] == true,
                              onTap: () => _showDetail(inspection),
                            );
                          },
                        ),
                ),
    );
  }

  Future<void> _showDetail(Map<String, dynamic> inspection) async {
    if (inspection['is_draft'] == true) {
      await _showDraftDialog();
      return;
    }
    var detail = inspection;
    final id = _intValue(inspection['id']);

    if (id != null) {
      try {
        detail = await context
            .read<ApiService>()
            .getInspectionDetail('incidents', id);
      } catch (_) {
        detail = inspection;
      }
    }

    if (!mounted) return;
    final currentUser = _mapFrom(context.read<AuthService>().user);
    final canEdit = _canEdit(detail, currentUser);
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (context) {
        final images = _listFrom(detail['images']);
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.72,
          minChildSize: 0.4,
          maxChildSize: 0.94,
          builder: (context, controller) {
            return ListView(
              controller: controller,
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
              children: [
                Text(
                  'Detail Inspection',
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 16),
                _DetailRow(
                  label: 'Referensi',
                  value: detail['reference_no'],
                ),
                _DetailRow(
                  label: 'Tanggal',
                  value: _dateOnly(detail['incident_date']),
                ),
                _DetailRow(
                  label: 'Jam',
                  value: _timeOnly(detail['incident_time']),
                ),
                _DetailRow(
                  label: 'Lokasi',
                  value: detail['location_text'],
                ),
                _DetailRow(
                  label: 'Inspection Type',
                  value: _relationName(
                    detail['incident_type'],
                    detail['incident_type_id'],
                  ),
                ),
                _DetailRow(
                  label: 'Inspector',
                  value: _relationName(
                    detail['reporter'],
                    detail['reporter_id'],
                  ),
                ),
                _DetailRow(
                  label: 'Status',
                  value: _displayStatus(detail['status']),
                ),
                _DetailRow(
                  label: 'Keterangan',
                  value: detail['description'],
                ),
                if (images.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  const Text(
                    'Gambar',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: images.map((value) {
                      final image = _mapFrom(value);
                      return _InspectionImage(path: image['image_path']);
                    }).toList(),
                  ),
                ],
                if (canEdit) ...[
                  const SizedBox(height: 20),
                  FilledButton.icon(
                    onPressed: () {
                      Navigator.pop(context);
                      _editInspection(detail);
                    },
                    icon: const Icon(Icons.edit_outlined),
                    label: const Text('Edit Inspection'),
                  ),
                ],
              ],
            );
          },
        );
      },
    );
  }

  bool _canEdit(
    Map<String, dynamic> inspection,
    Map<String, dynamic> currentUser,
  ) {
    final role = currentUser['role']?.toString();
    if (role == 'admin' || role == 'super_admin') return true;
    final reporterId = _intValue(inspection['reporter_id']);
    final userId = _intValue(currentUser['id']);
    return reporterId != null && userId != null && reporterId == userId;
  }
}

class _InspectionListItem extends StatelessWidget {
  const _InspectionListItem({
    required this.inspection,
    required this.pendingSync,
    required this.onTap,
  });

  final Map<String, dynamic> inspection;
  final bool pendingSync;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final type = _relationName(
      inspection['incident_type'],
      inspection['incident_type_id'],
    );
    final date = _dateOnly(inspection['incident_date']);
    final time = _timeOnly(inspection['incident_time']);

    return Material(
      color: Colors.white,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      type,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  _StatusBadge(status: inspection['status']?.toString() ?? ''),
                  const SizedBox(width: 8),
                  SaveStatusBadge(isPending: pendingSync),
                ],
              ),
              const SizedBox(height: 8),
              _IconText(
                icon: Icons.calendar_today_outlined,
                text: time == '-' ? date : '$date • $time',
              ),
              const SizedBox(height: 5),
              _IconText(
                icon: Icons.location_on_outlined,
                text: _textOrDash(inspection['location_text']),
              ),
              const SizedBox(height: 8),
              Text(
                _textOrDash(inspection['description']),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Color(0xFF475569),
                  height: 1.35,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _IconText extends StatelessWidget {
  const _IconText({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 16, color: const Color(0xFF64748B)),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Color(0xFF64748B),
              fontSize: 12,
            ),
          ),
        ),
      ],
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final isClosed = status == 'closed' || status == 'close';
    final color = isClosed ? const Color(0xFF475569) : const Color(0xFF15803D);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(99),
      ),
      child: Text(
        _displayStatus(status),
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
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
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 118,
            child: Text(
              label,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
            ),
          ),
          Expanded(child: Text(_textOrDash(value))),
        ],
      ),
    );
  }
}

class _InspectionImage extends StatelessWidget {
  const _InspectionImage({required this.path});

  final dynamic path;

  @override
  Widget build(BuildContext context) {
    final url = _mediaUrl(context, path);
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: 96,
        height: 96,
        color: const Color(0xFFE2E8F0),
        child: url == null
            ? const Icon(Icons.image_not_supported_outlined)
            : Image.network(
                url,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) =>
                    const Icon(Icons.broken_image_outlined),
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

Map<String, dynamic> _mapFrom(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

List<dynamic> _listFrom(dynamic value) {
  return value is List ? value : <dynamic>[];
}

int? _intValue(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '');
}

String _relationName(dynamic relation, dynamic fallbackId) {
  final data = _mapFrom(relation);
  final value =
      data['name'] ?? data['title'] ?? data['name_type'] ?? fallbackId;
  return _textOrDash(value);
}

String _dateOnly(dynamic value) {
  if (value == null) return '-';
  final text = value.toString();
  return text.length >= 10 ? text.substring(0, 10) : text;
}

String _timeOnly(dynamic value) {
  if (value == null) return '-';
  final text = value.toString();
  return text.length >= 5 ? text.substring(0, 5) : text;
}

String _displayStatus(dynamic value) {
  return switch (value?.toString().toLowerCase()) {
    'close' || 'closed' => 'Close',
    'open' || 'reported' => 'Open',
    final status when status != null && status.isNotEmpty => status,
    _ => '-',
  };
}

String _textOrDash(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
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

String _errorMessage(Object error) {
  final text = error.toString().replaceFirst('Exception: ', '').trim();
  return text.isEmpty ? 'Data inspection gagal dimuat.' : text;
}
