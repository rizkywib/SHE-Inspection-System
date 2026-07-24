import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:she_inspection_mobile/main.dart';
import 'package:she_inspection_mobile/screens/home_screen.dart';
import 'package:she_inspection_mobile/screens/incident/incident_form_screen.dart';
import 'package:she_inspection_mobile/services/api_service.dart';
import 'package:she_inspection_mobile/services/auth_service.dart';

void main() {
  testWidgets('App menampilkan splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp());

    expect(find.text('SHE Inspection'), findsOneWidget);
    expect(find.text('Safety, Health & Environment'), findsOneWidget);
  });

  testWidgets('Form Inspection memuat field dan master data',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: _FakeApiService(),
        child: const MaterialApp(home: IncidentFormScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Inspection'), findsOneWidget);
    expect(find.text('Lokasi'), findsOneWidget);
    expect(find.byType(TextFormField), findsNWidgets(2));
    expect(find.byType(DropdownButtonFormField<int>), findsOneWidget);
    expect(find.text('Inspection Type'), findsOneWidget);
    expect(find.text('Status'), findsOneWidget);
    expect(find.text('Keterangan'), findsOneWidget);
    await tester.drag(find.byType(ListView), const Offset(0, -500));
    await tester.pumpAndSettle();
    expect(find.text('Upload Gambar'), findsOneWidget);
    expect(find.text('Simpan'), findsOneWidget);
  });

  testWidgets('Dashboard menampilkan hasil Inspection',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: _FakeApiService()),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(home: HomeScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('INC-TEST-001'), findsOneWidget);
    expect(find.text('Inspection'), findsOneWidget);
    expect(find.text('Ruang Produksi'), findsOneWidget);
    expect(find.text('Open'), findsWidgets);
  });
}

class _FakeApiService extends ApiService {
  @override
  Future<List<dynamic>> getFireHydrants() async => [];

  @override
  Future<List<dynamic>> getFireExtinguishers() async => [];

  @override
  Future<List<dynamic>> getFireAlarms() async => [];

  @override
  Future<List<dynamic>> getEsEw() async => [];

  @override
  Future<List<dynamic>> getIncidents() async => [
        {
          'id': 1,
          'reference_no': 'INC-TEST-001',
          'incident_date': '2026-07-21',
          'incident_time': '10:30:00',
          'location_text': 'Ruang Produksi',
          'status': 'reported',
          'reporter': {'id': 1, 'name': 'Inspector Test'},
          'incident_type': {'id': 1, 'name': 'Unsafe Condition'},
        },
      ];

  @override
  Future<List<dynamic>> getIncidentTypes() async => [
        {'id': 1, 'name': 'Unsafe Condition', 'is_active': true},
      ];
}
