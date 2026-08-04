import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:she_inspection_mobile/main.dart';
import 'package:she_inspection_mobile/screens/home_screen.dart';
import 'package:she_inspection_mobile/screens/incident/incident_form_screen.dart';
import 'package:she_inspection_mobile/screens/incident/incident_list_screen.dart';
import 'package:she_inspection_mobile/screens/inspection/fire_extinguisher_create_screen.dart';
import 'package:she_inspection_mobile/screens/inspection/fire_extinguisher_screen.dart';
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

  testWidgets('Fire Extinguisher List membuka detail dengan aksi Edit',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakeFireExtinguisherApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(home: FireExtinguisherScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('FE-TEST-001'), findsOneWidget);
    expect(find.text('8 - Area APAR'), findsOneWidget);
    expect(find.text('2026-07-23'), findsOneWidget);

    await tester.tap(find.text('8 - Area APAR'));
    await tester.pumpAndSettle();

    expect(find.text('APAR-101'), findsOneWidget);
    expect(find.text('DC - SP - 9 Kg'), findsOneWidget);
    expect(find.text('Pressure'), findsOneWidget);
    expect(find.byIcon(Icons.edit_outlined), findsOneWidget);

    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    expect(find.text('Edit Fire Extinguisher'), findsOneWidget);
    expect(find.text('Reference Number'), findsOneWidget);
    expect(find.text('APAR Number'), findsOneWidget);
    expect(find.text('Delete'), findsOneWidget);
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(api.updateCalls, 1);

    await tester.longPress(find.text('8 - Area APAR'));
    await tester.pumpAndSettle();
    expect(find.text('Delete Fire Extinguisher?'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Delete'));
    await tester.pumpAndSettle();
    expect(api.deleteCalls, 1);
  });

  testWidgets('Form awal Fire Extinguisher mempertahankan Scan QR di awal',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: _FakeFireExtinguisherApiService(),
        child: const MaterialApp(home: FireExtinguisherCreateScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Inspection Date'), findsOneWidget);
    expect(find.text('Location'), findsOneWidget);
    expect(find.text('Scan QR Code'), findsOneWidget);
    expect(find.byType(DropdownButtonFormField<int>), findsOneWidget);

    await tester.tap(find.text('Scan QR Code'));
    await tester.pump();
    expect(find.text('Pilih lokasi APAR terlebih dahulu.'), findsOneWidget);
  });

  testWidgets('QR Fire Extinguisher mengisi detail APAR otomatis',
      (WidgetTester tester) async {
    final api = _FakeFireExtinguisherApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(
          home: FireExtinguisherQrFormScreen(
            inspectionDate: '2026-07-23',
            locationId: 8,
            locationName: 'Area APAR',
            scannedQr: 'POINT-101-TEST',
            point: {
              'id': 101,
              'name_point': 'APAR-101',
              'ket1': 'DC - SP - 9 Kg',
              'ket2': 'Gedung A dekat pintu utama',
            },
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Fire Extinguisher Detail'), findsOneWidget);
    expect(find.text('QR Scanned'), findsOneWidget);
    expect(find.text('APAR-101'), findsOneWidget);
    expect(find.text('DC - SP - 9 Kg'), findsOneWidget);
    expect(find.text('Gedung A dekat pintu utama'), findsOneWidget);
    expect(find.text('YES'), findsNWidgets(3));
    expect(find.text('NO'), findsNWidgets(3));

    await tester.ensureVisible(find.text('Save'));
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(api.createCalls, 1);
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

class _FakeFireExtinguisherApiService extends _FakeApiService {
  int createCalls = 0;
  int updateCalls = 0;
  int deleteCalls = 0;

  @override
  Future<List<dynamic>> getFireExtinguishers() async => [
        {
          'id': 3,
          'reference_no': 'FE-TEST-001',
          'inspection_date': '2026-07-23',
          'location_id': 8,
          'inspector_id': 2,
          'location': {'id_location': 8, 'name': 'Area APAR'},
          'inspector': {'id': 2, 'name': 'APAR Inspector'},
          'items': [
            {
              'id': 3,
              'name': 'APAR-101',
              'type': 'DC - SP - 9 Kg',
              'location_detail': 'Gedung A dekat pintu utama',
              'pressure_condition': true,
              'seal_condition': true,
              'nozzle_condition': false,
              'remark': 'Periksa nozzle',
            },
          ],
        },
      ];

  @override
  Future<Map<String, dynamic>> getFireExtinguisher(int id) async {
    final inspections = await getFireExtinguishers();
    return Map<String, dynamic>.from(inspections.first as Map);
  }

  @override
  Future<List<dynamic>> getPoints() async => [
        {
          'id': 101,
          'name_point': 'APAR-101',
          'ket1': 'DC - SP - 9 Kg',
          'ket2': 'Gedung A dekat pintu utama',
          'qr_code': 'POINT-101-TEST',
        },
      ];

  @override
  Future<List<dynamic>> getFireExtinguisherLocations() async => [
        {'id_location': 8, 'name': 'Area APAR'},
      ];

  @override
  Future<Map<String, dynamic>> createFireExtinguisher(
      Map<String, dynamic> data) async {
    createCalls++;
    return {'data': data};
  }

  @override
  Future<Map<String, dynamic>> updateFireExtinguisher(
      int id, Map<String, dynamic> data) async {
    updateCalls++;
    return {'data': data};
  }

  @override
  Future<Map<String, dynamic>> deleteFireExtinguisher(int id) async {
    deleteCalls++;
    return {'message': 'Deleted'};
  }
}
