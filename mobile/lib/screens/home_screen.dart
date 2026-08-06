import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';

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
        color: const Color(0xFF145F3A),
        icon: Icons.water_damage_outlined,
      ),
      _loadType(
        loader: api.getFireExtinguishers,
        type: 'Fire Extinguisher',
        route: '/fire-extinguisher',
        detailPath: 'fire-extinguishers',
        color: const Color(0xFF177245),
        icon: Icons.fire_extinguisher,
      ),
      _loadType(
        loader: api.getFireAlarms,
        type: 'Fire Alarm',
        route: '/fire-alarm',
        detailPath: 'fire-alarms',
        color: const Color(0xFF238653),
        icon: Icons.notifications_active_outlined,
      ),
      _loadType(
        loader: api.getEsEw,
        type: 'ES/EW',
        route: '/es-ew',
        detailPath: 'es-ew',
        color: const Color(0xFF329566),
        icon: Icons.shower_outlined,
      ),
      _loadType(
        loader: api.getIncidents,
        type: 'Inspection',
        route: '/incident-form',
        detailPath: 'incidents',
        color: const Color(0xFF4AA878),
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

  int get _followUpCount => _inspections
      .where((item) => item.status == 'new' || item.status == 'reported')
      .length;

  int get _completedCount => _inspections
      .where((item) =>
          item.status == 'completed' ||
          item.status == 'signed' ||
          item.status == 'closed')
      .length;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final user = auth.user;
    final filtered = _filteredInspections;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            tooltip: 'Refresh dashboard',
            onPressed: _loadInspections,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      drawer: _buildDrawer(context, user),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadInspections,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _WelcomeCard(userName: user?['name']?.toString()),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: _SummaryCard(
                              label: 'Total Data',
                              value: _inspections.length,
                              icon: Icons.folder_copy_outlined,
                              color: AppColors.primaryDark,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _SummaryCard(
                              label: 'Tindak Lanjut',
                              value: _followUpCount,
                              icon: Icons.pending_actions_outlined,
                              color: const Color(0xFFB66A13),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _SummaryCard(
                              label: 'Selesai',
                              value: _completedCount,
                              icon: Icons.task_alt_outlined,
                              color: const Color(0xFF238653),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 22),
                      const _SectionHeading(
                        title: 'Akses Cepat',
                        subtitle: 'Buka modul inspeksi dengan satu sentuhan',
                      ),
                      const SizedBox(height: 12),
                      LayoutBuilder(
                        builder: (context, constraints) {
                          final columns = constraints.maxWidth >= 600 ? 7 : 4;
                          return GridView.count(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            crossAxisCount: columns,
                            mainAxisSpacing: 8,
                            crossAxisSpacing: 8,
                            childAspectRatio: 0.86,
                            children: [
                              _QuickAction(
                                label: 'Hydrant',
                                icon: Icons.water_damage_outlined,
                                color: const Color(0xFF145F3A),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/fire-hydrant',
                                ),
                              ),
                              _QuickAction(
                                label: 'APAR',
                                icon: Icons.fire_extinguisher,
                                color: const Color(0xFF177245),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/fire-extinguisher',
                                ),
                              ),
                              _QuickAction(
                                label: 'Fire Alarm',
                                icon: Icons.notifications_active_outlined,
                                color: const Color(0xFF238653),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/fire-alarm',
                                ),
                              ),
                              _QuickAction(
                                label: 'ES/EW',
                                icon: Icons.shower_outlined,
                                color: const Color(0xFF329566),
                                onTap: () =>
                                    Navigator.pushNamed(context, '/es-ew'),
                              ),
                              _QuickAction(
                                label: 'Permit',
                                icon: Icons.fact_check_outlined,
                                color: const Color(0xFF3B7B5C),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/permit-matrix',
                                ),
                              ),
                              _QuickAction(
                                label: 'Safety Talk',
                                icon: Icons.record_voice_over_outlined,
                                color: const Color(0xFF4A8969),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/safety-talk-training',
                                ),
                              ),
                              _QuickAction(
                                label: 'Inspection',
                                icon: Icons.assignment_outlined,
                                color: const Color(0xFF58A978),
                                onTap: () => Navigator.pushNamed(
                                  context,
                                  '/incidents',
                                ),
                              ),
                            ],
                          );
                        },
                      ),
                      const SizedBox(height: 24),
                      const _SectionHeading(
                        title: 'Aktivitas Terbaru',
                        subtitle: 'Ringkasan inspeksi dari seluruh modul',
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 46,
                        child: TextField(
                          key: const ValueKey('dashboard_search'),
                          controller: _searchController,
                          onChanged: (_) => setState(() {}),
                          textAlignVertical: TextAlignVertical.center,
                          decoration: InputDecoration(
                            hintText: 'Cari inspector atau remark',
                            prefixIcon: const Icon(Icons.search, size: 20),
                            suffixIcon: _searchController.text.isEmpty
                                ? null
                                : IconButton(
                                    onPressed: () {
                                      _searchController.clear();
                                      setState(() {});
                                    },
                                    icon: const Icon(Icons.close, size: 18),
                                    tooltip: 'Hapus pencarian',
                                  ),
                            contentPadding: EdgeInsets.zero,
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: [
                            _StatusFilterChip(
                              label: 'Semua',
                              selected: _selectedStatus == 'all',
                              onTap: () =>
                                  setState(() => _selectedStatus = 'all'),
                            ),
                            _StatusFilterChip(
                              label: 'Baru',
                              selected: _selectedStatus == 'new',
                              onTap: () =>
                                  setState(() => _selectedStatus = 'new'),
                            ),
                            _StatusFilterChip(
                              label: 'Selesai',
                              selected: _selectedStatus == 'completed',
                              onTap: () => setState(
                                () => _selectedStatus = 'completed',
                              ),
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
                              onTap: () => setState(
                                () => _selectedStatus = 'reported',
                              ),
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
                      const SizedBox(height: 10),
                      _CompactListSummary(
                        shown: filtered.length,
                        total: _inspections.length,
                        isLoading: _isLoading,
                      ),
                      const SizedBox(height: 10),
                    ],
                  ),
                ),
              ),
              if (_isLoading && _inspections.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (_error != null)
                SliverFillRemaining(
                  hasScrollBody: false,
                  child: _ErrorState(
                    message: _error!,
                    onRetry: _loadInspections,
                  ),
                )
              else if (filtered.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: _EmptyDashboardState(),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  sliver: SliverList.builder(
                    itemCount: filtered.length,
                    itemBuilder: (context, index) {
                      final item = filtered[index];
                      return _InspectionListTile(
                        item: item,
                        onTap: () => _showInspectionDetail(item),
                      );
                    },
                  ),
                ),
            ],
          ),
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
            decoration: const BoxDecoration(color: AppColors.primaryDark),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const CircleAvatar(
                  radius: 28,
                  backgroundColor: AppColors.surfaceSoft,
                  child: Icon(
                    Icons.health_and_safety_outlined,
                    size: 28,
                    color: AppColors.primaryDark,
                  ),
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
            leading: const Icon(Icons.fact_check_outlined),
            title: const Text('Permit Matrix'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/permit-matrix');
            },
          ),
          ListTile(
            leading: const Icon(Icons.record_voice_over_outlined),
            title: const Text('Safety Talk/Training'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/safety-talk-training');
            },
          ),
          ListTile(
            leading: const Icon(Icons.assignment_outlined),
            title: const Text('Inspection'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/incidents');
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

class _WelcomeCard extends StatelessWidget {
  const _WelcomeCard({this.userName});

  final String? userName;

  @override
  Widget build(BuildContext context) {
    final name = userName?.trim();
    final displayName = name == null || name.isEmpty
        ? 'SHE Inspection'
        : name.split(RegExp(r'\s+')).first;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.primaryDark,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${_greetingFor(DateTime.now().hour)}, $displayName',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Pantau aktivitas keselamatan kerja hari ini.',
                  style: TextStyle(color: Colors.white70, height: 1.35),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    const Icon(
                      Icons.calendar_today_outlined,
                      size: 15,
                      color: Colors.white70,
                    ),
                    const SizedBox(width: 7),
                    Text(
                      _todayLabel(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 14),
          Container(
            width: 58,
            height: 58,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Icon(
              Icons.health_and_safety_outlined,
              color: Colors.white,
              size: 30,
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final int value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minHeight: 112),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(height: 7),
          Text(
            '$value',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
          ),
          Text(
            label,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Colors.black54,
              fontSize: 11,
              fontWeight: FontWeight.w600,
              height: 1.15,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeading extends StatelessWidget {
  const _SectionHeading({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
        ),
        const SizedBox(height: 2),
        Text(
          subtitle,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.black54,
              ),
        ),
      ],
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: const BorderSide(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 10),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: color, size: 21),
              ),
              const SizedBox(height: 7),
              Text(
                label,
                maxLines: 2,
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 10.5,
                  fontWeight: FontWeight.w700,
                  height: 1.1,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyDashboardState extends StatelessWidget {
  const _EmptyDashboardState();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.fromLTRB(24, 24, 24, 72),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.search_off_outlined, size: 50, color: Colors.black38),
          SizedBox(height: 12),
          Text(
            'Aktivitas tidak ditemukan',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
          SizedBox(height: 4),
          Text(
            'Coba ubah kata pencarian atau filter status.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.black54),
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
          '$shown dari $total aktivitas',
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
      padding: const EdgeInsets.only(right: 7),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        visualDensity: VisualDensity.compact,
        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
        labelStyle: TextStyle(
          fontSize: 12,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          color: selected ? AppColors.primaryDark : Colors.black54,
        ),
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
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 0,
      color: AppColors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: item.color.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(item.icon, color: item.color, size: 20),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.type,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          item.dateText.isEmpty ? '-' : item.dateText,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.black54,
                            fontWeight: FontWeight.w500,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(status: item.status),
                  const SizedBox(width: 3),
                  const Icon(
                    Icons.chevron_right,
                    size: 20,
                    color: Colors.black38,
                  ),
                ],
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 11),
                child: Divider(),
              ),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _ActivityField(
                      label: 'Inspector / Pelapor',
                      value: item.inspector,
                      icon: Icons.person_outline,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _ActivityField(
                      label: 'Remark',
                      value: item.remark,
                      icon: Icons.notes_outlined,
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

class _ActivityField extends StatelessWidget {
  const _ActivityField({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: Colors.black45),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  color: Colors.black54,
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                value.isEmpty ? '-' : value,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  height: 1.25,
                ),
              ),
            ],
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
    final color = switch (status) {
      'completed' => const Color(0xFF15803D),
      'signed' => AppColors.primary,
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
        color: AppColors.surfaceSoft,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: AppColors.border),
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
        border: Border.all(color: AppColors.border),
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
            color: AppColors.surfaceSoft,
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
    required this.remark,
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
    final remark = _remarkFrom(data);

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
      remark: remark,
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
  final String remark;
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
      remark,
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

String _remarkFrom(Map<String, dynamic> data) {
  for (final key in ['remark', 'remarks', 'description', 'notes']) {
    final value = data[key]?.toString().trim() ?? '';
    if (value.isNotEmpty) return value;
  }

  final remarks = <String>[];
  for (final value in _listFrom(data['items'])) {
    final item = _mapFrom(value);
    for (final key in ['remark', 'remarks', 'description', 'notes']) {
      final remark = item[key]?.toString().trim() ?? '';
      if (remark.isNotEmpty && !remarks.contains(remark)) {
        remarks.add(remark);
        break;
      }
    }
  }
  return remarks.join(' • ');
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

String _greetingFor(int hour) {
  if (hour < 11) return 'Selamat pagi';
  if (hour < 15) return 'Selamat siang';
  if (hour < 18) return 'Selamat sore';
  return 'Selamat malam';
}

String _todayLabel() {
  const days = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
    'Minggu',
  ];
  const months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ];
  final today = DateTime.now();
  return '${days[today.weekday - 1]}, ${today.day} '
      '${months[today.month - 1]} ${today.year}';
}
