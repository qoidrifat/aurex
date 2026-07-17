import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mocktail/mocktail.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../lib/screens/login_screen.dart';
import '../lib/screens/forgot_password_screen.dart';
import '../lib/services/auth_service.dart';
import '../lib/providers/auth_provider.dart';

// ==================== MOCKS ====================

class MockAuthService extends Mock implements AuthService {}

/// Helper GoRouter untuk testing navigasi.
GoRouter _testRouter() {
  return GoRouter(
    initialLocation: '/login',
    routes: [
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: '/forgot-password',
        name: 'forgot-password',
        builder: (_, __) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (_, __) => const Scaffold(body: Center(child: Text('Register'))),
      ),
      GoRoute(
        path: '/upload',
        name: 'upload',
        builder: (_, __) => const Scaffold(body: Center(child: Text('Upload'))),
      ),
    ],
  );
}

// ==================== TESTS ====================

void main() {
  late ProviderContainer container;
  late MockAuthService mockAuthService;

  setUp(() {
    SharedPreferences.setMockInitialValues({});
    mockAuthService = MockAuthService();
    when(() => mockAuthService.hasToken()).thenAnswer((_) async => false);

    container = ProviderContainer(
      overrides: [
        authServiceProvider.overrideWithValue(mockAuthService),
      ],
    );
  });

  tearDown(() {
    container.dispose();
  });

  /// Helper untuk membuat test app dengan container yang sudah di-mock
  /// dan GoRouter test router.
  Future<Widget> createLoginScreen() async {
    await container.read(authProvider.notifier).checkAuthStatus();
    return UncontrolledProviderScope(
      container: container,
      child: MaterialApp.router(
        routerConfig: _testRouter(),
      ),
    );
  }

  group('LoginScreen - UI Elements', () {
    testWidgets('should display Welcome Back title', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(find.text('Welcome Back'), findsOneWidget);
    });

    testWidgets('should display subtitle', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(
        find.text('Sign in to continue your style journey'),
        findsOneWidget,
      );
    });

    testWidgets('should display email and password fields', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(find.byType(TextFormField), findsNWidgets(2));
      expect(find.text('Email'), findsOneWidget);
      expect(find.text('Password'), findsOneWidget);
    });

    testWidgets('should display Login button', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(find.text('Login'), findsOneWidget);
    });

    testWidgets('should display Forgot Password link', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(find.text('Forgot Password?'), findsOneWidget);
    });

    testWidgets('should display Register link', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      expect(find.text("Don't have an account? "), findsOneWidget);
      expect(find.text('Register'), findsOneWidget);
    });
  });

  group('LoginScreen - Form Validation', () {
    testWidgets('should show error when email is empty', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      // Tekan tombol Login tanpa mengisi form
      await tester.tap(find.text('Login'));
      await tester.pumpAndSettle();

      expect(find.text('Email tidak boleh kosong'), findsOneWidget);
      expect(find.text('Password tidak boleh kosong'), findsOneWidget);
    });

    testWidgets('should show error for invalid email format', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      await tester.enterText(
        find.byType(TextFormField).first,
        'invalid-email',
      );
      await tester.enterText(
        find.byType(TextFormField).last,
        'password123',
      );

      await tester.tap(find.text('Login'));
      await tester.pumpAndSettle();

      expect(find.text('Format email tidak valid'), findsOneWidget);
    });

    testWidgets('should show error for short password', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      await tester.enterText(
        find.byType(TextFormField).first,
        'test@example.com',
      );
      await tester.enterText(
        find.byType(TextFormField).last,
        'short',
      );

      await tester.tap(find.text('Login'));
      await tester.pumpAndSettle();

      expect(find.text('Password minimal 8 karakter'), findsOneWidget);
    });
  });

  group('LoginScreen - Password Visibility Toggle', () {
    testWidgets('should toggle password visibility icon', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      // Awalnya ada icon visibility_off
      expect(find.byIcon(Icons.visibility_off), findsOneWidget);
      expect(find.byIcon(Icons.visibility), findsNothing);

      // Tekan visibility toggle
      await tester.tap(find.byIcon(Icons.visibility_off));
      await tester.pump();

      // Icon berubah jadi visibility
      expect(find.byIcon(Icons.visibility), findsOneWidget);
      expect(find.byIcon(Icons.visibility_off), findsNothing);
    });
  });

  group('LoginScreen - Navigation', () {
    testWidgets('should navigate to ForgotPasswordScreen', (tester) async {
      await tester.pumpWidget(await createLoginScreen());

      await tester.tap(find.text('Forgot Password?'));
      await tester.pumpAndSettle();

      // Harusnya navigasi ke ForgotPasswordScreen
      expect(find.byType(ForgotPasswordScreen), findsOneWidget);
    });
  });
}
