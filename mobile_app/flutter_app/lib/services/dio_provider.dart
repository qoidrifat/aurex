import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'tracing_interceptor.dart';
import 'secure_storage_service.dart';

/// Provider untuk SecureStorageService (DI — memudahkan mocking di test)
final secureStorageProvider = Provider<SecureStorageService>((ref) {
  return SecureStorageService();
});

/// Provider untuk Dio instance dengan konfigurasi base.
///
/// Menggunakan Riverpod Provider agar Dio bisa di-mock/test dengan mudah.
/// Di test, kita bisa override provider ini dengan FakeDio atau MockAdapter.
///
/// @see https://pub.dev/packages/mocktail untuk mocking Dio
final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    baseUrl: 'http://10.0.2.2:8000/api/v1', // Android emulator -> localhost
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    connectTimeout: const Duration(seconds: 15),
    receiveTimeout: const Duration(seconds: 30),
    sendTimeout: const Duration(seconds: 30),
  ));

  // Tracing interceptor untuk X-Request-Id (dipasang pertama)
  dio.interceptors.add(TracingInterceptor());

  // Auth token interceptor — membaca token dari SecureStorage (terenkripsi)
  final secureStorage = ref.read(secureStorageProvider);
  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      final token = await secureStorage.getToken();
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      return handler.next(options);
    },
    onError: (error, handler) {
      // Jangan expose internal error details ke user
      return handler.next(error);
    },
  ));

  return dio;
});
