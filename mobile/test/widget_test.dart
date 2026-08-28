import 'dart:io';

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
import 'package:she_inspection_mobile/screens/inspection/es_ew_create_flow.dart';
import 'package:she_inspection_mobile/screens/inspection/es_ew_screen.dart';
import 'package:she_inspection_mobile/screens/permit_matrix/permit_matrix_form_screen.dart';
import 'package:she_inspection_mobile/screens/permit_matrix/permit_matrix_screen.dart';
import 'package:she_inspection_mobile/screens/safety_talk/safety_talk_form_screen.dart';
import 'package:she_inspection_mobile/screens/safety_talk/safety_talk_screen.dart';
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
    expect(find.text('Foto Temuan Awal *'), findsOneWidget);
    expect(find.text('Upload Gambar'), findsOneWidget);
    expect(find.text('Perbaikan'), findsOneWidget);
    expect(find.text('Upload Perbaikan'), findsOneWidget);
    expect(find.text('Simpan'), findsOneWidget);
  });

  testWidgets('Dashboard menampilkan hasil Inspection',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3000);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
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

    expect(find.text('Dashboard'), findsOneWidget);
    expect(find.text('Akses Cepat'), findsOneWidget);
    expect(find.text('Total Data'), findsOneWidget);
    expect(find.text('Tindak Lanjut'), findsOneWidget);
    expect(find.text('Selesai'), findsWidgets);
    expect(find.text('Aktivitas Terbaru'), findsOneWidget);
    expect(find.text('Safety Talk'), findsOneWidget);
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
    await tester.ensureVisible(find.text('Permit Matrix'));
    expect(find.text('Permit Matrix'), findsOneWidget);
    await tester.ensureVisible(find.text('Safety Talk/Training'));
    expect(find.text('Safety Talk/Training'), findsOneWidget);
    final inspectionMenu = find.descendant(
      of: find.byType(Drawer),
      matching: find.text('Inspection'),
    );
    await tester.ensureVisible(inspectionMenu);
    await tester.pumpAndSettle();
    await tester.tap(inspectionMenu);
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

    expect(find.text('FE-TEST-001'), findsNothing);
    expect(find.text('8 - Area APAR'), findsOneWidget);
    expect(find.text('2026-07-23'), findsOneWidget);

    await tester.tap(find.text('8 - Area APAR'));
    await tester.pumpAndSettle();

    expect(find.text('FE-TEST-001'), findsOneWidget);
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

  testWidgets('ES/EW List membuka detail, Edit, dan Delete',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2600);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakeEsEwApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(home: EsEwScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Area 4'), findsOneWidget);
    expect(find.text('ES/EW Inspector'), findsOneWidget);
    expect(find.text('2026-08-04'), findsOneWidget);

    await tester.tap(find.text('Area 4'));
    await tester.pumpAndSettle();
    expect(find.text('ESEW-TEST-001'), findsOneWidget);
    expect(find.text('ES & EW - 54'), findsOneWidget);
    expect(find.text('Water Flow ES'), findsOneWidget);
    expect(find.byIcon(Icons.edit_outlined), findsOneWidget);

    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    expect(find.text('Edit ES/EW Inspection'), findsOneWidget);
    expect(find.text('ES/EW Point'), findsOneWidget);
    expect(find.text('Delete'), findsOneWidget);
    await tester.ensureVisible(find.text('Save'));
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(api.updateCalls, 1);

    await tester.longPress(find.text('Area 4'));
    await tester.pumpAndSettle();
    expect(find.text('Delete ES/EW Inspection?'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Delete'));
    await tester.pumpAndSettle();
    expect(api.deleteCalls, 1);
  });

  testWidgets('Form awal ES/EW mempertahankan Scan QR di awal',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: _FakeEsEwApiService(),
        child: const MaterialApp(home: EsEwSetupScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Inspection Date'), findsOneWidget);
    expect(find.text('Area'), findsOneWidget);
    expect(find.text('Scan QR Code'), findsOneWidget);
    expect(find.byType(DropdownButtonFormField<int>), findsOneWidget);

    await tester.tap(find.text('Scan QR Code'));
    await tester.pump();
    expect(find.text('Pilih area terlebih dahulu.'), findsOneWidget);
  });

  testWidgets('QR ES/EW mengisi Point dan menyimpan semua kondisi',
      (WidgetTester tester) async {
    final api = _FakeEsEwApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
        ],
        child: const MaterialApp(
          home: EsEwCreateScreen(
            inspectionDate: '2026-08-05',
            areaId: 14,
            areaName: 'Area 4',
            scannedQr: 'POINT-2275-TEST',
            point: {
              'id': 2275,
              'name_point': 'ES & EW - 54',
              'ket1': 'UTILITY ICW EOB3',
              'ket2': '732',
            },
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('ES/EW Detail'), findsOneWidget);
    expect(find.text('QR Scanned'), findsOneWidget);
    expect(find.text('ES & EW - 54'), findsOneWidget);
    expect(find.text('UTILITY ICW EOB3'), findsOneWidget);
    expect(find.text('732'), findsOneWidget);
    expect(find.text('YES'), findsNWidgets(11));
    expect(find.text('NO'), findsNWidgets(11));

    await tester.ensureVisible(find.text('Save'));
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(api.createCalls, 1);
    expect(api.lastCreateFields['point_id'], '2275');
    for (final field in [
      'water_flow_es',
      'water_flow_ew',
      'water_condition',
      'actual_valve_es',
      'actual_valve_ew',
      'physical_condition_es',
      'physical_condition_ew',
      'sign_board_condition',
      'housekeeping_condition',
      'road_access_condition',
      'sewer_condition',
    ]) {
      expect(api.lastCreateFields['items[0][$field]'], '1');
    }
  });

  testWidgets('Permit Matrix List membuka detail, Edit, dan Delete',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3000);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakePermitMatrixApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>.value(
            value: _FakeAuthService(),
          ),
        ],
        child: const MaterialApp(home: PermitMatrixScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('No. Permit PM-TEST-001'), findsOneWidget);
    expect(find.text('Contractor Test'), findsOneWidget);
    expect(find.text('Ada Temuan'), findsOneWidget);

    await tester.tap(find.text('No. Permit PM-TEST-001'));
    await tester.pumpAndSettle();
    expect(find.text('Permit PM-TEST-001'), findsOneWidget);
    expect(find.text('Job Performance'), findsOneWidget);
    expect(find.text('Pekerjaan pengelasan'), findsOneWidget);
    expect(find.byIcon(Icons.edit_outlined), findsOneWidget);

    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    expect(find.text('Edit Permit Matrix'), findsOneWidget);
    expect(find.text('No. Permit *'), findsOneWidget);
    expect(find.byKey(const ValueKey('permit_delete')), findsOneWidget);
    await tester.ensureVisible(find.byKey(const ValueKey('permit_save')));
    await tester.tap(find.byKey(const ValueKey('permit_save')));
    await tester.pumpAndSettle();
    expect(api.updateCalls, 1);

    await tester.longPress(find.text('No. Permit PM-TEST-001'));
    await tester.pumpAndSettle();
    expect(find.text('Hapus Permit Matrix?'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Hapus'));
    await tester.pumpAndSettle();
    expect(api.deleteCalls, 1);
  });

  testWidgets('Tambah Permit Matrix memvalidasi master dan menyimpan data',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3000);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakePermitMatrixApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<ApiService>.value(value: api),
          ChangeNotifierProvider<AuthService>.value(
            value: _FakeAuthService(),
          ),
        ],
        child: const MaterialApp(home: PermitMatrixFormScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Tambah Permit Matrix'), findsOneWidget);
    expect(find.text('System Admin'), findsOneWidget);
    expect(find.text('Temuan Terkait Safe Work Permit'), findsOneWidget);

    Future<void> selectOption(String key, String option) async {
      final field = find.descendant(
        of: find.byKey(ValueKey(key)),
        matching: find.byType(DropdownButtonFormField<int>),
      );
      await tester.ensureVisible(field);
      await tester.tap(field);
      await tester.pumpAndSettle();
      await tester.tap(find.text(option).last);
      await tester.pumpAndSettle();
    }

    await tester.enterText(
      find.byKey(const ValueKey('permit_number')),
      'PM-NEW-001',
    );
    await selectOption('permit_type_id', 'HOT WORK');
    await selectOption('supervision_area_id', 'A1 - Area Produksi');
    await selectOption('main_area_id', 'EOB1');
    await selectOption('sub_area_id-1', 'METHYLESTER');
    final jpField = find.descendant(
      of: find.byKey(const ValueKey('job_performance')),
      matching: find.byType(DropdownButtonFormField<String>),
    );
    await tester.ensureVisible(jpField);
    await tester.tap(jpField);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Pengelasan pipa').last);
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const ValueKey('section_equipment')),
      'Tank 101',
    );
    await tester.enterText(
      find.byKey(const ValueKey('authorized_craftman')),
      'Craftman A',
    );
    await tester.enterText(
      find.byKey(const ValueKey('authorized_facility')),
      'Facility A',
    );
    await tester.enterText(
      find.byKey(const ValueKey('contractor_name')),
      'Contractor Baru',
    );
    await tester.enterText(
      find.byKey(const ValueKey('work_description')),
      'Perbaikan pipa proses',
    );
    await tester.enterText(
      find.byKey(const ValueKey('permit_findings')),
      'APD perlu dilengkapi',
    );

    await tester.ensureVisible(find.byKey(const ValueKey('permit_save')));
    await tester.tap(find.byKey(const ValueKey('permit_save')));
    await tester.pumpAndSettle();
    expect(api.createCalls, 1);
    expect(api.lastCreateData['permit_number'], 'PM-NEW-001');
    expect(api.lastCreateData['main_area_id'], 1);
    expect(api.lastCreateData['sub_area_id'], 11);
    expect(api.lastCreateData['job_performance'], 'Pengelasan pipa');
    expect(api.lastCreateData['permit_findings'], 'APD perlu dilengkapi');
  });

  testWidgets('Safety Talk List membuka detail, Edit, dan Delete',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3000);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakeSafetyTalkApiService();
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: api,
        child: const MaterialApp(home: SafetyTalkScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Safety Speaker'), findsOneWidget);
    expect(find.text('Lock Out Tag Out'), findsOneWidget);
    expect(find.text('12 peserta'), findsOneWidget);
    expect(find.text('20 menit'), findsOneWidget);

    await tester.tap(find.text('Lock Out Tag Out'));
    await tester.pumpAndSettle();
    expect(find.text('Detail Safety Talk/Training'), findsOneWidget);
    expect(find.text('Peserta'), findsOneWidget);
    expect(find.text('Dibuat Oleh'), findsOneWidget);
    expect(find.byIcon(Icons.edit_outlined), findsOneWidget);

    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    expect(find.text('Edit Safety Talk/Training'), findsOneWidget);
    expect(find.byKey(const ValueKey('safety_delete')), findsOneWidget);
    await tester.ensureVisible(find.byKey(const ValueKey('safety_save')));
    await tester.tap(find.byKey(const ValueKey('safety_save')));
    await tester.pumpAndSettle();
    expect(api.updateCalls, 1);

    await tester.longPress(find.text('Lock Out Tag Out'));
    await tester.pumpAndSettle();
    expect(find.text('Hapus Safety Talk/Training?'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Hapus'));
    await tester.pumpAndSettle();
    expect(api.deleteCalls, 1);
  });

  testWidgets('Tambah Safety Talk menghitung peserta dan mengunggah foto',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3000);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final api = _FakeSafetyTalkApiService();
    await tester.pumpWidget(
      ChangeNotifierProvider<ApiService>.value(
        value: api,
        child: MaterialApp(
          home: SafetyTalkFormScreen(
            photoPicker: () async => File('safety-talk-test.jpg'),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Tambah Safety Talk/Training'), findsOneWidget);
    expect(find.text('Belum ada foto dipilih'), findsOneWidget);

    Future<void> selectSafetyOption(String key, String option) async {
      final field = find.byKey(ValueKey(key));
      await tester.ensureVisible(field);
      await tester.tap(field);
      await tester.pumpAndSettle();
      await tester.tap(find.text(option).last);
      await tester.pumpAndSettle();
    }

    await selectSafetyOption('safety_speaker_id', 'Safety Speaker');
    await tester.enterText(
      find.byKey(const ValueKey('safety_topic')),
      'Materi penggunaan APD',
    );
    await tester.enterText(
      find.byKey(const ValueKey('safety_ecogreen')),
      '3',
    );
    await tester.enterText(
      find.byKey(const ValueKey('safety_outsourcing')),
      '2',
    );
    await tester.enterText(
      find.byKey(const ValueKey('safety_contractor')),
      '1',
    );
    await tester.enterText(
      find.byKey(const ValueKey('safety_duration')),
      '15',
    );
    await selectSafetyOption('safety_area', 'Area 2');
    await tester.ensureVisible(find.byKey(const ValueKey('safety_photo')));
    await tester.tap(find.byKey(const ValueKey('safety_photo')));
    await tester.pumpAndSettle();
    expect(find.text('Ganti Foto'), findsOneWidget);
    expect(
      find.descendant(
        of: find.byKey(const ValueKey('safety_total')),
        matching: find.text('6'),
      ),
      findsOneWidget,
    );

    await tester.ensureVisible(find.byKey(const ValueKey('safety_save')));
    await tester.tap(find.byKey(const ValueKey('safety_save')));
    await tester.pumpAndSettle();
    expect(api.createCalls, 1);
    expect(api.lastCreateFields['speaker_id'], '62');
    expect(api.lastCreateFields['implementation_area'], '2');
    expect(api.lastCreateFields['topic'], 'Materi penggunaan APD');
    expect(api.lastCreatePhoto?.path, 'safety-talk-test.jpg');
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
  Future<Map<String, dynamic>> createFireExtinguisherWithPhotos(
    Map<String, String> fields, {
    File? photoBefore,
    File? photoAfter,
  }) async {
    createCalls++;
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> updateFireExtinguisher(
      int id, Map<String, dynamic> data) async {
    updateCalls++;
    return {'data': data};
  }

  @override
  Future<Map<String, dynamic>> updateFireExtinguisherWithPhotos(
    int id,
    Map<String, String> fields, {
    File? photoBefore,
    File? photoAfter,
  }) async {
    updateCalls++;
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> deleteFireExtinguisher(int id) async {
    deleteCalls++;
    return {'message': 'Deleted'};
  }
}

class _FakeEsEwApiService extends _FakeApiService {
  int createCalls = 0;
  int updateCalls = 0;
  int deleteCalls = 0;
  Map<String, String> lastCreateFields = {};

  Map<String, dynamic> get _inspection => {
        'id': 163,
        'reference_no': 'ESEW-TEST-001',
        'inspection_date': '2026-08-04',
        'area_id': 14,
        'inspector_id': 62,
        'status': 'draft',
        'area': {'id': 14, 'name': 'Area 4'},
        'inspector': {'id': 62, 'name': 'ES/EW Inspector'},
        'items': [
          {
            'id': 165,
            'name': 'ES & EW - 54',
            'type': 'UTILITY ICW EOB3',
            'location_detail': '732',
            'water_flow_es': true,
            'water_flow_ew': true,
            'water_condition': true,
            'actual_valve_es': true,
            'actual_valve_ew': false,
            'physical_condition_es': true,
            'physical_condition_ew': true,
            'sign_board_condition': true,
            'housekeeping_condition': true,
            'road_access_condition': true,
            'sewer_condition': true,
            'remark': 'Testing Area 4',
          },
        ],
      };

  @override
  Future<List<dynamic>> getEsEw() async => [_inspection];

  @override
  Future<Map<String, dynamic>> getEsEwInspection(int id) async => _inspection;

  @override
  Future<Map<String, dynamic>> getEsEwMasterData() async => {
        'areas': [
          {'id': 14, 'name': 'Area 4'},
        ],
        'inspectors': [
          {'id': 62, 'name': 'ES/EW Inspector'},
        ],
        'points': await getPoints(),
      };

  @override
  Future<List<dynamic>> getPoints() async => [
        {
          'id': 2275,
          'name_point': 'ES & EW - 54',
          'ket1': 'UTILITY ICW EOB3',
          'ket2': '732',
          'qr_code': 'POINT-2275-TEST',
        },
      ];

  @override
  Future<Map<String, dynamic>> createEsEw(
    Map<String, String> fields, {
    File? eyeWashPhoto,
    File? emergencyShowerPhoto,
  }) async {
    createCalls++;
    lastCreateFields = Map<String, String>.from(fields);
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> updateEsEw(
    int id,
    Map<String, String> fields, {
    File? eyeWashPhoto,
    File? emergencyShowerPhoto,
  }) async {
    updateCalls++;
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> deleteEsEw(int id) async {
    deleteCalls++;
    return {'message': 'Deleted'};
  }
}

class _FakePermitMatrixApiService extends _FakeApiService {
  int createCalls = 0;
  int updateCalls = 0;
  int deleteCalls = 0;
  Map<String, dynamic> lastCreateData = {};

  Map<String, dynamic> get _inspection => {
        'id': 57,
        'permit_date': '2026-08-05',
        'inspector_id': 62,
        'permit_number': 'PM-TEST-001',
        'permit_type_id': 1,
        'supervision_area_id': 1,
        'main_area_id': 1,
        'sub_area_id': 11,
        'section_equipment': 'Tank 101',
        'job_performance': 'Pekerjaan pengelasan',
        'authorized_craftman': 'Craftman Test',
        'authorized_facility': 'Facility Test',
        'contractor_name': 'Contractor Test',
        'work_description': 'Perbaikan pipa proses',
        'permit_findings': 'APD perlu dilengkapi',
        'finding_status': 'Ada Temuan',
        'inspector': {'id': 62, 'name': 'System Admin'},
        'permit_type': {'id': 1, 'name': 'HOT WORK'},
        'supervision_area': {
          'id': 1,
          'code': 'A1',
          'name': 'Area Produksi',
        },
        'main_area': {'id': 1, 'name': 'EOB1'},
        'sub_area': {'id': 11, 'main_area_id': 1, 'name': 'METHYLESTER'},
      };

  @override
  Future<List<dynamic>> getPermitMatrix() async => [_inspection];

  @override
  Future<Map<String, dynamic>> getPermitMatrixInspection(int id) async =>
      _inspection;

  @override
  Future<Map<String, dynamic>> getPermitMatrixMasterData() async => {
        'inspectors': [
          {'id': 62, 'name': 'System Admin'},
        ],
        'permit_types': [
          {'id': 1, 'name': 'HOT WORK'},
          {'id': 2, 'name': 'COOL WORK'},
        ],
        'supervision_areas': [
          {'id': 1, 'code': 'A1', 'name': 'Area Produksi'},
        ],
        'main_areas': [
          {'id': 1, 'name': 'EOB1'},
          {'id': 2, 'name': 'EOB2'},
        ],
        'sub_areas': [
          {'id': 11, 'main_area_id': 1, 'name': 'METHYLESTER'},
          {'id': 12, 'main_area_id': 2, 'name': 'FATTY ACID'},
        ],
        'job_performances': [
          {'id': 1, 'name': 'Pekerjaan pengelasan'},
          {'id': 2, 'name': 'Pengelasan pipa'},
        ],
      };

  @override
  Future<Map<String, dynamic>> createPermitMatrix(
    Map<String, dynamic> data,
  ) async {
    createCalls++;
    lastCreateData = Map<String, dynamic>.from(data);
    return {'data': data};
  }

  @override
  Future<Map<String, dynamic>> updatePermitMatrix(
    int id,
    Map<String, dynamic> data,
  ) async {
    updateCalls++;
    return {'data': data};
  }

  @override
  Future<Map<String, dynamic>> deletePermitMatrix(int id) async {
    deleteCalls++;
    return {'message': 'Deleted'};
  }
}

class _FakeSafetyTalkApiService extends _FakeApiService {
  int createCalls = 0;
  int updateCalls = 0;
  int deleteCalls = 0;
  Map<String, String> lastCreateFields = {};
  File? lastCreatePhoto;

  Map<String, dynamic> get _training => {
        'id': 72,
        'speaker_id': 62,
        'implementation_date': '2026-08-05',
        'topic': 'Lock Out Tag Out',
        'ecogreen_participants': 5,
        'outsourcing_participants': 4,
        'contractor_participants': 3,
        'total_participants': 12,
        'duration_minutes': 20,
        'implementation_area': 2,
        'activity_photo_url': '',
        'speaker': {'id': 62, 'name': 'Safety Speaker'},
        'creator': {'id': 62, 'name': 'System Admin'},
        'created_at': '2026-08-05T08:00:00.000000Z',
        'updated_at': '2026-08-05T09:00:00.000000Z',
      };

  @override
  Future<List<dynamic>> getSafetyTalkTrainings() async => [_training];

  @override
  Future<Map<String, dynamic>> getSafetyTalkTraining(int id) async => _training;

  @override
  Future<Map<String, dynamic>> getSafetyTalkMasterData() async => {
        'speakers': [
          {'id': 62, 'name': 'Safety Speaker'},
          {'id': 63, 'name': 'Trainer Dua'},
        ],
        'areas': [1, 2, 3, 4, 5, 6],
      };

  @override
  Future<Map<String, dynamic>> createSafetyTalkTraining(
    Map<String, String> fields,
    File activityPhoto,
  ) async {
    createCalls++;
    lastCreateFields = Map<String, String>.from(fields);
    lastCreatePhoto = activityPhoto;
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> updateSafetyTalkTraining(
    int id,
    Map<String, String> fields, {
    File? activityPhoto,
  }) async {
    updateCalls++;
    return {'data': fields};
  }

  @override
  Future<Map<String, dynamic>> deleteSafetyTalkTraining(int id) async {
    deleteCalls++;
    return {'message': 'Deleted'};
  }
}

class _FakeAuthService extends AuthService {
  @override
  Map<String, dynamic>? get user => {
        'id': 62,
        'name': 'System Admin',
        'email': 'admin@example.com',
        'role': 'super_admin',
        'permissions': <String>[],
      };
}
