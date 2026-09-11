import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import 'safety_talk_common.dart';
import 'safety_talk_form_screen.dart';

class SafetyTalkScreen extends StatefulWidget {
  const SafetyTalkScreen({super.key, this.initialId});

  final String? initialId;

  @override
  State<SafetyTalkScreen> createState() => _SafetyTalkScreenState();
}

class _SafetyTalkScreenState extends State<SafetyTalkScreen> {
  final _searchController = TextEditingController();
  final _dateFromController = TextEditingController();
  final _dateToController = TextEditingController();
  List<dynamic> _trainings = const [];
  List<dynamic> _speakers = const [];
  List<dynamic> _areas = const [];
  int? _speakerId;
  int? _area;
  bool _isLoading = true;
  String? _error;
  bool _initialDetailHandled = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _dateFromController.dispose();
    _dateToController.dispose();
    super.dispose();
  }

  List<Map<String, dynamic>> get _visibleTrainings {
    final search = _searchController.text.trim().toLowerCase();
    final dateFrom = _dateFromController.text;
    final dateTo = _dateToController.text;
    return _trainings.map(safetyTalkMap).where((row) {
      final date = safetyTalkDate(row['implementation_date']);
      final matchesSearch = search.isEmpty ||
          safetyTalkSpeakerName(row).toLowerCase().contains(search) ||
          safetyTalkText(row['topic']).toLowerCase().contains(search);
      final matchesDateFrom = dateFrom.isEmpty || date.compareTo(dateFrom) >= 0;
      final matchesDateTo = dateTo.isEmpty || date.compareTo(dateTo) <= 0;
      final matchesArea =
          _area == null || safetyTalkInt(row['implementation_area']) == _area;
      final matchesSpeaker =
          _speakerId == null || safetyTalkInt(row['speaker_id']) == _speakerId;
      return matchesSearch &&
          matchesDateFrom &&
          matchesDateTo &&
          matchesArea &&
          matchesSpeaker;
    }).toList(growable: false);
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final api = context.read<ApiService>();
      final result = await Future.wait<dynamic>([
        api.getSafetyTalkTrainings(),
        api.getSafetyTalkMasterData(),
      ]);
      if (!mounted) return;
      final master = safetyTalkMap(result[1]);
      setState(() {
        _trainings = safetyTalkList(result[0]);
        _speakers = safetyTalkList(master['speakers']);
        _areas = safetyTalkList(master['areas']);
        _isLoading = false;
      });
      _showInitialDetail();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  void _showInitialDetail() {
    if (_initialDetailHandled || widget.initialId == null) return;
    _initialDetailHandled = true;
    final id = safetyTalkInt(widget.initialId);
    if (id == null) return;
    final local = _trainings
        .map(safetyTalkMap)
        .where((row) => safetyTalkInt(row['id']) == id)
        .firstOrNull;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (local != null) {
        _showDetail(local);
      } else {
        _loadAndShowDetail(id);
      }
    });
  }

  Future<void> _loadAndShowDetail(int id) async {
    try {
      final detail = await context.read<ApiService>().getSafetyTalkTraining(id);
      if (mounted) _openDetailSheet(detail);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Detail gagal dimuat: $error')),
      );
    }
  }

  Future<void> _showDetail(Map<String, dynamic> row) async {
    final id = safetyTalkInt(row['id']);
    if (id == null) return;
    try {
      final detail = await context.read<ApiService>().getSafetyTalkTraining(id);
      if (mounted) _openDetailSheet(detail);
    } catch (_) {
      if (mounted) _openDetailSheet(row);
    }
  }

  void _openDetailSheet(Map<String, dynamic> row) {
    final canModify = safetyTalkCanModify(
      row,
      safetyTalkMap(context.read<AuthService>().user),
    );
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: SizedBox(
          height: MediaQuery.sizeOf(sheetContext).height * 0.88,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Detail Safety Talk/Training',
                        style: Theme.of(sheetContext).textTheme.titleLarge,
                      ),
                    ),
                    IconButton(
                      tooltip: 'Tutup',
                      onPressed: () => Navigator.pop(sheetContext),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _DetailItem(
                        label: 'Pembicara',
                        value: safetyTalkSpeakerName(row),
                      ),
                      _DetailItem(
                        label: 'Tanggal Pelaksanaan',
                        value: safetyTalkDate(row['implementation_date']),
                      ),
                      _DetailItem(
                        label: 'Topik / Materi',
                        value: safetyTalkText(row['topic']),
                      ),
                      const Divider(height: 28),
                      Text(
                        'Peserta',
                        style: Theme.of(sheetContext).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      _DetailItem(
                        label: 'Ecogreen',
                        value: safetyTalkText(row['ecogreen_participants']),
                      ),
                      _DetailItem(
                        label: 'Outsourcing',
                        value: safetyTalkText(row['outsourcing_participants']),
                      ),
                      _DetailItem(
                        label: 'Contractor',
                        value: safetyTalkText(row['contractor_participants']),
                      ),
                      _DetailItem(
                        label: 'Total Peserta',
                        value: safetyTalkTotalParticipants(row).toString(),
                      ),
                      _DetailItem(
                        label: 'Durasi',
                        value:
                            '${safetyTalkText(row['duration_minutes'])} menit',
                      ),
                      _DetailItem(
                        label: 'Area Pelaksanaan',
                        value:
                            'Area ${safetyTalkText(row['implementation_area'])}',
                      ),
                      _DetailItem(
                        label: 'Dibuat Oleh',
                        value: safetyTalkText(
                          safetyTalkMap(row['creator'])['name'],
                        ),
                      ),
                      _DetailItem(
                        label: 'Waktu Dibuat',
                        value: safetyTalkDateTime(row['created_at']),
                      ),
                      _DetailItem(
                        label: 'Terakhir Diperbarui',
                        value: safetyTalkDateTime(row['updated_at']),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Foto Kegiatan',
                        style: Theme.of(sheetContext).textTheme.labelLarge,
                      ),
                      const SizedBox(height: 8),
                      _NetworkPhoto(
                        url: safetyTalkText(
                          row['activity_photo_url'],
                          fallback: '',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (canModify)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
                  child: Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () {
                            Navigator.pop(sheetContext);
                            _confirmDelete(row);
                          },
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Hapus'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () {
                            Navigator.pop(sheetContext);
                            _openForm(row);
                          },
                          icon: const Icon(Icons.edit_outlined),
                          label: const Text('Edit'),
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openForm([Map<String, dynamic>? training]) async {
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (_) => SafetyTalkFormScreen(training: training),
      ),
    );
    if (!mounted || result == null) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result == 'deleted'
              ? 'Data Safety Talk berhasil dihapus.'
              : 'Data Safety Talk berhasil disimpan.',
        ),
      ),
    );
    await _loadData();
  }

  Future<void> _confirmDelete(Map<String, dynamic> row) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Safety Talk/Training?'),
        content: const Text(
          'Data dan foto kegiatan akan dihapus. Tindakan ini tidak dapat dibatalkan.',
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
    final id = safetyTalkInt(row['id']);
    if (id == null) return;
    final response =
        await context.read<ApiService>().deleteSafetyTalkTraining(id);
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
      const SnackBar(content: Text('Data Safety Talk berhasil dihapus.')),
    );
    await _loadData();
  }

  Future<void> _selectFilterDate(TextEditingController controller) async {
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.tryParse(controller.text) ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      setState(() {
        controller.text = selected.toIso8601String().split('T').first;
      });
    }
  }

  void _resetFilters() {
    setState(() {
      _searchController.clear();
      _dateFromController.clear();
      _dateToController.clear();
      _speakerId = null;
      _area = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final rows = _visibleTrainings;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Safety Talk/Training'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _isLoading ? null : _loadData,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _ListError(message: _error!, onRetry: _loadData)
                : RefreshIndicator(
                    onRefresh: _loadData,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
                      children: [
                        TextField(
                          key: const ValueKey('safety_search'),
                          controller: _searchController,
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            hintText: 'Nama pembicara atau topik',
                            prefixIcon: const Icon(Icons.search),
                            suffixIcon: _searchController.text.isEmpty
                                ? null
                                : IconButton(
                                    onPressed: () {
                                      _searchController.clear();
                                      setState(() {});
                                    },
                                    icon: const Icon(Icons.clear),
                                  ),
                            border: const OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          margin: EdgeInsets.zero,
                          child: ExpansionTile(
                            key: const ValueKey('safety_filters'),
                            leading: const Icon(Icons.tune),
                            title: const Text('Filter Data'),
                            childrenPadding:
                                const EdgeInsets.fromLTRB(16, 0, 16, 16),
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: _FilterDateField(
                                      label: 'Tanggal Mulai',
                                      controller: _dateFromController,
                                      onTap: () => _selectFilterDate(
                                        _dateFromController,
                                      ),
                                      onClear: () => setState(
                                        _dateFromController.clear,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: _FilterDateField(
                                      label: 'Tanggal Selesai',
                                      controller: _dateToController,
                                      onTap: () => _selectFilterDate(
                                        _dateToController,
                                      ),
                                      onClear: () => setState(
                                        _dateToController.clear,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              DropdownButtonFormField<int>(
                                initialValue: _area,
                                decoration: const InputDecoration(
                                  labelText: 'Area',
                                  border: OutlineInputBorder(),
                                ),
                                hint: const Text('Semua Area'),
                                items: _areas
                                    .map(safetyTalkInt)
                                    .whereType<int>()
                                    .map(
                                      (area) => DropdownMenuItem<int>(
                                        value: area,
                                        child: Text('Area $area'),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (value) =>
                                    setState(() => _area = value),
                              ),
                              const SizedBox(height: 12),
                              DropdownButtonFormField<int>(
                                initialValue: _speakerId,
                                isExpanded: true,
                                decoration: const InputDecoration(
                                  labelText: 'Pembicara',
                                  border: OutlineInputBorder(),
                                ),
                                hint: const Text('Semua Pembicara'),
                                items:
                                    _speakers.map(safetyTalkMap).map((speaker) {
                                  return DropdownMenuItem<int>(
                                    value: safetyTalkInt(speaker['id']),
                                    child: Text(
                                      safetyTalkText(speaker['name']),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  );
                                }).toList(),
                                onChanged: (value) =>
                                    setState(() => _speakerId = value),
                              ),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton.icon(
                                  onPressed: _resetFilters,
                                  icon: const Icon(Icons.restart_alt),
                                  label: const Text('Reset Filter'),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: Text(
                            '${rows.length} dari ${_trainings.length} data',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ),
                        if (rows.isEmpty)
                          const _EmptyState()
                        else
                          ...rows.indexed.map((entry) {
                            return _TrainingCard(
                              number: entry.$1 + 1,
                              training: entry.$2,
                              onTap: () => _showDetail(entry.$2),
                              onLongPress: safetyTalkCanModify(
                                entry.$2,
                                safetyTalkMap(context.read<AuthService>().user),
                              )
                                  ? () => _confirmDelete(entry.$2)
                                  : null,
                            );
                          }),
                      ],
                    ),
                  ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        key: const ValueKey('safety_add'),
        onPressed: _isLoading ? null : () => _openForm(),
        icon: const Icon(Icons.add),
        label: const Text('Tambah'),
      ),
    );
  }
}

class _TrainingCard extends StatelessWidget {
  const _TrainingCard({
    required this.number,
    required this.training,
    required this.onTap,
    required this.onLongPress,
  });

  final int number;
  final Map<String, dynamic> training;
  final VoidCallback onTap;
  final VoidCallback? onLongPress;

  @override
  Widget build(BuildContext context) {
    final total = safetyTalkTotalParticipants(training);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 18,
                    child: Text('$number'),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          safetyTalkSpeakerName(training),
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        Text(
                          safetyTalkDate(training['implementation_date']),
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                  Chip(
                    avatar: const Icon(Icons.place_outlined, size: 17),
                    label: Text(
                      'Area ${safetyTalkText(training['implementation_area'])}',
                    ),
                    visualDensity: VisualDensity.compact,
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                safetyTalkText(training['topic']),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(Icons.groups_outlined, size: 18),
                  const SizedBox(width: 6),
                  Text('$total peserta'),
                  const SizedBox(width: 18),
                  const Icon(Icons.timer_outlined, size: 18),
                  const SizedBox(width: 6),
                  Text(
                    '${safetyTalkText(training['duration_minutes'])} menit',
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

class _DetailItem extends StatelessWidget {
  const _DetailItem({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.labelMedium),
          const SizedBox(height: 3),
          Text(value),
        ],
      ),
    );
  }
}

class _NetworkPhoto extends StatelessWidget {
  const _NetworkPhoto({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) return const Text('Tidak ada foto.');
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Image.network(
        url,
        width: double.infinity,
        height: 260,
        fit: BoxFit.contain,
        errorBuilder: (_, __, ___) => Container(
          height: 120,
          alignment: Alignment.center,
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          child: const Text('Foto tidak dapat dimuat.'),
        ),
      ),
    );
  }
}

class _FilterDateField extends StatelessWidget {
  const _FilterDateField({
    required this.label,
    required this.controller,
    required this.onTap,
    required this.onClear,
  });

  final String label;
  final TextEditingController controller;
  final VoidCallback onTap;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      readOnly: true,
      onTap: onTap,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        suffixIcon: controller.text.isEmpty
            ? const Icon(Icons.calendar_today_outlined)
            : IconButton(
                onPressed: onClear,
                icon: const Icon(Icons.clear),
              ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 72),
      child: Column(
        children: [
          Icon(Icons.inbox_outlined, size: 54),
          SizedBox(height: 12),
          Text('Belum ada data Safety Talk/Training.'),
        ],
      ),
    );
  }
}

class _ListError extends StatelessWidget {
  const _ListError({required this.message, required this.onRetry});

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
