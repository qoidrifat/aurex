import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Wrapper aman untuk token autentikasi.
///
/// Menggunakan FlutterSecureStorage (terenkripsi) sebagai pengganti
/// SharedPreferences untuk menyimpan auth_token.
///
/// Keuntungan:
/// - Data terenkripsi di Android (EncryptedSharedPreferences)
/// - Data terenkripsi di iOS (Keychain Services)
/// - Tidak bisa dibaca oleh aplikasi lain pada device root/jailbreak
class SecureStorageService {
  static const _tokenKey = 'auth_token';

  final FlutterSecureStorage _storage;

  SecureStorageService()
      : _storage = const FlutterSecureStorage(
          aOptions: AndroidOptions(
            encryptedSharedPreferences: true,
          ),
        );

  /// Simpan token autentikasi
  Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  /// Ambil token autentikasi yang tersimpan
  Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  /// Hapus token autentikasi (saat logout)
  Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
  }

  /// Cek apakah ada token tersimpan
  Future<bool> hasToken() async {
    final token = await _storage.read(key: _tokenKey);
    return token != null && token.isNotEmpty;
  }
}
