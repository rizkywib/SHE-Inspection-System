import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';

class FireExtinguisherCreateScreen extends StatefulWidget {
  const FireExtinguisherCreateScreen({super.key});

  @override
  State<FireExtinguisherCreateScreen> createState() => _FireExtinguisherCreateScreenState();
}

class _FireExtinguisherCreateScreenState extends State<FireExtinguisherCreateScreen> {
  List<dynamic> _locations = [];
  List<dynamic> _points = [];
  bool _isLoading = true;

  String? _selectedLocationId;
  int? _selectedPointId;
  String? _itemType;
  String? _itemLocationDetail;
  bool? _pressureCondition;
  bool? _sealCondition;
  bool? _nozzleCondition;

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
        api.getFireExtinguisherLocations(),
        api.getPoints(),
      ]);
      if (mounted) {
        setState(() {
          _locations = results[0];
          _points = results[1];
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

  Future<void> _onPointChanged(int? pointId) async {
    setState(() {
      _selectedPointId = pointId;
      _itemType = null;
      _itemLocationDetail = null;
    });

    if (pointId == null) return;

    try {
      final api = context.read<ApiService>();
      final point = await api.getPoint(pointId);
      if (mounted) {
        setState(() {
          _itemType = point['ket1'];
          _itemLocationDetail = point['ket2'];
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memuat data titik: $e')),
        );
      }
    }
  }

  Future<void> _createInspection() async {
    final scaffold = ScaffoldMessenger.of(context);
    final selectedLocation = _selectedLocationId;
    final selectedPoint = _selectedPointId;

    if (selectedLocation == null || selectedPoint == null) {
      scaffold.showSnackBar(
        const SnackBar(content: Text('Pilih lokasi dan titik inspeksi')),
      );
      return;
    }

    try {
      final api = context.read<ApiService>();
      await api.createFireExtinguisher({
        'location_id': int.parse(selectedLocation),
        'point_id': selectedPoint,
        'inspection_date': DateTime.now().toIso8601String().split('T')[0],
        'item': {
          'type': _itemType,
          'location_detail': _itemLocationDetail,
          'pressure_condition': _pressureCondition == true ? 1 : 0,
          'seal_condition': _sealCondition == true ? 1 : 0,
          'nozzle_condition': _nozzleCondition == true ? 1 : 0,
        },
      });

      if (mounted) {
        scaffold.showSnackBar(
          const SnackBar(content: Text('Inspeksi berhasil dibuat')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        scaffold.showSnackBar(
          SnackBar(content: Text('Gagal membuat inspeksi: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('New Inspection'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Card(
                    elevation: 2,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Buat Inspeksi Baru',
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 16),
                          DropdownButtonFormField<String>(
                            decoration: const InputDecoration(
                              labelText: 'Lokasi APAR',
                              border: OutlineInputBorder(),
                              prefixIcon: Icon(Icons.location_on_outlined),
                            ),
                            initialValue: _selectedLocationId,
                            items: _locations
                                .map((loc) => DropdownMenuItem(
                                      value: (loc['id_location'] ?? loc['id']).toString(),
                                      child: Text(loc['name'] ?? ''),
                                    ))
                                .toList(),
                            onChanged: (value) {
                              setState(() => _selectedLocationId = value);
                            },
                          ),
                          const SizedBox(height: 16),
                          DropdownButtonFormField<int?>(
                            decoration: const InputDecoration(
                              labelText: 'List APAR',
                              border: OutlineInputBorder(),
                              prefixIcon: Icon(Icons.place_outlined),
                            ),
                            initialValue: _selectedPointId,
                            items: _points
                                .map<DropdownMenuItem<int?>>((point) {
                                  final id = point['id'] is int
                                      ? point['id'] as int
                                      : int.tryParse('${point['id']}');
                                  final name = point['name_point'] ?? point['name'] ?? point['ket1'] ?? '';
                                  return DropdownMenuItem<int?>(
                                    value: id,
                                    child: Text(
                                      name,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  );
                                })
                                .toList(),
                            onChanged: _onPointChanged,
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'Item APAR',
                            style: TextStyle(
                                fontWeight: FontWeight.w600, fontSize: 14),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: TextFormField(
                                  decoration: const InputDecoration(
                                    labelText: 'Tipe (Ket 1)',
                                    border: OutlineInputBorder(),
                                  ),
                                  readOnly: true,
                                  initialValue: _itemType ?? '',
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: TextFormField(
                                  decoration: const InputDecoration(
                                    labelText: 'Detail Lokasi (Ket 2)',
                                    border: OutlineInputBorder(),
                                  ),
                                  readOnly: true,
                                  initialValue: _itemLocationDetail ?? '',
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 20),
                          const Text(
                            'Kondisi APAR',
                            style: TextStyle(
                                fontWeight: FontWeight.w600, fontSize: 14),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: Card(
                                  child: Padding(
                                    padding: const EdgeInsets.all(12),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Pressure',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey[600],
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            Radio<bool>(
                                              value: true,
                                              groupValue: _pressureCondition,
                                              onChanged: (value) {
                                                setState(() => _pressureCondition = value);
                                              },
                                            ),
                                            const Text('YES'),
                                            Radio<bool>(
                                              value: false,
                                              groupValue: _pressureCondition,
                                              onChanged: (value) {
                                                setState(() => _pressureCondition = value);
                                              },
                                            ),
                                            const Text('NO'),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Card(
                                  child: Padding(
                                    padding: const EdgeInsets.all(12),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Seal',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey[600],
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            Radio<bool>(
                                              value: true,
                                              groupValue: _sealCondition,
                                              onChanged: (value) {
                                                setState(() => _sealCondition = value);
                                              },
                                            ),
                                            const Text('YES'),
                                            Radio<bool>(
                                              value: false,
                                              groupValue: _sealCondition,
                                              onChanged: (value) {
                                                setState(() => _sealCondition = value);
                                              },
                                            ),
                                            const Text('NO'),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Card(
                                  child: Padding(
                                    padding: const EdgeInsets.all(12),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Nozzle',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey[600],
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            Radio<bool>(
                                              value: true,
                                              groupValue: _nozzleCondition,
                                              onChanged: (value) {
                                                setState(() => _nozzleCondition = value);
                                              },
                                            ),
                                            const Text('YES'),
                                            Radio<bool>(
                                              value: false,
                                              groupValue: _nozzleCondition,
                                              onChanged: (value) {
                                                setState(() => _nozzleCondition = value);
                                              },
                                            ),
                                            const Text('NO'),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: _createInspection,
                              icon: const Icon(Icons.save),
                              label: const Text('Save'),
                              style: ElevatedButton.styleFrom(
                                minimumSize: const Size.fromHeight(48),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}