import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService extends ChangeNotifier {
  late String baseUrl;
  static const Duration _requestTimeout = Duration(seconds: 15);
  static const String _defaultBaseUrl = 'http://127.0.0.1:8000/api';
  static String? _resolvedBaseUrl;

  ApiService() {
    // Default to localhost for USB debugging with:
    // adb reverse tcp:8000 tcp:8000
    baseUrl = _resolvedBaseUrl ??
        const String.fromEnvironment(
          'API_URL',
          defaultValue: _defaultBaseUrl,
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

  List<String> get _baseUrlCandidates {
    const configured = String.fromEnvironment(
      'API_URL',
      defaultValue: _defaultBaseUrl,
    );
    final candidates = <String>[
      if (_resolvedBaseUrl != null) _resolvedBaseUrl!,
      configured,
      baseUrl,
      _defaultBaseUrl,
      'http://localhost:8000/api',
      'http://[::1]:8000/api',
      'http://10.0.2.2:8000/api',
    ];
    return candidates
        .map(_normalizeBaseUrl)
        .where((url) => url.isNotEmpty)
        .toSet()
        .toList();
  }

  String _normalizeBaseUrl(String url) => url.trim().replaceFirst(
        RegExp(r'/+$'),
        '',
      );

  Uri _apiUri(String candidateBaseUrl, String path) {
    final cleanPath = path.startsWith('/') ? path : '/$path';
    return Uri.parse('${_normalizeBaseUrl(candidateBaseUrl)}$cleanPath');
  }

  bool _isConnectionError(Object error) =>
      error is SocketException ||
      error is TimeoutException ||
      error is http.ClientException;

  Future<http.Response> _requestWithFallback(
    Future<http.Response> Function(String candidateBaseUrl) request,
  ) async {
    Object? lastError;

    for (final candidate in _baseUrlCandidates) {
      try {
        final response = await request(candidate).timeout(_requestTimeout);
        baseUrl = candidate;
        _resolvedBaseUrl = candidate;
        return response;
      } catch (error) {
        if (!_isConnectionError(error)) rethrow;
        lastError = error;
      }
    }

    if (lastError != null) throw lastError;
    throw const SocketException('Tidak ada alamat API yang bisa dicoba.');
  }

  Future<http.Response> _getWithFallback(String path) {
    return _requestWithFallback(
      (candidate) => http.get(_apiUri(candidate, path), headers: headers),
    );
  }

  Future<http.Response> _postJsonWithFallback(
    String path,
    Map<String, dynamic> body, {
    Map<String, String>? requestHeaders,
  }) {
    return _requestWithFallback(
      (candidate) => http.post(
        _apiUri(candidate, path),
        headers: requestHeaders ?? headers,
        body: jsonEncode(body),
      ),
    );
  }

  Future<http.Response> _putJsonWithFallback(
    String path,
    Map<String, dynamic> body,
  ) {
    return _requestWithFallback(
      (candidate) => http.put(
        _apiUri(candidate, path),
        headers: headers,
        body: jsonEncode(body),
      ),
    );
  }

  Future<http.Response> _deleteWithFallback(String path) {
    return _requestWithFallback(
      (candidate) => http.delete(_apiUri(candidate, path), headers: headers),
    );
  }

  Future<http.Response> _postMultipartWithFallback(
    String path,
    Map<String, String> fields,
    File? image,
  ) {
    return _requestWithFallback((candidate) async {
      final request = http.MultipartRequest('POST', _apiUri(candidate, path));
      request.headers.addAll({
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      });
      request.fields.addAll(fields);
      if (image != null) {
        request.files.add(
          await http.MultipartFile.fromPath('image', image.path),
        );
      }
      return http.Response.fromStream(await request.send());
    });
  }

  List<dynamic> _dataList(http.Response response) {
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    return jsonDecode(response.body)['data'] ?? [];
  }

  // Auth
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _postJsonWithFallback(
      '/auth/login',
      {'username': email, 'password': password},
      requestHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
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
    final response = await _getWithFallback('/auth/me');
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
    final response = await _getWithFallback('/fire-hydrants');
    return _dataList(response);
  }

  Future<Map<String, dynamic>> getFireHydrant(int id) async {
    final response = await _getWithFallback('/fire-hydrants/$id');
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    final body = jsonDecode(response.body);
    return Map<String, dynamic>.from(body['data'] ?? {});
  }

  Future<Map<String, dynamic>> getInspectionDetail(
      String resourcePath, int id) async {
    final response = await _getWithFallback('/$resourcePath/$id');
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    final body = jsonDecode(response.body);
    return Map<String, dynamic>.from(body['data'] ?? {});
  }

  Future<List<dynamic>> getFireHydrantLocations() async {
    final response = await _getWithFallback('/fire-hydrant-locations');
    return _dataList(response);
  }

  Future<List<dynamic>> getUsers() async {
    final response = await _getWithFallback('/users');
    return _dataList(response);
  }

  Future<List<dynamic>> getAreas() async {
    final response = await _getWithFallback('/areas');
    return _dataList(response);
  }

  Future<List<dynamic>> getPoints() async {
    final response = await _getWithFallback('/points');
    return _dataList(response);
  }

  Future<Map<String, dynamic>> getPoint(int id) async {
    final response = await _getWithFallback('/points/$id');
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    final body = jsonDecode(response.body);
    return Map<String, dynamic>.from(body['data'] ?? {});
  }

  Future<Map<String, dynamic>> createFireHydrant(
      Map<String, dynamic> data) async {
    final response = await _postJsonWithFallback('/fire-hydrants', data);
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> updateFireHydrant(
      int id, Map<String, dynamic> data) async {
    final response = await _putJsonWithFallback('/fire-hydrants/$id', data);
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> deleteFireHydrant(int id) async {
    final response = await _deleteWithFallback('/fire-hydrants/$id');
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

  Future<List<dynamic>> getFireExtinguisherLocations() async {
    final response = await _getWithFallback('/fire-extinguisher-locations');
    return _dataList(response);
  }

  Future<Map<String, dynamic>> getFireExtinguisher(int id) async {
    final response = await _getWithFallback('/fire-extinguishers/$id');
    if (response.statusCode >= 400) {
      throw Exception(_errorMessage(response));
    }
    final body = jsonDecode(response.body);
    return Map<String, dynamic>.from(body['data'] ?? {});
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

  Future<Map<String, dynamic>> updateFireExtinguisher(
      int id, Map<String, dynamic> data) async {
    final response = await http.put(
      Uri.parse('$baseUrl/fire-extinguishers/$id'),
      headers: headers,
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> deleteFireExtinguisher(int id) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/fire-extinguishers/$id'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> checkinFireExtinguisher(
      int id, double lat, double lng) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fire-extinguishers/$id/checkin'),
      headers: headers,
      body: jsonEncode({'checkin_lat': lat, 'checkin_lng': lng}),
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
    final response = await _getWithFallback('/incidents');
    return _dataList(response);
  }

  Future<List<dynamic>> getIncidentTypes() async {
    final response = await _getWithFallback('/incident-types');
    return _dataList(response);
  }

  Future<List<dynamic>> getLocations() async {
    final response = await _getWithFallback('/locations');
    return _dataList(response);
  }

  Future<Map<String, dynamic>> createIncident(
    Map<String, String> data, {
    File? image,
  }) async {
    final response = await _postMultipartWithFallback(
      '/incidents',
      data,
      image,
    );
    if (response.statusCode >= 400) {
      return {'error': _errorMessage(response)};
    }
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
