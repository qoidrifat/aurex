import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../lib/models/user_model.dart';
import '../lib/services/api_service.dart';
import '../lib/services/auth_service.dart';
import '../lib/providers/auth_provider.dart';

// ==================== MOCKS ====================

class MockApiService extends Mock implements ApiService {}

class MockAuthService extends Mock implements AuthService {}

// ==================== HELPERS ====================

UserModel createMockUser() {
  return UserModel(
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
  );
}

/// Membuat Response Dio mock dengan data tertentu
Response createDioResponse(Map<String, dynamic> data) {
  return Response(
    requestOptions: RequestOptions(path: ''),
    data: data,
    statusCode: 200,
  );
}

/// Membuat ProviderContainer dengan AuthService yang sudah di-mock
ProviderContainer createContainer({
  AuthService? authService,
  List<Override> overrides = const [],
}) {
  final mockAuthService = authService ?? MockAuthService();

  return ProviderContainer(
    overrides: [
      authServiceProvider.overrideWithValue(mockAuthService),
      ...overrides,
    ],
  );
}

// ==================== TESTS ====================

void main() {
  late MockAuthService mockAuthService;
  late MockApiService mockApiService;

  setUp(() {
    mockAuthService = MockAuthService();
    mockApiService = MockApiService();
    SharedPreferences.setMockInitialValues({});
  });

  group('AuthNotifier - Initial State', () {
    test('initial state should be initializing with no user', () {
      final container = createContainer(authService: mockAuthService);
      final authState = container.read(authProvider);

      expect(authState.status, AuthStatus.initializing);
      expect(authState.user, isNull);
      expect(authState.errorMessage, isNull);
      expect(authState.isLoading, isTrue);
      expect(authState.isAuthenticated, isFalse);
    });

    test('isLoading returns true for initializing status', () {
      final state = const AuthState(status: AuthStatus.initializing);
      expect(state.isLoading, isTrue);
    });

    test('isLoading returns true for loading status', () {
      final state = const AuthState(status: AuthStatus.loading);
      expect(state.isLoading, isTrue);
    });

    test('isLoading returns false for authenticated status', () {
      final state = const AuthState(status: AuthStatus.authenticated);
      expect(state.isLoading, isFalse);
    });
  });

  group('AuthNotifier - checkAuthStatus', () {
    test('should set authenticated when token exists', () async {
      SharedPreferences.setMockInitialValues({'auth_token': 'test-token'});
      when(() => mockAuthService.hasToken()).thenAnswer((_) async => true);

      final container = createContainer(authService: mockAuthService);
      await container.read(authProvider.notifier).checkAuthStatus();

      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.authenticated);
      expect(authState.isAuthenticated, isTrue);
    });

    test('should set unauthenticated when no token', () async {
      when(() => mockAuthService.hasToken()).thenAnswer((_) async => false);

      final container = createContainer(authService: mockAuthService);
      await container.read(authProvider.notifier).checkAuthStatus();

      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.unauthenticated);
      expect(authState.isAuthenticated, isFalse);
    });
  });

  group('AuthNotifier - login', () {
    test('should return true and set authenticated on success', () async {
      final mockUser = createMockUser();
      when(() => mockAuthService.login('test@example.com', 'password123'))
          .thenAnswer((_) async => mockUser);

      final container = createContainer(authService: mockAuthService);
      final success = await container.read(authProvider.notifier).login(
            'test@example.com',
            'password123',
          );

      expect(success, isTrue);
      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.authenticated);
      expect(authState.user, mockUser);
    });

    test('should show loading state during login', () async {
      final mockUser = createMockUser();
      late Future<UserModel> delayedFuture;
      delayedFuture = Future<UserModel>.delayed(
        const Duration(seconds: 1),
        () => mockUser,
      );
      when(() => mockAuthService.login(any(), any()))
          .thenAnswer((_) => delayedFuture);

      final container = createContainer(authService: mockAuthService);

      final loginFuture = container.read(authProvider.notifier).login(
            'test@example.com',
            'password123',
          );

      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.loading);
      expect(authState.isLoading, isTrue);

      await loginFuture;
    });

    test('should return false and set error on ApiException', () async {
      when(() => mockAuthService.login(any(), any())).thenThrow(
        ApiException('Email atau password salah.'),
      );

      final container = createContainer(authService: mockAuthService);
      final success = await container.read(authProvider.notifier).login(
            'test@example.com',
            'wrong_password',
          );

      expect(success, isFalse);
      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.error);
      expect(authState.errorMessage, 'Email atau password salah.');
    });

    test('should return false and set generic error on unexpected exception',
        () async {
      when(() => mockAuthService.login(any(), any()))
          .thenThrow(Exception('Unexpected error'));

      final container = createContainer(authService: mockAuthService);
      final success = await container.read(authProvider.notifier).login(
            'test@example.com',
            'password123',
          );

      expect(success, isFalse);
      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.error);
      expect(authState.errorMessage,
          'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.');
    });
  });

  group('AuthNotifier - register', () {
    test('should return true and set authenticated on success', () async {
      final mockUser = createMockUser();
      when(() => mockAuthService.register('Test', 'test@example.com', 'pass123'))
          .thenAnswer((_) async => mockUser);

      final container = createContainer(authService: mockAuthService);
      final success = await container.read(authProvider.notifier).register(
            'Test',
            'test@example.com',
            'pass123',
          );

      expect(success, isTrue);
      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.authenticated);
      expect(authState.user, mockUser);
    });

    test('should return false and set error on ApiException', () async {
      when(() => mockAuthService.register(any(), any(), any())).thenThrow(
        ApiException('Email sudah terdaftar.'),
      );

      final container = createContainer(authService: mockAuthService);
      final success = await container.read(authProvider.notifier).register(
            'Test',
            'existing@example.com',
            'password123',
          );

      expect(success, isFalse);
      final authState = container.read(authProvider);
      expect(authState.errorMessage, 'Email sudah terdaftar.');
    });
  });

  group('AuthNotifier - logout', () {
    test('should set unauthenticated after logout', () async {
      when(() => mockAuthService.logout()).thenAnswer((_) async {});

      final container = createContainer(authService: mockAuthService);
      await container.read(authProvider.notifier).logout();

      final authState = container.read(authProvider);
      expect(authState.status, AuthStatus.unauthenticated);
      expect(authState.user, isNull);
    });
  });

  group('AuthNotifier - forgotPassword', () {
    test('should return success message on success', () async {
      when(() => mockApiService.forgotPassword('test@example.com'))
          .thenAnswer((_) async => createDioResponse({
                'message': 'Link reset password telah dikirim ke email Anda.',
              }));
      when(() => mockAuthService.getApiService()).thenReturn(mockApiService);

      final container = createContainer(authService: mockAuthService);
      final message = await container.read(authProvider.notifier).forgotPassword(
            'test@example.com',
          );

      expect(message, 'Link reset password telah dikirim ke email Anda.');
    });

    test('should return error message on ApiException', () async {
      when(() => mockApiService.forgotPassword(any())).thenThrow(
        ApiException('Email tidak ditemukan.'),
      );
      when(() => mockAuthService.getApiService()).thenReturn(mockApiService);

      final container = createContainer(authService: mockAuthService);
      final message = await container.read(authProvider.notifier).forgotPassword(
            'unknown@example.com',
          );

      expect(message, 'Email tidak ditemukan.');
    });
  });

  group('AuthNotifier - clearError', () {
    test('should clear error and set unauthenticated after failed login',
        () async {
      when(() => mockAuthService.login(any(), any())).thenThrow(
        ApiException('Error'),
      );

      final container = createContainer(authService: mockAuthService);

      // Trigger error state via failed login
      await container.read(authProvider.notifier).login('test@example.com', 'wrong');

      // Clear error (sync, no await needed)
      container.read(authProvider.notifier).clearError();

      final authState = container.read(authProvider);
      expect(authState.errorMessage, isNull);
      expect(authState.status, AuthStatus.unauthenticated);
    });

    test('should clear error and stay authenticated', () async {
      final mockUser = createMockUser();
      // Register general matcher FIRST, then specific override
      when(() => mockAuthService.login(any(), any())).thenThrow(
        ApiException('Error'),
      );
      when(() => mockAuthService.login('test@example.com', 'pass'))
          .thenAnswer((_) async => mockUser);

      final container = createContainer(authService: mockAuthService);

      // Login success first
      await container.read(authProvider.notifier).login('test@example.com', 'pass');
      expect(container.read(authProvider).isAuthenticated, isTrue);

      // Login fail
      await container.read(authProvider.notifier).login('test@example.com', 'wrong');
      expect(container.read(authProvider).hasError, isTrue);

      // Clear error (sync, no await needed)
      container.read(authProvider.notifier).clearError();

      final authState = container.read(authProvider);
      expect(authState.errorMessage, isNull);
      expect(authState.isAuthenticated, isTrue);
    });
  });

  group('AuthNotifier - setUser', () {
    test('should set user in state', () {
      final container = createContainer(authService: mockAuthService);
      final mockUser = createMockUser();

      container.read(authProvider.notifier).setUser(mockUser);

      final authState = container.read(authProvider);
      expect(authState.user, mockUser);
    });
  });

  group('AuthNotifier - resendVerification', () {
    test('should return success message on success', () async {
      when(() => mockApiService.resendVerification('test@example.com'))
          .thenAnswer((_) async => createDioResponse({
                'message': 'Email verifikasi telah dikirim ulang.',
              }));
      when(() => mockAuthService.getApiService()).thenReturn(mockApiService);

      final container = createContainer(authService: mockAuthService);
      final message =
          await container.read(authProvider.notifier).resendVerification(
                'test@example.com',
              );

      expect(message, 'Email verifikasi telah dikirim ulang.');
    });
  });
}
