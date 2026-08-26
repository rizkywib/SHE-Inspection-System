import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'dart:async';
import 'dart:io';
import 'api_service.dart';

class AuthService extends ChangeNotifier {
  final ApiService _api = ApiService();
  static const _tokenKey = 'auth_token';
  static const _storage = FlutterSecureStorage();
  Map<String, dynamic>? _user;
  String? _token;
  bool _isLoading = false;

  Map<String, dynamic>? get user => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _token != null && _user != null;

  Future<void> init() async {
    _token = await _storage.read(key: _tokenKey);
    if (_token != null) {
      _api.setToken(_token);
      try {
        final response = await _api.getProfile();
        _user = response;
      } catch (e) {
        _token = null;
        _user = null;
        await _storage.delete(key: _tokenKey);
      }
    }
    notifyListeners();
  }

  Future<Map<String, dynamic>?> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _api.login(email, password);
      if (response.containsKey('token')) {
        _token = response['token'];
        _user = response['user'];
        _api.setToken(_token);
        await _storage.write(key: _tokenKey, value: _token!);
        _isLoading = false;
        notifyListeners();
        return null;
      }
      _isLoading = false;
      notifyListeners();
      return response;
    } on TimeoutException {
      _isLoading = false;
      notifyListeners();
      return {
        'error':
            'Koneksi ke server timeout. Pastikan backend berjalan dan adb reverse aktif.'
      };
    } on SocketException {
      _isLoading = false;
      notifyListeners();
      return {
        'error':
            'Tidak bisa terhubung ke server. Pastikan backend berjalan dan adb reverse tcp:8000 tcp:8000 aktif.'
      };
    } on http.ClientException catch (e) {
      _isLoading = false;
      notifyListeners();
      return {
        'error':
            'Tidak bisa terhubung ke server (${e.message}). Pastikan backend berjalan dan adb reverse tcp:8000 tcp:8000 aktif.'
      };
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'error': 'Login gagal: ${e.toString()}'};
    }
  }

  Future<void> logout() async {
    _token = null;
    _user = null;
    _api.setToken(null);
    await _storage.delete(key: _tokenKey);
    notifyListeners();
  }
}
