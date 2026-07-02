import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/api_service.dart';
import 'services/auth_service.dart';
import 'screens/splash_screen.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/inspection/fire_hydrant_screen.dart';
import 'screens/inspection/fire_extinguisher_screen.dart';
import 'screens/inspection/fire_alarm_screen.dart';
import 'screens/inspection/es_ew_screen.dart';
import 'screens/inspection/checklist_screen.dart';
import 'screens/incident/incident_list_screen.dart';
import 'screens/incident/incident_form_screen.dart';
import 'screens/qr_scanner_screen.dart';
import 'screens/profile_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthService()),
        ChangeNotifierProvider(create: (_) => ApiService()),
      ],
      child: MaterialApp(
        title: 'SHE Inspection',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(0xFF1A56DB),
            brightness: Brightness.light,
          ),
          useMaterial3: true,
          appBarTheme: const AppBarTheme(
            centerTitle: true,
            elevation: 0,
          ),
        ),
        initialRoute: '/splash',
        routes: {
          '/splash': (context) => const SplashScreen(),
          '/login': (context) => const LoginScreen(),
          '/home': (context) => const HomeScreen(),
          '/profile': (context) => const ProfileScreen(),
          '/qr-scanner': (context) => const QrScannerScreen(),
          '/fire-hydrant': (context) => const FireHydrantScreen(),
          '/fire-extinguisher': (context) => const FireExtinguisherScreen(),
          '/fire-alarm': (context) => const FireAlarmScreen(),
          '/es-ew': (context) => const EsEwScreen(),
          '/checklist': (context) => const ChecklistScreen(),
          '/incidents': (context) => const IncidentListScreen(),
          '/incident-form': (context) => const IncidentFormScreen(),
        },
      ),
    );
  }
}