import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'tracing_interceptor.dart';
import 'secure_storage_service.dart';

/// Exception khusus untuk error API yang user-friendly
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() => message;
}

class ApiService {
  final Dio _dio;
  final _secureStorage = SecureStorageService();

  ApiService()
      : _dio = Dio(BaseOptions(
          baseUrl: 'http://10.0.2.2:8000/api/v1', // Android emulator -> localhost (v1)
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 30),
          sendTimeout: const Duration(seconds: 30),
        )) {
    // Tracing interceptor untuk X-Request-Id (dipasang pertama agar wrapping
    // request_id bisa diakses oleh interceptor lain)
    _dio.interceptors.add(TracingInterceptor());

    // Auth token interceptor — membaca token dari SecureStorage (terenkripsi)
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _secureStorage.getToken();
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
  }

  /// Helper untuk mengekstrak pesan error dari DioException
  String _extractErrorMessage(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      return 'Koneksi timeout. Silakan coba lagi.';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
    }
    if (e.response != null) {
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        // Laravel validation error format
        if (data.containsKey('message')) {
          return data['message'].toString();
        }
        // Ambil pesan error pertama dari field validation
        for (final entry in data.entries) {
          if (entry.value is List && (entry.value as List).isNotEmpty) {
            return (entry.value as List).first.toString();
          }
        }
      }
      return 'Terjadi kesalahan server (${e.response?.statusCode}). Silakan coba lagi.';
    }
    return 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.';
  }

  Future<Response> register(String name, String email, String password) async {
    try {
      return await _dio.post('/register', data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
      });
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> login(String email, String password) async {
    try {
      return await _dio.post('/login', data: {
        'email': email,
        'password': password,
      });
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> logout() async {
    try {
      return await _dio.post('/logout');
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> uploadSelfie(String imagePath) async {
    try {
      final formData = FormData.fromMap({
        'image': await MultipartFile.fromFile(
          imagePath,
          filename: imagePath.split('/').last,
        ),
      });
      return await _dio.post(
        '/upload-selfie',
        data: formData,
        options: Options(
          contentType: 'multipart/form-data',
          receiveTimeout: const Duration(seconds: 60),
          sendTimeout: const Duration(seconds: 60),
        ),
      );
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> analyze(int imageId) async {
    try {
      return await _dio.post(
        '/analyze',
        data: {'image_id': imageId},
        options: Options(
          receiveTimeout: const Duration(seconds: 60),
        ),
      );
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> getHistory() async {
    try {
      return await _dio.get('/history');
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> getResult(int analysisId) async {
    try {
      return await _dio.get('/result/$analysisId');
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> forgotPassword(String email) async {
    try {
      return await _dio.post('/forgot-password', data: {
        'email': email,
      });
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> resetPassword(String email, String token, String password) async {
    try {
      return await _dio.post('/reset-password', data: {
        'email': email,
        'token': token,
        'password': password,
        'password_confirmation': password,
      });
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }

  Future<Response> resendVerification(String email) async {
    try {
      return await _dio.post('/resend-verification', data: {
        'email': email,
      });
    } on DioException catch (e) {
      throw ApiException(_extractErrorMessage(e), statusCode: e.response?.statusCode);
    }
  }
}

/// Riverpod provider untuk ApiService
final apiServiceProvider = Provider<ApiService>((ref) => ApiService());
