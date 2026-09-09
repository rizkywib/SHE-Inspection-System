import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'route_observer.dart';
import 'services/api_service.dart';
import 'services/auth_service.dart';
import 'services/connectivity_service.dart';
import 'services/sync_service.dart';
import 'theme/app_theme.dart';
import 'screens/splash_screen.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/inspection/fire_hydrant_screen.dart';
import 'screens/inspection/fire_extinguisher_screen.dart';
import 'screens/inspection/fire_extinguisher_create_screen.dart';
import 'screens/inspection/fire_alarm_screen.dart';
import 'screens/inspection/es_ew_screen.dart';
import 'screens/permit_matrix/permit_matrix_screen.dart';
import 'screens/safety_talk/safety_talk_screen.dart';
import 'screens/inspection/checklist_screen.dart';
import 'screens/incident/incident_list_screen.dart';
import 'screens/incident/incident_form_screen.dart';
import 'screens/qr_scanner_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/master_data/master_data_config.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
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
        ChangeNotifierProvider(create: (_) => ConnectivityService()),
        ChangeNotifierProvider(
          create: (context) => SyncService(
            api: context.read<ApiService>(),
            connectivity: context.read<ConnectivityService>(),
          ),
        ),
      ],
      child: MaterialApp(
        title: 'SHE Inspection',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light,
        initialRoute: '/splash',
        navigatorObservers: [routeObserver],
        onGenerateRoute: (settings) {
          Widget page;
          String? idArg;
          if (settings.arguments is String) {
            idArg = settings.arguments as String;
          }

          switch (settings.name) {
            case '/splash':
              page = const SplashScreen();
              break;
            case '/login':
              page = const LoginScreen();
              break;
            case '/home':
              page = const HomeScreen();
              break;
            case '/profile':
              page = const ProfileScreen();
              break;
            case '/qr-scanner':
              page = const QrScannerScreen();
              break;
            case '/fire-hydrant':
              page = FireHydrantScreen(initialId: idArg);
              break;
            case '/fire-extinguisher':
              page = const FireExtinguisherScreen();
              break;
            case '/fire-extinguisher-create':
              page = const FireExtinguisherCreateScreen();
              break;
            case '/fire-alarm':
              page = const FireAlarmScreen();
              break;
            case '/es-ew':
              page = EsEwScreen(initialId: idArg);
              break;
            case '/permit-matrix':
              page = PermitMatrixScreen(initialId: idArg);
              break;
            case '/safety-talk-training':
              page = SafetyTalkScreen(initialId: idArg);
              break;
            case '/checklist':
              page = const ChecklistScreen();
              break;
            case '/incidents':
              page = const IncidentListScreen();
              break;
            case '/incident-form':
              page = IncidentFormScreen(
                inspection: settings.arguments is Map<String, dynamic>
                    ? settings.arguments as Map<String, dynamic>
                    : null,
              );
              break;
            case '/master-hydrant-locations':
              page = hydrantLocationsScreen();
              break;
            case '/master-incident-types':
              page = incidentTypesScreen();
              break;
            case '/master-es-ew-areas':
              page = esEwAreasScreen();
              break;
            case '/master-fire-alarm-locations':
              page = fireAlarmLocationsScreen();
              break;
            case '/master-users':
              page = usersScreen();
              break;
            default:
              page = const HomeScreen();
          }
          return MaterialPageRoute(builder: (_) => page, settings: settings);
        },
      ),
    );
  }
}
