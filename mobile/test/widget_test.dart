import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:she_inspection_mobile/main.dart';
import 'package:she_inspection_mobile/screens/home_screen.dart';
import 'package:she_inspection_mobile/screens/incident/incident_form_screen.dart';
import 'package:she_inspection_mobile/screens/incident/incident_list_screen.dart';
import 'package:she_inspection_mobile/screens/inspection/fire_hydrant_create_flow.dart';
import 'package:she_inspection_mobile/screens/inspection/fire_hydrant_screen.dart';
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

    expect(find.text('Inspector Test'), findsOneWidget);
    expect(find.text('2026-07-21'), findsOneWidget);
    expect(find.text('Area perlu segera diperbaiki'), findsOneWidget);
    expect(find.text('Hydrant Inspector'), findsOneWidget);
    expect(find.text('Selang perlu diganti'), findsOneWidget);
    expect(find.text('Inspector / Pelapor'), findsNWidgets(2));
    expect(find.text('Remark'), findsNWidgets(2));
    expect(find.text('INC-TEST-001'), findsNothing);
    expect(find.text('Ruang Produksi'), findsNothing);
  });

  testWidgets('Menu Inspection membuka list inspection',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: _FakeApiService()),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: MaterialApp(
          home: const HomeScreen(),
          routes: {
            '/incidents': (_) => const IncidentListScreen(),
            '/incident-form': (_) => const IncidentFormScreen(),
          },
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byTooltip('Open navigation menu'));
    await tester.pumpAndSettle();
    expect(find.text('Scan QR'), findsNothing);
    expect(find.text('Checklist'), findsNothing);
    await tester.ensureVisible(find.text('Inspection'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Inspection'));
    await tester.pumpAndSettle();

    expect(find.byType(IncidentListScreen), findsOneWidget);
    expect(find.text('Unsafe Condition'), findsOneWidget);
    expect(find.text('Ruang Produksi'), findsOneWidget);
    expect(find.text('Area perlu segera diperbaiki'), findsOneWidget);
    expect(find.text('2026-07-21 • 10:30'), findsOneWidget);
  });

  testWidgets('Fire Hydrant List hanya menampilkan tiga field',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: _FakeApiService()),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(home: FireHydrantScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Location'), findsOneWidget);
    expect(find.text('3 - Area Hydrant'), findsOneWidget);
    expect(find.text('Inspected By'), findsOneWidget);
    expect(find.text('2 - Hydrant Inspector'), findsOneWidget);
    expect(find.text('Inspection Date'), findsOneWidget);
    expect(find.text('2026-07-22'), findsOneWidget);
    expect(find.text('FH-TEST-001'), findsNothing);
    expect(find.text('1 item'), findsNothing);
    expect(find.byIcon(Icons.edit_outlined), findsNothing);

    await tester.tap(find.text('3 - Area Hydrant'));
    await tester.pumpAndSettle();

    expect(find.text('Inspection Data'), findsNothing);
    expect(find.text('Fire Hydrant Items'), findsNothing);
    expect(find.text('FH-TEST-001'), findsNothing);
    expect(find.text('Item 1'), findsNothing);
    expect(find.text('Checklist'), findsOneWidget);
    expect(find.text('Hose'), findsOneWidget);
    expect(find.text('Extra Coupling'), findsOneWidget);
    expect(find.text('OK'), findsNothing);
    expect(find.text('Need Correction'), findsNothing);
    expect(find.byIcon(Icons.check_box_rounded), findsNWidgets(3));
    expect(
      find.byIcon(Icons.check_box_outline_blank_rounded),
      findsNWidgets(3),
    );
  });

  testWidgets('Form awal Fire Hydrant hanya menampilkan tanggal dan location',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: _FakeApiService(),
        child: const MaterialApp(home: FireHydrantSetupScreen()),
      ),
    );
    await tester.pumpAndSettle();

    final dateField = find.byType(TextFormField).first;
    final editableDate = tester.widget<EditableText>(
      find.descendant(of: dateField, matching: find.byType(EditableText)),
    );
    expect(editableDate.readOnly, isTrue);
    expect(find.text('Inspection Date'), findsOneWidget);
    expect(find.text('Location'), findsOneWidget);
    expect(find.text('Add Form'), findsOneWidget);
    expect(find.byType(DropdownButtonFormField<int>), findsOneWidget);

    await tester.tap(find.text('Add Form'));
    await tester.pump();
    expect(find.text('Pilih location terlebih dahulu.'), findsOneWidget);
  });

  testWidgets('QR Fire Hydrant mengisi Hydrant Detail otomatis',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: _FakeApiService()),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(
          home: FireHydrantCreateScreen(
            inspectionDate: '2026-07-24',
            locationId: 1,
            locationName: 'Area Produksi',
            scannedQr: 'POINT-7-TEST',
            point: {
              'id': 7,
              'name_point': 'FH-007',
              'ket1': 'Hydrant Gedung A',
              'ket2': 'Lantai 1 dekat pintu utama',
            },
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Hydrant Detail'), findsOneWidget);
    expect(find.text('QR Scanned'), findsOneWidget);
    expect(find.text('FH-007'), findsOneWidget);
    expect(find.text('Hydrant Gedung A'), findsOneWidget);
    expect(find.text('Lantai 1 dekat pintu utama'), findsOneWidget);
    expect(find.text('YES'), findsNWidgets(6));
    expect(find.text('NO'), findsNWidgets(6));
    expect(find.text('Notes'), findsNothing);
  });
}

class _FakeApiService extends ApiService {
  @override
  Future<List<dynamic>> getFireHydrants() async => [
        {
          'id': 2,
          'reference_no': 'FH-TEST-001',
          'inspection_date': '2026-07-22',
          'location': {'id_location': 3, 'name': 'Area Hydrant'},
          'inspector': {'id': 2, 'name': 'Hydrant Inspector'},
          'items': [
            {
              'hydrant_number': 'FH-007',
              'name': 'Hydrant Gedung A',
              'remark': 'Selang perlu diganti',
              'hose_condition': true,
              'nozzle_condition': false,
              'coupling_condition': true,
              'wrench_condition': false,
              'valve_condition': true,
              'coupling_extra_condition': false,
            },
          ],
        },
      ];

  @override
  Future<Map<String, dynamic>> getFireHydrant(int id) async {
    final inspections = await getFireHydrants();
    return Map<String, dynamic>.from(inspections.first as Map);
  }

  @override
  Future<List<dynamic>> getFireExtinguishers() async => [];

  @override
  Future<List<dynamic>> getFireAlarms() async => [];

  @override
  Future<List<dynamic>> getEsEw() async => [];

  @override
  Future<List<dynamic>> getUsers() async => [];

  @override
  Future<List<dynamic>> getPoints() async => [];

  @override
  Future<List<dynamic>> getFireHydrantLocations() async => [
        {'id_location': 1, 'name': 'Area Produksi'},
      ];

  @override
  Future<List<dynamic>> getIncidents() async => [
        {
          'id': 1,
          'reference_no': 'INC-TEST-001',
          'incident_date': '2026-07-21',
          'incident_time': '10:30:00',
          'location_text': 'Ruang Produksi',
          'status': 'reported',
          'description': 'Area perlu segera diperbaiki',
          'reporter': {'id': 1, 'name': 'Inspector Test'},
          'incident_type': {'id': 1, 'name': 'Unsafe Condition'},
        },
      ];

  @override
  Future<List<dynamic>> getIncidentTypes() async => [
        {'id': 1, 'name': 'Unsafe Condition', 'is_active': true},
      ];
}
