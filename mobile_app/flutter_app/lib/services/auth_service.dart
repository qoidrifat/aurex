import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'api_service.dart';
import '../models/user_model.dart';
import 'secure_storage_service.dart';

// apiServiceProvider sudah didefinisikan di api_service.dart

final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService(ref.read(apiServiceProvider));
});

class AuthService {
  final ApiService _apiService;
  final SecureStorageService _secureStorage;

  AuthService(this._apiService)
      : _secureStorage = SecureStorageService();

  /// Login dan simpan token ke SecureStorage (terenkripsi)
  Future<UserModel> login(String email, String password) async {
    final response = await _apiService.login(email, password);
    final token = response.data['access_token'] as String;
    final userData = response.data['user'] as Map<String, dynamic>;

    await _secureStorage.saveToken(token);

    return UserModel.fromJson(userData);
  }

  /// Register, auto-login, dan simpan token
  Future<UserModel> register(String name, String email, String password) async {
    final response = await _apiService.register(name, email, password);
    final token = response.data['access_token'] as String;
    final userData = response.data['user'] as Map<String, dynamic>;

    await _secureStorage.saveToken(token);

    return UserModel.fromJson(userData);
  }

  /// Logout dari server dan hapus token lokal
  Future<void> logout() async {
    try {
      await _apiService.logout();
    } catch (e) {
      // Tetap hapus token lokal meskipun request logout gagal
    } finally {
      await _secureStorage.deleteToken();
    }
  }

  /// Cek apakah user memiliki token tersimpan
  Future<bool> hasToken() async {
    return await _secureStorage.hasToken();
  }

  /// Hapus token (untuk force logout)
  Future<void> clearToken() async {
    await _secureStorage.deleteToken();
  }

  /// Akses ke ApiService (untuk provider)
  ApiService getApiService() => _apiService;
}
