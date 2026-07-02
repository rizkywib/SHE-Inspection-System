import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService extends ChangeNotifier {
  static const String baseUrl = 'http://10.0.2.2:8000/api'; // Android emulator
  String? _token;

  Map<String, String> get headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  void setToken(String? token) {
    _token = token;
    notifyListeners();
  }

  // Auth
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> register(
      String name, String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'name': name, 'email': email, 'password': password}),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> getProfile() async {
    final response = await http.get(
      Uri.parse('$baseUrl/auth/me'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  // Dashboard
  Future<Map<String, dynamic>> getDashboardStats() async {
    final response = await http.get(
      Uri.parse('$baseUrl/dashboard/stats'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  // QR Code
  Future<Map<String, dynamic>> scanQrCode(String qrData) async {
    final response = await http.post(
      Uri.parse('$baseUrl/qr-codes/scan'),
      headers: headers,
      body: jsonEncode({'qr_code': qrData}),
    );
    return jsonDecode(response.body);
  }

  // Fire Hydrant
  Future<List<dynamic>> getFireHydrants() async {
    final response = await http.get(
      Uri.parse('$baseUrl/fire-hydrants'),
      headers: headers,
    );
    return jsonDecode(response.body)['data'] ?? [];
  }

  Future<Map<String, dynamic>> createFireHydrant(
      Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fire-hydrants'),
      headers: headers,
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> checkinFireHydrant(
      int id, double lat, double lng) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fire-hydrants/$id/checkin'),
      headers: headers,
      body: jsonEncode({'checkin_lat': lat, 'checkin_lng': lng}),
    );
    return jsonDecode(response.body);
  }

  // Fire Extinguisher
  Future<List<dynamic>> getFireExtinguishers() async {
    final response = await http.get(
      Uri.parse('$baseUrl/fire-extinguishers'),
      headers: headers,
    );
    return jsonDecode(response.body)['data'] ?? [];
  }

  Future<Map<String, dynamic>> createFireExtinguisher(
      Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fire-extinguishers'),
      headers: headers,
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }

  // Fire Alarm
  Future<List<dynamic>> getFireAlarms() async {
    final response = await http.get(
      Uri.parse('$baseUrl/fire-alarms'),
      headers: headers,
    );
    return jsonDecode(response.body)['data'] ?? [];
  }

  // ES/EW
  Future<List<dynamic>> getEsEw() async {
    final response = await http.get(
      Uri.parse('$baseUrl/es-ew'),
      headers: headers,
    );
    return jsonDecode(response.body)['data'] ?? [];
  }

  // Incidents
  Future<List<dynamic>> getIncidents() async {
    final response = await http.get(
      Uri.parse('$baseUrl/incidents'),
      headers: headers,
    );
    return jsonDecode(response.body)['data'] ?? [];
  }

  Future<Map<String, dynamic>> createIncident(
      Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/incidents'),
      headers: headers,
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }

  // File Upload
  Future<Map<String, dynamic>> uploadFile(File file) async {
    var request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/upload'),
    );
    request.headers.addAll(headers);
    request.files.add(await http.MultipartFile.fromPath('file', file.path));
    var response = await request.send();
    var responseData = await response.stream.bytesToString();
    return jsonDecode(responseData);
  }
}