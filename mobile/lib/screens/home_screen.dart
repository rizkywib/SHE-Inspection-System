import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final user = auth.user;

    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        title: const Text('SHE Inspection'),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner),
            onPressed: () => Navigator.pushNamed(context, '/qr-scanner'),
          ),
          IconButton(
            icon: const Icon(Icons.person),
            onPressed: () => Navigator.pushNamed(context, '/profile'),
          ),
        ],
      ),
      drawer: _buildDrawer(context, user),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Welcome Card
            Card(
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF1A56DB), Color(0xFF1E40AF)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.all(Radius.circular(12)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Welcome, ${user?['name'] ?? 'Inspector'}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      user?['email'] ?? '',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Quick Actions
            const Text(
              'Quick Actions',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            // Scan QR Card
            _buildActionCard(
              context,
              icon: Icons.qr_code_scanner,
              title: 'Scan QR Code',
              subtitle: 'Check-in to inspection point',
              color: const Color(0xFF1A56DB),
              onTap: () => Navigator.pushNamed(context, '/qr-scanner'),
            ),
            const SizedBox(height: 12),

            // Inspection Modules
            const Text(
              'Inspections',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.water_damage,
                    title: 'Fire\nHydrant',
                    color: const Color(0xFFDC2626),
                    route: '/fire-hydrant',
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.fire_extinguisher,
                    title: 'Fire\nExtinguisher',
                    color: const Color(0xFFEA580C),
                    route: '/fire-extinguisher',
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.notifications_active,
                    title: 'Fire\nAlarm',
                    color: const Color(0xFFD97706),
                    route: '/fire-alarm',
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.shower,
                    title: 'ES/EW',
                    color: const Color(0xFF059669),
                    route: '/es-ew',
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            _buildMenuCard(
              context,
              icon: Icons.checklist,
              title: 'General Checklist',
              color: const Color(0xFF7C3AED),
              route: '/checklist',
              fullWidth: true,
            ),
            const SizedBox(height: 20),

            // Incident Section
            const Text(
              'Incidents',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.report_problem,
                    title: 'Report\nIncident',
                    color: const Color(0xFFB91C1C),
                    route: '/incident-form',
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildMenuCard(
                    context,
                    icon: Icons.list_alt,
                    title: 'Incident\nList',
                    color: const Color(0xFF1D4ED8),
                    route: '/incidents',
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionCard(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 2,
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withValues(alpha: 0.1),
          child: Icon(icon, color: color),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: onTap,
      ),
    );
  }

  Widget _buildMenuCard(
    BuildContext context, {
    required IconData icon,
    required String title,
    required Color color,
    required String route,
    bool fullWidth = false,
  }) {
    return Card(
      elevation: 2,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => Navigator.pushNamed(context, route),
        child: Container(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Icon(icon, size: 32, color: color),
              const SizedBox(height: 8),
              Text(
                title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDrawer(BuildContext context, dynamic user) {
    return Drawer(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          DrawerHeader(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF1A56DB), Color(0xFF1E40AF)],
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const CircleAvatar(
                  radius: 30,
                  child: Icon(Icons.person, size: 30),
                ),
                const SizedBox(height: 12),
                Text(
                  user?['name'] ?? 'User',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  user?['email'] ?? '',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.8),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          ListTile(
            leading: const Icon(Icons.dashboard),
            title: const Text('Dashboard'),
            onTap: () => Navigator.pop(context),
          ),
          ListTile(
            leading: const Icon(Icons.qr_code_scanner),
            title: const Text('Scan QR'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/qr-scanner');
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.water_damage),
            title: const Text('Fire Hydrant'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-hydrant');
            },
          ),
          ListTile(
            leading: const Icon(Icons.fire_extinguisher),
            title: const Text('Fire Extinguisher'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-extinguisher');
            },
          ),
          ListTile(
            leading: const Icon(Icons.notifications_active),
            title: const Text('Fire Alarm'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/fire-alarm');
            },
          ),
          ListTile(
            leading: const Icon(Icons.shower),
            title: const Text('ES/EW'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/es-ew');
            },
          ),
          ListTile(
            leading: const Icon(Icons.checklist),
            title: const Text('Checklist'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/checklist');
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.report_problem),
            title: const Text('Incidents'),
            onTap: () {
              Navigator.pop(context);
              Navigator.pushNamed(context, '/incidents');
            },
          ),
          ListTile(
            leading: const Icon(Icons.medical_services),
            title: const Text('Medical Reports'),
            onTap: () => Navigator.pop(context),
          ),
          const Spacer(),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: const Text('Logout', style: TextStyle(color: Colors.red)),
            onTap: () async {
              final auth = context.read<AuthService>();
              await auth.logout();
              if (context.mounted) {
                Navigator.pushReplacementNamed(context, '/login');
              }
            },
          ),
        ],
      ),
    );
  }
}