import 'package:flutter/material.dart';

class IncidentListScreen extends StatelessWidget {
  const IncidentListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Incidents')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => Navigator.pushNamed(context, '/incident-form'),
        child: const Icon(Icons.add),
      ),
      body: const Center(
        child: Text('Incident list belum tersedia'),
      ),
    );
  }
}
