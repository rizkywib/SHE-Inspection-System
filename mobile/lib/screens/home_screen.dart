import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final TextEditingController _searchController = TextEditingController();
  List<_InspectionItem> _inspections = [];
  String _selectedStatus = 'all';
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadInspections();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadInspections() async {
    setState(() {
      _isLoading = _inspections.isEmpty;
      _error = null;
    });

    final api = context.read<ApiService>();
    final loaded = <_InspectionItem>[];
    final loaders = [
      _loadType(
        loader: api.getFireHydrants,
        type: 'Fire Hydrant',
        route: '/fire-hydrant',
        detailPath: 'fire-hydrants',
        color: const Color(0xFFDC2626),
        icon: Icons.water_damage_outlined,
      ),
      _loadType(
        loader: api.getFireExtinguishers,
        type: 'Fire Extinguisher',
        route: '/fire-extinguisher',
        detailPath: 'fire-extinguishers',
        color: const Color(0xFFEA580C),
        icon: Icons.fire_extinguisher,
      ),
      _loadType(
        loader: api.getFireAlarms,
        type: 'Fire Alarm',
        route: '/fire-alarm',
        detailPath: 'fire-alarms',
        color: const Color(0xFFD97706),
        icon: Icons.notifications_active_outlined,
      ),
      _loadType(
        loader: api.getEsEw,
        type: 'ES/EW',
        route: '/es-ew',
        detailPath: 'es-ew',
        color: const Color(0xFF059669),
        icon: Icons.shower_outlined,
      ),
      _loadType(
        loader: api.getIncidents,
        type: 'Inspection',
        route: '/incident-form',
        detailPath: 'incidents',
        color: const Color(0xFF7C3AED),
        icon: Icons.assignment_outlined,
      ),
    ];

    await Future.wait(loaders.map((future) async {
      final items = await future;
      loaded.addAll(items);
      loaded.sort((a, b) => b.dateText.compareTo(a.dateText));
      if (!mounted) return;
      setState(() {
        _inspections = List.of(loaded);
        _isLoading = false;
      });
    }));

    if (!mounted) return;
    if (loaded.isEmpty) {
      setState(() => _isLoading = false);
    }
  }

  Future<List<_InspectionItem>> _loadType({
    required Future<List<dynamic>> Function() loader,
    required String type,
    required String route,
    required String detailPath,
    required Color color,
    required IconData icon,
  }) async {
    try {
      final rows = await loader();
      return rows
          .map((row) => _InspectionItem.fromMap(
                _mapFrom(row),
                type: type,
                route: route,
                detailPath: detailPath,
                color: color,
                icon: icon,
              ))
          .toList();
    } catch (_) {
      return [];
    }
  }

  List<_InspectionItem> get _filteredInspections {
    final query = _searchController.text.trim().toLowerCase();
    return _inspections.where((item) {
      final matchesStatus =
          _selectedStatus == 'all' || item.status == _selectedStatus;
      if (!matchesStatus) return false;
      if (query.isEmpty) return true;
      return item.searchText.contains(query);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final user = auth.user;
    final filtered = _filteredInspections;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Inspections'),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner),
            tooltip: 'Scan QR',
            onPressed: () => Navigator.pushNamed(context, '/qr-scanner'),
          ),
          IconButton(
            icon: const Icon(Icons.person_outline),
            tooltip: 'Profile',
            onPressed: () => Navigator.pushNamed(context, '/profile'),
          ),
        ],
      ),
      drawer: _buildDrawer(context, user),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    height: 42,
                    child: TextField(
                      controller: _searchController,
                      onChanged: (_) => setState(() {}),
                      textAlignVertical: TextAlignVertical.center,
                      decoration: InputDecoration(
                        hintText: 'Search inspections',
                        prefixIcon: const Icon(Icons.search, size: 20),
                        suffixIcon: _searchController.text.isEmpty
                            ? null
                            : IconButton(
                                onPressed: () {
                                  _searchController.clear();
                                  setState(() {});
                                },
                                icon: const Icon(Icons.close, size: 18),
                                tooltip: 'Clear search',
                              ),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: EdgeInsets.zero,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide:
                              const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _StatusFilterChip(
                          label: 'All',
                          selected: _selectedStatus == 'all',
                          onTap: () => setState(() => _selectedStatus = 'all'),
                        ),
                        _StatusFilterChip(
                          label: 'New',
                          selected: _selectedStatus == 'new',
                          onTap: () => setState(() => _selectedStatus = 'new'),
                        ),
                        _StatusFilterChip(
                          label: 'Completed',
                          selected: _selectedStatus == 'completed',
                          onTap: () =>
                              setState(() => _selectedStatus = 'completed'),
                        ),
                        _StatusFilterChip(
                          label: 'Signed',
                          selected: _selectedStatus == 'signed',
                          onTap: () =>
                              setState(() => _selectedStatus = 'signed'),
                        ),
                        _StatusFilterChip(
                          label: 'Open',
                          selected: _selectedStatus == 'reported',
                          onTap: () =>
                              setState(() => _selectedStatus = 'reported'),
                        ),
                        _StatusFilterChip(
                          label: 'Close',
                          selected: _selectedStatus == 'closed',
                          onTap: () =>
                              setState(() => _selectedStatus = 'closed'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 6),
                  _CompactListSummary(
                    shown: filtered.length,
                    total: _inspections.length,
                    isLoading: _isLoading,
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? _ErrorState(message: _error!, onRetry: _loadInspections)
                      : RefreshIndicator(
                          onRefresh: _loadInspections,
                          child: filtered.isEmpty
                              ? ListView(
                                  padding: const EdgeInsets.all(24),
                                  children: const [
                                    SizedBox(height: 96),
                                    Icon(Icons.assignment_outlined,
                                        size: 56, color: Colors.black38),
                                    SizedBox(height: 12),
                                    Center(
                                      child: Text(
                                        'No inspections found',
                                        style: TextStyle(
                                            fontWeight: FontWeight.w600),
                                      ),
                                    ),
                                  ],
                                )
                              : ListView.separated(
                                  padding: EdgeInsets.zero,
                                  itemCount: filtered.length,
                                  separatorBuilder: (_, __) => const Divider(
                                    height: 1,
                                    indent: 56,
                                  ),
                                  itemBuilder: (context, index) {
                                    final item = filtered[index];
                                    return _InspectionListTile(
                                      item: item,
                                      onTap: () => _showInspectionDetail(item),
                                    );
                                  },
                                ),
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showInspectionDetail(_InspectionItem item) async {
    Map<String, dynamic> detail = item.rawData;
    final id = int.tryParse(item.id);

    if (id != null) {
      try {
        detail = await context
            .read<ApiService>()
            .getInspectionDetail(item.detailPath, id);
      } catch (_) {
        detail = item.rawData;
      }
    }

    if (!mounted) return;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final items = _listFrom(detail['items']);
        final images = _listFrom(detail['images']);
        final isGeneralInspection = item.detailPath == 'incidents';
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.82,
          minChildSize: 0.42,
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
                        color: item.color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(item.icon, color: item.color),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.reference,
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 2),
                          Text(item.type, style: TextStyle(color: item.color)),
                        ],
                      ),
                    ),
                    _StatusBadge(status: item.status),
                  ],
                ),
                const SizedBox(height: 18),
                _DetailSection(
                  title: 'Inspection Detail',
                  children: [
                    _DetailRow(label: 'Reference', value: item.reference),
                    _DetailRow(label: 'Type', value: item.type),
                    _DetailRow(
                        label: 'Date',
                        value: _dateOnly(detail['inspection_date'] ??
                            detail['incident_date'])),
                    if (isGeneralInspection &&
                        _hasValue(detail['incident_time']))
                      _DetailRow(
                          label: 'Time',
                          value: _timeOnly(detail['incident_time'])),
                    _DetailRow(
                        label: 'Status',
                        value: _displayStatus(detail['status'])),
                    if (isGeneralInspection &&
                        _hasValue(detail['location_text']))
                      _DetailRow(
                          label: 'Location', value: detail['location_text']),
                    if (isGeneralInspection &&
                        _hasValue(_relationName(detail['incident_type'],
                            detail['incident_type_id'])))
                      _DetailRow(
                          label: 'Inspection Type',
                          value: _relationName(detail['incident_type'],
                              detail['incident_type_id'])),
                    if (isGeneralInspection &&
                        _hasValue(_relationName(
                            detail['reporter'], detail['reporter_id'])))
                      _DetailRow(
                          label: 'Inspector',
                          value: _relationName(
                              detail['reporter'], detail['reporter_id'])),
                    if (isGeneralInspection && _hasValue(detail['description']))
                      _DetailRow(
                          label: 'Description', value: detail['description']),
                    if (_hasValue(_relationName(
                            detail['location'], detail['location_id'])) &&
                        !isGeneralInspection)
                      _DetailRow(
                          label: 'Location',
                          value: _relationName(
                              detail['location'], detail['location_id'])),
                    if (_hasValue(
                        _relationName(detail['area'], detail['area_id'])))
                      _DetailRow(
                          label: 'Area',
                          value:
                              _relationName(detail['area'], detail['area_id'])),
                    if (_hasValue(_relationName(
                        detail['inspector'], detail['inspector_id'])))
                      _DetailRow(
                          label: 'Inspector',
                          value: _relationName(
                              detail['inspector'], detail['inspector_id'])),
                    if (_hasValue(detail['assigned_to']))
                      _DetailRow(
                          label: 'Assigned To', value: detail['assigned_to']),
                    if (_hasValue(_dateTime(detail['checked_in_at'])))
                      _DetailRow(
                          label: 'Checked In At',
                          value: _dateTime(detail['checked_in_at'])),
                    if (_hasValue(_dateTime(detail['signed_at'])))
                      _DetailRow(
                          label: 'Signed At',
                          value: _dateTime(detail['signed_at'])),
                    if (_hasValue(detail['checkin_lat']))
                      _DetailRow(
                          label: 'Check-in Lat', value: detail['checkin_lat']),
                    if (_hasValue(detail['checkin_lng']))
                      _DetailRow(
                          label: 'Check-in Lng', value: detail['checkin_lng']),
                    if (_hasValue(detail['notes']))
                      _DetailRow(label: 'Notes', value: detail['notes']),
                  ],
                ),
                if (isGeneralInspection && images.isNotEmpty) ...[
                  const SizedBox(height: 14),
                  Text(
                    'Image',
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: images
                        .map((image) => _SmallPhotoPreview(
                              label: 'Inspection',
                              path: _mapFrom(image)['image_path'],
                            ))
                        .toList(),
                  ),
                ],
                if (!isGeneralInspection) ...[
                  const SizedBox(height: 14),
                  Text(
                    'Items',
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  if (items.isEmpty)
                    const Text('No items')
                  else
                    ...items.asMap().entries.map((entry) {
                      return _InspectionDetailItem(
                        index: entry.key + 1,
                        item: _mapFrom(entry.value),
                      );
                    }),
                ],
                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                  label: const Text('Close'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Widget _buildDrawer(BuildContext context, dynamic user) {
    return Drawer(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          DrawerHeader(
            decoration: const BoxDecoration(color: Color(0xFF0F172A)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const CircleAvatar(
                  radius: 28,
                  child: Icon(Icons.person_outline, size: 28),
                ),
                const SizedBox(height: 12),
                Text(
                  user?['name'] ?? 'User',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  user?['email'] ?? '',
                  style: const TextStyle(color: Colors.white70, fontSize: 14),
                ),
              ],
            ),
          ),
          ListTile(
            leading: const Icon(Icons.dashboard_outlined),
            title: const Text('Dashboard'),
            selected: true,
            onTap: () => Navigator.pop(context),
          ),
          ListTile(
            leading: const Icon(Icons.qr_code_scanner),
            title: const Text('Scan QR'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/qr-scanner');
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.water_damage_outlined),
            title: const Text('Fire Hydrant'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-hydrant');
            },
          ),
          ListTile(
            leading: const Icon(Icons.fire_extinguisher),
            title: const Text('Fire Extinguisher'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-extinguisher');
            },
          ),
          ListTile(
            leading: const Icon(Icons.notifications_active_outlined),
            title: const Text('Fire Alarm'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-alarm');
            },
          ),
          ListTile(
            leading: const Icon(Icons.shower_outlined),
            title: const Text('ES/EW'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/es-ew');
            },
          ),
          ListTile(
            leading: const Icon(Icons.checklist_outlined),
            title: const Text('Checklist'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/checklist');
            },
          ),
          ListTile(
            leading: const Icon(Icons.assignment_outlined),
            title: const Text('Inspection'),
            onTap: () async {
              Navigator.pop(context);
              final created =
                  await Navigator.pushNamed(context, '/incident-form');
              if (created == true && mounted) {
                await _loadInspections();
              }
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: const Text('Logout', style: TextStyle(color: Colors.red)),
            onTap: () async {
              final auth = context.read<AuthService>();
              await auth.logout();
              if (context.mounted) {
                Navigator.pushReplacementNamed(context, '/login');
              }
            },
          ),
        ],
      ),
    );
  }
}

class _CompactListSummary extends StatelessWidget {
  const _CompactListSummary({
    required this.shown,
    required this.total,
    required this.isLoading,
  });

  final int shown;
  final int total;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          '$shown of $total inspections',
          style: const TextStyle(fontSize: 12, color: Colors.black54),
        ),
        if (isLoading) ...[
          const SizedBox(width: 8),
          const SizedBox(
            width: 12,
            height: 12,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: 4),
          const Text(
            'Loading',
            style: TextStyle(fontSize: 12, color: Colors.black54),
          ),
        ],
      ],
    );
  }
}

class _StatusFilterChip extends StatelessWidget {
  const _StatusFilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        visualDensity: VisualDensity.compact,
        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
        labelStyle: const TextStyle(fontSize: 12),
        onSelected: (_) => onTap(),
      ),
    );
  }
}

class _InspectionListTile extends StatelessWidget {
  const _InspectionListTile({required this.item, required this.onTap});

  final _InspectionItem item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: item.color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Icon(item.icon, color: item.color, size: 18),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            item.reference,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          item.dateText.isEmpty ? '-' : item.dateText,
                          style: const TextStyle(
                            color: Colors.black54,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Text(
                          item.type,
                          style: TextStyle(
                            color: item.color,
                            fontWeight: FontWeight.w600,
                            fontSize: 11,
                          ),
                        ),
                        const Text(
                          '  |  ',
                          style: TextStyle(color: Colors.black26),
                        ),
                        Expanded(
                          child: Text(
                            item.location.isEmpty ? '-' : item.location,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.black54,
                              fontSize: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              _StatusBadge(status: item.status),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'completed' => const Color(0xFF15803D),
      'signed' => const Color(0xFF1D4ED8),
      'reported' => const Color(0xFF15803D),
      'closed' => const Color(0xFF475569),
      _ => const Color(0xFFB45309),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        _displayStatus(status),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          color: color,
          fontSize: 10,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  const _DetailSection({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          ...children,
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
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(color: Colors.black54, fontSize: 12),
            ),
          ),
          Expanded(child: Text(text == null || text.isEmpty ? '-' : text)),
        ],
      ),
    );
  }
}

class _InspectionDetailItem extends StatelessWidget {
  const _InspectionDetailItem({required this.index, required this.item});

  final int index;
  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final title = item['name'] ??
        item['hydrant_number'] ??
        item['alarm_number'] ??
        item['type'] ??
        'Item $index';
    final rows = item.entries
        .where((entry) =>
            !_hiddenItemFields.contains(entry.key) &&
            entry.value != null &&
            entry.value.toString().isNotEmpty)
        .toList();
    final photoBefore = item['photo_before'];
    final photoAfter = item['photo_after'];

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '$index. $title',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          ...rows.map((entry) {
            return _DetailRow(
              label: _formatKey(entry.key),
              value: _formatValue(entry.value),
            );
          }),
          if (_hasValue(photoBefore) || _hasValue(photoAfter)) ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                if (_hasValue(photoBefore))
                  _SmallPhotoPreview(label: 'Before', path: photoBefore),
                if (_hasValue(photoAfter))
                  _SmallPhotoPreview(label: 'After', path: photoAfter),
              ],
            ),
          ],
        ],
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

class _InspectionItem {
  const _InspectionItem({
    required this.id,
    required this.reference,
    required this.type,
    required this.status,
    required this.dateText,
    required this.location,
    required this.inspector,
    required this.route,
    required this.detailPath,
    required this.rawData,
    required this.color,
    required this.icon,
  });

  factory _InspectionItem.fromMap(
    Map<String, dynamic> data, {
    required String type,
    required String route,
    required String detailPath,
    required Color color,
    required IconData icon,
  }) {
    final id = data['id']?.toString() ?? '';
    final reference =
        (data['reference_no'] ?? data['code'] ?? data['inspection_no'] ?? '')
            .toString();
    final location = (data['location_text'] ?? '').toString().trim().isNotEmpty
        ? data['location_text'].toString()
        : _relationName(data['location'], data['location_id']);
    final inspector = data['reporter'] != null
        ? _relationName(data['reporter'], data['reporter_id'])
        : _relationName(data['inspector'], data['inspector_id']);

    final status = detailPath == 'fire-extinguishers'
        ? 'new'
        : (data['status'] ?? 'new').toString();

    return _InspectionItem(
      id: id,
      reference: reference.isEmpty ? '$type #$id' : reference,
      type: type,
      status: status,
      dateText: _dateOnly(data['inspection_date'] ?? data['incident_date']),
      location: location,
      inspector: inspector,
      route: route,
      detailPath: detailPath,
      rawData: data,
      color: color,
      icon: icon,
    );
  }

  final String id;
  final String reference;
  final String type;
  final String status;
  final String dateText;
  final String location;
  final String inspector;
  final String route;
  final String detailPath;
  final Map<String, dynamic> rawData;
  final Color color;
  final IconData icon;

  String get searchText {
    return [
      id,
      reference,
      type,
      status,
      dateText,
      location,
      inspector,
    ].join(' ').toLowerCase();
  }
}

Map<String, dynamic> _mapFrom(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

const Set<String> _hiddenItemFields = {
  'id',
  'inspection_id',
  'created_at',
  'updated_at',
  'deleted_at',
  'photo_before',
  'photo_after',
  'item_lat',
  'item_lng',
};

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

String _relationName(dynamic relation, dynamic fallbackId) {
  final data = _mapFrom(relation);
  if (data.isNotEmpty) {
    final name = data['name'] ??
        data['location_name'] ??
        data['description'] ??
        data['username'] ??
        data['name_point'];
    if (name != null) return name.toString();
  }
  return fallbackId == null ? '' : '#$fallbackId';
}

String _dateOnly(dynamic value) {
  if (value == null) return '';
  final text = value.toString();
  if (text.isEmpty) return '';
  return text.split('T').first.split(' ').first;
}

String _dateTime(dynamic value) {
  if (value == null) return '';
  final text = value.toString();
  if (text.isEmpty) return '';
  return text.replaceFirst('T', ' ').split('.').first;
}

String _timeOnly(dynamic value) {
  if (value == null) return '';
  final text = value.toString();
  if (text.isEmpty) return '';
  return text.length >= 5 ? text.substring(0, 5) : text;
}

String _displayStatus(dynamic value) {
  final status = value?.toString().toLowerCase() ?? '';
  return switch (status) {
    'reported' => 'Open',
    'closed' => 'Close',
    '' => 'New',
    _ => status[0].toUpperCase() + status.substring(1),
  };
}

List<dynamic> _listFrom(dynamic value) {
  if (value is List) return value;
  return <dynamic>[];
}

String _formatKey(String key) {
  return key
      .split('_')
      .map((word) =>
          word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1)}')
      .join(' ');
}

String _formatValue(dynamic value) {
  if (value is bool) return value ? 'Yes' : 'No';
  if (value is num && (value == 0 || value == 1)) {
    return value == 1 ? 'Yes' : 'No';
  }
  return value.toString();
}
