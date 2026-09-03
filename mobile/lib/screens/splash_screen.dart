import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/connectivity_service.dart';
import '../services/offline_storage_service.dart';
import '../services/sync_service.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    final connectivity = context.read<ConnectivityService>();
    final sync = context.read<SyncService>();
    final auth = context.read<AuthService>();
    final navigator = Navigator.of(context);

    await OfflineStorageService.instance.init();
    await connectivity.init();
    await auth.init(offlineMode: !connectivity.isOnline);
    await sync.init();

    if (!mounted) return;

    Future.delayed(const Duration(seconds: 2), () {
      if (auth.isLoggedIn) {
        navigator.pushReplacementNamed('/home');
      } else {
        navigator.pushReplacementNamed('/login');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: colors.primary,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.health_and_safety, size: 80, color: Colors.white),
            const SizedBox(height: 20),
            const Text(
              'SHE Inspection',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Safety, Health & Environment',
              style: TextStyle(
                fontSize: 14,
                color: Colors.white.withValues(alpha: 0.8),
              ),
            ),
            const SizedBox(height: 40),
            const CircularProgressIndicator(color: Colors.white),
          ],
        ),
      ),
    );
  }
}
