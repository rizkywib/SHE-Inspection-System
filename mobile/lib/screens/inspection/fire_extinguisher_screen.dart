import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';

class FireExtinguisherScreen extends StatefulWidget {
  const FireExtinguisherScreen({super.key});

  @override
  State<FireExtinguisherScreen> createState() => _FireExtinguisherScreenState();
}

class _FireExtinguisherScreenState extends State<FireExtinguisherScreen> {
  List<dynamic> _inspections = [];
  List<dynamic> _locations = [];
  List<dynamic> _points = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<ApiService>();
      final results = await Future.wait([
        api.getFireExtinguishers(),
        api.getFireExtinguisherLocations(),
        api.getPoints(),
      ]);
      if (mounted) {
        setState(() {
          _inspections = results[0];
          _locations = results[1];
          _points = results[2];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat data: $e')),
        );
      }
    }
  }

  Future<void> _deleteInspection(String id) async {
    try {
      final api = context.read<ApiService>();
      await api.deleteFireExtinguisher(int.parse(id));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Inspeksi berhasil dihapus')),
        );
        _loadData();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menghapus: $e')),
        );
      }
    }
  }

  void _showDetail(Map<String, dynamic> inspection) {
    final location = inspection['location'];
    final items = inspection['items'] ?? [];
    final inspector = inspection['inspector'];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.65,
          minChildSize: 0.4,
          maxChildSize: 0.9,
          builder: (context, controller) {
            return ListView(
              controller: controller,
              padding: const EdgeInsets.all(20),
              children: [
                Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: const Color(0xFFEA580C).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.fire_extinguisher,
                        color: Color(0xFFEA580C),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            inspection['reference_no'] ?? 'Detail',
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 16,
                            ),
                          ),
                          Text(
                            '${inspection['inspection_date']}',
                            style: TextStyle(
                              color: Colors.grey[600],
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                _DetailRow(
                  label: 'Lokasi',
                  value: location != null ? location['name'] : '-',
                ),
                const SizedBox(height: 8),
                _DetailRow(
                  label: 'Inspector',
                  value: inspector != null ? inspector['name'] : '-',
                ),
                if (inspection['checked_in_at'] != null) ...[
                  const SizedBox(height: 8),
                  _DetailRow(
                    label: 'Check-in',
                    value: inspection['checked_in_at'].toString().replaceFirst('T', ' '),
                  ),
                ],
                if (inspection['signed_at'] != null) ...[
                  const SizedBox(height: 8),
                  _DetailRow(
                    label: 'Signed',
                    value: inspection['signed_at'].toString().replaceFirst('T', ' '),
                  ),
                ],
                const SizedBox(height: 16),
                const Divider(),
                const SizedBox(height: 8),
                const Text(
                  'Item Inspeksi',
                  style: TextStyle(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  const Text('Tidak ada item')
                else
                  ...items.map((item) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item['name'] ?? 'Item',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          if (item['type'] != null) ...[
                            const SizedBox(height: 6),
                            _DetailRow(
                              label: 'Tipe',
                              value: item['type'],
                            ),
                          ],
                          if (item['location_detail'] != null) ...[
                            const SizedBox(height: 4),
                            _DetailRow(
                              label: 'Detail',
                              value: item['location_detail'],
                            ),
                          ],
                          if (item['pressure_condition'] != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                SizedBox(
                                  width: 120,
                                  child: Text(
                                    'Pressure',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                                Icon(
                                  item['pressure_condition'] == 1
                                      ? Icons.check_circle
                                      : Icons.cancel,
                                  color: item['pressure_condition'] == 1
                                      ? Colors.green
                                      : Colors.red,
                                  size: 18,
                                ),
                              ],
                            ),
                          ],
                          if (item['seal_condition'] != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                SizedBox(
                                  width: 120,
                                  child: Text(
                                    'Seal',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                                Icon(
                                  item['seal_condition'] == 1
                                      ? Icons.check_circle
                                      : Icons.cancel,
                                  color: item['seal_condition'] == 1
                                      ? Colors.green
                                      : Colors.red,
                                  size: 18,
                                ),
                              ],
                            ),
                          ],
                          if (item['nozzle_condition'] != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                SizedBox(
                                  width: 120,
                                  child: Text(
                                    'Nozzle',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                                Icon(
                                  item['nozzle_condition'] == 1
                                      ? Icons.check_circle
                                      : Icons.cancel,
                                  color: item['nozzle_condition'] == 1
                                      ? Colors.green
                                      : Colors.red,
                                  size: 18,
                                ),
                              ],
                            ),
                          ],
                          if (item['remark'] != null &&
                              item['remark'].toString().isNotEmpty) ...[
                            const SizedBox(height: 8),
                            _DetailRow(
                              label: 'Remark',
                              value: item['remark'],
                            ),
                          ],
                        ],
                      ),
                    );
                  }),
                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                  label: const Text('Tutup'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Fire Extinguisher Inspection'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: _inspections.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.inbox_outlined,
                                size: 64,
                                color: Colors.grey[300],
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Belum ada inspeksi',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 16,
                                ),
                              ),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _inspections.length,
                          itemBuilder: (context, index) {
                            final inspection = _inspections[index];
                            final location = inspection['location'];
                            final items = inspection['items'] ?? [];

                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              child: ListTile(
                                contentPadding: const EdgeInsets.all(16),
                                title: Text(
                                  inspection['reference_no'] ?? 'No Reference',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 14,
                                  ),
                                ),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const SizedBox(height: 4),
                                    Row(
                                      children: [
                                        Icon(
                                          Icons.calendar_today_outlined,
                                          size: 14,
                                          color: Colors.grey[600],
                                        ),
                                        const SizedBox(width: 4),
                                        Text(
                                          '${inspection['inspection_date']}',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey[600],
                                          ),
                                        ),
                                      ],
                                    ),
                                    if (location != null) ...[
                                      const SizedBox(height: 4),
                                      Row(
                                        children: [
                                          Icon(
                                            Icons.location_on_outlined,
                                            size: 14,
                                            color: Colors.grey[600],
                                          ),
                                          const SizedBox(width: 4),
                                          Expanded(
                                            child: Text(
                                              location['name'] ?? '',
                                              style: TextStyle(
                                                fontSize: 12,
                                                color: Colors.grey[600],
                                              ),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                    if (items.isNotEmpty) ...[
                                      const SizedBox(height: 4),
                                      Row(
                                        children: [
                                          Icon(
                                            Icons.check_circle_outline,
                                            size: 14,
                                            color: Colors.grey[600],
                                          ),
                                          const SizedBox(width: 4),
                                          Expanded(
                                            child: Text(
                                              items[0]['name'] ?? '',
                                              style: TextStyle(
                                                fontSize: 12,
                                                color: Colors.grey[600],
                                              ),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ],
                                ),
                                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                                onTap: () => _showDetail(inspection),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.pushNamed(context, '/fire-extinguisher-create'),
        icon: const Icon(Icons.add),
        label: const Text('New Inspection'),
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
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 100,
          child: Text(
            label,
            style: TextStyle(
              color: Colors.grey[600],
              fontSize: 12,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontSize: 13),
          ),
        ),
      ],
    );
  }
}