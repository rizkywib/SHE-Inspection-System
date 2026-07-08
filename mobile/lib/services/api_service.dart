import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService extends ChangeNotifier {
  late String baseUrl;
  static const Duration _requestTimeout = Duration(seconds: 15);
  
  ApiService() {
    // Default to localhost for USB debugging with:
    // adb reverse tcp:8000 tcp:8000
    baseUrl = const String.fromEnvironment(
      'API_URL',
      defaultValue: 'http://127.0.0.1:8000/api',
    );
  }
  
  static String? _token;

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
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'username': email, 'password': password}),
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      try {
        return jsonDecode(response.body);
      } catch (_) {
        return {'error': 'Server error (${response.statusCode})'};
      }
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> register(
      String name, String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'name': name, 'email': email, 'password': password}),
    );
    if (response.statusCode >= 400) {
      try {
        return jsonDecode(response.body);
      } catch (_) {
        return {'error': 'Server error (${response.statusCode})'};
      }
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> getProfile() async {
    final response = await http.get(
      Uri.parse('$baseUrl/auth/me'),
      headers: headers,
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      try {
        return jsonDecode(response.body);
      } catch (_) {
        return {'error': 'Server error (${response.statusCode})'};
      }
    }
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
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    return jsonDecode(response.body)['data'] ?? [];
  }

  Future<Map<String, dynamic>> createFireHydrant(
      Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fire-hydrants'),
      headers: headers,
      body: jsonEncode(data),
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> updateFireHydrant(
      int id, Map<String, dynamic> data) async {
    final response = await http.put(
      Uri.parse('$baseUrl/fire-hydrants/$id'),
      headers: headers,
      body: jsonEncode(data),
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> deleteFireHydrant(int id) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/fire-hydrants/$id'),
      headers: headers,
    ).timeout(_requestTimeout);
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
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

  String _errorMessage(http.Response response) {
    try {
      final body = jsonDecode(response.body);
      if (body is Map<String, dynamic>) {
        if (body['message'] != null) return body['message'].toString();
        if (body['error'] != null) return body['error'].toString();
        if (body['errors'] is Map && (body['errors'] as Map).isNotEmpty) {
          final first = (body['errors'] as Map).values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
          return first.toString();
        }
      }
    } catch (_) {}
    return 'Server error (${response.statusCode})';
  }
}
