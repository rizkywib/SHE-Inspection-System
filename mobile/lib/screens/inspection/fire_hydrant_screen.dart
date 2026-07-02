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
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Fire Hydrant Inspection'),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showInspectionForm(context),
        child: const Icon(Icons.add),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _inspections.isEmpty
              ? const Center(child: Text('No inspections found'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _inspections.length,
                  itemBuilder: (context, index) {
                    final item = _inspections[index];
                    return Card(
                      child: ListTile(
                        title: Text(item['reference_no'] ?? ''),
                        subtitle: Text(item['location']?['name'] ?? ''),
                        trailing: Chip(
                          label: Text(
                            item['status'] ?? '',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ),
                    );
                  },
                ),
    );
  }

  void _showInspectionForm(BuildContext context) {
    Navigator.pushNamed(context, '/');
  }
}