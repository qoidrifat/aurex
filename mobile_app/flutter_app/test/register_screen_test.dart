import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../lib/screens/register_screen.dart';
import '../lib/services/auth_service.dart';
import '../lib/providers/auth_provider.dart';

// ==================== MOCKS ====================

class MockAuthService extends Mock implements AuthService {}

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

  /// Helper untuk membuat test app dengan container yang sudah di-mock.
  /// Memanggil checkAuthStatus() dulu agar auth state = unauthenticated,
  /// sehingga isLoading = false dan tombol Register tampil sebagai teks.
  Future<Widget> createRegisterScreen() async {
    await container.read(authProvider.notifier).checkAuthStatus();
    return UncontrolledProviderScope(
      container: container,
      child: const MaterialApp(
        home: RegisterScreen(),
      ),
    );
  }

  group('RegisterScreen - UI Elements', () {
    testWidgets('should display Create Account title', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      expect(find.text('Create Account'), findsOneWidget);
    });

    testWidgets('should display subtitle', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      expect(
        find.text('Join AUREX and discover your style'),
        findsOneWidget,
      );
    });

    testWidgets('should display all 4 form fields', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      expect(find.byType(TextFormField), findsNWidgets(4));
      expect(find.text('Full Name'), findsOneWidget);
      expect(find.text('Email'), findsOneWidget);
      expect(find.text('Password'), findsOneWidget);
      expect(find.text('Confirm Password'), findsOneWidget);
    });

    testWidgets('should display Register button', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      expect(find.text('Register'), findsOneWidget);
    });

    testWidgets('should display AppBar back button', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      expect(find.byIcon(Icons.arrow_back), findsOneWidget);
    });
  });

  group('RegisterScreen - Form Validation', () {
    testWidgets('should show error when all fields are empty', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      // Tekan tombol Register tanpa mengisi form
      await tester.tap(find.text('Register'));
      await tester.pumpAndSettle();

      expect(find.text('Nama tidak boleh kosong'), findsOneWidget);
      expect(find.text('Email tidak boleh kosong'), findsOneWidget);
      expect(find.text('Password tidak boleh kosong'), findsOneWidget);
      expect(find.text('Konfirmasi password tidak boleh kosong'), findsOneWidget);
    });

    testWidgets('should show error for short name', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      await tester.enterText(
        find.byType(TextFormField).at(0),
        'A',
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        'test@example.com',
      );
      await tester.enterText(
        find.byType(TextFormField).at(2),
        'password123',
      );
      await tester.enterText(
        find.byType(TextFormField).at(3),
        'password123',
      );

      await tester.tap(find.text('Register'));
      await tester.pumpAndSettle();

      expect(find.text('Nama minimal 2 karakter'), findsOneWidget);
    });

    testWidgets('should show error for invalid email format', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      await tester.enterText(
        find.byType(TextFormField).at(0),
        'Test User',
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        'invalid-email',
      );
      await tester.enterText(
        find.byType(TextFormField).at(2),
        'password123',
      );
      await tester.enterText(
        find.byType(TextFormField).at(3),
        'password123',
      );

      await tester.tap(find.text('Register'));
      await tester.pumpAndSettle();

      expect(find.text('Format email tidak valid'), findsOneWidget);
    });

    testWidgets('should show error for short password', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      await tester.enterText(
        find.byType(TextFormField).at(0),
        'Test User',
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        'test@example.com',
      );
      await tester.enterText(
        find.byType(TextFormField).at(2),
        'short',
      );
      await tester.enterText(
        find.byType(TextFormField).at(3),
        'short',
      );

      await tester.tap(find.text('Register'));
      await tester.pumpAndSettle();

      expect(find.text('Password minimal 8 karakter'), findsOneWidget);
    });

    testWidgets('should show error when passwords do not match', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      await tester.enterText(
        find.byType(TextFormField).at(0),
        'Test User',
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        'test@example.com',
      );
      await tester.enterText(
        find.byType(TextFormField).at(2),
        'password123',
      );
      await tester.enterText(
        find.byType(TextFormField).at(3),
        'different456',
      );

      await tester.tap(find.text('Register'));
      await tester.pumpAndSettle();

      expect(find.text('Password tidak cocok'), findsOneWidget);
    });
  });

  group('RegisterScreen - Password Visibility Toggle', () {
    testWidgets('should toggle password visibility icon', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      // Password field starts with visibility_off
      expect(find.byIcon(Icons.visibility_off), findsNWidgets(2));
      expect(find.byIcon(Icons.visibility), findsNothing);

      // Toggle password visibility (first toggle button)
      await tester.tap(find.byIcon(Icons.visibility_off).first);
      await tester.pump();

      // One should now be visibility
      expect(find.byIcon(Icons.visibility), findsOneWidget);
      expect(find.byIcon(Icons.visibility_off), findsOneWidget);
    });

    testWidgets('should toggle confirm password visibility icon', (tester) async {
      await tester.pumpWidget(await createRegisterScreen());

      // Toggle confirm password visibility (second toggle button)
      await tester.tap(find.byIcon(Icons.visibility_off).last);
      await tester.pump();

      // One should now be visibility
      expect(find.byIcon(Icons.visibility), findsOneWidget);
      expect(find.byIcon(Icons.visibility_off), findsOneWidget);
    });
  });
}
